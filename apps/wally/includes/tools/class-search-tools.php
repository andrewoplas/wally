<?php
namespace Wally\Tools;

/**
 * Search and replace tools for WordPress content.
 *
 * Tools: search_content, replace_content.
 * Category: "search" — searches both standard post_content AND
 * Elementor _elementor_data post meta, so all site text is covered.
 */

/**
 * Search across all post content (standard + Elementor).
 */
class SearchContent extends ToolInterface {

	public function get_name(): string {
		return 'search_content';
	}

	public function get_description(): string {
		return 'Search for text across all WordPress post content, including Elementor page builder data. Returns matching posts with context snippets.';
	}

	public function get_category(): string {
		return 'search';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'query'          => [
					'type'        => 'string',
					'description' => 'The text to search for.',
				],
				'case_sensitive' => [
					'type'        => 'boolean',
					'description' => 'Whether the search should be case-sensitive.',
					'default'     => false,
				],
				'post_type'      => [
					'type'        => 'string',
					'description' => 'Limit search to a specific post type (e.g. post, page). Omit to search all.',
				],
				'post_status'    => [
					'type'        => 'string',
					'description' => 'Limit to a specific post status.',
					'enum'        => [ 'publish', 'draft', 'pending', 'private', 'any' ],
					'default'     => 'any',
				],
				'per_page'       => [
					'type'        => 'integer',
					'description' => 'Max results to return (max 50).',
					'default'     => 20,
				],
			],
			'required'   => [ 'query' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $input ): array {
		global $wpdb;

		$query          = $input['query'];
		$case_sensitive = ! empty( $input['case_sensitive'] );
		$per_page       = min( (int) ( $input['per_page'] ?? 20 ), 50 );
		$post_type      = ! empty( $input['post_type'] ) ? sanitize_key( $input['post_type'] ) : null;
		$post_status    = sanitize_key( $input['post_status'] ?? 'any' );

		// Build WHERE clause for post_content search.
		$collation    = $case_sensitive ? 'COLLATE utf8mb4_bin' : '';
		$like_query   = '%' . $wpdb->esc_like( $query ) . '%';
		$where_parts  = [ "p.post_content {$collation} LIKE %s" ];
		$params       = [ $like_query ];

		// Also search Elementor _elementor_data in postmeta.
		$where_parts[] = "p.ID IN (SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_key = '_elementor_data' AND pm.meta_value {$collation} LIKE %s)";
		$params[]      = $like_query;

		$where_combined = '(' . implode( ' OR ', $where_parts ) . ')';

		// Post type filter.
		if ( $post_type ) {
			$where_combined .= ' AND p.post_type = %s';
			$params[]        = $post_type;
		} else {
			// Exclude revisions and nav_menu_item.
			$where_combined .= " AND p.post_type NOT IN ('revision', 'nav_menu_item', 'wp_template', 'wp_template_part', 'wp_global_styles')";
		}

		// Post status filter.
		if ( 'any' !== $post_status ) {
			$where_combined .= ' AND p.post_status = %s';
			$params[]        = $post_status;
		} else {
			$where_combined .= " AND p.post_status NOT IN ('auto-draft', 'inherit')";
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- constructed safely above.
		$sql = $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_type, p.post_status, p.post_content
			 FROM {$wpdb->posts} p
			 WHERE {$where_combined}
			 ORDER BY p.post_date DESC
			 LIMIT %d",
			array_merge( $params, [ $per_page ] )
		);

		$posts   = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = [];

		foreach ( $posts as $post ) {
			$matches = [];

			// Search in post_content.
			$content_matches = $this->find_matches( $post->post_content, $query, $case_sensitive );
			if ( ! empty( $content_matches ) ) {
				$matches['post_content'] = $content_matches;
			}

			// Search in Elementor data.
			$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
			if ( $elementor_data ) {
				$elementor_matches = $this->find_matches( $elementor_data, $query, $case_sensitive );
				if ( ! empty( $elementor_matches ) ) {
					$matches['elementor_data'] = $elementor_matches;
				}
			}

			if ( ! empty( $matches ) ) {
				$total_count = 0;
				foreach ( $matches as $source_matches ) {
					$total_count += count( $source_matches );
				}

				$results[] = [
					'post_id'     => $post->ID,
					'title'       => $post->post_title,
					'type'        => $post->post_type,
					'status'      => $post->post_status,
					'permalink'   => get_permalink( $post->ID ),
					'match_count' => $total_count,
					'matches'     => $matches,
				];
			}
		}

		return [
			'query'         => $query,
			'case_sensitive' => $case_sensitive,
			'results'       => $results,
			'total_posts'   => count( $results ),
		];
	}

	/**
	 * Find all occurrences of a query string within text, returning context snippets.
	 *
	 * @param string $text           The text to search in.
	 * @param string $query          The text to find.
	 * @param bool   $case_sensitive Whether to match case.
	 * @return array Array of context snippets around each match.
	 */
	protected function find_matches( string $text, string $query, bool $case_sensitive ): array {
		$matches    = [];
		$search_fn  = $case_sensitive ? 'mb_strpos' : 'mb_stripos';
		$offset     = 0;
		$query_len  = mb_strlen( $query );
		$context    = 60; // Characters of context on each side.
		$max_matches = 10; // Cap per source to avoid huge responses.

		while ( count( $matches ) < $max_matches ) {
			$pos = $search_fn( $text, $query, $offset );
			if ( false === $pos ) {
				break;
			}

			$start   = max( 0, $pos - $context );
			$length  = $query_len + ( $context * 2 );
			$snippet = mb_substr( $text, $start, $length );

			// Clean up for display (strip HTML tags, compress whitespace).
			$snippet = wp_strip_all_tags( $snippet );
			$snippet = preg_replace( '/\s+/', ' ', $snippet );

			$prefix = $start > 0 ? '...' : '';
			$suffix = ( $start + $length ) < mb_strlen( $text ) ? '...' : '';

			$matches[] = $prefix . trim( $snippet ) . $suffix;

			$offset = $pos + $query_len;
		}

		return $matches;
	}
}

/**
 * Replace text across post content with dry-run preview.
 *
 * First call returns a preview of all matches. The actual replacement
 * requires user confirmation via the ToolExecutor confirmation flow.
 */
class ReplaceContent extends ToolInterface {

	public function get_name(): string {
		return 'replace_content';
	}

	public function get_description(): string {
		return 'Replace text across WordPress posts and Elementor data. Shows a dry-run preview of all matches first, then requires confirmation to execute the replacement.';
	}

	public function get_category(): string {
		return 'search';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'search'         => [
					'type'        => 'string',
					'description' => 'The text to find and replace.',
				],
				'replace'        => [
					'type'        => 'string',
					'description' => 'The replacement text.',
				],
				'case_sensitive' => [
					'type'        => 'boolean',
					'description' => 'Whether the search should be case-sensitive.',
					'default'     => false,
				],
				'post_type'      => [
					'type'        => 'string',
					'description' => 'Limit to a specific post type. Omit to search all.',
				],
				'dry_run'        => [
					'type'        => 'boolean',
					'description' => 'If true, show what would change without making changes. Defaults to true for safety.',
					'default'     => true,
				],
			],
			'required'   => [ 'search', 'replace' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		global $wpdb;

		$search         = $input['search'];
		$replace        = $input['replace'];
		$case_sensitive = ! empty( $input['case_sensitive'] );
		$post_type      = ! empty( $input['post_type'] ) ? sanitize_key( $input['post_type'] ) : null;
		$dry_run        = $input['dry_run'] ?? true;

		$collation  = $case_sensitive ? 'COLLATE utf8mb4_bin' : '';
		$like_query = '%' . $wpdb->esc_like( $search ) . '%';

		// Find posts with matching content.
		$where_parts = [ "p.post_content {$collation} LIKE %s" ];
		$params      = [ $like_query ];

		if ( $post_type ) {
			$type_clause = ' AND p.post_type = %s';
			$where_parts[0] .= $type_clause;
			$params[] = $post_type;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$content_sql = $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_type, p.post_content
			 FROM {$wpdb->posts} p
			 WHERE {$where_parts[0]}
			 AND p.post_status NOT IN ('auto-draft', 'inherit')
			 AND p.post_type NOT IN ('revision', 'nav_menu_item', 'wp_template', 'wp_template_part', 'wp_global_styles')
			 ORDER BY p.post_date DESC
			 LIMIT 100",
			$params
		);

		$content_posts = $wpdb->get_results( $content_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Find posts with matching Elementor data.
		$elementor_params = [ $like_query ];
		$elementor_type_clause = '';
		if ( $post_type ) {
			$elementor_type_clause = ' AND p.post_type = %s';
			$elementor_params[] = $post_type;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$elementor_sql = $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_type, pm.meta_value AS elementor_data
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_elementor_data'
			 AND pm.meta_value {$collation} LIKE %s
			 {$elementor_type_clause}
			 AND p.post_status NOT IN ('auto-draft', 'inherit')
			 AND p.post_type NOT IN ('revision', 'nav_menu_item')
			 ORDER BY p.post_date DESC
			 LIMIT 100",
			$elementor_params
		);

		$elementor_posts = $wpdb->get_results( $elementor_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Build preview of all changes.
		$changes           = [];
		$total_replacements = 0;

		// Process standard content matches.
		foreach ( $content_posts as $post ) {
			$count = $case_sensitive
				? mb_substr_count( $post->post_content, $search )
				: mb_substr_count( mb_strtolower( $post->post_content ), mb_strtolower( $search ) );

			if ( $count > 0 ) {
				$changes[ $post->ID ] = [
					'post_id'   => $post->ID,
					'title'     => $post->post_title,
					'type'      => $post->post_type,
					'sources'   => [ 'post_content' ],
					'count'     => $count,
				];
				$total_replacements += $count;
			}
		}

		// Process Elementor matches.
		foreach ( $elementor_posts as $post ) {
			$data  = $post->elementor_data;
			$count = $case_sensitive
				? mb_substr_count( $data, $search )
				: mb_substr_count( mb_strtolower( $data ), mb_strtolower( $search ) );

			if ( $count > 0 ) {
				if ( isset( $changes[ $post->ID ] ) ) {
					$changes[ $post->ID ]['sources'][] = 'elementor_data';
					$changes[ $post->ID ]['count']    += $count;
				} else {
					$changes[ $post->ID ] = [
						'post_id'   => $post->ID,
						'title'     => $post->post_title,
						'type'      => $post->post_type,
						'sources'   => [ 'elementor_data' ],
						'count'     => $count,
					];
				}
				$total_replacements += $count;
			}
		}

		// Dry run — return preview only.
		if ( $dry_run ) {
			return [
				'dry_run'            => true,
				'search'             => $search,
				'replace'            => $replace,
				'total_replacements' => $total_replacements,
				'affected_posts'     => count( $changes ),
				'changes'            => array_values( $changes ),
				'message'            => $total_replacements > 0
					? "Found {$total_replacements} occurrence(s) across " . count( $changes ) . ' post(s). Confirm to execute replacement.'
					: 'No matches found.',
			];
		}

		// Execute replacement.
		$replaced_posts = 0;

		foreach ( $changes as $post_id => $change ) {
			// Replace in post_content.
			if ( in_array( 'post_content', $change['sources'], true ) ) {
				$post    = get_post( $post_id );
				$content = $post->post_content;
				$new_content = $case_sensitive
					? str_replace( $search, $replace, $content )
					: str_ireplace( $search, $replace, $content );

				if ( $new_content !== $content ) {
					wp_update_post([
						'ID'           => $post_id,
						'post_content' => $new_content,
					]);
				}
			}

			// Replace in Elementor data.
			if ( in_array( 'elementor_data', $change['sources'], true ) ) {
				$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
				if ( $elementor_data ) {
					$new_data = $case_sensitive
						? str_replace( $search, $replace, $elementor_data )
						: str_ireplace( $search, $replace, $elementor_data );

					if ( $new_data !== $elementor_data ) {
						update_post_meta( $post_id, '_elementor_data', wp_slash( $new_data ) );
					}
				}
			}

			$replaced_posts++;
		}

		return [
			'dry_run'            => false,
			'search'             => $search,
			'replace'            => $replace,
			'total_replacements' => $total_replacements,
			'affected_posts'     => $replaced_posts,
			'message'            => "Replaced {$total_replacements} occurrence(s) across {$replaced_posts} post(s).",
		];
	}
}

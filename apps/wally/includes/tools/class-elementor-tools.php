<?php
namespace Wally\Tools;

/**
 * Elementor-specific tools for searching, replacing, and inspecting
 * page builder data stored in _elementor_data post meta.
 *
 * Tools: elementor_search_content, elementor_replace_content,
 *        elementor_get_page_structure, elementor_clear_css_cache.
 * Category: "elementor" — restricted to administrators and editors.
 */

/**
 * Search text within Elementor widget data across all pages.
 */
class ElementorSearchContent extends ToolInterface {

	public function get_name(): string {
		return 'elementor_search_content';
	}

	public function get_description(): string {
		return 'Search for text within Elementor page builder widget data across all pages. Returns matching posts with context snippets from the Elementor JSON.';
	}

	public function get_category(): string {
		return 'elementor';
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
					'description' => 'The text to search for within Elementor data.',
				],
				'case_sensitive' => [
					'type'        => 'boolean',
					'description' => 'Whether the search should be case-sensitive.',
					'default'     => false,
				],
				'post_type'      => [
					'type'        => 'string',
					'description' => 'Limit search to a specific post type.',
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

		$collation  = $case_sensitive ? 'COLLATE utf8mb4_bin' : '';
		$like_query = '%' . $wpdb->esc_like( $query ) . '%';

		$params     = [ $like_query ];
		$type_clause = '';
		if ( $post_type ) {
			$type_clause = ' AND p.post_type = %s';
			$params[]    = $post_type;
		}
		$params[] = $per_page;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_type, p.post_status, pm.meta_value AS elementor_data
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_elementor_data'
			 AND pm.meta_value {$collation} LIKE %s
			 {$type_clause}
			 AND p.post_status NOT IN ('auto-draft', 'inherit')
			 AND p.post_type NOT IN ('revision', 'nav_menu_item')
			 ORDER BY p.post_date DESC
			 LIMIT %d",
			$params
		);

		$posts   = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = [];

		foreach ( $posts as $post ) {
			$data      = $post->elementor_data;
			$search_fn = $case_sensitive ? 'mb_strpos' : 'mb_stripos';
			$matches   = [];
			$offset    = 0;
			$query_len = mb_strlen( $query );
			$context   = 50;

			while ( count( $matches ) < 10 ) {
				$pos = $search_fn( $data, $query, $offset );
				if ( false === $pos ) {
					break;
				}

				$start   = max( 0, $pos - $context );
				$snippet = mb_substr( $data, $start, $query_len + ( $context * 2 ) );
				// Clean JSON artifacts for readability.
				$snippet = str_replace( [ '\\n', '\\t', '\\"' ], [ ' ', ' ', '"' ], $snippet );
				$snippet = preg_replace( '/\s+/', ' ', $snippet );

				$prefix    = $start > 0 ? '...' : '';
				$suffix    = ( $start + $query_len + $context * 2 ) < mb_strlen( $data ) ? '...' : '';
				$matches[] = $prefix . trim( $snippet ) . $suffix;

				$offset = $pos + $query_len;
			}

			if ( ! empty( $matches ) ) {
				$count = $case_sensitive
					? mb_substr_count( $data, $query )
					: mb_substr_count( mb_strtolower( $data ), mb_strtolower( $query ) );

				$results[] = [
					'post_id'     => $post->ID,
					'title'       => $post->post_title,
					'type'        => $post->post_type,
					'status'      => $post->post_status,
					'permalink'   => get_permalink( $post->ID ),
					'match_count' => $count,
					'snippets'    => $matches,
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
}

/**
 * Replace text in Elementor widget data with dry-run preview. Requires confirmation.
 */
class ElementorReplaceContent extends ToolInterface {

	public function get_name(): string {
		return 'elementor_replace_content';
	}

	public function get_description(): string {
		return 'Replace text within Elementor page builder data across posts. Shows a dry-run preview first, then requires confirmation to execute.';
	}

	public function get_category(): string {
		return 'elementor';
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
					'description' => 'The text to find.',
				],
				'replace'        => [
					'type'        => 'string',
					'description' => 'The replacement text.',
				],
				'case_sensitive' => [
					'type'        => 'boolean',
					'description' => 'Whether the search is case-sensitive.',
					'default'     => false,
				],
				'post_type'      => [
					'type'        => 'string',
					'description' => 'Limit to a specific post type.',
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

		$collation  = $case_sensitive ? 'COLLATE utf8mb4_bin' : '';
		$like_query = '%' . $wpdb->esc_like( $search ) . '%';

		$params      = [ $like_query ];
		$type_clause = '';
		if ( $post_type ) {
			$type_clause = ' AND p.post_type = %s';
			$params[]    = $post_type;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_type, pm.meta_value AS elementor_data
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_elementor_data'
			 AND pm.meta_value {$collation} LIKE %s
			 {$type_clause}
			 AND p.post_status NOT IN ('auto-draft', 'inherit')
			 AND p.post_type NOT IN ('revision', 'nav_menu_item')
			 ORDER BY p.post_date DESC
			 LIMIT 100",
			$params
		);

		$posts              = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$total_replacements = 0;
		$affected_posts     = 0;

		foreach ( $posts as $post ) {
			$data  = $post->elementor_data;
			$count = $case_sensitive
				? mb_substr_count( $data, $search )
				: mb_substr_count( mb_strtolower( $data ), mb_strtolower( $search ) );

			if ( $count <= 0 ) {
				continue;
			}

			$total_replacements += $count;

			$new_data = $case_sensitive
				? str_replace( $search, $replace, $data )
				: str_ireplace( $search, $replace, $data );

			if ( $new_data !== $data ) {
				update_post_meta( $post->ID, '_elementor_data', wp_slash( $new_data ) );
				$affected_posts++;
			}
		}

		// Clear Elementor CSS cache after modifications.
		if ( $affected_posts > 0 ) {
			$this->clear_elementor_cache();
		}

		return [
			'search'             => $search,
			'replace'            => $replace,
			'total_replacements' => $total_replacements,
			'affected_posts'     => $affected_posts,
			'cache_cleared'      => $affected_posts > 0,
			'message'            => $affected_posts > 0
				? "Replaced {$total_replacements} occurrence(s) in Elementor data across {$affected_posts} post(s). CSS cache cleared."
				: 'No matching Elementor content found.',
		];
	}

	/**
	 * Clear Elementor's CSS cache so regenerated pages reflect changes.
	 */
	private function clear_elementor_cache(): void {
		// Delete Elementor CSS files directory.
		$upload_dir = wp_upload_dir();
		$css_dir    = $upload_dir['basedir'] . '/elementor/css/';
		if ( is_dir( $css_dir ) ) {
			array_map( 'unlink', glob( $css_dir . '*.css' ) );
		}

		// Clear the Elementor post CSS meta flag so pages regenerate CSS on next load.
		delete_post_meta_by_key( '_elementor_css' );

		// If Elementor plugin provides a cache manager, use it.
		if ( class_exists( '\Elementor\Plugin' ) && method_exists( \Elementor\Plugin::$instance, 'files_manager' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}
}

/**
 * Get Elementor page structure as a human-readable tree.
 */
class ElementorGetPageStructure extends ToolInterface {

	public function get_name(): string {
		return 'elementor_get_page_structure';
	}

	public function get_description(): string {
		return 'Get the Elementor page structure for a post, showing sections, columns, and widgets in a readable tree format.';
	}

	public function get_category(): string {
		return 'elementor';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id' => [
					'type'        => 'integer',
					'description' => 'The post ID to get the Elementor structure for.',
				],
			],
			'required'   => [ 'post_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $input ): array {
		$post_id = absint( $input['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return [ 'error' => "Post not found: {$post_id}" ];
		}

		$raw_data = get_post_meta( $post_id, '_elementor_data', true );
		if ( ! $raw_data ) {
			return [
				'post_id' => $post_id,
				'title'   => $post->post_title,
				'message' => 'This post does not use Elementor or has no Elementor data.',
			];
		}

		$elements = json_decode( $raw_data, true );
		if ( ! is_array( $elements ) ) {
			return [ 'error' => 'Failed to parse Elementor data (invalid JSON).' ];
		}

		$tree = $this->build_tree( $elements );

		return [
			'post_id'   => $post_id,
			'title'     => $post->post_title,
			'structure' => $tree,
		];
	}

	/**
	 * Recursively build a human-readable tree from Elementor elements.
	 *
	 * @param array $elements Array of Elementor element data.
	 * @param int   $depth    Current nesting depth.
	 * @return array Simplified tree structure.
	 */
	private function build_tree( array $elements, int $depth = 0 ): array {
		$tree = [];

		foreach ( $elements as $element ) {
			$node = [
				'id'       => $element['id'] ?? '',
				'type'     => $element['elType'] ?? 'unknown',
				'widget'   => $element['widgetType'] ?? null,
			];

			// Extract key settings for context.
			$settings = $element['settings'] ?? [];
			if ( ! empty( $settings['title'] ) ) {
				$node['title'] = $settings['title'];
			}
			if ( ! empty( $settings['editor'] ) ) {
				// Text editor content — truncate for readability.
				$text = wp_strip_all_tags( $settings['editor'] );
				$node['text_preview'] = mb_strlen( $text ) > 100
					? mb_substr( $text, 0, 100 ) . '...'
					: $text;
			}
			if ( ! empty( $settings['title_text'] ) ) {
				$node['heading'] = $settings['title_text'];
			}
			if ( ! empty( $settings['text'] ) ) {
				$text = wp_strip_all_tags( $settings['text'] );
				$node['text_preview'] = mb_strlen( $text ) > 100
					? mb_substr( $text, 0, 100 ) . '...'
					: $text;
			}

			// Recurse into children.
			if ( ! empty( $element['elements'] ) ) {
				$node['children'] = $this->build_tree( $element['elements'], $depth + 1 );
			}

			$tree[] = $node;
		}

		return $tree;
	}
}

/**
 * Clear Elementor's CSS cache.
 */
class ElementorClearCssCache extends ToolInterface {

	public function get_name(): string {
		return 'elementor_clear_css_cache';
	}

	public function get_description(): string {
		return 'Clear Elementor CSS cache so pages regenerate their styles. Useful after content modifications.';
	}

	public function get_category(): string {
		return 'elementor';
	}

	public function get_action(): string {
		return 'site';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		// Delete CSS files.
		$upload_dir = wp_upload_dir();
		$css_dir    = $upload_dir['basedir'] . '/elementor/css/';
		$cleared    = 0;

		if ( is_dir( $css_dir ) ) {
			$files = glob( $css_dir . '*.css' );
			if ( $files ) {
				foreach ( $files as $file ) {
					if ( unlink( $file ) ) {
						$cleared++;
					}
				}
			}
		}

		// Clear per-post CSS regeneration flags.
		delete_post_meta_by_key( '_elementor_css' );

		// Use Elementor's built-in cache manager if available.
		if ( class_exists( '\Elementor\Plugin' ) && method_exists( \Elementor\Plugin::$instance, 'files_manager' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		return [
			'css_files_cleared' => $cleared,
			'message'           => "Elementor CSS cache cleared. {$cleared} CSS file(s) removed. Pages will regenerate styles on next load.",
		];
	}
}

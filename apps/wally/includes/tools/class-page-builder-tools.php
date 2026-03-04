<?php
namespace Wally\Tools;

/**
 * Page builder tools for Beaver Builder and Divi (non-Elementor builders).
 * Elementor has its own dedicated file (class-elementor-tools.php).
 *
 * Tools: beaver_builder_search_content, beaver_builder_get_layout,
 *        divi_search_content, divi_get_layout.
 * Category: "content" — requires edit_posts capability.
 */

/**
 * Search text within Beaver Builder layout data across posts and pages.
 */
class BeaverBuilderSearchContent extends ToolInterface {

	public function get_name(): string {
		return 'beaver_builder_search_content';
	}

	public function get_description(): string {
		return 'Search for text within Beaver Builder page builder layout data across posts and pages. Searches the _fl_builder_data post meta where Beaver Builder stores its rows, columns, and module settings. Returns matching posts with context snippets.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'FLBuilderModel' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'query'     => [
					'type'        => 'string',
					'description' => 'The text to search for within Beaver Builder layout data.',
				],
				'post_type' => [
					'type'        => 'string',
					'description' => 'Limit search to a specific post type (e.g., "page", "post"). Omit to search all types.',
				],
				'per_page'  => [
					'type'        => 'integer',
					'description' => 'Maximum number of results to return (max 50).',
					'default'     => 20,
				],
			],
			'required'   => [ 'query' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $params ): array {
		global $wpdb;

		$query      = $params['query'];
		$per_page   = min( (int) ( $params['per_page'] ?? 20 ), 50 );
		$post_type  = ! empty( $params['post_type'] ) ? sanitize_key( $params['post_type'] ) : null;
		$like_query = '%' . $wpdb->esc_like( $query ) . '%';

		$sql_params  = [ $like_query ];
		$type_clause = '';
		if ( $post_type ) {
			$type_clause  = ' AND p.post_type = %s';
			$sql_params[] = $post_type;
		}
		$sql_params[] = $per_page;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_type, p.post_status, pm.meta_value AS bb_data
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_fl_builder_data'
			 AND pm.meta_value LIKE %s
			 {$type_clause}
			 AND p.post_status NOT IN ('auto-draft', 'inherit', 'trash')
			 AND p.post_type NOT IN ('revision', 'nav_menu_item')
			 ORDER BY p.post_date DESC
			 LIMIT %d",
			$sql_params
		);

		$posts   = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = [];

		foreach ( $posts as $post ) {
			$data      = $post->bb_data;
			$matches   = [];
			$offset    = 0;
			$query_len = mb_strlen( $query );
			$context   = 60;

			while ( count( $matches ) < 5 ) {
				$pos = mb_stripos( $data, $query, $offset );
				if ( false === $pos ) {
					break;
				}

				$start   = max( 0, $pos - $context );
				$snippet = mb_substr( $data, $start, $query_len + ( $context * 2 ) );
				$snippet = str_replace( [ '\\n', '\\t', '\\"' ], [ ' ', ' ', '"' ], $snippet );
				$snippet = preg_replace( '/\s+/', ' ', $snippet );

				$prefix    = $start > 0 ? '...' : '';
				$suffix    = ( $start + $query_len + $context * 2 ) < mb_strlen( $data ) ? '...' : '';
				$matches[] = $prefix . trim( $snippet ) . $suffix;

				$offset = $pos + $query_len;
			}

			if ( ! empty( $matches ) ) {
				$results[] = [
					'post_id'   => (int) $post->ID,
					'title'     => $post->post_title,
					'type'      => $post->post_type,
					'status'    => $post->post_status,
					'permalink' => get_permalink( $post->ID ),
					'snippets'  => $matches,
				];
			}
		}

		return [
			'success' => true,
			'data'    => [
				'query'   => $query,
				'results' => $results,
				'total'   => count( $results ),
			],
		];
	}
}

/**
 * Get Beaver Builder layout structure for a specific post or page.
 */
class BeaverBuilderGetLayout extends ToolInterface {

	public function get_name(): string {
		return 'beaver_builder_get_layout';
	}

	public function get_description(): string {
		return 'Get the Beaver Builder layout structure for a specific post or page. Returns a hierarchical tree of rows, columns, and modules with their types and text previews. Useful for understanding page structure before editing.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'FLBuilderModel' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'      => [
					'type'        => 'integer',
					'description' => 'The post or page ID to retrieve the Beaver Builder layout for.',
				],
				'include_draft' => [
					'type'        => 'boolean',
					'description' => 'If true, returns the unpublished draft layout instead of the published layout.',
					'default'     => false,
				],
			],
			'required'   => [ 'post_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $params ): array {
		$post_id       = absint( $params['post_id'] );
		$include_draft = ! empty( $params['include_draft'] );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return [ 'success' => false, 'error' => "Post not found: {$post_id}" ];
		}

		// Check if Beaver Builder is active on this post.
		$is_enabled = get_post_meta( $post_id, '_fl_builder_enabled', true );
		if ( ! $is_enabled ) {
			return [
				'success' => true,
				'data'    => [
					'post_id' => $post_id,
					'title'   => $post->post_title,
					'message' => 'Beaver Builder is not active on this post.',
				],
			];
		}

		$status      = $include_draft ? 'draft' : 'published';
		$layout_data = \FLBuilderModel::get_layout_data( $status, $post_id );

		if ( empty( $layout_data ) || ! is_array( $layout_data ) ) {
			return [
				'success' => true,
				'data'    => [
					'post_id' => $post_id,
					'title'   => $post->post_title,
					'message' => 'No Beaver Builder layout data found.',
					'layout'  => [],
				],
			];
		}

		$tree = $this->build_tree( $layout_data );

		return [
			'success' => true,
			'data'    => [
				'post_id'     => $post_id,
				'title'       => $post->post_title,
				'permalink'   => get_permalink( $post_id ),
				'status'      => $status,
				'layout'      => $tree,
				'total_nodes' => count( $layout_data ),
			],
		];
	}

	/**
	 * Build a hierarchical tree from Beaver Builder's flat node array.
	 *
	 * Beaver Builder stores nodes as a flat keyed array. Each node has
	 * `type` (row|column|module), `parent` (parent node ID), `position`,
	 * and `settings` (module config as stdClass).
	 *
	 * @param array $nodes Flat array of stdClass nodes keyed by node ID.
	 * @return array Hierarchical tree rooted at top-level rows.
	 */
	private function build_tree( array $nodes ): array {
		$children_map = [];
		$roots        = [];

		foreach ( $nodes as $node_id => $node ) {
			$parent = $node->parent ?? null;
			if ( $parent && isset( $nodes[ $parent ] ) ) {
				$children_map[ $parent ][] = (string) $node_id;
			} else {
				$roots[] = (string) $node_id;
			}
		}

		// Sort roots by position.
		usort( $roots, fn( $a, $b ) => ( $nodes[ $a ]->position ?? 0 ) <=> ( $nodes[ $b ]->position ?? 0 ) );

		$build_node = null;
		$build_node = function ( string $node_id ) use ( &$build_node, $nodes, $children_map ): array {
			if ( ! isset( $nodes[ $node_id ] ) ) {
				return [];
			}
			$node = $nodes[ $node_id ];
			$item = [
				'id'       => $node_id,
				'type'     => $node->type ?? 'unknown',
				'position' => $node->position ?? 0,
			];

			// Extract useful settings for context.
			if ( ! empty( $node->settings ) ) {
				$settings = (array) $node->settings;
				if ( ! empty( $settings['text'] ) ) {
					$plain             = wp_strip_all_tags( $settings['text'] );
					$item['text_preview'] = mb_strlen( $plain ) > 100
						? mb_substr( $plain, 0, 100 ) . '...'
						: $plain;
				}
				if ( ! empty( $settings['title'] ) ) {
					$item['title'] = $settings['title'];
				}
				if ( ! empty( $settings['node_label'] ) ) {
					$item['label'] = $settings['node_label'];
				}
			}

			if ( ! empty( $children_map[ $node_id ] ) ) {
				$children = $children_map[ $node_id ];
				usort(
					$children,
					fn( $a, $b ) => ( $nodes[ $a ]->position ?? 0 ) <=> ( $nodes[ $b ]->position ?? 0 )
				);
				$item['children'] = array_values(
					array_filter( array_map( $build_node, $children ) )
				);
			}

			return $item;
		};

		return array_values( array_filter( array_map( $build_node, $roots ) ) );
	}
}

/**
 * Search text within Divi page builder content across posts and pages.
 */
class DiviSearchContent extends ToolInterface {

	public function get_name(): string {
		return 'divi_search_content';
	}

	public function get_description(): string {
		return 'Search for text within Divi page builder content across posts and pages. Divi stores layout as shortcodes directly in post_content (e.g., [et_pb_section], [et_pb_text]). Only searches posts where the Divi builder is active.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return function_exists( 'et_pb_is_pagebuilder_used' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'query'     => [
					'type'        => 'string',
					'description' => 'The text to search for within Divi builder content.',
				],
				'post_type' => [
					'type'        => 'string',
					'description' => 'Limit search to a specific post type (e.g., "page", "post"). Omit to search all types.',
				],
				'per_page'  => [
					'type'        => 'integer',
					'description' => 'Maximum number of results to return (max 50).',
					'default'     => 20,
				],
			],
			'required'   => [ 'query' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $params ): array {
		global $wpdb;

		$query      = $params['query'];
		$per_page   = min( (int) ( $params['per_page'] ?? 20 ), 50 );
		$post_type  = ! empty( $params['post_type'] ) ? sanitize_key( $params['post_type'] ) : null;
		$like_query = '%' . $wpdb->esc_like( $query ) . '%';

		$sql_params  = [ $like_query ];
		$type_clause = '';
		if ( $post_type ) {
			$type_clause  = ' AND p.post_type = %s';
			$sql_params[] = $post_type;
		}
		$sql_params[] = $per_page;

		// Divi stores content in post_content; filter to Divi-enabled posts via _et_pb_use_builder meta.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_type, p.post_status, p.post_content
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_et_pb_use_builder'
			 AND pm.meta_value = 'on'
			 AND p.post_content LIKE %s
			 {$type_clause}
			 AND p.post_status NOT IN ('auto-draft', 'inherit', 'trash')
			 AND p.post_type NOT IN ('revision', 'nav_menu_item')
			 ORDER BY p.post_date DESC
			 LIMIT %d",
			$sql_params
		);

		$posts   = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = [];

		foreach ( $posts as $post ) {
			$content   = $post->post_content;
			$matches   = [];
			$offset    = 0;
			$query_len = mb_strlen( $query );
			$context   = 80;

			while ( count( $matches ) < 5 ) {
				$pos = mb_stripos( $content, $query, $offset );
				if ( false === $pos ) {
					break;
				}

				$start   = max( 0, $pos - $context );
				$snippet = mb_substr( $content, $start, $query_len + ( $context * 2 ) );
				$snippet = preg_replace( '/\s+/', ' ', $snippet );

				$prefix    = $start > 0 ? '...' : '';
				$suffix    = ( $start + $query_len + $context * 2 ) < mb_strlen( $content ) ? '...' : '';
				$matches[] = $prefix . trim( $snippet ) . $suffix;

				$offset = $pos + $query_len;
			}

			if ( ! empty( $matches ) ) {
				$results[] = [
					'post_id'   => (int) $post->ID,
					'title'     => $post->post_title,
					'type'      => $post->post_type,
					'status'    => $post->post_status,
					'permalink' => get_permalink( $post->ID ),
					'snippets'  => $matches,
				];
			}
		}

		return [
			'success' => true,
			'data'    => [
				'query'   => $query,
				'results' => $results,
				'total'   => count( $results ),
			],
		];
	}
}

/**
 * Get Divi layout structure for a specific post or page.
 */
class DiviGetLayout extends ToolInterface {

	public function get_name(): string {
		return 'divi_get_layout';
	}

	public function get_description(): string {
		return 'Get the Divi page builder layout structure for a specific post or page. Parses the shortcode hierarchy (sections, rows, columns, modules) from post_content and returns a readable summary including module types and counts.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return function_exists( 'et_pb_is_pagebuilder_used' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id' => [
					'type'        => 'integer',
					'description' => 'The post or page ID to retrieve the Divi layout for.',
				],
			],
			'required'   => [ 'post_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $params ): array {
		$post_id = absint( $params['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return [ 'success' => false, 'error' => "Post not found: {$post_id}" ];
		}

		if ( ! et_pb_is_pagebuilder_used( $post_id ) ) {
			return [
				'success' => true,
				'data'    => [
					'post_id' => $post_id,
					'title'   => $post->post_title,
					'message' => 'Divi Builder is not active on this post.',
				],
			];
		}

		$content = $post->post_content;
		if ( empty( trim( $content ) ) ) {
			return [
				'success' => true,
				'data'    => [
					'post_id' => $post_id,
					'title'   => $post->post_title,
					'message' => 'No Divi content found in this post.',
					'layout'  => [],
				],
			];
		}

		$layout = $this->parse_divi_structure( $content );

		return [
			'success' => true,
			'data'    => [
				'post_id'       => $post_id,
				'title'         => $post->post_title,
				'permalink'     => get_permalink( $post_id ),
				'section_count' => count( $layout ),
				'layout'        => $layout,
			],
		];
	}

	/**
	 * Parse Divi shortcode content into a section-level structure.
	 *
	 * Divi shortcode hierarchy: [et_pb_section] > [et_pb_row] > [et_pb_column] > modules.
	 * Regex-based parsing is safe here because sections cannot be nested within sections.
	 *
	 * @param string $content Post content with Divi shortcodes.
	 * @return array Array of section summaries.
	 */
	private function parse_divi_structure( string $content ): array {
		$sections = [];
		$skip     = [ 'row', 'column', 'row_inner', 'column_inner' ];

		preg_match_all(
			'/\[et_pb_section([^\]]*)\](.*?)\[\/et_pb_section\]/s',
			$content,
			$section_matches,
			PREG_SET_ORDER
		);

		foreach ( $section_matches as $idx => $section ) {
			$section_content = $section[2];
			$section_attrs   = $this->parse_shortcode_attrs( $section[1] );

			// Count rows within this section.
			preg_match_all( '/\[et_pb_row\b/', $section_content, $row_m );
			$row_count = count( $row_m[0] );

			// Collect all module shortcode opening tags, excluding structural elements.
			preg_match_all( '/\[et_pb_(\w+)[^\]]*\]/', $section_content, $mod_m, PREG_SET_ORDER );
			$module_counts = [];
			foreach ( $mod_m as $mod ) {
				if ( ! in_array( $mod[1], $skip, true ) ) {
					$key                   = 'et_pb_' . $mod[1];
					$module_counts[ $key ] = ( $module_counts[ $key ] ?? 0 ) + 1;
				}
			}

			$section_item = [
				'section_index' => $idx + 1,
				'row_count'     => $row_count,
				'modules'       => $module_counts,
			];

			if ( ! empty( $section_attrs['admin_label'] ) ) {
				$section_item['label'] = $section_attrs['admin_label'];
			}
			if ( ! empty( $section_attrs['specialty'] ) && 'on' === $section_attrs['specialty'] ) {
				$section_item['specialty'] = true;
			}

			$sections[] = $section_item;
		}

		return $sections;
	}

	/**
	 * Parse shortcode attribute string into a key => value array.
	 *
	 * @param string $attrs_string The attribute portion of a shortcode tag.
	 * @return array Parsed attributes.
	 */
	private function parse_shortcode_attrs( string $attrs_string ): array {
		$attrs = [];
		preg_match_all( '/(\w+)=["\']([^"\']*)["\']/', $attrs_string, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$attrs[ $match[1] ] = $match[2];
		}
		return $attrs;
	}
}

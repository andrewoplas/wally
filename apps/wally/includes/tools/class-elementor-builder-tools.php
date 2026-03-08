<?php
namespace Wally\Tools;

/**
 * Elementor builder tools: create pages, design layouts, add/update/delete widgets.
 *
 * All tools only register when Elementor is active (class_exists check in can_register).
 *
 * Tools: elementor_list_templates, elementor_create_page, elementor_create_from_template,
 *        elementor_duplicate_page, elementor_update_page_layout, elementor_add_widget,
 *        elementor_update_widget, elementor_delete_element.
 */

/**
 * Abstract base providing shared helpers for all Elementor builder tools.
 */
abstract class ElementorBuilderBase extends ToolInterface {

	/** @inheritDoc */
	public static function can_register(): bool {
		return class_exists( '\Elementor\Plugin' );
	}

	/**
	 * Get decoded _elementor_data for a post. Returns [] on missing or invalid JSON.
	 */
	protected function get_elementor_data( int $post_id ): array {
		$raw = get_post_meta( $post_id, '_elementor_data', true );
		if ( ! $raw ) {
			return [];
		}
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Persist Elementor elements data using a 3-tier approach so that CSS is
	 * generated and post_content is updated (preventing blank pages).
	 *
	 * Tier 1 (primary)  — Elementor Document API: triggers the full save
	 *   pipeline (persists _elementor_data, generates per-post CSS file,
	 *   updates post_content with rendered HTML, fires hooks).
	 *   Requires _elementor_edit_mode = 'builder', which init_elementor_meta()
	 *   sets before this method is called.
	 *
	 * Tier 2 (fallback) — Raw meta save + explicit CSS regeneration via the
	 *   Post CSS class. Used when the Document API is unavailable (older
	 *   Elementor versions).
	 *
	 * Tier 3 (last resort) — Raw meta save + global cache clear (original
	 *   behaviour). Used when the CSS Post class is also unavailable.
	 */
	protected function save_elementor_data( int $post_id, array $data ): void {
		// Tier 1: Elementor Document API (full save pipeline).
		if (
			class_exists( '\Elementor\Plugin' ) &&
			isset( \Elementor\Plugin::$instance->documents ) &&
			method_exists( \Elementor\Plugin::$instance->documents, 'get' )
		) {
			$document = \Elementor\Plugin::$instance->documents->get( $post_id );
			if ( $document && method_exists( $document, 'save' ) ) {
				$document->save( [ 'elements' => $data ] );
				return;
			}
		}

		// Tier 2: Raw meta save + per-post CSS regeneration.
		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			$post_css = new \Elementor\Core\Files\CSS\Post( $post_id );
			if ( method_exists( $post_css, 'update' ) ) {
				$post_css->update();
				return;
			}
		}

		// Tier 3: Raw meta already saved above; clear the global cache.
		$this->clear_elementor_css( $post_id );
	}

	/**
	 * Write the required Elementor meta keys that flag a post as Elementor-built.
	 */
	protected function init_elementor_meta( int $post_id ): void {
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_template_type', 'wp-page' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
		}
		update_post_meta( $post_id, '_elementor_css', '' );
	}

	/**
	 * Generate a random 8-character hex element ID as used by Elementor.
	 */
	protected function generate_id(): string {
		return substr( md5( uniqid( '', true ) ), 0, 8 );
	}

	/**
	 * Delete the Elementor CSS cache for a post so it regenerates on next load.
	 */
	protected function clear_elementor_css( int $post_id ): void {
		delete_post_meta( $post_id, '_elementor_css' );
		if (
			class_exists( '\Elementor\Plugin' ) &&
			isset( \Elementor\Plugin::$instance->files_manager ) &&
			method_exists( \Elementor\Plugin::$instance->files_manager, 'clear_cache' )
		) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}

	/**
	 * Recursively find an element by ID and merge new settings into it.
	 *
	 * @param array  $elements Element tree (by reference).
	 * @param string $id       Target element ID.
	 * @param array  $settings Settings to merge in.
	 * @return bool True if the element was found and updated.
	 */
	protected function update_element_settings( array &$elements, string $id, array $settings ): bool {
		foreach ( $elements as &$el ) {
			if ( ( $el['id'] ?? '' ) === $id ) {
				$el['settings'] = array_merge(
					is_array( $el['settings'] ?? null ) ? $el['settings'] : [],
					$settings
				);
				return true;
			}
			if ( ! empty( $el['elements'] ) && $this->update_element_settings( $el['elements'], $id, $settings ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Recursively remove an element by ID from the tree.
	 *
	 * @param array  $elements Element tree (by reference).
	 * @param string $id       Target element ID.
	 * @return bool True if the element was found and removed.
	 */
	protected function delete_element( array &$elements, string $id ): bool {
		foreach ( $elements as $i => $el ) {
			if ( ( $el['id'] ?? '' ) === $id ) {
				array_splice( $elements, $i, 1 );
				return true;
			}
			if ( ! empty( $elements[ $i ]['elements'] ) && $this->delete_element( $elements[ $i ]['elements'], $id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Recursively find a container by ID and insert a widget into it.
	 *
	 * @param array    $elements     Element tree (by reference).
	 * @param string   $container_id Target container element ID.
	 * @param array    $widget       Widget element to insert.
	 * @param int|null $position     Zero-based index to insert at (null = append).
	 * @return bool True if the container was found and widget inserted.
	 */
	protected function insert_into_container( array &$elements, string $container_id, array $widget, ?int $position ): bool {
		foreach ( $elements as &$el ) {
			if ( ( $el['id'] ?? '' ) === $container_id ) {
				if ( ! is_array( $el['elements'] ) ) {
					$el['elements'] = [];
				}
				if ( $position === null || $position >= count( $el['elements'] ) ) {
					$el['elements'][] = $widget;
				} else {
					array_splice( $el['elements'], max( 0, $position ), 0, [ $widget ] );
				}
				return true;
			}
			if ( ! empty( $el['elements'] ) && $this->insert_into_container( $el['elements'], $container_id, $widget, $position ) ) {
				return true;
			}
		}
		return false;
	}
}

/**
 * List saved Elementor templates from the template library.
 */
class ElementorListTemplates extends ElementorBuilderBase {

	public function get_name(): string {
		return 'elementor_list_templates';
	}

	public function get_description(): string {
		return 'List saved Elementor templates from the template library (elementor_library post type). Returns IDs, titles, and types. Use template IDs with elementor_create_from_template.';
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
				'type'     => [
					'type'        => 'string',
					'description' => 'Filter by template type: page, section, block, popup. Omit to return all.',
				],
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Max results to return (default 50, max 100).',
					'default'     => 50,
				],
			],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $input ): array {
		$args = [
			'post_type'      => 'elementor_library',
			'post_status'    => 'publish',
			'posts_per_page' => min( (int) ( $input['per_page'] ?? 50 ), 100 ),
		];

		if ( ! empty( $input['type'] ) ) {
			$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'elementor_library_type',
					'field'    => 'slug',
					'terms'    => sanitize_key( $input['type'] ),
				],
			];
		}

		$query     = new \WP_Query( $args );
		$templates = [];

		foreach ( $query->posts as $post ) {
			$type_terms  = wp_get_post_terms( $post->ID, 'elementor_library_type', [ 'fields' => 'names' ] );
			$templates[] = [
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'type'    => ( ! is_wp_error( $type_terms ) && ! empty( $type_terms ) ) ? $type_terms[0] : 'unknown',
				'created' => $post->post_date,
			];
		}

		return [
			'total'     => count( $templates ),
			'templates' => $templates,
		];
	}
}

/**
 * Create a new WordPress page with a designer Elementor layout.
 */
class ElementorCreatePage extends ElementorBuilderBase {

	public function get_name(): string {
		return 'elementor_create_page';
	}

	public function get_description(): string {
		return 'Create a new WordPress page with an Elementor layout. Provide the COMPLETE elements array in one call — include all sections, columns, and widgets up front. After creating, call elementor_get_page_structure to verify the content saved correctly.

ELEMENT STRUCTURE:
- Container: {"id":"8hexchars","elType":"container","isInner":false,"settings":{},"elements":[...]}
- Widget:    {"id":"8hexchars","elType":"widget","widgetType":"TYPE","isInner":false,"settings":{...},"elements":[]}

WIDGET TYPES AND KEY SETTINGS:
- heading:    {"title":"text","header_size":"h1|h2|h3|h4|h5|h6","align":"left|center|right","title_color":"#hex"}
- text-editor:{"editor":"<p>HTML content</p>"}
- image:      {"image":{"url":"","id":0},"align":"left|center|right"}
- button:     {"text":"Label","link":{"url":"https://..."},"align":"left|center|right|justify","button_type":"default|info|success|warning|danger"}
- divider:    {}
- spacer:     {"space":{"unit":"px","size":50,"sizes":[]}}
- icon-box:   {"title_text":"Title","description_text":"Desc","icon":{"value":"fas fa-star","library":"fa-solid"}}
- video:      {"youtube_url":"https://..."}
- html:       {"html":"<div>custom HTML</div>"}
- shortcode:  {"shortcode":"[my_shortcode]"}

MULTI-COLUMN LAYOUT: Use a container with "flex_direction":"row" containing inner containers (isInner:true) with "width":{"unit":"%","size":50} (two cols) or "size":33 (three cols).

STYLING: Background color: "background_background":"classic","background_color":"#1a1a2e". Padding: "padding":{"unit":"px","top":"60","right":"40","bottom":"60","left":"40","isLinked":false}.

COMPLETE EXAMPLE — landing page with hero, 3-column features, and CTA:
[
  {"id":"aaa11111","elType":"container","isInner":false,
   "settings":{"background_background":"classic","background_color":"#1a1a2e","padding":{"unit":"px","top":"80","right":"40","bottom":"80","left":"40","isLinked":false}},
   "elements":[
     {"id":"aaa11112","elType":"widget","widgetType":"heading","isInner":false,"settings":{"title":"Your Headline","header_size":"h1","align":"center","title_color":"#ffffff"},"elements":[]},
     {"id":"aaa11113","elType":"widget","widgetType":"text-editor","isInner":false,"settings":{"editor":"<p style=\"text-align:center;color:#cccccc;\">Sub-headline text.</p>"},"elements":[]},
     {"id":"aaa11114","elType":"widget","widgetType":"button","isInner":false,"settings":{"text":"Get Started","link":{"url":"#"},"align":"center"},"elements":[]}
   ]},
  {"id":"bbb22222","elType":"container","isInner":false,
   "settings":{"padding":{"unit":"px","top":"60","right":"40","bottom":"60","left":"40","isLinked":false},"flex_direction":"row"},
   "elements":[
     {"id":"bbb22223","elType":"container","isInner":true,"settings":{"width":{"unit":"%","size":33}},"elements":[
       {"id":"bbb22224","elType":"widget","widgetType":"icon-box","isInner":false,"settings":{"icon":{"value":"fas fa-star","library":"fa-solid"},"title_text":"Feature One","description_text":"Description of this feature."},"elements":[]}
     ]},
     {"id":"bbb22225","elType":"container","isInner":true,"settings":{"width":{"unit":"%","size":33}},"elements":[
       {"id":"bbb22226","elType":"widget","widgetType":"icon-box","isInner":false,"settings":{"icon":{"value":"fas fa-bolt","library":"fa-solid"},"title_text":"Feature Two","description_text":"Description of this feature."},"elements":[]}
     ]},
     {"id":"bbb22227","elType":"container","isInner":true,"settings":{"width":{"unit":"%","size":33}},"elements":[
       {"id":"bbb22228","elType":"widget","widgetType":"icon-box","isInner":false,"settings":{"icon":{"value":"fas fa-check","library":"fa-solid"},"title_text":"Feature Three","description_text":"Description of this feature."},"elements":[]}
     ]}
   ]},
  {"id":"ccc33333","elType":"container","isInner":false,
   "settings":{"background_background":"classic","background_color":"#0066cc","padding":{"unit":"px","top":"60","right":"40","bottom":"60","left":"40","isLinked":false}},
   "elements":[
     {"id":"ccc33334","elType":"widget","widgetType":"heading","isInner":false,"settings":{"title":"Ready to Get Started?","header_size":"h2","align":"center","title_color":"#ffffff"},"elements":[]},
     {"id":"ccc33335","elType":"widget","widgetType":"button","isInner":false,"settings":{"text":"Contact Us","link":{"url":"/contact"},"align":"center"},"elements":[]}
   ]}
]

Always generate unique 8-character hex IDs for each element (e.g. "a1b2c3d4"). Never reuse an ID within the same page.';
	}

	public function get_category(): string {
		return 'elementor';
	}

	public function get_action(): string {
		return 'create';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'title'         => [
					'type'        => 'string',
					'description' => 'Page title.',
				],
				'status'        => [
					'type'        => 'string',
					'description' => 'Post status: draft or publish.',
					'enum'        => [ 'draft', 'publish' ],
					'default'     => 'draft',
				],
				'page_template' => [
					'type'        => 'string',
					'description' => 'Page template: "elementor_canvas" for full-width (no theme header/footer), "default" to use the active theme layout.',
					'enum'        => [ 'default', 'elementor_canvas' ],
					'default'     => 'default',
				],
				'elements'      => [
					'type'        => 'array',
					'description' => 'Elementor elements array defining the full page layout. See tool description for structure and widget types.',
					'items'       => [ 'type' => 'object' ],
				],
			],
			'required'   => [ 'title', 'elements' ],
		];
	}

	public function get_required_capability(): string {
		return 'publish_posts';
	}

	public function execute( array $input ): array {
		$title         = sanitize_text_field( $input['title'] );
		$status        = $input['status'] ?? 'draft';
		$page_template = $input['page_template'] ?? 'default';
		$elements      = $input['elements'] ?? [];

		$post_id = wp_insert_post( [
			'post_title'  => $title,
			'post_status' => $status,
			'post_type'   => 'page',
			'post_author' => get_current_user_id(),
		] );

		if ( is_wp_error( $post_id ) ) {
			return [ 'success' => false, 'error' => $post_id->get_error_message() ];
		}

		if ( 'elementor_canvas' === $page_template ) {
			update_post_meta( $post_id, '_wp_page_template', 'elementor_canvas' );
		}

		$this->init_elementor_meta( $post_id );
		$this->save_elementor_data( $post_id, $elements );

		return [
			'success'     => true,
			'post_id'     => $post_id,
			'title'       => $title,
			'status'      => $status,
			'edit_url'    => admin_url( "post.php?post={$post_id}&action=elementor" ),
			'preview_url' => get_permalink( $post_id ),
			'message'     => "Created Elementor page \"{$title}\" (ID: {$post_id}, status: {$status}).",
		];
	}
}

/**
 * Create a new page by duplicating a saved Elementor template.
 */
class ElementorCreateFromTemplate extends ElementorBuilderBase {

	public function get_name(): string {
		return 'elementor_create_from_template';
	}

	public function get_description(): string {
		return 'Create a new page by duplicating an existing Elementor saved template. Use elementor_list_templates to find available template IDs.';
	}

	public function get_category(): string {
		return 'elementor';
	}

	public function get_action(): string {
		return 'create';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'template_id' => [
					'type'        => 'integer',
					'description' => 'ID of the Elementor template (elementor_library post). Get from elementor_list_templates.',
				],
				'title'       => [
					'type'        => 'string',
					'description' => 'Title for the new page.',
				],
				'status'      => [
					'type'        => 'string',
					'description' => 'Post status: draft or publish.',
					'enum'        => [ 'draft', 'publish' ],
					'default'     => 'draft',
				],
			],
			'required'   => [ 'template_id', 'title' ],
		];
	}

	public function get_required_capability(): string {
		return 'publish_posts';
	}

	public function execute( array $input ): array {
		$template_id = absint( $input['template_id'] );
		$title       = sanitize_text_field( $input['title'] );
		$status      = $input['status'] ?? 'draft';

		$template = get_post( $template_id );
		if ( ! $template || 'elementor_library' !== $template->post_type ) {
			return [ 'success' => false, 'error' => "Elementor template not found: {$template_id}. Use elementor_list_templates." ];
		}

		$post_id = wp_insert_post( [
			'post_title'   => $title,
			'post_status'  => $status,
			'post_type'    => 'page',
			'post_content' => $template->post_content,
			'post_author'  => get_current_user_id(),
		] );

		if ( is_wp_error( $post_id ) ) {
			return [ 'success' => false, 'error' => $post_id->get_error_message() ];
		}

		// Copy Elementor meta from template to new page.
		foreach ( [ '_elementor_data', '_elementor_page_settings', '_elementor_page_assets', '_elementor_controls_usage' ] as $key ) {
			$value = get_post_meta( $template_id, $key, true );
			if ( '' !== $value ) {
				update_post_meta( $post_id, $key, $value );
			}
		}

		$this->init_elementor_meta( $post_id );

		$page_template = get_post_meta( $template_id, '_wp_page_template', true );
		if ( $page_template ) {
			update_post_meta( $post_id, '_wp_page_template', $page_template );
		}

		return [
			'success'         => true,
			'post_id'         => $post_id,
			'title'           => $title,
			'status'          => $status,
			'source_template' => $template->post_title,
			'edit_url'        => admin_url( "post.php?post={$post_id}&action=elementor" ),
			'preview_url'     => get_permalink( $post_id ),
			'message'         => "Created page \"{$title}\" from template \"{$template->post_title}\" (ID: {$post_id}).",
		];
	}
}

/**
 * Duplicate an existing Elementor page with its full layout.
 */
class ElementorDuplicatePage extends ElementorBuilderBase {

	public function get_name(): string {
		return 'elementor_duplicate_page';
	}

	public function get_description(): string {
		return 'Duplicate an existing Elementor page including its full layout, widgets, and settings. The copy is created as a draft.';
	}

	public function get_category(): string {
		return 'elementor';
	}

	public function get_action(): string {
		return 'create';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id' => [
					'type'        => 'integer',
					'description' => 'ID of the Elementor page to duplicate.',
				],
				'title'   => [
					'type'        => 'string',
					'description' => 'Title for the duplicate. Defaults to "Copy of [original title]".',
				],
			],
			'required'   => [ 'post_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'publish_posts';
	}

	public function execute( array $input ): array {
		$post_id = absint( $input['post_id'] );
		$source  = get_post( $post_id );

		if ( ! $source ) {
			return [ 'success' => false, 'error' => "Post not found: {$post_id}" ];
		}

		$title = ! empty( $input['title'] )
			? sanitize_text_field( $input['title'] )
			: 'Copy of ' . $source->post_title;

		$new_id = wp_insert_post( [
			'post_title'   => $title,
			'post_status'  => 'draft',
			'post_type'    => $source->post_type,
			'post_content' => $source->post_content,
			'post_author'  => get_current_user_id(),
		] );

		if ( is_wp_error( $new_id ) ) {
			return [ 'success' => false, 'error' => $new_id->get_error_message() ];
		}

		// Copy all Elementor and page template meta.
		foreach ( [ '_wp_page_template', '_elementor_data', '_elementor_page_settings', '_elementor_page_assets', '_elementor_controls_usage' ] as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( '' !== $value ) {
				update_post_meta( $new_id, $key, $value );
			}
		}

		$this->init_elementor_meta( $new_id );

		return [
			'success'      => true,
			'post_id'      => $new_id,
			'title'        => $title,
			'source_id'    => $post_id,
			'source_title' => $source->post_title,
			'edit_url'     => admin_url( "post.php?post={$new_id}&action=elementor" ),
			'message'      => "Duplicated \"{$source->post_title}\" → \"{$title}\" (draft, ID: {$new_id}).",
		];
	}
}

/**
 * Replace the entire Elementor layout of an existing page.
 */
class ElementorUpdatePageLayout extends ElementorBuilderBase {

	public function get_name(): string {
		return 'elementor_update_page_layout';
	}

	public function get_description(): string {
		return 'Replace the entire Elementor layout of an existing page with a new elements array. CAUTION: fully overwrites all existing Elementor content — use elementor_get_page_structure first to see the current layout before replacing it.

Provide the complete new elements array in one call — same format as elementor_create_page (containers and widgets with id, elType, widgetType, isInner, settings, elements fields). After updating, call elementor_get_page_structure to confirm the new layout saved correctly.

MULTI-COLUMN LAYOUT: Use an outer container with "flex_direction":"row" containing inner containers (isInner:true) with "width":{"unit":"%","size":50} for two columns or "size":33 for three columns.

EXAMPLE — two-column section with text and image:
{"id":"sec00001","elType":"container","isInner":false,
 "settings":{"flex_direction":"row","padding":{"unit":"px","top":"60","right":"40","bottom":"60","left":"40","isLinked":false}},
 "elements":[
   {"id":"col00011","elType":"container","isInner":true,"settings":{"width":{"unit":"%","size":50}},"elements":[
     {"id":"hd000111","elType":"widget","widgetType":"heading","isInner":false,"settings":{"title":"Our Story","header_size":"h2","align":"left"},"elements":[]},
     {"id":"tx000111","elType":"widget","widgetType":"text-editor","isInner":false,"settings":{"editor":"<p>Your story content here.</p>"},"elements":[]}
   ]},
   {"id":"col00012","elType":"container","isInner":true,"settings":{"width":{"unit":"%","size":50}},"elements":[
     {"id":"im000121","elType":"widget","widgetType":"image","isInner":false,"settings":{"image":{"url":"","id":0},"align":"center"},"elements":[]}
   ]}
 ]}';
	}

	public function get_category(): string {
		return 'elementor';
	}

	public function get_action(): string {
		return 'update';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'  => [
					'type'        => 'integer',
					'description' => 'ID of the page to update.',
				],
				'elements' => [
					'type'        => 'array',
					'description' => 'New Elementor elements array. Fully replaces the existing layout. See elementor_create_page for structure.',
					'items'       => [ 'type' => 'object' ],
				],
			],
			'required'   => [ 'post_id', 'elements' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $input ): array {
		$post_id  = absint( $input['post_id'] );
		$elements = $input['elements'] ?? [];

		$post = get_post( $post_id );
		if ( ! $post ) {
			return [ 'success' => false, 'error' => "Post not found: {$post_id}" ];
		}

		// Ensure the page is flagged as Elementor-built if it wasn't already.
		if ( 'builder' !== get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
			$this->init_elementor_meta( $post_id );
		}

		$this->save_elementor_data( $post_id, $elements );

		return [
			'success'  => true,
			'post_id'  => $post_id,
			'title'    => $post->post_title,
			'edit_url' => admin_url( "post.php?post={$post_id}&action=elementor" ),
			'message'  => "Replaced Elementor layout for \"{$post->post_title}\".",
		];
	}
}

/**
 * Add a widget to an existing Elementor page inside a target container.
 */
class ElementorAddWidget extends ElementorBuilderBase {

	public function get_name(): string {
		return 'elementor_add_widget';
	}

	public function get_description(): string {
		return 'Add a widget to an existing Elementor page inside a specific container. Use elementor_get_page_structure to find a valid container_id first.

WIDGET EXAMPLES (copy and adjust settings):

Heading:
{"id":"a1b2c3d4","elType":"widget","widgetType":"heading","isInner":false,"settings":{"title":"Section Title","header_size":"h2","align":"center","title_color":"#333333"},"elements":[]}

Text block:
{"id":"e5f6a7b8","elType":"widget","widgetType":"text-editor","isInner":false,"settings":{"editor":"<p>Your paragraph content here.</p>"},"elements":[]}

Button:
{"id":"12345678","elType":"widget","widgetType":"button","isInner":false,"settings":{"text":"Click Here","link":{"url":"https://example.com"},"align":"center","button_type":"default"},"elements":[]}

Icon-box:
{"id":"abcdef12","elType":"widget","widgetType":"icon-box","isInner":false,"settings":{"icon":{"value":"fas fa-star","library":"fa-solid"},"title_text":"Feature Title","description_text":"A short description."},"elements":[]}

Spacer:
{"id":"99887766","elType":"widget","widgetType":"spacer","isInner":false,"settings":{"space":{"unit":"px","size":40,"sizes":[]}},"elements":[]}

Use a unique 8-character hex ID for each widget. Never reuse an ID already on the page (check with elementor_get_page_structure).';
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
				'post_id'      => [
					'type'        => 'integer',
					'description' => 'ID of the Elementor page.',
				],
				'container_id' => [
					'type'        => 'string',
					'description' => 'Element ID of the parent container or column to insert into. Get from elementor_get_page_structure.',
				],
				'widget'       => [
					'type'        => 'object',
					'description' => 'Widget element object. Must have id (8-char hex), elType ("widget"), widgetType, isInner (false), settings, elements ([]).',
				],
				'position'     => [
					'type'        => 'integer',
					'description' => 'Zero-based index to insert at within the container. Omit to append at the end.',
				],
			],
			'required'   => [ 'post_id', 'container_id', 'widget' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $input ): array {
		$post_id      = absint( $input['post_id'] );
		$container_id = sanitize_text_field( $input['container_id'] );
		$widget       = $input['widget'];
		$position     = isset( $input['position'] ) ? (int) $input['position'] : null;

		$post = get_post( $post_id );
		if ( ! $post ) {
			return [ 'success' => false, 'error' => "Post not found: {$post_id}" ];
		}

		// Ensure widget has required fields.
		if ( empty( $widget['id'] ) ) {
			$widget['id'] = $this->generate_id();
		}
		$widget = array_merge(
			[ 'elType' => 'widget', 'isInner' => false, 'settings' => [], 'elements' => [] ],
			$widget
		);

		$elements = $this->get_elementor_data( $post_id );

		if ( ! $this->insert_into_container( $elements, $container_id, $widget, $position ) ) {
			return [
				'success' => false,
				'error'   => "Container not found: {$container_id}. Use elementor_get_page_structure to find valid container IDs.",
			];
		}

		$this->save_elementor_data( $post_id, $elements );

		$widget_type = $widget['widgetType'] ?? 'widget';

		return [
			'success'      => true,
			'post_id'      => $post_id,
			'widget_id'    => $widget['id'],
			'widget_type'  => $widget_type,
			'container_id' => $container_id,
			'message'      => "Added {$widget_type} widget (ID: {$widget['id']}) to \"{$post->post_title}\".",
		];
	}
}

/**
 * Update settings of a specific widget or element by element ID.
 */
class ElementorUpdateWidget extends ElementorBuilderBase {

	public function get_name(): string {
		return 'elementor_update_widget';
	}

	public function get_description(): string {
		return 'Update the settings of a specific widget or element on an Elementor page by its element ID. Only the provided settings keys are changed; all others are preserved. Use elementor_get_page_structure to find element IDs.

Common settings per widget type:
- heading:     {"title":"new text","align":"left|center|right","header_size":"h1|h2|h3"}
- text-editor: {"editor":"<p>new HTML</p>"}
- button:      {"text":"New Label","link":{"url":"https://..."}}
- image:       {"image":{"url":"https://...","id":0}}
- spacer:      {"space":{"unit":"px","size":80,"sizes":[]}}';
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
				'post_id'    => [
					'type'        => 'integer',
					'description' => 'ID of the Elementor page.',
				],
				'element_id' => [
					'type'        => 'string',
					'description' => 'The 8-character element ID from elementor_get_page_structure.',
				],
				'settings'   => [
					'type'        => 'object',
					'description' => 'Settings key-value pairs to merge into the element. Only supplied keys are updated.',
				],
			],
			'required'   => [ 'post_id', 'element_id', 'settings' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $input ): array {
		$post_id    = absint( $input['post_id'] );
		$element_id = sanitize_text_field( $input['element_id'] );
		$settings   = $input['settings'];

		$post = get_post( $post_id );
		if ( ! $post ) {
			return [ 'success' => false, 'error' => "Post not found: {$post_id}" ];
		}

		$elements = $this->get_elementor_data( $post_id );
		if ( empty( $elements ) ) {
			return [ 'success' => false, 'error' => "No Elementor data found for post {$post_id}." ];
		}

		if ( ! $this->update_element_settings( $elements, $element_id, $settings ) ) {
			return [
				'success' => false,
				'error'   => "Element not found: {$element_id}. Use elementor_get_page_structure to find valid element IDs.",
			];
		}

		$this->save_elementor_data( $post_id, $elements );

		return [
			'success'    => true,
			'post_id'    => $post_id,
			'element_id' => $element_id,
			'updated'    => array_keys( $settings ),
			'message'    => "Updated element {$element_id} on \"{$post->post_title}\".",
		];
	}
}

/**
 * Remove a widget or container element from an Elementor page.
 */
class ElementorDeleteElement extends ElementorBuilderBase {

	public function get_name(): string {
		return 'elementor_delete_element';
	}

	public function get_description(): string {
		return 'Remove a widget or container from an Elementor page by its element ID. Deleting a container removes all its child widgets too. Use elementor_get_page_structure to find element IDs. Requires confirmation.';
	}

	public function get_category(): string {
		return 'elementor';
	}

	public function get_action(): string {
		return 'delete';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'    => [
					'type'        => 'integer',
					'description' => 'ID of the Elementor page.',
				],
				'element_id' => [
					'type'        => 'string',
					'description' => 'The 8-character element ID to remove. From elementor_get_page_structure.',
				],
			],
			'required'   => [ 'post_id', 'element_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $input ): array {
		$post_id    = absint( $input['post_id'] );
		$element_id = sanitize_text_field( $input['element_id'] );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return [ 'success' => false, 'error' => "Post not found: {$post_id}" ];
		}

		$elements = $this->get_elementor_data( $post_id );
		if ( empty( $elements ) ) {
			return [ 'success' => false, 'error' => "No Elementor data found for post {$post_id}." ];
		}

		if ( ! $this->delete_element( $elements, $element_id ) ) {
			return [
				'success' => false,
				'error'   => "Element not found: {$element_id}. Use elementor_get_page_structure to find valid element IDs.",
			];
		}

		$this->save_elementor_data( $post_id, $elements );

		return [
			'success'    => true,
			'post_id'    => $post_id,
			'element_id' => $element_id,
			'message'    => "Deleted element {$element_id} from \"{$post->post_title}\".",
		];
	}
}

/**
 * Verify an Elementor page has renderable content.
 *
 * Checks _elementor_data, element/widget counts, CSS generation, and
 * post_content to detect blank-page conditions after programmatic saves.
 */
class ElementorVerifyPage extends ElementorBuilderBase {

	public function get_name(): string {
		return 'elementor_verify_page';
	}

	public function get_description(): string {
		return 'Verify an Elementor page has renderable content. Call this after elementor_create_page or elementor_update_page_layout to confirm the page will display correctly. Reports issues if the page may appear blank.';
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
					'description' => 'ID of the Elementor page to verify.',
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

		$post = get_post( $post_id );
		if ( ! $post ) {
			return [ 'success' => false, 'error' => "Post not found: {$post_id}" ];
		}

		$issues = [];

		// Check 1: _elementor_edit_mode is 'builder'.
		$edit_mode        = get_post_meta( $post_id, '_elementor_edit_mode', true );
		$has_builder_mode = 'builder' === $edit_mode;
		if ( ! $has_builder_mode ) {
			$issues[] = '_elementor_edit_mode is not set to "builder" — re-create the page with elementor_create_page.';
		}

		// Check 2: _elementor_data exists and is valid JSON.
		$raw      = get_post_meta( $post_id, '_elementor_data', true );
		$has_data = ! empty( $raw );
		$elements = [];

		if ( $has_data ) {
			$decoded = json_decode( $raw, true );
			if ( ! is_array( $decoded ) ) {
				$has_data = false;
				$issues[] = '_elementor_data exists but contains invalid JSON — re-create the page with elementor_create_page.';
			} else {
				$elements = $decoded;
			}
		} else {
			$issues[] = '_elementor_data is missing — the page has no Elementor content.';
		}

		// Check 3: Elements array contains actual widgets (not just empty containers).
		$element_count = 0;
		$widget_count  = 0;
		if ( ! empty( $elements ) ) {
			$this->count_elements( $elements, $element_count, $widget_count );
		}

		if ( 0 === $element_count ) {
			$issues[] = 'Elements array is empty — no content was saved. Retry with elementor_create_page.';
		} elseif ( 0 === $widget_count ) {
			$issues[] = 'No widgets found — only empty containers exist. Add content with elementor_add_widget or rebuild with elementor_update_page_layout.';
		}

		// Check 4: CSS has been generated (_elementor_css meta is non-empty after generation).
		$css_meta = get_post_meta( $post_id, '_elementor_css', true );
		$has_css  = ! empty( $css_meta );
		if ( ! $has_css ) {
			$issues[] = 'No CSS generated yet — call elementor_clear_css_cache to force regeneration on next page load.';
		}

		// Check 5: post_content is non-empty (populated by Elementor Document API on save).
		$has_rendered_content = ! empty( $post->post_content );
		if ( ! $has_rendered_content ) {
			$issues[] = 'post_content is empty — the Elementor save pipeline may not have completed. Retry with elementor_update_page_layout.';
		}

		return [
			'success' => true,
			'data'    => [
				'post_id'              => $post_id,
				'title'                => $post->post_title,
				'has_builder_mode'     => $has_builder_mode,
				'has_data'             => $has_data,
				'element_count'        => $element_count,
				'widget_count'         => $widget_count,
				'has_css'              => $has_css,
				'has_rendered_content' => $has_rendered_content,
				'status'               => empty( $issues ) ? 'healthy' : 'unhealthy',
				'issues'               => $issues,
			],
		];
	}

	/**
	 * Recursively count total elements and widget elements in the Elementor tree.
	 *
	 * @param array $elements     Element tree.
	 * @param int   $total        Running count of all elements (by reference).
	 * @param int   $widget_count Running count of widget elements (by reference).
	 */
	private function count_elements( array $elements, int &$total, int &$widget_count ): void {
		foreach ( $elements as $el ) {
			++$total;
			if ( 'widget' === ( $el['elType'] ?? '' ) ) {
				++$widget_count;
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$this->count_elements( $el['elements'], $total, $widget_count );
			}
		}
	}
}

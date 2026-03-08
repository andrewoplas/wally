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
		return 'Create a new WordPress page with an Elementor layout. Design the page by providing an elements array of containers and widgets.

ELEMENT STRUCTURE:
- Container: {"id":"8hexchars","elType":"container","isInner":false,"settings":{"html_tag":"section"},"elements":[...]}
- Widget:    {"id":"8hexchars","elType":"widget","widgetType":"TYPE","isInner":false,"settings":{...},"elements":[]}

WIDGET TYPES AND KEY SETTINGS:
- heading:    {"title":"text","header_size":"h1|h2|h3|h4|h5|h6","align":"left|center|right"}
- text-editor:{"editor":"<p>HTML content</p>"}
- image:      {"image":{"url":"","id":0},"align":"left|center|right"}
- button:     {"text":"Label","link":{"url":"https://..."},"align":"left|center|right|justify","button_type":"default|info|success|warning|danger"}
- divider:    {}
- spacer:     {"space":{"unit":"px","size":50,"sizes":[]}}
- icon-box:   {"title_text":"Title","description_text":"Desc","icon":{"value":"fas fa-star","library":"fa-solid"}}
- video:      {"youtube_url":"https://..."}
- html:       {"html":"<div>custom HTML</div>"}
- shortcode:  {"shortcode":"[my_shortcode]"}

Always generate unique 8-character hex IDs for each element (e.g. "a1b2c3d4").';
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
		return 'Replace the entire Elementor layout of an existing page with a new elements array. Use this to fully redesign a page. CAUTION: this overwrites all existing Elementor content. Use elementor_get_page_structure first to see the current layout.

Elements format: same as elementor_create_page — array of container/widget objects with id, elType, widgetType, isInner, settings, elements fields.';
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
		return 'Add a widget to an existing Elementor page inside a specific container or section. Use elementor_get_page_structure to find the container_id.

Widget object format:
{"id":"8hexchars","elType":"widget","widgetType":"heading|text-editor|image|button|divider|spacer|icon-box|video|html|shortcode","isInner":false,"settings":{...},"elements":[]}';
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

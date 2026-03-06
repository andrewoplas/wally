<?php
namespace Wally\Tools;

/**
 * Theme customization tools for reading and updating WordPress theme mods.
 *
 * Tools: get_theme_mods, update_theme_mod.
 * Category: "site" — restricted to administrators (edit_theme_options).
 */

/**
 * Read all active theme modifications (theme mods).
 */
class GetThemeMods extends ToolInterface {

	public function get_name(): string {
		return 'get_theme_mods';
	}

	public function get_description(): string {
		return 'Read all theme modifications (theme mods) for the active WordPress theme. Returns all customizer settings stored for the theme, such as custom_logo, background_color, header_textcolor, and any theme-specific settings (e.g., Astra uses "astra-settings[...]", Kadence uses "kadence[...]"). Use this before updating any customizer setting so you know what keys are available.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => (object) [],
		];
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function execute( array $params ): array {
		$theme = wp_get_theme();
		$mods  = get_theme_mods();

		if ( false === $mods || ! is_array( $mods ) ) {
			$mods = [];
		}

		return [
			'success' => true,
			'data'    => [
				'theme_name'       => $theme->get( 'Name' ),
				'theme_stylesheet' => get_stylesheet(),
				'mod_count'        => count( $mods ),
				'mods'             => $mods,
			],
		];
	}
}

/**
 * Update a single theme mod (customizer setting). Requires confirmation.
 */
class UpdateThemeMod extends ToolInterface {

	public function get_name(): string {
		return 'update_theme_mod';
	}

	public function get_description(): string {
		return 'Update a single WordPress theme mod (customizer setting) by key and value. Use get_theme_mods first to discover available keys for the active theme. Common keys include: custom_logo (attachment ID), background_color (hex without #), header_textcolor (hex without #). Theme-specific keys vary — Astra uses "astra-settings[site-layout]", etc. This is a destructive action requiring confirmation.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'key'   => [
					'type'        => 'string',
					'description' => 'The theme mod key to update (e.g., "background_color", "custom_logo", or a theme-specific key).',
				],
				'value' => [
					'type'        => 'string',
					'description' => 'The new value to set. For numeric IDs (e.g., custom_logo), pass the number as a string.',
				],
			],
			'required'   => [ 'key', 'value' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $params ): array {
		$key = sanitize_text_field( $params['key'] );

		if ( empty( $key ) ) {
			return [ 'success' => false, 'error' => 'Theme mod key cannot be empty.' ];
		}

		$old_value = get_theme_mod( $key );
		$new_value = $params['value'];

		// Cast to integer for known attachment ID keys.
		$int_keys = [ 'custom_logo', 'custom_header', 'background_image_id' ];
		if ( in_array( $key, $int_keys, true ) ) {
			$new_value = absint( $new_value );
		}

		set_theme_mod( $key, $new_value );

		return [
			'success' => true,
			'data'    => [
				'key'       => $key,
				'old_value' => $old_value,
				'new_value' => $new_value,
				'message'   => "Theme mod \"{$key}\" updated successfully.",
			],
		];
	}
}

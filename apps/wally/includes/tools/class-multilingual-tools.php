<?php
namespace Wally\Tools;

/**
 * Multilingual plugin management tools.
 *
 * Tools: list_wpml_languages, get_wpml_translation_status, list_polylang_languages.
 * Each tool has per-tool conditional registration.
 * APIs: WPML wpml_active_languages filter, icl_translations table; Polylang pll_languages_list().
 */

/**
 * List active languages configured in WPML.
 */
class ListWpmlLanguages extends ToolInterface {

	public static function can_register(): bool {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	public function get_name(): string {
		return 'list_wpml_languages';
	}

	public function get_description(): string {
		return 'List languages configured in WPML (WordPress Multilingual Plugin). Returns language codes, names, locale, and active status.';
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
			'properties' => [],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		// Get active languages via WPML API filter.
		$active_languages = apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] );

		$languages = [];
		if ( is_array( $active_languages ) ) {
			foreach ( $active_languages as $lang_code => $lang ) {
				$languages[] = [
					'code'       => $lang_code,
					'name'       => $lang['native_name'] ?? $lang['translated_name'] ?? $lang_code,
					'url'        => $lang['url'] ?? '',
					'flag_url'   => $lang['country_flag_url'] ?? '',
					'is_default' => ! empty( $lang['default'] ),
					'active'     => (bool) ( $lang['active'] ?? true ),
				];
			}
		}

		// Get default language.
		$default_language = apply_filters( 'wpml_default_language', null );

		// Read from settings as fallback.
		if ( empty( $languages ) ) {
			$settings = get_option( 'icl_sitepress_settings', [] );
			$active   = $settings['active_languages'] ?? [];
			$default  = $settings['default_language'] ?? '';

			foreach ( $active as $code ) {
				$languages[] = [
					'code'       => $code,
					'name'       => $code,
					'is_default' => $code === $default,
				];
			}
			$default_language = $default;
		}

		return [
			'languages'        => $languages,
			'total'            => count( $languages ),
			'default_language' => $default_language,
		];
	}
}

/**
 * Get translation status for a post using WPML.
 */
class GetWpmlTranslationStatus extends ToolInterface {

	public static function can_register(): bool {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	public function get_name(): string {
		return 'get_wpml_translation_status';
	}

	public function get_description(): string {
		return 'Get the translation status of a post or page in all WPML languages. Returns which languages have translations and the translated post IDs.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'   => [
					'type'        => 'integer',
					'description' => 'The post/page ID to check translation status for.',
				],
				'post_type' => [
					'type'        => 'string',
					'description' => 'The post type (default: "post"). Used to build the WPML element_type (e.g., "post_post", "post_page").',
					'default'     => 'post',
				],
			],
			'required'   => [ 'post_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$post_id   = absint( $input['post_id'] );
		$post_type = sanitize_key( $input['post_type'] ?? 'post' );
		$post      = get_post( $post_id );

		if ( ! $post ) {
			return [ 'error' => "Post not found: {$post_id}" ];
		}

		$element_type = 'post_' . $post_type;

		// Get the translation group ID (trid).
		$trid = apply_filters( 'wpml_element_trid', null, $post_id, $element_type );

		// Get all translations in the group.
		$translations_raw = apply_filters( 'wpml_get_element_translations', null, $trid, $element_type );

		$translations = [];
		if ( is_array( $translations_raw ) ) {
			foreach ( $translations_raw as $lang => $translation ) {
				$translated_id = is_object( $translation ) ? ( $translation->element_id ?? 0 ) : ( $translation['element_id'] ?? 0 );
				$is_original   = is_object( $translation ) ? empty( $translation->source_language_code ) : empty( $translation['source_language_code'] );

				$translations[] = [
					'language'    => $lang,
					'post_id'     => (int) $translated_id,
					'title'       => $translated_id ? get_the_title( $translated_id ) : '',
					'is_original' => $is_original,
					'permalink'   => $translated_id ? get_permalink( $translated_id ) : '',
				];
			}
		}

		// Get active languages to show which are missing.
		$active_languages = apply_filters( 'wpml_active_languages', null, [] );
		$translated_langs  = array_column( $translations, 'language' );
		$missing          = [];
		if ( is_array( $active_languages ) ) {
			foreach ( array_keys( $active_languages ) as $lang ) {
				if ( ! in_array( $lang, $translated_langs, true ) ) {
					$missing[] = $lang;
				}
			}
		}

		return [
			'post_id'      => $post_id,
			'post_title'   => $post->post_title,
			'trid'         => $trid,
			'translations' => $translations,
			'missing'      => $missing,
		];
	}
}

/**
 * List languages configured in Polylang.
 */
class ListPolylangLanguages extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'pll_languages_list' );
	}

	public function get_name(): string {
		return 'list_polylang_languages';
	}

	public function get_description(): string {
		return 'List languages configured in Polylang. Returns language slugs, names, locales, and post counts per language.';
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
			'properties' => [],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		// Get list of language slugs.
		$language_slugs = pll_languages_list( [ 'hide_empty' => 0 ] );

		if ( empty( $language_slugs ) ) {
			return [ 'languages' => [], 'total' => 0 ];
		}

		// Get default language.
		$default_lang = function_exists( 'pll_default_language' ) ? pll_default_language( 'slug' ) : '';

		$languages = [];
		foreach ( $language_slugs as $slug ) {
			$name   = pll_languages_list( [ 'hide_empty' => 0, 'fields' => 'name' ] );
			$locale = pll_languages_list( [ 'hide_empty' => 0, 'fields' => 'locale' ] );

			// Get count of posts in this language.
			$count = function_exists( 'pll_count_posts' ) ? pll_count_posts( $slug ) : 0;
			$home  = function_exists( 'pll_home_url' ) ? pll_home_url( $slug ) : '';

			$languages[] = [
				'slug'       => $slug,
				'is_default' => $slug === $default_lang,
				'post_count' => (int) $count,
				'home_url'   => $home,
			];
		}

		// Re-fetch with proper fields for names.
		$names   = pll_languages_list( [ 'hide_empty' => 0, 'fields' => 'name' ] );
		$locales = pll_languages_list( [ 'hide_empty' => 0, 'fields' => 'locale' ] );

		foreach ( $languages as $i => &$lang ) {
			$lang['name']   = $names[ $i ] ?? $lang['slug'];
			$lang['locale'] = $locales[ $i ] ?? '';
		}
		unset( $lang );

		return [
			'languages'        => $languages,
			'total'            => count( $languages ),
			'default_language' => $default_lang,
		];
	}
}

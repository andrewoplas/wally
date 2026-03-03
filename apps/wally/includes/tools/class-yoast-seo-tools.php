<?php
namespace Wally\Tools;

/**
 * Yoast SEO management tools.
 *
 * Tools: get_yoast_meta, update_yoast_meta.
 * Uses WordPress post meta functions with Yoast's _yoast_wpseo_ prefix.
 * Requires Yoast SEO to be active (defined('WPSEO_VERSION')).
 */

/**
 * Get Yoast SEO metadata for a post or page.
 */
class GetYoastMeta extends ToolInterface {

	public static function can_register(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	public function get_name(): string {
		return 'get_yoast_meta';
	}

	public function get_description(): string {
		return 'Get Yoast SEO metadata for a post or page: SEO title, meta description, focus keyword, canonical URL, and SEO/readability scores.';
	}

	public function get_category(): string {
		return 'seo';
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
					'description' => 'The post or page ID to get Yoast SEO metadata for.',
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

		return [
			'post_id'            => $post_id,
			'post_title'         => $post->post_title,
			'seo_title'          => get_post_meta( $post_id, '_yoast_wpseo_title', true ) ?: '',
			'meta_description'   => get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ) ?: '',
			'focus_keyword'      => get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ) ?: '',
			'canonical_url'      => get_post_meta( $post_id, '_yoast_wpseo_canonical', true ) ?: '',
			'og_title'           => get_post_meta( $post_id, '_yoast_wpseo_opengraph-title', true ) ?: '',
			'og_description'     => get_post_meta( $post_id, '_yoast_wpseo_opengraph-description', true ) ?: '',
			'twitter_title'      => get_post_meta( $post_id, '_yoast_wpseo_twitter-title', true ) ?: '',
			'twitter_description' => get_post_meta( $post_id, '_yoast_wpseo_twitter-description', true ) ?: '',
			'seo_score'          => (int) get_post_meta( $post_id, '_yoast_wpseo_linkdex', true ),
			'readability_score'  => (int) get_post_meta( $post_id, '_yoast_wpseo_content_score', true ),
		];
	}
}

/**
 * Update Yoast SEO metadata for a post or page.
 */
class UpdateYoastMeta extends ToolInterface {

	public static function can_register(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	public function get_name(): string {
		return 'update_yoast_meta';
	}

	public function get_description(): string {
		return 'Update Yoast SEO metadata for a post or page: SEO title, meta description, focus keyword, or canonical URL. Provide post_id and any fields to change.';
	}

	public function get_category(): string {
		return 'seo';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'          => [
					'type'        => 'integer',
					'description' => 'The post or page ID to update Yoast SEO metadata for.',
				],
				'seo_title'        => [
					'type'        => 'string',
					'description' => 'Custom SEO title. Can include Yoast variables like %%title%% %%sep%% %%sitename%%.',
				],
				'meta_description' => [
					'type'        => 'string',
					'description' => 'Custom meta description (recommended: 120-156 characters).',
				],
				'focus_keyword'    => [
					'type'        => 'string',
					'description' => 'Primary focus keyword for SEO analysis.',
				],
				'canonical_url'    => [
					'type'        => 'string',
					'description' => 'Custom canonical URL override. Leave empty to use default.',
				],
				'og_title'         => [
					'type'        => 'string',
					'description' => 'Open Graph title for social sharing (Facebook, LinkedIn).',
				],
				'og_description'   => [
					'type'        => 'string',
					'description' => 'Open Graph description for social sharing.',
				],
				'twitter_title'    => [
					'type'        => 'string',
					'description' => 'Twitter/X card title.',
				],
				'twitter_description' => [
					'type'        => 'string',
					'description' => 'Twitter/X card description.',
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

		$field_map = [
			'seo_title'           => '_yoast_wpseo_title',
			'meta_description'    => '_yoast_wpseo_metadesc',
			'focus_keyword'       => '_yoast_wpseo_focuskw',
			'canonical_url'       => '_yoast_wpseo_canonical',
			'og_title'            => '_yoast_wpseo_opengraph-title',
			'og_description'      => '_yoast_wpseo_opengraph-description',
			'twitter_title'       => '_yoast_wpseo_twitter-title',
			'twitter_description' => '_yoast_wpseo_twitter-description',
		];

		$changes = [];
		foreach ( $field_map as $input_key => $meta_key ) {
			if ( isset( $input[ $input_key ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $input[ $input_key ] ) );
				$changes[] = $input_key;
			}
		}

		if ( empty( $changes ) ) {
			return [ 'error' => 'No fields provided to update.' ];
		}

		return [
			'post_id' => $post_id,
			'updated' => $changes,
			'message' => "Yoast SEO metadata updated for post #{$post_id}.",
		];
	}
}

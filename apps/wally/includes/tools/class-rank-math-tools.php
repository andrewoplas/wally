<?php
namespace Wally\Tools;

/**
 * Rank Math SEO management tools.
 *
 * Tools: get_rank_math_meta, update_rank_math_meta.
 * Uses WordPress post meta functions with Rank Math's rank_math_ prefix.
 * Requires Rank Math SEO to be active (class_exists('RankMath')).
 */

/**
 * Get Rank Math SEO metadata for a post or page.
 */
class GetRankMathMeta extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'RankMath' );
	}

	public function get_name(): string {
		return 'get_rank_math_meta';
	}

	public function get_description(): string {
		return 'Get Rank Math SEO metadata for a post or page: SEO title, meta description, focus keywords, canonical URL, robots settings, and SEO score.';
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
					'description' => 'The post or page ID to get Rank Math SEO metadata for.',
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

		$robots = get_post_meta( $post_id, 'rank_math_robots', true );

		return [
			'post_id'          => $post_id,
			'post_title'       => $post->post_title,
			'seo_title'        => get_post_meta( $post_id, 'rank_math_title', true ) ?: '',
			'meta_description' => get_post_meta( $post_id, 'rank_math_description', true ) ?: '',
			'focus_keyword'    => get_post_meta( $post_id, 'rank_math_focus_keyword', true ) ?: '',
			'canonical_url'    => get_post_meta( $post_id, 'rank_math_canonical_url', true ) ?: '',
			'robots'           => is_array( $robots ) ? $robots : [],
			'og_title'         => get_post_meta( $post_id, 'rank_math_facebook_title', true ) ?: '',
			'og_description'   => get_post_meta( $post_id, 'rank_math_facebook_description', true ) ?: '',
			'twitter_title'    => get_post_meta( $post_id, 'rank_math_twitter_title', true ) ?: '',
			'seo_score'        => (int) get_post_meta( $post_id, 'rank_math_seo_score', true ),
		];
	}
}

/**
 * Update Rank Math SEO metadata for a post or page.
 */
class UpdateRankMathMeta extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'RankMath' );
	}

	public function get_name(): string {
		return 'update_rank_math_meta';
	}

	public function get_description(): string {
		return 'Update Rank Math SEO metadata for a post or page: SEO title, meta description, focus keywords, or canonical URL. Provide post_id and any fields to change.';
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
					'description' => 'The post or page ID to update Rank Math SEO metadata for.',
				],
				'seo_title'        => [
					'type'        => 'string',
					'description' => 'Custom SEO title. Can use Rank Math variables like %title% %sep% %sitename%.',
				],
				'meta_description' => [
					'type'        => 'string',
					'description' => 'Custom meta description (recommended: 120-160 characters).',
				],
				'focus_keyword'    => [
					'type'        => 'string',
					'description' => 'Focus keyword(s) for SEO analysis. Multiple keywords can be comma-separated.',
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
			'seo_title'        => 'rank_math_title',
			'meta_description' => 'rank_math_description',
			'focus_keyword'    => 'rank_math_focus_keyword',
			'canonical_url'    => 'rank_math_canonical_url',
			'og_title'         => 'rank_math_facebook_title',
			'og_description'   => 'rank_math_facebook_description',
			'twitter_title'    => 'rank_math_twitter_title',
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
			'message' => "Rank Math SEO metadata updated for post #{$post_id}.",
		];
	}
}

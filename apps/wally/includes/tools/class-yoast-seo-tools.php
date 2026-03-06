<?php
namespace Wally\Tools;

/**
 * Yoast SEO management tools.
 *
 * Tools: get_yoast_meta, update_yoast_meta, list_yoast_seo_issues.
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

/**
 * List published posts and pages with missing or poor Yoast SEO metadata.
 */
class ListYoastSeoIssues extends ToolInterface {

	public static function can_register(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	public function get_name(): string {
		return 'list_yoast_seo_issues';
	}

	public function get_description(): string {
		return 'List published posts and pages that have Yoast SEO issues: missing meta description, missing focus keyword, or a low SEO score (below 40/100). Use this when the user asks to "audit my SEO", "find pages with missing meta", "which posts need SEO work", or wants to improve their site\'s SEO health. Returns each post with a list of specific issues found.';
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
				'post_type'  => [
					'type'        => 'string',
					'description' => 'Post type to audit: "post", "page", or "any" for both. Defaults to "any".',
					'enum'        => [ 'post', 'page', 'any' ],
					'default'     => 'any',
				],
				'issue_type' => [
					'type'        => 'string',
					'description' => 'Filter by specific issue: "missing_meta_description", "missing_focus_keyword", "low_seo_score", or "any" to return all issues. Defaults to "any".',
					'enum'        => [ 'missing_meta_description', 'missing_focus_keyword', 'low_seo_score', 'any' ],
					'default'     => 'any',
				],
				'per_page'   => [
					'type'        => 'integer',
					'description' => 'Number of posts to return (max 50). Defaults to 20.',
					'default'     => 20,
				],
				'seo_score_threshold' => [
					'type'        => 'integer',
					'description' => 'SEO score threshold below which a post is flagged as having a low score. Defaults to 40 (out of 100).',
					'default'     => 40,
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $params ): array {
		$per_page  = min( (int) ( $params['per_page'] ?? 20 ), 50 );
		$threshold = (int) ( $params['seo_score_threshold'] ?? 40 );
		$issue_type = sanitize_key( $params['issue_type'] ?? 'any' );

		$post_type_param = sanitize_key( $params['post_type'] ?? 'any' );
		$post_types      = $post_type_param === 'any' ? [ 'post', 'page' ] : [ $post_type_param ];

		// Build meta_query based on requested issue type.
		$meta_clauses = [];

		if ( $issue_type === 'any' || $issue_type === 'missing_meta_description' ) {
			$meta_clauses[] = [
				'key'     => '_yoast_wpseo_metadesc',
				'compare' => 'NOT EXISTS',
			];
			$meta_clauses[] = [
				'key'     => '_yoast_wpseo_metadesc',
				'value'   => '',
				'compare' => '=',
			];
		}

		if ( $issue_type === 'any' || $issue_type === 'missing_focus_keyword' ) {
			$meta_clauses[] = [
				'key'     => '_yoast_wpseo_focuskw',
				'compare' => 'NOT EXISTS',
			];
			$meta_clauses[] = [
				'key'     => '_yoast_wpseo_focuskw',
				'value'   => '',
				'compare' => '=',
			];
		}

		if ( $issue_type === 'any' || $issue_type === 'low_seo_score' ) {
			$meta_clauses[] = [
				'key'     => '_yoast_wpseo_linkdex',
				'value'   => $threshold,
				'compare' => '<',
				'type'    => 'NUMERIC',
			];
		}

		$query_args = [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array_merge( [ 'relation' => 'OR' ], $meta_clauses ),
		];

		$query = new \WP_Query( $query_args );
		$posts = [];

		foreach ( $query->posts as $post ) {
			$meta_desc   = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
			$focus_kw    = get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true );
			$seo_score   = (int) get_post_meta( $post->ID, '_yoast_wpseo_linkdex', true );

			$issues = [];
			if ( empty( $meta_desc ) ) {
				$issues[] = 'missing_meta_description';
			}
			if ( empty( $focus_kw ) ) {
				$issues[] = 'missing_focus_keyword';
			}
			if ( $seo_score < $threshold ) {
				$issues[] = 'low_seo_score';
			}

			// Skip if no relevant issues match the requested filter.
			if ( $issue_type !== 'any' && ! in_array( $issue_type, $issues, true ) ) {
				continue;
			}

			$posts[] = [
				'post_id'          => $post->ID,
				'title'            => $post->post_title,
				'post_type'        => $post->post_type,
				'permalink'        => get_permalink( $post->ID ),
				'date'             => $post->post_date,
				'meta_description' => $meta_desc ?: '',
				'focus_keyword'    => $focus_kw ?: '',
				'seo_score'        => $seo_score,
				'issues'           => $issues,
				'issue_count'      => count( $issues ),
			];
		}

		return [
			'success' => true,
			'data'    => [
				'posts'            => $posts,
				'total_with_issues' => count( $posts ),
				'threshold_used'   => $threshold,
				'issue_type_filter' => $issue_type,
			],
		];
	}
}

<?php
namespace Wally\Tools;

/**
 * Email marketing plugin management tools.
 *
 * Tools: list_mailchimp_lists, get_mailchimp_subscribers, list_optinmonster_campaigns.
 * Each tool has per-tool conditional registration.
 * APIs: MC4WP mc4wp_get_api(), OptinMonster OMAPI class.
 */

/**
 * List Mailchimp audiences via MC4WP.
 */
class ListMailchimpLists extends ToolInterface {

	public static function can_register(): bool {
		return defined( 'MC4WP_VERSION' );
	}

	public function get_name(): string {
		return 'list_mailchimp_lists';
	}

	public function get_description(): string {
		return 'List Mailchimp audiences (lists) connected via MC4WP plugin. Returns audience ID, name, and subscriber count from cached data.';
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
		// Check cached lists first (fast, no API call).
		$cached_lists = get_transient( 'mc4wp_mailchimp_lists' );

		if ( ! $cached_lists || ! is_array( $cached_lists ) ) {
			// Try to fetch via MC4WP API.
			if ( function_exists( 'mc4wp_get_api' ) ) {
				$api = mc4wp_get_api();
				if ( is_object( $api ) && method_exists( $api, 'get_lists' ) ) {
					$cached_lists = $api->get_lists();
				}
			}
		}

		if ( empty( $cached_lists ) || ! is_array( $cached_lists ) ) {
			// Get the configured API key to confirm connection.
			$options = get_option( 'mc4wp', [] );
			$has_key = ! empty( $options['api_key'] );

			return [
				'lists'   => [],
				'message' => $has_key
					? 'Mailchimp is configured but list data is not cached yet. Visit MC4WP settings to refresh.'
					: 'MC4WP API key not configured. Add your Mailchimp API key in MC4WP > Settings.',
			];
		}

		$result = [];
		foreach ( $cached_lists as $list ) {
			if ( is_object( $list ) ) {
				$result[] = [
					'id'               => $list->id ?? '',
					'name'             => $list->name ?? '',
					'subscriber_count' => $list->stats->member_count ?? 0,
				];
			} elseif ( is_array( $list ) ) {
				$result[] = [
					'id'               => $list['id'] ?? '',
					'name'             => $list['name'] ?? '',
					'subscriber_count' => $list['stats']['member_count'] ?? 0,
				];
			}
		}

		// Get configured default list.
		$options      = get_option( 'mc4wp', [] );
		$default_list = $options['list_id'] ?? '';

		return [
			'lists'        => $result,
			'total'        => count( $result ),
			'default_list' => $default_list,
		];
	}
}

/**
 * Get subscriber count and recent activity for a Mailchimp list via MC4WP.
 */
class GetMailchimpSubscribers extends ToolInterface {

	public static function can_register(): bool {
		return defined( 'MC4WP_VERSION' );
	}

	public function get_name(): string {
		return 'get_mailchimp_subscribers';
	}

	public function get_description(): string {
		return 'Get subscriber statistics for a Mailchimp audience via MC4WP. Returns total member count and subscription stats from cached list data.';
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
			'properties' => [
				'list_id' => [
					'type'        => 'string',
					'description' => 'The Mailchimp audience/list ID. Use list_mailchimp_lists to find available list IDs.',
				],
			],
			'required'   => [ 'list_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$list_id = sanitize_text_field( $input['list_id'] );

		$cached_lists = get_transient( 'mc4wp_mailchimp_lists' );

		if ( ! $cached_lists || ! is_array( $cached_lists ) ) {
			return [ 'error' => 'Mailchimp list data not cached. Visit MC4WP settings or use list_mailchimp_lists first.' ];
		}

		$target_list = null;
		foreach ( $cached_lists as $list ) {
			$current_id = is_object( $list ) ? ( $list->id ?? '' ) : ( $list['id'] ?? '' );
			if ( $current_id === $list_id ) {
				$target_list = $list;
				break;
			}
		}

		if ( ! $target_list ) {
			return [ 'error' => "Mailchimp list not found: {$list_id}" ];
		}

		if ( is_object( $target_list ) ) {
			$stats = $target_list->stats ?? new \stdClass();
			return [
				'list_id'          => $list_id,
				'name'             => $target_list->name ?? '',
				'member_count'     => $stats->member_count ?? 0,
				'unsubscribe_count' => $stats->unsubscribe_count ?? 0,
				'cleaned_count'    => $stats->cleaned_count ?? 0,
				'open_rate'        => $stats->open_rate ?? 0,
				'click_rate'       => $stats->click_rate ?? 0,
			];
		}

		$stats = $target_list['stats'] ?? [];
		return [
			'list_id'          => $list_id,
			'name'             => $target_list['name'] ?? '',
			'member_count'     => $stats['member_count'] ?? 0,
			'unsubscribe_count' => $stats['unsubscribe_count'] ?? 0,
			'cleaned_count'    => $stats['cleaned_count'] ?? 0,
			'open_rate'        => $stats['open_rate'] ?? 0,
			'click_rate'       => $stats['click_rate'] ?? 0,
		];
	}
}

/**
 * List OptinMonster campaigns (locally cached mappings).
 */
class ListOptinMonsterCampaigns extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'OMAPI' ) || defined( 'OMAPI_VERSION' );
	}

	public function get_name(): string {
		return 'list_optinmonster_campaigns';
	}

	public function get_description(): string {
		return 'List OptinMonster campaigns from the local WordPress configuration. Returns campaign slug, type, and status. Full campaign details are managed in the OptinMonster cloud dashboard.';
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
		// OptinMonster campaigns are stored remotely. Local option stores slug-to-ID mappings.
		$campaign_ids = get_option( 'optin_monster_ids', [] );
		$api_settings = get_option( 'optin_monster_api', [] );

		$account_id = '';
		if ( is_array( $api_settings ) ) {
			$account_id = $api_settings['accountId'] ?? '';
		}

		$campaigns = [];
		if ( is_array( $campaign_ids ) ) {
			foreach ( $campaign_ids as $slug => $campaign_data ) {
				if ( is_array( $campaign_data ) ) {
					$campaigns[] = [
						'slug'   => $slug,
						'id'     => $campaign_data['id'] ?? '',
						'type'   => $campaign_data['type'] ?? '',
						'status' => $campaign_data['status'] ?? '',
					];
				} else {
					$campaigns[] = [
						'slug' => $slug,
						'id'   => $campaign_data,
					];
				}
			}
		}

		// Also check for campaigns stored as posts (some versions use CPTs).
		$campaign_posts = get_posts(
			[
				'post_type'      => 'optin_monster_campaign',
				'post_status'    => [ 'publish', 'draft' ],
				'posts_per_page' => 50,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		foreach ( $campaign_posts as $post ) {
			$slug = $post->post_name;
			$already_added = false;
			foreach ( $campaigns as $c ) {
				if ( $c['slug'] === $slug ) {
					$already_added = true;
					break;
				}
			}
			if ( ! $already_added ) {
				$campaigns[] = [
					'slug'   => $slug,
					'title'  => $post->post_title,
					'status' => $post->post_status,
				];
			}
		}

		return [
			'account_id' => $account_id,
			'campaigns'  => $campaigns,
			'total'      => count( $campaigns ),
			'note'       => 'Full campaign configuration and analytics are available in the OptinMonster cloud dashboard.',
		];
	}
}

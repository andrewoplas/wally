<?php
namespace Wally;

/**
 * Summarizes tool results before they are persisted to wp_wally_messages.
 *
 * Tool results can be large (plugin lists, post content, Elementor data).
 * This class converts them into compact, always-complete summaries so that
 * stored conversation history is never a mid-sentence truncation.
 *
 * The full raw result is still sent to the LLM during the active tool-use
 * loop — summarization only affects what gets written to the database.
 */
class ResultSummarizer {

	/**
	 * Convert a tool result array into a human-readable one-line summary.
	 *
	 * @param string $tool_name Name of the tool that produced the result.
	 * @param array  $result    Result array returned by the tool executor.
	 * @return string Summary string, always complete and under 400 characters.
	 */
	public static function summarize( string $tool_name, array $result ): string {
		switch ( $tool_name ) {

			case 'list_plugins':
				$plugins      = $result['plugins'] ?? [];
				$total        = count( $plugins );
				$active       = array_filter( $plugins, fn( $p ) => ! empty( $p['active'] ) );
				$active_count = count( $active );
				$preview      = implode( ', ', array_slice( array_column( array_values( $active ), 'name' ), 0, 5 ) );
				return "{$total} plugins found, {$active_count} active. Active (first 5): {$preview}.";

			case 'list_posts':
			case 'search_posts':
			case 'search_content':
				$posts  = $result['posts'] ?? $result['results'] ?? [];
				$count  = count( $posts );
				$titles = implode( ', ', array_slice( array_column( $posts, 'title' ), 0, 5 ) );
				return "{$count} posts returned. First 5: {$titles}.";

			case 'get_post':
			case 'get_post_content':
				$words = str_word_count( wp_strip_all_tags( $result['content'] ?? '' ) );
				$title = $result['title'] ?? 'untitled';
				$id    = $result['id'] ?? $result['post_id'] ?? '?';
				return "Post #{$id} \"{$title}\" retrieved. {$words} words.";

			case 'create_post':
			case 'update_post':
				$id    = $result['id'] ?? $result['post_id'] ?? '?';
				$title = $result['title'] ?? 'untitled';
				return "Post #{$id} \"{$title}\" saved successfully.";

			case 'trash_post':
				$id    = $result['id'] ?? $result['post_id'] ?? '?';
				$title = $result['title'] ?? 'untitled';
				return "Post #{$id} \"{$title}\" moved to trash.";

			case 'search_replace':
			case 'replace_content':
				$count = $result['replacements'] ?? $result['count'] ?? 0;
				$scope = $result['scope'] ?? 'content';
				return "Search/replace completed: {$count} replacement(s) in {$scope}.";

			case 'install_plugin':
				$name    = $result['name'] ?? $result['slug'] ?? 'unknown';
				$version = $result['version'] ?? 'unknown';
				return "Plugin \"{$name}\" installed. Version: {$version}.";

			case 'activate_plugin':
				$name = $result['name'] ?? $result['slug'] ?? 'unknown';
				return "Plugin \"{$name}\" activated.";

			case 'deactivate_plugin':
				$name = $result['name'] ?? $result['slug'] ?? 'unknown';
				return "Plugin \"{$name}\" deactivated.";

			case 'delete_plugin':
				$name = $result['name'] ?? $result['slug'] ?? 'unknown';
				return "Plugin \"{$name}\" deleted.";

			case 'get_site_health':
			case 'get_option':
				// Small results — encode as-is but cap at 300 chars
				$json = wp_json_encode( $result );
				return strlen( $json ) > 300 ? substr( $json, 0, 297 ) . '...' : $json;

			case 'get_site_info':
				$name = $result['name'] ?? 'unknown';
				$url  = $result['url'] ?? '';
				return "Site: \"{$name}\" ({$url}).";

			case 'update_option':
				$option = $result['option_name'] ?? 'unknown';
				return "Site option \"{$option}\" updated successfully.";

			case 'list_categories':
			case 'list_tags':
				$items = $result['categories'] ?? $result['tags'] ?? $result['terms'] ?? [];
				$count = count( $items );
				$names = implode( ', ', array_slice( array_column( $items, 'name' ), 0, 5 ) );
				return "{$count} terms returned. First 5: {$names}.";

			default:
				$json = wp_json_encode( $result );
				return strlen( $json ) > 400 ? substr( $json, 0, 397 ) . '...' : $json;
		}
	}
}

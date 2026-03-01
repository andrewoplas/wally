<?php
namespace Wally;

/**
 * REST API controller for Wally.
 *
 * Handles chat messaging (forwarding to backend orchestration server),
 * conversation CRUD, site profile, action confirmation, and audit log.
 */
class RestController {
	private $namespace = 'wally/v1';

	/** Max messages to include in conversation history sent to LLM. */
	private const HISTORY_LIMIT = 20;

	/** HTTP timeout for backend API calls (seconds). */
	private const BACKEND_TIMEOUT = 60;

	public function register_routes() {
		register_rest_route( $this->namespace, '/chat', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_chat' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'message'         => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'conversation_id' => [
					'required' => false,
					'type'     => 'integer',
				],
				'stream' => [
					'required' => false,
					'type'     => 'boolean',
					'default'  => true,
				],
			],
		]);

		register_rest_route( $this->namespace, '/conversations', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'list_conversations' ],
			'permission_callback' => [ $this, 'check_permission' ],
		]);

		register_rest_route( $this->namespace, '/conversations/(?P<id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_conversation' ],
			'permission_callback' => [ $this, 'check_permission' ],
		]);

		register_rest_route( $this->namespace, '/conversations/(?P<id>\d+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'delete_conversation' ],
			'permission_callback' => [ $this, 'check_permission' ],
		]);

		register_rest_route( $this->namespace, '/conversations/(?P<id>\d+)/title', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'generate_conversation_title' ],
			'permission_callback' => [ $this, 'check_permission' ],
		]);

		register_rest_route( $this->namespace, '/site-profile', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_site_profile' ],
			'permission_callback' => [ $this, 'check_permission' ],
		]);

		register_rest_route( $this->namespace, '/site-profile/rescan', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rescan_site' ],
			'permission_callback' => [ $this, 'check_admin' ],
		]);

		register_rest_route( $this->namespace, '/confirm/(?P<action_id>\d+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'confirm_action' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'approved' => [
					'required' => true,
					'type'     => 'boolean',
				],
			],
		]);

		register_rest_route( $this->namespace, '/actions', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_actions' ],
			'permission_callback' => [ $this, 'check_admin' ],
			'args'                => [
				'user_id'   => [ 'type' => 'integer' ],
				'tool_name' => [ 'type' => 'string' ],
				'status'    => [ 'type' => 'string' ],
				'date_from' => [ 'type' => 'string' ],
				'date_to'   => [ 'type' => 'string' ],
				'per_page'  => [ 'type' => 'integer', 'default' => 50 ],
				'page'      => [ 'type' => 'integer', 'default' => 1 ],
			],
		]);

		register_rest_route( $this->namespace, '/settings', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_settings' ],
				'permission_callback' => [ $this, 'check_permission' ],
			],
			[
				'methods'             => 'PATCH',
				'callback'            => [ $this, 'update_settings' ],
				'permission_callback' => [ $this, 'check_admin' ],
				'args'                => [
					'confirm_destructive' => [ 'type' => 'boolean' ],
					'stream_responses'    => [ 'type' => 'boolean' ],
					'notification_sounds' => [ 'type' => 'boolean' ],
				],
			],
		]);
	}

	public function check_permission() {
		return is_user_logged_in();
	}

	public function check_admin() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Handle chat message — enforces rate limit, then routes to SSE streaming or JSON response.
	 */
	public function handle_chat( $request ) {
		$user_id = get_current_user_id();

		// Enforce per-user daily rate limit before any message processing.
		if ( RateLimiter::is_rate_limited( $user_id ) ) {
			$status = RateLimiter::get_status( $user_id );

			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
					'You have reached your daily message limit (%d/%d). Your limit resets at %s.',
					$status['used'],
					$status['limit'],
					$status['resets_at']
				),
				[ 'status' => 429 ]
			);
		}

		// Enforce site-wide monthly token budget.
		if ( RateLimiter::is_token_budget_exceeded() ) {
			$budget_status = RateLimiter::get_token_budget_status();

			return new \WP_Error(
				'token_budget_exceeded',
				sprintf(
					'Monthly token budget exhausted (%s/%s tokens used). Budget resets next month.',
					number_format( $budget_status['used'] ),
					number_format( $budget_status['budget'] )
				),
				[ 'status' => 429 ]
			);
		}

		$stream_enabled = (bool) get_option( 'wally_stream_responses', true );
		$stream         = $stream_enabled && (bool) $request->get_param( 'stream' );

		if ( $stream ) {
			return $this->handle_chat_stream( $request );
		}

		return $this->handle_chat_json( $request );
	}

	/**
	 * SSE streaming chat handler.
	 *
	 * Streams tokens to the browser in real-time as they arrive from the
	 * backend orchestration server. Handles tool execution mid-stream.
	 */
	private function handle_chat_stream( $request ) {
		global $wpdb;

		$message         = $request->get_param( 'message' );
		$conversation_id = $request->get_param( 'conversation_id' );
		$user_id         = get_current_user_id();

		$conv_table = $wpdb->prefix . 'wally_conversations';
		$msg_table  = $wpdb->prefix . 'wally_messages';

		// 1. Create or verify conversation.
		if ( $conversation_id ) {
			$conv = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id FROM {$conv_table} WHERE id = %d AND user_id = %d",
					$conversation_id, $user_id
				)
			);
			if ( ! $conv ) {
				$conversation_id = null;
			}
		}

		if ( ! $conversation_id ) {
			$wpdb->insert( $conv_table, [
				'user_id' => $user_id,
				'title'   => mb_substr( $message, 0, 100 ),
			]);
			$conversation_id = (int) $wpdb->insert_id;
		}

		// 2. Build conversation history BEFORE storing the current message to avoid duplicates.
		$history_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT role, content FROM {$msg_table}
				 WHERE conversation_id = %d
				 ORDER BY created_at DESC
				 LIMIT %d",
				$conversation_id, self::HISTORY_LIMIT
			)
		);

		$conversation_history = [];
		foreach ( array_reverse( $history_rows ) as $row ) {
			$conversation_history[] = [
				'role'    => $row->role,
				'content' => $row->content,
			];
		}

		// 3. Store user message (after fetching history so it isn't included twice).
		$wpdb->insert( $msg_table, [
			'conversation_id' => $conversation_id,
			'role'            => 'user',
			'content'         => $message,
		]);

		// Build a history that includes the current message, used for tool-result calls
		// so the LLM has full context of why it called each tool.
		$history_with_current = array_merge(
			$conversation_history,
			[ [ 'role' => 'user', 'content' => $message ] ]
		);

		// 4. Prepare backend connection info.
		$backend_url      = rtrim( get_option( 'wally_backend_url', 'http://localhost:3100/api/v1' ), '/' );
		$license_key_enc  = get_option( 'wally_license_key', '' );
		$license_key      = $license_key_enc ? Settings::decrypt( $license_key_enc ) : '';
		$model            = get_option( 'wally_model', 'claude-haiku-4-5' );
		$site_profile  = SiteScanner::get_profile();
		$custom_prompt = get_option( 'wally_custom_prompt', '' );

		// 5. Begin SSE output.
		$this->start_sse();

		// Send conversation_id immediately so the frontend can track it.
		$this->send_sse_event( [ 'type' => 'conversation_id', 'id' => $conversation_id ] );

		// 6. Stream from backend, forwarding tokens in real-time.
		$accumulated_text  = '';
		$tool_calls        = [];
		$total_token_count = 0;

		$chat_payload = [
			'model'                => $model,
			'message'              => $message,
			'conversation_history' => $conversation_history,
			'site_profile'         => $site_profile,
		];
		if ( $custom_prompt ) {
			$chat_payload['custom_system_prompt'] = $custom_prompt;
		}

		$result = $this->stream_backend_sse(
			$backend_url . '/chat',
			$chat_payload,
			$license_key
		);

		if ( is_wp_error( $result ) ) {
			$error_msg = 'Sorry, I could not connect to the AI service. ' . $result->get_error_message();
			$this->send_sse_event( [ 'type' => 'error', 'message' => $error_msg ] );
			$this->send_sse_event( [ 'type' => 'done' ] );
			die();
		}

		$accumulated_text   = $result['text'];
		$tool_calls         = $result['tool_calls'];
		$total_token_count += ( $result['token_usage']['input_tokens'] ?? 0 ) + ( $result['token_usage']['output_tokens'] ?? 0 );

		// 7. Tool execution loop (up to 5 iterations).
		$executor     = ToolExecutor::instance();
		$confirmation = null;
		$max_loops    = 5;

		for ( $loop = 0; $loop < $max_loops && ! empty( $tool_calls ); $loop++ ) {
			$this->send_sse_event([
				'type'  => 'tool_start',
				'tools' => array_map( fn( $tc ) => $tc['tool'] ?? '', $tool_calls ),
			]);

			$tool_results = [];

			foreach ( $tool_calls as $tc ) {
				$tool_name = $tc['tool'] ?? '';
				$input     = $tc['input'] ?? [];
				$call_id   = $tc['tool_call_id'] ?? '';

				$exec_result = $executor->execute( $tool_name, $input, $user_id, $conversation_id );

				// Handle confirmation flow.
				if ( isset( $exec_result['status'] ) && 'pending_confirmation' === $exec_result['status'] ) {
					$confirmation = $exec_result['result'];
					$this->send_sse_event([
						'type'         => 'confirmation',
						'action_id'    => $confirmation['action_id'] ?? null,
						'tool_name'    => $tool_name,
						'preview'      => $confirmation['preview'] ?? $input,
					]);
					$tool_results[] = [
						'tool_call_id' => $call_id,
						'tool_name'    => $tool_name,
						'result'       => 'Action requires user confirmation. Awaiting approval.',
						'is_error'     => false,
					];
					continue;
				}

				$tool_results[] = [
					'tool_call_id' => $call_id,
					'tool_name'    => $tool_name,
					'result'       => $exec_result['result'] ?? $exec_result,
					'is_error'     => ! ( $exec_result['success'] ?? true ),
				];
			}

			// Send tool results back to backend and stream its response.
			$result = $this->stream_backend_sse(
				$backend_url . '/tool-result',
				[
					'model'                => $model,
					'conversation_history' => $history_with_current,
					'site_profile'         => $site_profile,
					'tool_results'         => $tool_results,
					'pending_tool_calls'   => $tool_calls,
				],
				$license_key
			);

			if ( is_wp_error( $result ) ) {
				$accumulated_text .= "\n\nI executed the tools but couldn't get a follow-up response.";
				$this->send_sse_event( [ 'type' => 'token', 'content' => "\n\nI executed the tools but couldn't get a follow-up response." ] );
				break;
			}

			$accumulated_text   .= $result['text'];
			$tool_calls          = $result['tool_calls'];
			$total_token_count  += ( $result['token_usage']['input_tokens'] ?? 0 ) + ( $result['token_usage']['output_tokens'] ?? 0 );
		}

		// 8. Store assistant response with token count for budget tracking.
		if ( $accumulated_text ) {
			$wpdb->insert( $msg_table, [
				'conversation_id' => $conversation_id,
				'role'            => 'assistant',
				'content'         => $accumulated_text,
				'token_count'     => $total_token_count,
			]);
		}

		$wpdb->update( $conv_table, [ 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $conversation_id ] );

		// 9. Finalize stream.
		$this->send_sse_event( [ 'type' => 'done' ] );
		die();
	}

	/**
	 * Non-streaming JSON chat handler (fallback).
	 */
	private function handle_chat_json( $request ) {
		global $wpdb;

		$message         = $request->get_param( 'message' );
		$conversation_id = $request->get_param( 'conversation_id' );
		$user_id         = get_current_user_id();

		$conv_table = $wpdb->prefix . 'wally_conversations';
		$msg_table  = $wpdb->prefix . 'wally_messages';

		if ( $conversation_id ) {
			$conv = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id FROM {$conv_table} WHERE id = %d AND user_id = %d",
					$conversation_id, $user_id
				)
			);
			if ( ! $conv ) {
				$conversation_id = null;
			}
		}

		if ( ! $conversation_id ) {
			$wpdb->insert( $conv_table, [
				'user_id' => $user_id,
				'title'   => mb_substr( $message, 0, 100 ),
			]);
			$conversation_id = (int) $wpdb->insert_id;
		}

		// Build history BEFORE storing current message to avoid duplicates.
		$history_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT role, content FROM {$msg_table}
				 WHERE conversation_id = %d
				 ORDER BY created_at DESC
				 LIMIT %d",
				$conversation_id, self::HISTORY_LIMIT
			)
		);

		$conversation_history = [];
		foreach ( array_reverse( $history_rows ) as $row ) {
			$conversation_history[] = [
				'role'    => $row->role,
				'content' => $row->content,
			];
		}

		$wpdb->insert( $msg_table, [
			'conversation_id' => $conversation_id,
			'role'            => 'user',
			'content'         => $message,
		]);

		$backend_url      = rtrim( get_option( 'wally_backend_url', 'http://localhost:3100/api/v1' ), '/' );
		$license_key_enc  = get_option( 'wally_license_key', '' );
		$license_key      = $license_key_enc ? Settings::decrypt( $license_key_enc ) : '';
		$model            = get_option( 'wally_model', 'claude-haiku-4-5' );
		$site_profile     = SiteScanner::get_profile();
		$custom_prompt    = get_option( 'wally_custom_prompt', '' );

		$chat_payload = [
			'model'                => $model,
			'message'              => $message,
			'conversation_history' => $conversation_history,
			'site_profile'         => $site_profile,
		];
		if ( $custom_prompt ) {
			$chat_payload['custom_system_prompt'] = $custom_prompt;
		}

		$backend_response = $this->backend_request_buffered( $backend_url . '/chat', $chat_payload, $license_key );

		if ( is_wp_error( $backend_response ) ) {
			return rest_ensure_response([
				'reply'           => 'Sorry, I could not connect to the AI service. ' . $backend_response->get_error_message(),
				'conversation_id' => $conversation_id,
			]);
		}

		$events            = $this->parse_sse( $backend_response );
		$reply_text        = '';
		$tool_calls        = [];
		$confirmation      = null;
		$total_token_count = 0;

		foreach ( $events as $event ) {
			if ( 'token' === $event['type'] ) {
				$reply_text .= $event['content'] ?? '';
			} elseif ( 'tool_call' === $event['type'] ) {
				$tool_calls[] = $event;
			} elseif ( 'usage' === $event['type'] ) {
				$total_token_count += (int) ( $event['input_tokens'] ?? 0 ) + (int) ( $event['output_tokens'] ?? 0 );
			}
		}

		if ( ! empty( $tool_calls ) ) {
			$result = $this->process_tool_calls_json(
				$tool_calls, $user_id, $conversation_id, $backend_url, $license_key, $model,
				array_merge( $conversation_history, [ [ 'role' => 'user', 'content' => $message ] ] ),
				$site_profile, $reply_text
			);
			$reply_text         = $result['reply'];
			$confirmation       = $result['confirmation'];
			$total_token_count += $result['token_count'];
		}

		if ( $reply_text ) {
			$wpdb->insert( $msg_table, [
				'conversation_id' => $conversation_id,
				'role'            => 'assistant',
				'content'         => $reply_text,
				'token_count'     => $total_token_count,
			]);
		}

		$wpdb->update( $conv_table, [ 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $conversation_id ] );

		$response = [
			'reply'           => $reply_text ?: 'I processed your request but had no text response.',
			'conversation_id' => $conversation_id,
		];

		if ( $confirmation ) {
			$response['confirmation'] = $confirmation;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Tool execution loop for the JSON (non-streaming) path.
	 */
	private function process_tool_calls_json(
		array $tool_calls, int $user_id, int $conversation_id,
		string $backend_url, string $license_key, string $model,
		array $conversation_history, array $site_profile, string $accumulated_text
	): array {
		$executor          = ToolExecutor::instance();
		$confirmation      = null;
		$max_loops         = 5;
		$total_token_count = 0;

		for ( $loop = 0; $loop < $max_loops; $loop++ ) {
			$tool_results = [];

			foreach ( $tool_calls as $tc ) {
				$tool_name = $tc['tool'] ?? '';
				$input     = $tc['input'] ?? [];
				$call_id   = $tc['tool_call_id'] ?? '';

				$exec_result = $executor->execute( $tool_name, $input, $user_id, $conversation_id );

				if ( isset( $exec_result['status'] ) && 'pending_confirmation' === $exec_result['status'] ) {
					$confirmation = $exec_result['result'];
					$tool_results[] = [
						'tool_call_id' => $call_id,
						'tool_name'    => $tool_name,
						'result'       => 'Action requires user confirmation. Awaiting approval.',
						'is_error'     => false,
					];
					continue;
				}

				$tool_results[] = [
					'tool_call_id' => $call_id,
					'tool_name'    => $tool_name,
					'result'       => $exec_result['result'] ?? $exec_result,
					'is_error'     => ! ( $exec_result['success'] ?? true ),
				];
			}

			$response = $this->backend_request_buffered( $backend_url . '/tool-result', [
				'model'                => $model,
				'conversation_history' => $conversation_history,
				'site_profile'         => $site_profile,
				'tool_results'         => $tool_results,
				'pending_tool_calls'   => $tool_calls,
			], \$license_key );

			if ( is_wp_error( $response ) ) {
				$accumulated_text .= "\n\nI executed the tools but couldn't get a follow-up response.";
				break;
			}

			$events     = $this->parse_sse( $response );
			$tool_calls = [];

			foreach ( $events as $event ) {
				if ( 'token' === $event['type'] ) {
					$accumulated_text .= $event['content'] ?? '';
				} elseif ( 'tool_call' === $event['type'] ) {
					$tool_calls[] = $event;
				} elseif ( 'usage' === $event['type'] ) {
					$total_token_count += (int) ( $event['input_tokens'] ?? 0 ) + (int) ( $event['output_tokens'] ?? 0 );
				}
			}

			if ( empty( $tool_calls ) ) {
				break;
			}
		}

		return [
			'reply'        => $accumulated_text,
			'confirmation' => $confirmation,
			'token_count'  => $total_token_count,
		];
	}

	// ─── URL Validation ─────────────────────────────────────────────────

	/**
	 * Validate that a backend URL is safe to call (SSRF protection).
	 *
	 * Ensures the URL uses http/https scheme and the host does not resolve
	 * to a private/internal IP range (except localhost in WP_DEBUG mode).
	 *
	 * @param string $url URL to validate.
	 * @return true|\WP_Error True if safe, WP_Error otherwise.
	 */
	private function validate_backend_url( string $url ) {
		$parsed = wp_parse_url( $url );

		if ( ! $parsed || empty( $parsed['scheme'] ) || ! in_array( $parsed['scheme'], [ 'http', 'https' ], true ) ) {
			return new \WP_Error( 'invalid_url', 'Backend URL must use http or https.' );
		}

		$host = $parsed['host'] ?? '';

		// Allow localhost only in debug/development mode.
		$is_localhost = in_array( $host, [ 'localhost', '127.0.0.1', '::1' ], true );
		if ( $is_localhost ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				return true;
			}
			return new \WP_Error( 'ssrf_blocked', 'Localhost backend URLs are only allowed in debug mode.' );
		}

		// Resolve hostname and check for private IP ranges.
		$ip = gethostbyname( $host );
		if ( $ip === $host ) {
			return new \WP_Error( 'dns_failed', 'Could not resolve backend hostname.' );
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
			return new \WP_Error( 'ssrf_blocked', 'Backend URL must not resolve to a private or reserved IP range.' );
		}

		return true;
	}

	// ─── SSE Streaming Helpers ───────────────────────────────────────────

	/**
	 * Start SSE output: headers, disable buffering.
	 */
	private function start_sse() {
		// Prevent WordPress and PHP from buffering output.
		while ( ob_get_level() ) {
			ob_end_flush();
		}

		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' ); // nginx
		header( 'X-Content-Type-Options: nosniff' );
	}

	/**
	 * Send a single SSE event to the browser.
	 */
	private function send_sse_event( array $data ) {
		echo 'data: ' . wp_json_encode( $data ) . "\n\n";
		if ( ob_get_level() ) {
			ob_flush();
		}
		flush();
	}

	/**
	 * Stream SSE from backend, forwarding tokens to browser in real-time.
	 *
	 * Uses cURL with CURLOPT_WRITEFUNCTION to process chunks as they arrive.
	 * Token events are forwarded immediately; tool_call events are collected.
	 *
	 * @param string $url         Backend endpoint URL.
	 * @param array  $payload     Request body.
	 * @param string $license_key License key for auth.
	 * @return array|\WP_Error { text: string, tool_calls: array } or WP_Error.
	 */
	private function stream_backend_sse( string $url, array $payload, string $license_key ) {
		$url_check = $this->validate_backend_url( $url );
		if ( is_wp_error( $url_check ) ) {
			return $url_check;
		}

		$site_id          = md5( get_site_url() );
		$buffer           = '';
		$accumulated_text = '';
		$tool_calls       = [];
		$token_usage      = [ 'input_tokens' => 0, 'output_tokens' => 0 ];
		$curl_error       = null;

		// Reference $this for the closure.
		$controller = $this;

		$ch = curl_init( $url );

		if ( false === $ch ) {
			return new \WP_Error( 'curl_init_failed', 'Could not initialize cURL connection.' );
		}

		curl_setopt_array( $ch, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => wp_json_encode( $payload ),
			CURLOPT_HTTPHEADER     => [
				'Content-Type: application/json',
				'X-Site-ID: ' . $site_id,
				'X-License-Key: ' . $license_key,
			],
			CURLOPT_TIMEOUT        => self::BACKEND_TIMEOUT,
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_WRITEFUNCTION  => function ( $ch, $chunk ) use ( &$buffer, &$accumulated_text, &$tool_calls, &$token_usage, $controller ) {
				$buffer .= $chunk;

				// Process complete SSE lines (terminated by \n).
				while ( false !== ( $pos = strpos( $buffer, "\n" ) ) ) {
					$line   = substr( $buffer, 0, $pos );
					$buffer = substr( $buffer, $pos + 1 );
					$line   = trim( $line );

					if ( '' === $line || ! str_starts_with( $line, 'data: ' ) ) {
						continue;
					}

					$json  = substr( $line, 6 );
					$event = json_decode( $json, true );

					if ( ! is_array( $event ) || ! isset( $event['type'] ) ) {
						continue;
					}

					switch ( $event['type'] ) {
						case 'token':
							$accumulated_text .= $event['content'] ?? '';
							$controller->send_sse_event( $event );
							break;

						case 'tool_call':
							$tool_calls[] = $event;
							break;

						case 'error':
							$controller->send_sse_event( $event );
							break;

						case 'usage':
							$token_usage['input_tokens']  += (int) ( $event['input_tokens'] ?? 0 );
							$token_usage['output_tokens'] += (int) ( $event['output_tokens'] ?? 0 );
							break;

						// done — don't forward (we emit our own done).
					}
				}

				return strlen( $chunk );
			},
		]);

		$success = curl_exec( $ch );

		if ( false === $success ) {
			$curl_error = curl_error( $ch );
		}

		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $curl_error ) {
			return new \WP_Error( 'backend_curl_error', $curl_error );
		}

		if ( $http_code >= 400 ) {
			return new \WP_Error( 'backend_error', "Backend returned HTTP {$http_code}" );
		}

		return [
			'text'        => $accumulated_text,
			'tool_calls'  => $tool_calls,
			'token_usage' => $token_usage,
		];
	}

	// ─── Non-Streaming (Buffered) Backend Request ────────────────────────

	/**
	 * Buffered HTTP POST to backend (for non-streaming JSON path).
	 */
	private function backend_request_buffered( string $url, array $payload, string $license_key ) {
		$url_check = $this->validate_backend_url( $url );
		if ( is_wp_error( $url_check ) ) {
			return $url_check;
		}

		$site_url = get_site_url();

		$response = wp_remote_post( $url, [
			'timeout' => self::BACKEND_TIMEOUT,
			'headers' => [
				'Content-Type'  => 'application/json',
				'X-Site-ID'     => md5( $site_url ),
				'X-License-Key' => $license_key,
			],
			'body' => wp_json_encode( $payload ),
		]);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code >= 400 ) {
			$decoded   = json_decode( $body, true );
			$error_msg = $decoded['error'] ?? "Backend returned HTTP {$code}";
			return new \WP_Error( 'backend_error', $error_msg );
		}

		return $body;
	}

	/**
	 * Parse a buffered SSE response body into structured events.
	 */
	private function parse_sse( string $body ): array {
		$events = [];
		$lines  = explode( "\n", $body );

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( str_starts_with( $line, 'data: ' ) ) {
				$json = substr( $line, 6 );
				$data = json_decode( $json, true );
				if ( is_array( $data ) && isset( $data['type'] ) ) {
					$events[] = $data;
				}
			}
		}

		return $events;
	}

	/**
	 * Generate an AI summary title for a conversation and persist it.
	 *
	 * Called by the frontend after the first exchange completes on a new
	 * conversation. Sends the first user message + assistant response to the
	 * backend with a title-generation prompt, then saves the result.
	 */
	public function generate_conversation_title( $request ) {
		global $wpdb;
		$id      = (int) $request['id'];
		$user_id = get_current_user_id();

		$conv_table = $wpdb->prefix . 'wally_conversations';
		$msg_table  = $wpdb->prefix . 'wally_messages';

		// Verify the conversation belongs to this user.
		$conv = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$conv_table} WHERE id = %d AND user_id = %d",
				$id, $user_id
			)
		);

		if ( ! $conv ) {
			return new \WP_Error( 'not_found', 'Conversation not found', [ 'status' => 404 ] );
		}

		// Fetch the first user message + first assistant response (enough context for a title).
		$messages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT role, content FROM {$msg_table}
				 WHERE conversation_id = %d
				 ORDER BY created_at ASC
				 LIMIT 2",
				$id
			)
		);

		if ( empty( $messages ) ) {
			return rest_ensure_response( [ 'title' => '' ] );
		}

		// Cap each message to avoid sending huge token counts for a simple title.
		$history = array_map( fn( $m ) => [
			'role'    => $m->role,
			'content' => mb_substr( $m->content, 0, 600 ),
		], $messages );

		$backend_url     = rtrim( get_option( 'wally_backend_url', 'http://localhost:3100/api/v1' ), '/' );
		$license_key_enc = get_option( 'wally_license_key', '' );
		$license_key     = $license_key_enc ? Settings::decrypt( $license_key_enc ) : '';
		$model           = get_option( 'wally_model', 'claude-haiku-4-5' );

		$response = $this->backend_request_buffered(
			$backend_url . '/chat',
			[
				'model'                => $model,
				'message'              => 'Write a concise title (4-6 words) for this conversation that captures what the user was trying to accomplish. Reply with ONLY the title — no quotes, no trailing punctuation, no explanation.',
				'conversation_history' => $history,
				'site_profile'         => [],
				'custom_system_prompt' => 'You generate short, accurate conversation titles. Respond with only the title text.',
			],
			$license_key
		);

		// On backend failure, keep the existing truncated-message title.
		if ( is_wp_error( $response ) ) {
			return rest_ensure_response( [ 'title' => '' ] );
		}

		$events = $this->parse_sse( $response );
		$title  = '';
		foreach ( $events as $event ) {
			if ( 'token' === $event['type'] ) {
				$title .= $event['content'] ?? '';
			}
		}

		$title = trim( strip_tags( $title ) );
		$title = mb_substr( $title, 0, 100 );

		if ( $title ) {
			$wpdb->update( $conv_table, [ 'title' => $title ], [ 'id' => $id ] );
		}

		return rest_ensure_response( [ 'title' => $title ] );
	}

	public function list_conversations( $request ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'wally_conversations';
		$user_id = get_current_user_id();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC LIMIT 50",
				$user_id
			)
		);

		return rest_ensure_response( $rows );
	}

	public function get_conversation( $request ) {
		global $wpdb;
		$conv_table = $wpdb->prefix . 'wally_conversations';
		$msg_table  = $wpdb->prefix . 'wally_messages';
		$id         = (int) $request['id'];
		$user_id    = get_current_user_id();

		$conv = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$conv_table} WHERE id = %d AND user_id = %d",
				$id, $user_id
			)
		);

		if ( ! $conv ) {
			return new \WP_Error( 'not_found', 'Conversation not found', [ 'status' => 404 ] );
		}

		$messages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$msg_table} WHERE conversation_id = %d ORDER BY created_at ASC",
				$id
			)
		);

		$conv->messages = $messages;
		return rest_ensure_response( $conv );
	}

	public function delete_conversation( $request ) {
		global $wpdb;
		$conv_table = $wpdb->prefix . 'wally_conversations';
		$msg_table  = $wpdb->prefix . 'wally_messages';
		$id         = (int) $request['id'];
		$user_id    = get_current_user_id();

		$deleted = $wpdb->delete( $conv_table, [ 'id' => $id, 'user_id' => $user_id ] );

		if ( $deleted ) {
			$wpdb->delete( $msg_table, [ 'conversation_id' => $id ] );
		}

		return rest_ensure_response( [ 'deleted' => (bool) $deleted ] );
	}

	public function get_site_profile() {
		return rest_ensure_response( SiteScanner::get_profile() );
	}

	public function rescan_site() {
		return rest_ensure_response( SiteScanner::scan() );
	}

	/**
	 * Handle action confirmation or rejection.
	 */
	public function confirm_action( $request ) {
		$action_id = (int) $request['action_id'];
		$approved  = (bool) $request->get_param( 'approved' );
		$user_id   = get_current_user_id();
		$executor  = ToolExecutor::instance();

		if ( $approved ) {
			$result = $executor->confirm_action( $action_id, $user_id );
		} else {
			$result = $executor->reject_action( $action_id, $user_id );
		}

		$status_code = $result['success'] ? 200 : 400;
		if ( $result['status'] === 'denied' ) {
			$status_code = 403;
		}

		return new \WP_REST_Response( $result, $status_code );
	}

	/**
	 * Get behavior settings (available to all authenticated users).
	 */
	public function get_settings() {
		return rest_ensure_response( [
			'confirm_destructive' => (bool) get_option( 'wally_confirm_destructive', true ),
			'stream_responses'    => (bool) get_option( 'wally_stream_responses', true ),
			'notification_sounds' => (bool) get_option( 'wally_notification_sounds', false ),
		] );
	}

	/**
	 * Update behavior settings (admin only).
	 */
	public function update_settings( $request ) {
		$fields = [
			'confirm_destructive' => 'wally_confirm_destructive',
			'stream_responses'    => 'wally_stream_responses',
			'notification_sounds' => 'wally_notification_sounds',
		];

		foreach ( $fields as $param => $option ) {
			$value = $request->get_param( $param );
			if ( $value !== null ) {
				update_option( $option, (bool) $value );
			}
		}

		return $this->get_settings();
	}

	/**
	 * Get audit log entries (admin only).
	 */
	public function get_actions( $request ) {
		$filters = [
			'user_id'   => $request->get_param( 'user_id' ),
			'tool_name' => $request->get_param( 'tool_name' ),
			'status'    => $request->get_param( 'status' ),
			'date_from' => $request->get_param( 'date_from' ),
			'date_to'   => $request->get_param( 'date_to' ),
			'per_page'  => $request->get_param( 'per_page' ),
			'page'      => $request->get_param( 'page' ),
		];

		$filters = array_filter( $filters, fn( $v ) => $v !== null );

		return rest_ensure_response( AuditLog::get_actions( $filters ) );
	}
}

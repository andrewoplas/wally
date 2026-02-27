<?php
namespace Wally;

use Wally\Tools\ToolInterface;

/**
 * Tool registry, input validation, execution pipeline, and audit logging.
 *
 * Flow: register tools → receive tool call from backend → validate input
 * against JSON schema → check WP capability → check role-based category
 * permission → handle confirmation if required → execute → log to audit table.
 *
 * The LLM never executes anything directly. It returns tool call specs.
 * This class validates and executes them locally against WordPress.
 */
class ToolExecutor {

	/** @var ToolInterface[] Registered tools keyed by name. */
	private array $tools = [];

	/** @var self|null Singleton instance. */
	private static ?self $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register a tool with the executor.
	 *
	 * @param ToolInterface $tool Tool instance.
	 */
	public function register_tool( ToolInterface $tool ): void {
		$this->tools[ $tool->get_name() ] = $tool;
	}

	/**
	 * Get a registered tool by name.
	 *
	 * @param string $name Tool name.
	 * @return ToolInterface|null
	 */
	public function get_tool( string $name ): ?ToolInterface {
		return $this->tools[ $name ] ?? null;
	}

	/**
	 * Get all registered tools.
	 *
	 * @return ToolInterface[]
	 */
	public function get_all_tools(): array {
		return $this->tools;
	}

	/**
	 * Get tool names grouped by category.
	 *
	 * @return array<string, string[]>
	 */
	public function get_tools_by_category(): array {
		$grouped = [];
		foreach ( $this->tools as $tool ) {
			$grouped[ $tool->get_category() ][] = $tool->get_name();
		}
		return $grouped;
	}

	/**
	 * Execute a tool by name with full validation pipeline.
	 *
	 * Pipeline: find tool → validate input → check capability → check
	 * category permission → handle confirmation → execute → audit log.
	 *
	 * @param string $tool_name       Tool identifier.
	 * @param array  $input           Raw input from LLM tool call.
	 * @param int    $user_id         WordPress user ID executing the action.
	 * @param int    $conversation_id Conversation context for audit.
	 * @param int    $message_id      Message context for audit (optional).
	 * @return array {
	 *     @type bool   $success Whether execution succeeded.
	 *     @type string $status  'success', 'failed', 'pending_confirmation', 'denied'.
	 *     @type mixed  $result  Tool output data or error details.
	 *     @type int    $action_id Audit log row ID.
	 * }
	 */
	public function execute(
		string $tool_name,
		array $input,
		int $user_id,
		int $conversation_id = 0,
		int $message_id = 0
	): array {
		// 1. Find tool in registry.
		$tool = $this->get_tool( $tool_name );
		if ( ! $tool ) {
			return $this->fail( "Unknown tool: {$tool_name}", $tool_name, $input, $user_id, $conversation_id, $message_id );
		}

		// 2. Validate input against JSON schema.
		$validation = $this->validate_input( $input, $tool->get_parameters_schema() );
		if ( ! $validation['valid'] ) {
			return $this->fail(
				'Input validation failed: ' . implode( '; ', $validation['errors'] ),
				$tool_name, $input, $user_id, $conversation_id, $message_id
			);
		}

		// 3. Check WordPress capability.
		if ( ! user_can( $user_id, $tool->get_required_capability() ) ) {
			return $this->deny(
				"Insufficient permissions. Requires: {$tool->get_required_capability()}",
				$tool_name, $input, $user_id, $conversation_id, $message_id
			);
		}

		// 4. Check role-based action permission.
		if ( ! Permissions::can_use_action( $tool->get_action(), $user_id ) ) {
			return $this->deny(
				"Action '{$tool->get_action()}' is not permitted for your role.",
				$tool_name, $input, $user_id, $conversation_id, $message_id
			);
		}

		// 5. If tool requires confirmation (and the setting is enabled), create a pending action.
		$confirm_enabled = (bool) get_option( 'wally_confirm_destructive', true );
		if ( $confirm_enabled && $tool->requires_confirmation() ) {
			$action_id = AuditLog::log_action([
				'conversation_id' => $conversation_id,
				'message_id'      => $message_id,
				'user_id'         => $user_id,
				'tool_name'       => $tool_name,
				'tool_input'      => $input,
				'tool_output'     => [ 'message' => 'Awaiting user confirmation.' ],
				'status'          => 'pending',
			]);

			return [
				'success'   => true,
				'status'    => 'pending_confirmation',
				'result'    => [
					'message'   => 'This action requires confirmation before execution.',
					'action_id' => $action_id,
					'tool_name' => $tool_name,
					'preview'   => $input,
				],
				'action_id' => $action_id,
			];
		}

		// 6. Execute the tool.
		return $this->run_tool( $tool, $input, $user_id, $conversation_id, $message_id );
	}

	/**
	 * Confirm and execute a pending action.
	 *
	 * @param int $action_id Audit log row ID of the pending action.
	 * @param int $user_id   User confirming (must be the same user who initiated).
	 * @return array Execution result.
	 */
	public function confirm_action( int $action_id, int $user_id ): array {
		$action = AuditLog::get_action( $action_id );

		if ( ! $action ) {
			return [
				'success' => false,
				'status'  => 'failed',
				'result'  => [ 'error' => 'Action not found.' ],
			];
		}

		if ( (int) $action->user_id !== $user_id ) {
			return [
				'success' => false,
				'status'  => 'denied',
				'result'  => [ 'error' => 'You can only confirm your own actions.' ],
			];
		}

		if ( $action->status !== 'pending' ) {
			return [
				'success' => false,
				'status'  => 'failed',
				'result'  => [ 'error' => "Action is already {$action->status}." ],
			];
		}

		$tool = $this->get_tool( $action->tool_name );
		if ( ! $tool ) {
			AuditLog::update_action( $action_id, [
				'status'      => 'failed',
				'tool_output' => [ 'error' => 'Tool no longer registered.' ],
			]);
			return [
				'success' => false,
				'status'  => 'failed',
				'result'  => [ 'error' => 'Tool no longer registered.' ],
			];
		}

		$input = json_decode( $action->tool_input, true ) ?: [];

		return $this->run_tool(
			$tool,
			$input,
			$user_id,
			(int) $action->conversation_id,
			(int) ( $action->message_id ?? 0 ),
			$action_id
		);
	}

	/**
	 * Reject a pending action.
	 *
	 * @param int $action_id Audit log row ID.
	 * @param int $user_id   User rejecting.
	 * @return array Result.
	 */
	public function reject_action( int $action_id, int $user_id ): array {
		$action = AuditLog::get_action( $action_id );

		if ( ! $action ) {
			return [
				'success' => false,
				'status'  => 'failed',
				'result'  => [ 'error' => 'Action not found.' ],
			];
		}

		if ( (int) $action->user_id !== $user_id ) {
			return [
				'success' => false,
				'status'  => 'denied',
				'result'  => [ 'error' => 'You can only reject your own actions.' ],
			];
		}

		if ( $action->status !== 'pending' ) {
			return [
				'success' => false,
				'status'  => 'failed',
				'result'  => [ 'error' => "Action is already {$action->status}." ],
			];
		}

		AuditLog::update_action( $action_id, [
			'status'      => 'cancelled',
			'tool_output' => [ 'message' => 'Action rejected by user.' ],
		]);

		return [
			'success'   => true,
			'status'    => 'cancelled',
			'result'    => [ 'message' => 'Action cancelled.' ],
			'action_id' => $action_id,
		];
	}

	/**
	 * Execute a tool and log the result.
	 *
	 * @param ToolInterface $tool            Tool instance.
	 * @param array         $input           Validated input.
	 * @param int           $user_id         Executing user.
	 * @param int           $conversation_id Conversation context.
	 * @param int           $message_id      Message context.
	 * @param int|null      $action_id       Existing action ID to update (for confirmed actions).
	 * @return array Execution result.
	 */
	private function run_tool(
		ToolInterface $tool,
		array $input,
		int $user_id,
		int $conversation_id,
		int $message_id,
		?int $action_id = null
	): array {
		try {
			$result = $tool->execute( $input );

			$log_data = [
				'status'      => 'success',
				'tool_output' => $result,
			];

			if ( $action_id ) {
				// Update existing pending action.
				AuditLog::update_action( $action_id, $log_data );
			} else {
				$action_id = AuditLog::log_action([
					'conversation_id' => $conversation_id,
					'message_id'      => $message_id,
					'user_id'         => $user_id,
					'tool_name'       => $tool->get_name(),
					'tool_input'      => $input,
					'tool_output'     => $result,
					'status'          => 'success',
				]);
			}

			return [
				'success'   => true,
				'status'    => 'success',
				'result'    => $result,
				'action_id' => $action_id,
			];
		} catch ( \Throwable $e ) {
			$error_data = [
				'error'   => $e->getMessage(),
				'code'    => $e->getCode(),
			];

			$log_data = [
				'status'      => 'failed',
				'tool_output' => $error_data,
			];

			if ( $action_id ) {
				AuditLog::update_action( $action_id, $log_data );
			} else {
				$action_id = AuditLog::log_action([
					'conversation_id' => $conversation_id,
					'message_id'      => $message_id,
					'user_id'         => $user_id,
					'tool_name'       => $tool->get_name(),
					'tool_input'      => $input,
					'tool_output'     => $error_data,
					'status'          => 'failed',
				]);
			}

			return [
				'success'   => false,
				'status'    => 'failed',
				'result'    => $error_data,
				'action_id' => $action_id,
			];
		}
	}

	/**
	 * Validate input against a JSON Schema-like definition.
	 *
	 * Validates: required properties, type checking (string, integer, number,
	 * boolean, array, object), enum constraints. Does not implement full
	 * JSON Schema (no $ref, allOf, etc.) — sufficient for tool parameter validation.
	 *
	 * @param array $input  Input data to validate.
	 * @param array $schema JSON Schema definition.
	 * @return array { 'valid' => bool, 'errors' => string[] }
	 */
	public function validate_input( array $input, array $schema ): array {
		$errors = [];

		// Check required properties.
		if ( ! empty( $schema['required'] ) ) {
			foreach ( $schema['required'] as $field ) {
				if ( ! array_key_exists( $field, $input ) ) {
					$errors[] = "Missing required field: {$field}";
				}
			}
		}

		// Validate each provided property against its schema.
		$properties = $schema['properties'] ?? [];
		foreach ( $input as $key => $value ) {
			if ( ! isset( $properties[ $key ] ) ) {
				// Extra properties are silently ignored (LLMs may add them).
				continue;
			}

			$prop_schema = $properties[ $key ];

			// Type check.
			if ( isset( $prop_schema['type'] ) ) {
				$type_error = $this->check_type( $value, $prop_schema['type'], $key );
				if ( $type_error ) {
					$errors[] = $type_error;
					continue;
				}
			}

			// Enum check.
			if ( isset( $prop_schema['enum'] ) && ! in_array( $value, $prop_schema['enum'], true ) ) {
				$allowed = implode( ', ', $prop_schema['enum'] );
				$errors[] = "Field '{$key}' must be one of: {$allowed}";
			}
		}

		return [
			'valid'  => empty( $errors ),
			'errors' => $errors,
		];
	}

	/**
	 * Check if a value matches the expected JSON Schema type.
	 *
	 * @param mixed  $value    Value to check.
	 * @param string $expected Expected JSON Schema type.
	 * @param string $field    Field name for error messages.
	 * @return string|null Error message or null if valid.
	 */
	private function check_type( $value, string $expected, string $field ): ?string {
		$valid = match ( $expected ) {
			'string'  => is_string( $value ),
			'integer' => is_int( $value ),
			'number'  => is_int( $value ) || is_float( $value ),
			'boolean' => is_bool( $value ),
			'array'   => is_array( $value ) && array_is_list( $value ),
			'object'  => is_array( $value ) && ! array_is_list( $value ),
			default   => true,
		};

		if ( ! $valid ) {
			return "Field '{$field}' must be of type {$expected}";
		}

		return null;
	}

	/**
	 * Log a failed execution and return error response.
	 */
	private function fail(
		string $message,
		string $tool_name,
		array $input,
		int $user_id,
		int $conversation_id,
		int $message_id
	): array {
		$action_id = AuditLog::log_action([
			'conversation_id' => $conversation_id,
			'message_id'      => $message_id,
			'user_id'         => $user_id,
			'tool_name'       => $tool_name,
			'tool_input'      => $input,
			'tool_output'     => [ 'error' => $message ],
			'status'          => 'failed',
		]);

		return [
			'success'   => false,
			'status'    => 'failed',
			'result'    => [ 'error' => $message ],
			'action_id' => $action_id,
		];
	}

	/**
	 * Log a denied execution and return error response.
	 */
	private function deny(
		string $message,
		string $tool_name,
		array $input,
		int $user_id,
		int $conversation_id,
		int $message_id
	): array {
		$action_id = AuditLog::log_action([
			'conversation_id' => $conversation_id,
			'message_id'      => $message_id,
			'user_id'         => $user_id,
			'tool_name'       => $tool_name,
			'tool_input'      => $input,
			'tool_output'     => [ 'error' => $message ],
			'status'          => 'failed',
		]);

		return [
			'success'   => false,
			'status'    => 'denied',
			'result'    => [ 'error' => $message ],
			'action_id' => $action_id,
		];
	}
}

<?php
namespace Wally\Tools;

/**
 * Abstract base class for all Wally tools.
 *
 * Each tool must define its name, category, JSON schema for parameters,
 * required WordPress capability, and execution logic. The ToolExecutor
 * validates inputs and checks permissions before calling execute().
 */
abstract class ToolInterface {

	/**
	 * Unique tool name matching the backend tool definition.
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Human-readable description of what this tool does.
	 *
	 * @return string
	 */
	abstract public function get_description(): string;

	/**
	 * Tool category for permission grouping.
	 * One of: content, site, plugins, search, elementor.
	 *
	 * @return string
	 */
	abstract public function get_category(): string;

	/**
	 * Action type for permission enforcement.
	 * One of: read, create, update, delete, plugins, site.
	 *
	 * @return string
	 */
	abstract public function get_action(): string;

	/**
	 * JSON Schema for input parameter validation.
	 * Must match the schema defined in backend tool-definitions.js.
	 *
	 * @return array
	 */
	abstract public function get_parameters_schema(): array;

	/**
	 * WordPress capability required to execute this tool.
	 * Checked via current_user_can() before execution.
	 *
	 * @return string
	 */
	abstract public function get_required_capability(): string;

	/**
	 * Whether this tool requires user confirmation before execution.
	 * Destructive or high-impact actions should return true.
	 *
	 * @return bool
	 */
	public function requires_confirmation(): bool {
		return false;
	}

	/**
	 * Execute the tool with validated input.
	 *
	 * Called only after input validation and permission checks pass.
	 * Must return an associative array with the result data.
	 *
	 * @param array $input Validated input parameters.
	 * @return array Result data.
	 */
	abstract public function execute( array $input ): array;
}

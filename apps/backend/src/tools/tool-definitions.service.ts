import { Injectable } from '@nestjs/common';

// ─── JSON Schema Types ────────────────────────────────────────────────────────

export type JsonSchemaType = 'string' | 'integer' | 'number' | 'boolean' | 'object' | 'array';

export interface JsonSchemaProperty {
  type?: JsonSchemaType;
  description?: string;
  enum?: string[];
  default?: string | number | boolean;
  items?: { type: JsonSchemaType };
}

export interface JsonSchema {
  type: 'object';
  properties: Record<string, JsonSchemaProperty>;
  required?: string[];
}

// ─── Internal Tool Definition ─────────────────────────────────────────────────

export interface ToolDefinition {
  name: string;
  description: string;
  category: 'content' | 'site' | 'plugins' | 'search' | 'elementor';
  requires_confirmation?: boolean;
  parameters: JsonSchema;
}

// ─── Provider-Specific Formats ────────────────────────────────────────────────

export interface AnthropicTool {
  name: string;
  description: string;
  input_schema: JsonSchema;
}

export interface OpenAiTool {
  type: 'function';
  function: {
    name: string;
    description: string;
    parameters: JsonSchema;
  };
}

export type LlmProvider = 'anthropic' | 'openai';

// ─── Tool Definitions ─────────────────────────────────────────────────────────

const TOOL_DEFINITIONS: ToolDefinition[] = [
  // ── Content Tools ────────────────────────────────────────────────────────────
  {
    name: 'list_posts',
    description: 'List WordPress posts or pages with optional filters',
    category: 'content',
    parameters: {
      type: 'object',
      properties: {
        post_type: { type: 'string', enum: ['post', 'page'], default: 'post', description: 'Post type to list' },
        status: { type: 'string', enum: ['publish', 'draft', 'pending', 'private', 'trash', 'any'], default: 'any' },
        search: { type: 'string', description: 'Search term to filter results' },
        per_page: { type: 'integer', default: 10, description: 'Results per page (max 100)' },
        page: { type: 'integer', default: 1 },
        orderby: { type: 'string', enum: ['date', 'title', 'modified'], default: 'date' },
        order: { type: 'string', enum: ['asc', 'desc'], default: 'desc' },
      },
    },
  },
  {
    name: 'get_post',
    description: 'Get full details of a specific post or page by ID',
    category: 'content',
    parameters: {
      type: 'object',
      properties: {
        post_id: { type: 'integer', description: 'The post ID' },
      },
      required: ['post_id'],
    },
  },
  {
    name: 'create_post',
    description: 'Create a new WordPress post or page',
    category: 'content',
    parameters: {
      type: 'object',
      properties: {
        title: { type: 'string', description: 'Post title' },
        content: { type: 'string', description: 'Post content (HTML)' },
        status: { type: 'string', enum: ['draft', 'publish', 'pending'], default: 'draft' },
        post_type: { type: 'string', enum: ['post', 'page'], default: 'post' },
        categories: { type: 'array', items: { type: 'integer' }, description: 'Category IDs' },
        tags: { type: 'array', items: { type: 'integer' }, description: 'Tag IDs' },
      },
      required: ['title'],
    },
  },
  {
    name: 'update_post',
    description: 'Update an existing post or page',
    category: 'content',
    parameters: {
      type: 'object',
      properties: {
        post_id: { type: 'integer', description: 'The post ID to update' },
        title: { type: 'string', description: 'New title' },
        content: { type: 'string', description: 'New content (HTML)' },
        status: { type: 'string', enum: ['draft', 'publish', 'pending', 'private'] },
        excerpt: { type: 'string', description: 'Post excerpt' },
      },
      required: ['post_id'],
    },
  },
  {
    name: 'delete_post',
    description: 'Move a post or page to the trash',
    category: 'content',
    requires_confirmation: true,
    parameters: {
      type: 'object',
      properties: {
        post_id: { type: 'integer', description: 'The post ID to trash' },
      },
      required: ['post_id'],
    },
  },

  // ── Taxonomy Tools ───────────────────────────────────────────────────────────
  {
    name: 'list_categories',
    description: 'List all categories',
    category: 'content',
    parameters: { type: 'object', properties: {} },
  },
  {
    name: 'list_tags',
    description: 'List all tags',
    category: 'content',
    parameters: { type: 'object', properties: {} },
  },
  {
    name: 'create_category',
    description: 'Create a new category',
    category: 'content',
    parameters: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Category name' },
        parent: { type: 'integer', description: 'Parent category ID (0 for top-level)' },
        description: { type: 'string' },
      },
      required: ['name'],
    },
  },
  {
    name: 'create_tag',
    description: 'Create a new tag',
    category: 'content',
    parameters: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Tag name' },
        description: { type: 'string' },
      },
      required: ['name'],
    },
  },

  // ── Site Tools ───────────────────────────────────────────────────────────────
  {
    name: 'get_site_info',
    description: 'Get WordPress site information (version, theme, server, etc.)',
    category: 'site',
    parameters: { type: 'object', properties: {} },
  },
  {
    name: 'get_site_health',
    description: 'Run WordPress Site Health checks and return a summary of passed, recommended, and critical issues.',
    category: 'site',
    parameters: { type: 'object', properties: {} },
  },
  {
    name: 'get_option',
    description: 'Read a WordPress option value by key',
    category: 'site',
    parameters: {
      type: 'object',
      properties: {
        option_name: { type: 'string', description: 'The option key to read' },
      },
      required: ['option_name'],
    },
  },
  {
    name: 'update_option',
    description: 'Update a WordPress option value',
    category: 'site',
    requires_confirmation: true,
    parameters: {
      type: 'object',
      properties: {
        option_name: { type: 'string', description: 'The option key to update' },
        option_value: { description: 'The new value' },
      },
      required: ['option_name', 'option_value'],
    },
  },

  // ── Plugin Tools ─────────────────────────────────────────────────────────────
  {
    name: 'list_plugins',
    description: 'List all installed WordPress plugins with their status and version',
    category: 'plugins',
    parameters: { type: 'object', properties: {} },
  },
  {
    name: 'install_plugin',
    description: 'Install a plugin from the WordPress.org repository by slug',
    category: 'plugins',
    requires_confirmation: true,
    parameters: {
      type: 'object',
      properties: {
        slug: { type: 'string', description: 'Plugin slug from WordPress.org' },
      },
      required: ['slug'],
    },
  },
  {
    name: 'activate_plugin',
    description: 'Activate an installed plugin',
    category: 'plugins',
    parameters: {
      type: 'object',
      properties: {
        plugin: { type: 'string', description: 'Plugin file path (e.g. "akismet/akismet.php")' },
      },
      required: ['plugin'],
    },
  },
  {
    name: 'deactivate_plugin',
    description: 'Deactivate a plugin',
    category: 'plugins',
    parameters: {
      type: 'object',
      properties: {
        plugin: { type: 'string', description: 'Plugin file path' },
      },
      required: ['plugin'],
    },
  },
  {
    name: 'update_plugin',
    description: 'Update a plugin to the latest version',
    category: 'plugins',
    requires_confirmation: true,
    parameters: {
      type: 'object',
      properties: {
        plugin: { type: 'string', description: 'Plugin file path' },
      },
      required: ['plugin'],
    },
  },
  {
    name: 'delete_plugin',
    description: 'Permanently delete a plugin',
    category: 'plugins',
    requires_confirmation: true,
    parameters: {
      type: 'object',
      properties: {
        plugin: { type: 'string', description: 'Plugin file path' },
      },
      required: ['plugin'],
    },
  },

  // ── Search Tools ─────────────────────────────────────────────────────────────
  {
    name: 'search_content',
    description: 'Search across all post content (standard post_content and Elementor _elementor_data)',
    category: 'search',
    parameters: {
      type: 'object',
      properties: {
        query: { type: 'string', description: 'Search text' },
        case_sensitive: { type: 'boolean', default: false },
        post_type: { type: 'string', default: 'any', description: 'Limit to a post type' },
      },
      required: ['query'],
    },
  },
  {
    name: 'replace_content',
    description: 'Find and replace text in post content (performs dry-run first, then requires confirmation)',
    category: 'search',
    requires_confirmation: true,
    parameters: {
      type: 'object',
      properties: {
        search: { type: 'string', description: 'Text to find' },
        replace: { type: 'string', description: 'Replacement text' },
        case_sensitive: { type: 'boolean', default: false },
        post_type: { type: 'string', default: 'any' },
        dry_run: { type: 'boolean', default: true, description: 'Preview matches without changing anything' },
      },
      required: ['search', 'replace'],
    },
  },

  // ── Elementor Tools ──────────────────────────────────────────────────────────
  {
    name: 'elementor_search_content',
    description: 'Search text within Elementor widget data across all pages',
    category: 'elementor',
    parameters: {
      type: 'object',
      properties: {
        query: { type: 'string', description: 'Search text' },
        case_sensitive: { type: 'boolean', default: false },
      },
      required: ['query'],
    },
  },
  {
    name: 'elementor_replace_content',
    description: 'Replace text in Elementor widgets with dry-run preview',
    category: 'elementor',
    requires_confirmation: true,
    parameters: {
      type: 'object',
      properties: {
        search: { type: 'string' },
        replace: { type: 'string' },
        case_sensitive: { type: 'boolean', default: false },
        dry_run: { type: 'boolean', default: true },
      },
      required: ['search', 'replace'],
    },
  },
  {
    name: 'elementor_get_page_structure',
    description: 'Get the section/column/widget tree structure of an Elementor page',
    category: 'elementor',
    parameters: {
      type: 'object',
      properties: {
        post_id: { type: 'integer', description: 'The page/post ID' },
      },
      required: ['post_id'],
    },
  },
  {
    name: 'elementor_clear_css_cache',
    description: 'Clear Elementor CSS cache after modifications',
    category: 'elementor',
    parameters: { type: 'object', properties: {} },
  },
];

// ─── Service ──────────────────────────────────────────────────────────────────

@Injectable()
export class ToolDefinitionsService {
  /**
   * Returns all tool definitions (internal format, including metadata such as
   * `requires_confirmation` and `category`).
   */
  getAllTools(): ToolDefinition[] {
    return TOOL_DEFINITIONS;
  }

  /**
   * Returns tool definitions formatted for a specific LLM provider.
   * - `'anthropic'` → `{ name, description, input_schema }` format
   * - `'openai'`    → `{ type: 'function', function: { name, description, parameters } }` format
   * - fallback      → raw internal definitions
   */
  getToolsForProvider(provider: LlmProvider): AnthropicTool[] | OpenAiTool[] {
    if (provider === 'anthropic') {
      return TOOL_DEFINITIONS.map(
        (tool): AnthropicTool => ({
          name: tool.name,
          description: tool.description,
          input_schema: tool.parameters,
        }),
      );
    }

    return TOOL_DEFINITIONS.map(
      (tool): OpenAiTool => ({
        type: 'function',
        function: {
          name: tool.name,
          description: tool.description,
          parameters: tool.parameters,
        },
      }),
    );
  }

  /**
   * Returns the set of tool names that require user confirmation before execution.
   */
  getConfirmationRequiredTools(): Set<string> {
    return new Set(
      TOOL_DEFINITIONS.filter((t) => t.requires_confirmation).map((t) => t.name),
    );
  }

  /**
   * Checks whether a given tool name requires user confirmation.
   */
  requiresConfirmation(toolName: string): boolean {
    return TOOL_DEFINITIONS.some((t) => t.name === toolName && t.requires_confirmation === true);
  }
}

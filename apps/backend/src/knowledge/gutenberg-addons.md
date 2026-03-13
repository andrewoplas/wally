# Gutenberg Addons

## When to Use
- User asks about block editor addon plugins (Kadence Blocks, Spectra/UAGB, GenerateBlocks)
- User wants to check if a Gutenberg blocks addon is installed or active
- User wants to check or update addon global settings
- User asks about Starter Templates or design library imports

## Available Tools
- `list_plugins` — check which Gutenberg addon is installed and active
- `get_option` — read addon settings (global colors, block defaults, API keys)
- `update_option` — change addon settings (requires confirmation)
- `create_post` / `update_post` — create/edit content using addon block markup in `content` field

## Workflows

### Check Which Gutenberg Addon is Active
1. Call `list_plugins`
2. Look for these common slugs:
   - Kadence Blocks: `kadence-blocks`
   - Spectra (Ultimate Addons for Gutenberg): `ultimate-addons-for-gutenberg`
   - GenerateBlocks: `generateblocks`
   - Starter Templates: `astra-starter-sites`

### Check Kadence Blocks Settings
1. Call `get_option` with key `kadence_blocks_global` — global color palette and typography
2. Call `get_option` with key `kadence_blocks_settings_blocks` — block visibility/disabled state

### Check Spectra (UAGB) Settings
1. Call `get_option` with key `uagb_admin_settings` — master plugin settings
2. Call `get_option` with key `uag_activated_blocks` — per-block enabled/disabled state

### Check GenerateBlocks Settings
1. Call `get_option` with key `generateblocks_defaults` — default block settings
2. Call `get_option` with key `generateblocks_global_colors` — global color definitions

### Create Content with Addon Blocks
1. Use standard Gutenberg block comment syntax: `<!-- wp:namespace/block-name {"attrs"} -->`
2. Block namespaces:
   - Kadence Blocks: `kadence/*` (e.g., `kadence/advancedheading`, `kadence/rowlayout`)
   - Spectra: `uagb/*` (e.g., `uagb/advanced-heading`, `uagb/container`)
   - GenerateBlocks: `generateblocks/*` (e.g., `generateblocks/container`, `generateblocks/headline`)
3. Pass the full markup in the `content` field of `create_post` or `update_post`

## Important Notes
- Addon block content is stored in `post_content` like standard blocks — `search_content` and `replace_content` work on this content
- Wally can read/update global settings via `get_option`/`update_option`, but cannot toggle individual blocks via the UI — guide user to the addon's admin panel for block management
- After editing content with addon blocks programmatically, the addon may need to regenerate its CSS — guide user to the addon's admin settings to clear/regenerate the CSS cache
- Starter Templates design library imports must be done via the admin UI (Appearance > Starter Templates) — Wally cannot trigger template imports
- Pro versions (Kadence Blocks Pro, Spectra Pro, GenerateBlocks Pro) must be uploaded manually — `install_plugin` only works for WordPress.org plugins

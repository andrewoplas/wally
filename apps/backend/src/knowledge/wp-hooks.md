# WordPress Hooks System

## When to Use
- User asks about actions, filters, hooks, or how plugins modify WordPress behavior
- User asks why something fires at a certain time or in a certain order
- User troubleshoots plugin conflicts or unexpected behavior

## Key Patterns

### Actions vs Filters
- **Actions** (`add_action` / `do_action`): Execute code at specific points. No return value expected.
- **Filters** (`add_filter` / `apply_filters`): Modify data and return it. Must return a value.

### Priority & Execution Order
- Lower priority number = runs earlier. Default is `10`.
- Use `1` for early, `999` for late.
- `accepted_args` must match the number of arguments the hook passes.

### WordPress Loading Order (Key Hooks)
1. `muplugins_loaded` — after MU plugins load
2. `plugins_loaded` — after all plugins load (plugin init, textdomains)
3. `after_setup_theme` — after theme's functions.php (theme features)
4. `init` — WP fully loaded (register CPTs, taxonomies, shortcodes)
5. `wp_loaded` — after WP + plugins + theme
6. `admin_init` — admin page load start
7. `wp_enqueue_scripts` — frontend CSS/JS
8. `template_redirect` — before template loads (redirects, access control)

### Post Lifecycle Hooks
- `save_post` — fires on save/update (3 args: `$post_id`, `$post`, `$update`)
- `save_post_{post_type}` — CPT-specific save
- `before_delete_post` — cleanup before deletion
- `transition_post_status` — status changes (3 args: `$new`, `$old`, `$post`)

### Common Gotchas
- `save_post` fires on autosave and revisions — guard with `DOING_AUTOSAVE` check
- `wp_update_post()` inside `save_post` causes infinite loops — remove/re-add the action
- `pre_get_posts` — always check `$query->is_main_query()` to avoid modifying widget/menu queries
- Closures/anonymous functions cannot be removed with `remove_action`
- `plugins_loaded` fires before `init` — don't use functions that depend on CPTs being registered

## Relevant Wally Tools
- `list_plugins` — check which plugins are active (each adds hooks)
- `get_option` — read plugin settings that control hook behavior
- `get_site_info` — check active theme (themes add hooks in `after_setup_theme`)

## Important Notes
- Wally cannot add, remove, or modify hooks — this is developer-level PHP code
- Hook conflicts between plugins are a common source of issues — guide user to deactivate plugins one-by-one to isolate
- Understanding hook order helps explain why certain settings or changes take effect at different times

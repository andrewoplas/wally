# Jetpack

## When to Use
- User asks about Jetpack features (stats, security, social sharing, related posts, backups)
- User wants to check which Jetpack modules are active
- User wants to check Jetpack connection status or settings
- User wants to install or activate Jetpack

## Available Tools
- `list_plugins` — check if Jetpack is installed and active
- `get_option` — read Jetpack settings and active modules
- `update_option` — update Jetpack settings (requires confirmation)
- `install_plugin` — install Jetpack from WordPress.org (requires confirmation)
- `activate_plugin` — activate Jetpack (requires confirmation)

## Workflows

### Check if Jetpack is Active
1. Call `list_plugins`
2. Look for `jetpack`
3. Check the `status` field — it must be active for Jetpack features to work

### Check Which Jetpack Modules Are Active
1. Call `get_option` with key `jetpack_active_modules`
2. Returns array of active module slugs
3. Common modules: `stats`, `protect`, `photon`, `publicize`, `related-posts`, `sitemaps`, `subscriptions`, `contact-form`

### Check Jetpack Connection Status
1. Call `get_option` with key `jetpack_options`
2. Look for `blog_id` (numeric) and `id` — if these are present, Jetpack is connected to WordPress.com
3. If missing or empty, guide user to **Jetpack > Dashboard** to reconnect

### Check Jetpack Stats Settings
1. Call `get_option` with key `stats_options`
2. Returns stats configuration (admin bar toggle, which roles can view stats)

### Check Social Sharing Settings
1. Call `get_option` with key `sharing-services`
2. Returns enabled sharing button services (Facebook, Twitter/X, LinkedIn, etc.)

### Install Jetpack
1. Call `install_plugin` with `slug: 'jetpack'` (requires confirmation)
2. Call `activate_plugin` with `slug: 'jetpack'` (requires confirmation)
3. Tell the user: "Jetpack is installed and active. Go to **Jetpack > Dashboard** to connect your WordPress.com account and configure features."

## Important Notes
- Jetpack requires a WordPress.com account connection — many features (Stats, Search, Publicize) do not work without it
- Wally cannot connect or reconnect Jetpack to WordPress.com — guide user to **Jetpack > Dashboard**
- Wally cannot enable or disable individual Jetpack modules — guide user to **Jetpack > Settings**
- Site stats (views, referrers) are stored remotely on WordPress.com — Wally cannot retrieve live stats
- Publicize (auto-sharing to social) is configured in Jetpack > Settings > Sharing — Wally cannot manage social connections
- Jetpack backups (VaultPress/Jetpack Backup) require a paid Jetpack plan — guide user to the Jetpack dashboard for backup management
- If Jetpack features stop working, the WordPress.com connection may have broken — guide user to **Jetpack > Dashboard** to reconnect

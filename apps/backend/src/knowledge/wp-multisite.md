# WordPress Multisite

## When to Use
- User asks about WordPress Multisite, network administration, or sub-sites
- User manages multiple sites in a single WordPress installation
- User asks about network-activated plugins or super admin roles

## Key Patterns

### Detecting Multisite
- `is_multisite()` returns true if Multisite is active
- Configured via `wp-config.php`: `MULTISITE`, `SUBDOMAIN_INSTALL`, `DOMAIN_CURRENT_SITE`
- Two modes: **subdirectory** (`example.com/site2/`) or **subdomain** (`site2.example.com`)

### Database Structure
Each sub-site gets its own set of tables with blog ID in the prefix:
- Main site (ID 1): `wp_posts`, `wp_options`
- Sub-site (ID 2): `wp_2_posts`, `wp_2_options`
- **Network-wide tables** (shared): `wp_blogs`, `wp_site`, `wp_sitemeta`, `wp_users`, `wp_usermeta`

### Key Concepts
- **Blog ID 1** is always the main/primary site
- **User accounts** are shared network-wide, but **roles/capabilities** are per-site
- **Uploads** are separated per site: `wp-content/uploads/sites/{blog_id}/`
- **Network options** use `get_site_option()` / `update_site_option()` (stored in `wp_sitemeta`)

### Network-Activated Plugins
- Apply to ALL sites in the network
- Stored in `wp_sitemeta` under key `active_sitewide_plugins`
- Only super admins can network-activate plugins

### Super Admin
- Network-level administrator with full access to all sites
- `is_super_admin()` checks the role
- Stored in `wp_sitemeta` under key `site_admins`

## Relevant Wally Tools
- `get_site_info` — returns basic site info (may indicate multisite context)
- `list_plugins` — shows installed plugins (network-activated plugins appear active on all sites)
- `get_option` — reads options for the current site only (not network options)

## Important Notes
- Wally operates within the context of a single site — it cannot switch between sites or manage the network
- `get_option` reads from the current site's `wp_options` table, not network options
- Wally cannot: create/delete sub-sites, grant super admin, network-activate plugins, or access other sites' data
- If user asks about network-level operations, guide them to the Network Admin dashboard (`/wp-admin/network/`)
- Plugin and theme updates on Multisite may require network-level permissions

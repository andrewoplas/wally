# WordPress Backup Plugins

## When to Use
- User mentions UpdraftPlus, Duplicator, or BackWPup
- User asks about backups, restoring, site migration, or backup schedules
- User wants to check backup settings or status

## Available Tools
- `list_plugins` — detect which backup plugin is active
- `get_option` — read backup plugin settings and history
- `update_option` — change backup settings (requires confirmation)

## Workflows

### Detect Active Backup Plugin
1. Call `list_plugins`
2. Look for: `updraftplus`, `duplicator`, `duplicator-pro`, `backwpup`

### UpdraftPlus — Check Backup Schedule
1. Call `get_option` with key `updraft_interval` — file backup schedule
2. Call `get_option` with key `updraft_interval_database` — database backup schedule
3. Values: `manual`, `every4hours`, `every8hours`, `twicedaily`, `daily`, `weekly`, `fortnightly`, `monthly`

### UpdraftPlus — Check What's Being Backed Up
1. Call `get_option` with key `updraft_include_plugins` — plugins included (1/0)
2. Call `get_option` with key `updraft_include_themes` — themes included (1/0)
3. Call `get_option` with key `updraft_include_uploads` — uploads included (1/0)
4. Call `get_option` with key `updraft_include_others` — other files included (1/0)

### UpdraftPlus — Check Remote Storage
1. Call `get_option` with key `updraft_service`
2. Values: `s3`, `dropbox`, `googledrive`, `ftp`, `sftp`, `email`, `updraftvault`

### UpdraftPlus — Check Backup History
1. Call `get_option` with key `updraft_backup_history`
2. Returns array of backup records with timestamps and file lists

### UpdraftPlus — Update Backup Schedule
1. Call `update_option` with key `updraft_interval` and desired schedule value (requires confirmation)
2. Call `update_option` with key `updraft_interval_database` for database schedule (requires confirmation)

### Duplicator — Check Packages
1. Duplicator stores packages in custom table `duplicator_packages` — not directly readable via Wally
2. Guide user to Duplicator admin (Duplicator > Packages) for package management

### BackWPup — Read Job Configuration
1. Call `get_option` with key `backwpup_jobs` for job list
2. Individual jobs: `get_option` with key `backwpup_job_{id}`

## Important Notes
- Wally cannot trigger backups or restores — guide user to the plugin's admin page for these actions
- Backup files are in `wp-content/updraft/` (UpdraftPlus) or `wp-content/backups-dup-lite/` (Duplicator) — not accessible via Wally
- Recommend triggering a backup before any destructive operation (plugin updates, theme changes, migrations)
- WP-Cron-based schedules can be unreliable on low-traffic sites — recommend server-level cron
- Backup directories must not be publicly accessible — plugins add `.htaccess` protection automatically
- Retention settings (`updraft_retain`, `updraft_retain_db`) control how many backups are kept

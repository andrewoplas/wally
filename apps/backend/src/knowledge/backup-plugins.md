## WordPress Backup Plugins

### UpdraftPlus
- **Settings**: wp_options keys prefixed `updraft_*`.
- **Key options**:
  - `updraft_interval` — files backup schedule (manual, every4hours, every8hours, twicedaily, daily, weekly, fortnightly, monthly)
  - `updraft_interval_database` — database backup schedule (same values)
  - `updraft_service` — remote storage destinations: `s3`, `dropbox`, `googledrive`, `ftp`, `sftp`, `email`, `azure`, `backblaze`, `onedrive`, `updraftvault`
  - `updraft_include_plugins`, `updraft_include_themes`, `updraft_include_uploads`, `updraft_include_others` — what to include (1/0)
  - `updraft_retain` — number of file backups to keep
  - `updraft_retain_db` — number of database backups to keep
  - `updraft_dir` — custom backup directory (default: `wp-content/updraft/`)
  - `updraft_email` — email address for backup notifications
- **Backup location**: `wp-content/updraft/` — contains backup zip files and log files.
- **Backup history**: `updraft_backup_history` option — serialized array of all backup records with timestamps, file lists, and nonces.
- **Functions**:
  - `UpdraftPlus_Backup_History::get_history()` — returns all backup history
  - `do_action('updraft_backup')` — trigger manual files backup
  - `do_action('updraft_backup_database')` — trigger manual database backup
  - `do_action('updraft_backupnow_backup_all')` — trigger full backup (files + database)
- **Hooks**: `updraft_backup_complete`, `updraftplus_restore_completed`, `updraft_report_sendreport`.
- **Restore**: UpdraftPlus_Restorer class handles restoration. Restores are initiated from admin UI or WP-CLI.

### Duplicator
- **Purpose**: Creates portable "packages" (installer + archive) for site migration and backup.
- **Settings**: wp_options keys prefixed `duplicator_*`.
- **Package table**: `{prefix}duplicator_packages` (Free) or `{prefix}duplicator_pro_packages` (Pro) — stores package metadata, build status, and file paths.
- **Key classes**: `DUP_Package` (Free), `DUP_PRO_Package` (Pro) — handles build, scan, and storage.
- **Build output**: Packages stored in `wp-content/backups-dup-lite/` (Free) or `wp-content/backups-dup-pro/` (Pro). Contains `*_archive.zip` and `*_installer.php`.
- **Schedules (Pro)**: `{prefix}duplicator_pro_entities` table stores scheduled backup jobs.
- **Functions**: `DUP_Package::get_all()` — list all packages, `DUP_Package::get_by_id($id)` — get specific package.

### BackWPup
- **Settings**: wp_options keys as `backwpup_jobs` (serialized job configurations). Individual jobs stored as `backwpup_job_{id}` options.
- **Log table**: `{prefix}backwpup_logs` — stores backup job run history and logs.
- **Key classes**: `BackWPup_Job` — handles job execution, `BackWPup_Option` — manages job options.
- **Job types**: Database backup, file backup, XML export, plugin list, check database tables, optimize tables.
- **Destinations**: Folder, email, FTP, S3, Dropbox, Azure, Rackspace, SugarSync.
- **Backup location**: Configurable per job. Default: `wp-content/uploads/backwpup-{hash}-backups/`.
- **Functions**: `BackWPup_Option::get($job_id, $key)`, `BackWPup_Option::update($job_id, $key, $value)`.
- **CLI support**: `wp backwpup start --jobid=1` (if WP-CLI bridge installed).

### Common Patterns
- All backup plugins need filesystem write access and database read access.
- Backups are typically scheduled via WP-Cron (`wp_schedule_event`). Unreliable on low-traffic sites — recommend server-level cron for `wp-cron.php`.
- Backup files must not be publicly accessible. Ensure `.htaccess` / web server rules block direct access to backup directories. UpdraftPlus adds `index.html` + `.htaccess` automatically.
- Large sites may hit PHP memory_limit or max_execution_time during backup. Plugins handle this with chunked processing and resumable jobs.
- Before any destructive operation (plugin update, theme change, database migration), triggering a backup is recommended.

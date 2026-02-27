<?php
namespace Wally;

class Database {
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $conversations = $wpdb->prefix . 'wally_conversations';
        $messages      = $wpdb->prefix . 'wally_messages';
        $actions       = $wpdb->prefix . 'wally_actions';

        $sql = "
            CREATE TABLE {$conversations} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                title varchar(255) DEFAULT '',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id)
            ) {$charset};

            CREATE TABLE {$messages} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                conversation_id bigint(20) unsigned NOT NULL,
                role varchar(20) NOT NULL,
                content longtext NOT NULL,
                token_count int unsigned DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY conversation_id (conversation_id)
            ) {$charset};

            CREATE TABLE {$actions} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                conversation_id bigint(20) unsigned NOT NULL,
                message_id bigint(20) unsigned DEFAULT NULL,
                user_id bigint(20) unsigned NOT NULL,
                tool_name varchar(100) NOT NULL,
                tool_input longtext,
                tool_output longtext,
                status varchar(20) DEFAULT 'success',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY conversation_id (conversation_id),
                KEY user_id (user_id)
            ) {$charset};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'wally_db_version', WALLY_VERSION );
    }

    /**
     * Delete conversations (and their messages) older than $days days.
     *
     * Called by WP-Cron via the wally_auto_prune hook. The retention
     * period is configurable via the wally_prune_days option (default 90).
     *
     * @return int Number of conversations deleted.
     */
    public static function prune_old_conversations(): int {
        global $wpdb;

        $days = absint( get_option( 'wally_prune_days', 90 ) );
        if ( 0 === $days ) {
            return 0; // 0 means disabled.
        }

        $conv_table = $wpdb->prefix . 'wally_conversations';
        $msg_table  = $wpdb->prefix . 'wally_messages';
        $cutoff     = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        // Get IDs of conversations to prune.
        $old_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$conv_table} WHERE updated_at < %s",
                $cutoff
            )
        );

        if ( empty( $old_ids ) ) {
            return 0;
        }

        // Delete messages belonging to old conversations.
        $placeholders = implode( ',', array_fill( 0, count( $old_ids ), '%d' ) );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$msg_table} WHERE conversation_id IN ({$placeholders})",
                ...$old_ids
            )
        );

        // Delete the conversations themselves.
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$conv_table} WHERE id IN ({$placeholders})",
                ...$old_ids
            )
        );

        return count( $old_ids );
    }
}

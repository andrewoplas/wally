<?php
namespace Wally;

/**
 * Lightweight CRUD helper for the wp_wally_snapshots table.
 *
 * Used by the undo system to save and restore pre-change state.
 * All methods are static — no instantiation needed.
 *
 * Snapshot types: 'post', 'option', 'menu', 'plugin'
 */
class Snapshot {

    /**
     * Save a snapshot of an object's previous state.
     *
     * @param int    $conversation_id Active conversation ID.
     * @param string $snapshot_type   Type of object being snapshotted ('post', 'option', 'menu', 'plugin').
     * @param int    $object_id       Post ID, menu ID, etc. (0 if not applicable).
     * @param string $object_key      Option name, meta key, or other identifier (empty if not applicable).
     * @param mixed  $previous_value  The value before the change (will be serialized).
     * @return int|false Inserted row ID, or false on failure.
     */
    public static function save( int $conversation_id, string $snapshot_type, int $object_id, string $object_key, $previous_value ) {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'wally_snapshots',
            [
                'conversation_id' => $conversation_id,
                'snapshot_type'   => $snapshot_type,
                'object_id'       => $object_id ?: null,
                'object_key'      => $object_key ?: null,
                'previous_value'  => serialize( $previous_value ),
            ],
            [ '%d', '%s', '%d', '%s', '%s' ]
        );

        if ( false === $result ) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Get the most recent snapshot for a conversation.
     *
     * @param int $conversation_id
     * @return object|null Row object with `previous_value` already unserialized, or null if none found.
     */
    public static function get_latest( int $conversation_id ) {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wally_snapshots
                 WHERE conversation_id = %d
                 ORDER BY id DESC
                 LIMIT 1",
                $conversation_id
            )
        );

        if ( $row ) {
            $row->previous_value = unserialize( $row->previous_value );
        }

        return $row;
    }

    /**
     * List all snapshots for a conversation, newest first.
     *
     * @param int $conversation_id
     * @param int $limit Maximum number of rows to return (default 20).
     * @return array Array of row objects (previous_value unserialized).
     */
    public static function list_for_conversation( int $conversation_id, int $limit = 20 ): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wally_snapshots
                 WHERE conversation_id = %d
                 ORDER BY id DESC
                 LIMIT %d",
                $conversation_id,
                $limit
            )
        );

        foreach ( $rows as $row ) {
            $row->previous_value = unserialize( $row->previous_value );
        }

        return $rows ?: [];
    }

    /**
     * Delete a snapshot by its ID.
     *
     * @param int $id Snapshot row ID.
     * @return bool True on success.
     */
    public static function delete( int $id ): bool {
        global $wpdb;

        return (bool) $wpdb->delete(
            $wpdb->prefix . 'wally_snapshots',
            [ 'id' => $id ],
            [ '%d' ]
        );
    }

    /**
     * Delete all snapshots older than 24 hours.
     * Called by the daily cron job.
     *
     * @return int Number of rows deleted.
     */
    public static function cleanup_old(): int {
        global $wpdb;

        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );

        return (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}wally_snapshots WHERE created_at < %s",
                $cutoff
            )
        );
    }
}

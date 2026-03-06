<?php
namespace Wally\Tools;

use Wally\Snapshot;

/**
 * Rollback / undo tools for the Wally snapshot system.
 *
 * Tools: list_recent_changes, undo_last_action.
 * Category: "site" — requires edit_posts capability.
 *
 * The conversation_id is always passed as a required input parameter
 * because ToolExecutor does not forward it to execute().
 */

/**
 * List recent changes Wally made in the current conversation.
 */
class ListRecentChanges extends ToolInterface {

    public function get_name(): string {
        return 'list_recent_changes';
    }

    public function get_description(): string {
        return 'List the changes Wally made in the current conversation that can be undone. Returns snapshot entries with type, object, and timestamp. Use this before calling undo_last_action so the user can see what will be reverted.';
    }

    public function get_category(): string {
        return 'site';
    }

    public function get_action(): string {
        return 'read';
    }

    public function get_parameters_schema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'conversation_id' => [
                    'type'        => 'integer',
                    'description' => 'The current conversation ID. Required to look up snapshots for this session.',
                ],
            ],
            'required' => [ 'conversation_id' ],
        ];
    }

    public function get_required_capability(): string {
        return 'edit_posts';
    }

    public function execute( array $input ): array {
        $conversation_id = absint( $input['conversation_id'] );

        $snapshots = Snapshot::list_for_conversation( $conversation_id, 20 );

        if ( empty( $snapshots ) ) {
            return [
                'success' => true,
                'data'    => [
                    'message'   => 'No undoable changes recorded in this conversation.',
                    'snapshots' => [],
                ],
            ];
        }

        $items = array_map( function( $row ) {
            return [
                'id'             => (int) $row->id,
                'snapshot_type'  => $row->snapshot_type,
                'object_id'      => $row->object_id ? (int) $row->object_id : null,
                'object_key'     => $row->object_key,
                'created_at'     => $row->created_at,
            ];
        }, $snapshots );

        return [
            'success' => true,
            'data'    => [
                'count'     => count( $items ),
                'snapshots' => $items,
            ],
        ];
    }
}

/**
 * Undo (revert) the last action Wally took in the current conversation.
 */
class UndoLastAction extends ToolInterface {

    public function get_name(): string {
        return 'undo_last_action';
    }

    public function get_description(): string {
        return 'Revert the last change Wally made in the current conversation by restoring the previous state from a snapshot. Supports reverting post updates, option changes, and menu modifications. Call list_recent_changes first to show the user what will be undone. This action requires confirmation.';
    }

    public function get_category(): string {
        return 'site';
    }

    public function get_action(): string {
        return 'update';
    }

    public function get_parameters_schema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'conversation_id' => [
                    'type'        => 'integer',
                    'description' => 'The current conversation ID. Used to find the most recent snapshot to revert.',
                ],
            ],
            'required' => [ 'conversation_id' ],
        ];
    }

    public function get_required_capability(): string {
        return 'edit_posts';
    }

    public function requires_confirmation(): bool {
        return true;
    }

    public function execute( array $input ): array {
        $conversation_id = absint( $input['conversation_id'] );

        $snapshot = Snapshot::get_latest( $conversation_id );

        if ( ! $snapshot ) {
            return [
                'success' => false,
                'error'   => 'No undoable changes found for this conversation.',
            ];
        }

        $result = $this->restore( $snapshot );

        if ( ! $result['success'] ) {
            return $result;
        }

        Snapshot::delete( (int) $snapshot->id );

        return [
            'success' => true,
            'data'    => [
                'message'       => "Reverted {$snapshot->snapshot_type} change successfully.",
                'snapshot_type' => $snapshot->snapshot_type,
                'object_id'     => $snapshot->object_id,
                'object_key'    => $snapshot->object_key,
                'reverted_at'   => current_time( 'mysql' ),
            ],
        ];
    }

    /**
     * Restore the state captured in a snapshot.
     *
     * @param object $snapshot Row from wp_wally_snapshots (previous_value already unserialized).
     * @return array { 'success' => bool, 'error' => string }
     */
    private function restore( object $snapshot ): array {
        $previous = $snapshot->previous_value;

        switch ( $snapshot->snapshot_type ) {
            case 'post':
                if ( ! $previous || ! is_object( $previous ) ) {
                    return [ 'success' => false, 'error' => 'Snapshot data is invalid for post restore.' ];
                }
                $result = wp_update_post( [
                    'ID'             => (int) $previous->ID,
                    'post_title'     => $previous->post_title,
                    'post_content'   => $previous->post_content,
                    'post_excerpt'   => $previous->post_excerpt,
                    'post_status'    => $previous->post_status,
                    'post_name'      => $previous->post_name,
                    'post_date'      => $previous->post_date,
                    'post_date_gmt'  => $previous->post_date_gmt,
                    'comment_status' => $previous->comment_status,
                    'ping_status'    => $previous->ping_status,
                ], true );

                if ( is_wp_error( $result ) ) {
                    return [ 'success' => false, 'error' => $result->get_error_message() ];
                }
                return [ 'success' => true ];

            case 'option':
                if ( empty( $snapshot->object_key ) ) {
                    return [ 'success' => false, 'error' => 'Snapshot missing option name.' ];
                }
                update_option( $snapshot->object_key, $previous );
                return [ 'success' => true ];

            case 'menu':
                // previous_value is the full menu items array; restore each item's data.
                if ( ! is_array( $previous ) ) {
                    return [ 'success' => false, 'error' => 'Snapshot data is invalid for menu restore.' ];
                }
                foreach ( $previous as $item ) {
                    if ( isset( $item['ID'] ) ) {
                        wp_update_nav_menu_item( 0, $item['ID'], $item );
                    }
                }
                return [ 'success' => true ];

            default:
                return [ 'success' => false, 'error' => "Unsupported snapshot type: {$snapshot->snapshot_type}" ];
        }
    }
}

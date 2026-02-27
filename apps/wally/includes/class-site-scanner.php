<?php
namespace Wally;

class SiteScanner {
    public static function scan() {
        global $wpdb;

        $profile = [
            'wp_version'     => get_bloginfo( 'version' ),
            'php_version'    => phpversion(),
            'site_url'       => get_site_url(),
            'site_name'      => get_bloginfo( 'name' ),
            'theme'          => self::get_theme_info(),
            'plugins'        => self::get_plugins_info(),
            'post_types'     => self::get_post_types(),
            'taxonomies'     => self::get_taxonomies(),
            'content_counts' => self::get_content_counts(),
            'elementor'      => self::get_elementor_info(),
            'acf_field_groups' => self::get_acf_field_groups(),
            'menus'          => self::get_menus(),
            'front_page'     => self::get_front_page_info(),
            'posts_page'     => self::get_posts_page_info(),
            'permalink'      => get_option( 'permalink_structure' ),
            'multisite'      => is_multisite(),
            'user_roles'     => self::get_user_role_counts(),
            'active_plugins_summary' => self::get_active_plugins_summary(),
            'scanned_at'     => current_time( 'mysql' ),
        ];

        update_option( 'wally_site_profile', $profile, false );
        return $profile;
    }

    public static function get_profile() {
        $profile = get_option( 'wally_site_profile', null );
        if ( ! $profile ) {
            $profile = self::scan();
        }
        return $profile;
    }

    private static function get_theme_info() {
        $theme = wp_get_theme();
        return [
            'name'     => $theme->get( 'Name' ),
            'version'  => $theme->get( 'Version' ),
            'is_child' => is_child_theme(),
            'parent'   => is_child_theme() ? $theme->parent()->get( 'Name' ) : null,
        ];
    }

    private static function get_plugins_info() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins    = get_plugins();
        $active_plugins = get_option( 'active_plugins', [] );
        $result         = [];

        foreach ( $all_plugins as $path => $data ) {
            $result[] = [
                'name'    => $data['Name'],
                'version' => $data['Version'],
                'active'  => in_array( $path, $active_plugins ),
                'slug'    => dirname( $path ),
            ];
        }

        return $result;
    }

    /**
     * Get a compact comma-separated list of active plugin names.
     */
    private static function get_active_plugins_summary() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins    = get_plugins();
        $active_plugins = get_option( 'active_plugins', [] );
        $names          = [];

        foreach ( $all_plugins as $path => $data ) {
            if ( in_array( $path, $active_plugins ) ) {
                $names[] = $data['Name'];
            }
        }

        return implode( ', ', $names );
    }

    private static function get_post_types() {
        $types  = get_post_types( [ 'public' => true ], 'objects' );
        $result = [];
        foreach ( $types as $type ) {
            if ( $type->name === 'attachment' ) {
                continue; // Skip attachment, covered by content_counts
            }
            $result[] = [
                'name'  => $type->name,
                'label' => $type->label,
                'count' => (int) wp_count_posts( $type->name )->publish,
            ];
        }
        return $result;
    }

    /**
     * Get all public taxonomies with term counts.
     */
    private static function get_taxonomies() {
        $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
        $result     = [];

        foreach ( $taxonomies as $tax ) {
            if ( $tax->name === 'post_format' ) {
                continue; // Skip internal taxonomy
            }
            $result[] = [
                'name'       => $tax->name,
                'label'      => $tax->label,
                'count'      => (int) wp_count_terms( [ 'taxonomy' => $tax->name, 'hide_empty' => false ] ),
                'post_types' => $tax->object_type,
            ];
        }

        return $result;
    }

    private static function get_content_counts() {
        $media_counts = (array) wp_count_attachments();
        $total_media  = 0;
        foreach ( $media_counts as $count ) {
            $total_media += (int) $count;
        }

        return [
            'posts' => (int) wp_count_posts( 'post' )->publish,
            'pages' => (int) wp_count_posts( 'page' )->publish,
            'media' => $total_media,
        ];
    }

    private static function get_elementor_info() {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $active = is_plugin_active( 'elementor/elementor.php' ) ||
                  is_plugin_active( 'elementor-pro/elementor-pro.php' );

        if ( ! $active ) {
            return [ 'installed' => false ];
        }

        global $wpdb;
        $elementor_pages = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
             WHERE meta_key = '_elementor_data'
             AND meta_value != ''"
        );

        return [
            'installed' => true,
            'pro'       => is_plugin_active( 'elementor-pro/elementor-pro.php' ),
            'version'   => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : 'unknown',
            'pages'     => $elementor_pages,
        ];
    }

    /**
     * Get ACF field groups with their field names and associated post types.
     */
    private static function get_acf_field_groups() {
        if ( ! function_exists( 'acf_get_field_groups' ) ) {
            return [];
        }

        $field_groups = acf_get_field_groups();
        $result       = [];

        foreach ( $field_groups as $group ) {
            $fields     = acf_get_fields( $group['key'] );
            $field_names = [];

            if ( $fields ) {
                foreach ( $fields as $field ) {
                    $field_names[] = $field['name'];
                }
            }

            // Extract post type rules from location array
            $post_types = [];
            if ( ! empty( $group['location'] ) ) {
                foreach ( $group['location'] as $rule_group ) {
                    foreach ( $rule_group as $rule ) {
                        if ( $rule['param'] === 'post_type' && $rule['operator'] === '==' ) {
                            $post_types[] = $rule['value'];
                        }
                    }
                }
            }

            $result[] = [
                'title'      => $group['title'],
                'key'        => $group['key'],
                'post_types' => $post_types,
                'fields'     => $field_names,
                'active'     => $group['active'],
            ];
        }

        return $result;
    }

    /**
     * Get all navigation menus with their locations and item counts.
     */
    private static function get_menus() {
        $menus     = wp_get_nav_menus();
        $locations = get_nav_menu_locations();
        $loc_map   = array_flip( $locations ); // menu_id => location_slug
        $result    = [];

        // Also get location labels registered by theme
        $registered_locations = get_registered_nav_menus();

        foreach ( $menus as $menu ) {
            $location_slug = isset( $loc_map[ $menu->term_id ] ) ? $loc_map[ $menu->term_id ] : null;
            $location_label = null;

            if ( $location_slug && isset( $registered_locations[ $location_slug ] ) ) {
                $location_label = $registered_locations[ $location_slug ];
            }

            $result[] = [
                'name'       => $menu->name,
                'id'         => $menu->term_id,
                'location'   => $location_label ?: $location_slug,
                'item_count' => $menu->count,
            ];
        }

        return $result;
    }

    /**
     * Get the static front page info (if set).
     */
    private static function get_front_page_info() {
        if ( get_option( 'show_on_front' ) !== 'page' ) {
            return null;
        }

        $page_id = (int) get_option( 'page_on_front' );
        if ( ! $page_id ) {
            return null;
        }

        $page = get_post( $page_id );
        if ( ! $page ) {
            return null;
        }

        return [
            'id'    => $page_id,
            'title' => $page->post_title,
        ];
    }

    /**
     * Get the blog/posts page info (if set).
     */
    private static function get_posts_page_info() {
        $page_id = (int) get_option( 'page_for_posts' );
        if ( ! $page_id ) {
            return null;
        }

        $page = get_post( $page_id );
        if ( ! $page ) {
            return null;
        }

        return [
            'id'    => $page_id,
            'title' => $page->post_title,
        ];
    }

    private static function get_user_role_counts() {
        $counts = count_users();
        return $counts['avail_roles'];
    }
}

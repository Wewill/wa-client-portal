<?php
/**
 * Handles the menu display for logged-in users.
 *
 * @package Wa_Client_Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Wa_Client_Portal_Menu {

    /**
     * Initialize hooks for menu display.
     */
    public static function init() {
        add_action( 'wp_nav_menu_items', [ __CLASS__, 'add_logged_in_menu_items' ], 10, 2 );
    }

    /**
     * Add menu items for logged-in users.
     *
     * @param string $items The HTML list content for the menu items.
     * @param object $args  An object containing wp_nav_menu() arguments.
     * @return string Modified menu items.
     */
    public static function add_logged_in_menu_items( $items, $args ) {

        if ( $args->theme_location !== 'account' ) {
            return $items;
        }

        if ( is_user_logged_in() ) {

            // Build the parent menu item using the same structure as other menu items.
            $menu_classes = isset( $args->menu_class ) ? esc_attr( $args->menu_class ) : '';
            $add_li_classes = isset( $args->add_li_class ) ? esc_attr( $args->add_li_class ) : '';
            $portal_url = wacp_get_portal_page_url();
            $parent_item  = '<li id="menu-item-client-portal" class="menu-item --menu-item-type-custom --menu-item-object-custom menu-item-has-children '.$add_li_classes.' --link-featured">';
            $parent_item .= '<a href="' . esc_url( $portal_url ) . '"><i class="bi bi-person-heart fs-4 lh-0"></i></a>';

            // Start sub-menu.
            $parent_item .= '<ul class="sub-menu"><i class="icon icon-down-right"></i>';

            // Add link to portal page with its title
            $args_template = [
                'meta_key'   => '_wp_page_template',
                'meta_value' => '../templates/template-client-portal.php',
                'post_type'  => 'page',
                'post_status'=> 'publish',
                'numberposts'=> 1,
            ];
            $template_pages = get_posts( $args_template );
            if ( !empty( $template_pages ) ) {
                $template_page = reset($template_pages);
                $parent_item .= sprintf(
                    '<li class="menu-item menu-item-type-custom menu-item-object-custom"><a href="%s">%s</a></li>',
                    esc_url( get_permalink( $template_page->ID ) ),
                    esc_html( $template_page->post_title )
                );
            }
            
            // List all pages that are private and accessible to the user.
            $private_pages = get_posts( array(
                'post_type'   => 'page',
                'post_status' => 'private',
                'numberposts' => -1,
            ) );

            foreach ( $private_pages as $page ) {
                // Check if the current user can read the private page.
                if ( current_user_can( 'read_private_pages', $page->ID ) )
                    $parent_item .= sprintf(
                        '<li id="menu-item-client-portal-%1$d" class="menu-item menu-item-type-post_type menu-item-object-page"><a href="%2$s">%3$s</a></li>',
                        esc_attr( $page->ID ),
                        esc_url( get_permalink( $page->ID ) ),
                        esc_html( $page->post_title )
                    );
            }

            // Add logout link.
            $parent_item .= '<li class="menu-item menu-item-type-custom menu-item-object-custom"><a href="' . esc_url( wp_logout_url( home_url() ) ) . '">' . esc_html__( 'Logout', 'wacp' ) . '</a></li>';

            // End sub-menu and parent item.
            $parent_item .= '</ul></li>';

            // Return 
            $items .= $parent_item;
        } else {

            // Build the parent menu item using the same structure as other menu items.
            $menu_classes = isset( $args->menu_class ) ? esc_attr( $args->menu_class ) : '';
            $add_li_classes = isset( $args->add_li_class ) ? esc_attr( $args->add_li_class ) : '';
            $portal_url = wacp_get_portal_page_url();
            $parent_item  = '<li id="menu-item-client-portal" class="menu-item --menu-item-type-custom --menu-item-object-custom menu-item-has-children '.$add_li_classes.' --link-featured">';
            $parent_item .= '<a href="' . esc_url( $portal_url ) . '"><i class="bi bi-person fs-4 lh-0"></i></a>';

            // End sub-menu and parent item.
            $parent_item .= '</li>';

            // Return
            $items .= $parent_item;
        }

        return $items;
    }
}

// Initialize the menu functionality.
Wa_Client_Portal_Menu::init();
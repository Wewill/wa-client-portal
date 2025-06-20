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

        if ( is_user_logged_in() && $args->theme_location === 'secondary' ) {

            // Add a link to page where the _wp_page_template (page template slug ) is : template-client-portal.php
            $args_template = [
                'meta_key'   => '_wp_page_template',
                'meta_value' => '../templates/template-client-portal.php',
                'post_type'  => 'page',
                'post_status'=> 'publish',
                'numberposts'=> 1,
            ];

            // Build the parent menu item using the same structure as other menu items.
            $menu_classes = isset( $args->menu_class ) ? esc_attr( $args->menu_class ) : '';
            $add_li_classes = isset( $args->add_li_class ) ? esc_attr( $args->add_li_class ) : '';
            $parent_item  = '<li id="menu-item-client-portal" class="menu-item --menu-item-type-custom --menu-item-object-custom menu-item-has-children '.$add_li_classes.' --link-featured">';
            $parent_item .= '<a href="' . ( !empty( $template_pages ) ? esc_url( get_permalink( $template_page->ID ) ) : '#' ) . '"><i class="bi bi-person-fill-lock fs-3 lh-0"></i></a>';

            // Start sub-menu.
            $parent_item .= '<ul class="sub-menu">';

            $template_pages = get_posts( $args_template );
            if ( !empty( $template_pages ) ) {
                $template_page = reset($template_pages);
                $parent_item .= sprintf(
                    '<li class="menu-item menu-item-type-custom menu-item-object-custom"><a href="%s">%s</a></li>',
                    esc_url( get_permalink( $template_page->ID ) ),
                    esc_html( $template_page->post_title )
                );
            }
            
            $private_pages = get_pages( [
                'post_status' => 'private',
            ] );

            foreach ( $private_pages as $page ) {
                $parent_item .= sprintf(
                    '<li id="menu-item-client-portal-%1$d" class="menu-item menu-item-type-post_type menu-item-object-page"><a href="%2$s">%3$s</a></li>',
                    esc_attr( $page->ID ),
                    esc_url( get_permalink( $page->ID ) ),
                    esc_html( $page->post_title )
                );
            }

            // Add logout link.
            $parent_item .= '<li class="menu-item menu-item-type-custom menu-item-object-custom"><a href="' . esc_url( wp_logout_url() ) . '">' . esc_html__( 'Logout', 'wacp' ) . '</a></li>';

            // End sub-menu and parent item.
            $parent_item .= '</ul></li>';

            // Return 
            $items .= $parent_item;
        } else {

            // Add a link to page where the _wp_page_template (page template slug ) is : template-client-portal.php
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

                // Build the parent menu item using the same structure as other menu items.
                $menu_classes = isset( $args->menu_class ) ? esc_attr( $args->menu_class ) : '';
                $add_li_classes = isset( $args->add_li_class ) ? esc_attr( $args->add_li_class ) : '';
                $parent_item  = '<li id="menu-item-client-portal" class="menu-item --menu-item-type-custom --menu-item-object-custom menu-item-has-children '.$add_li_classes.' --link-featured">';
                $parent_item .= '<a href="' . esc_url( get_permalink( $template_page->ID ) ) . '"><i class="bi bi-person-fill-lock fs-3 lh-0"></i></a>';
                // esc_html( $template_page->post_title )
                
                // End sub-menu and parent item.
                $parent_item .= '</li>';

                // Return 
                $items .= $parent_item;
            }
        }

        return $items;
    }
}

// Initialize the menu functionality.
Wa_Client_Portal_Menu::init();

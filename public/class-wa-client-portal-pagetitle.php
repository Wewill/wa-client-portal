<?php
/**
 * Handles the page titles for private pages.
 *
 * @package Wa_Client_Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Removes "Private :" prefix from private page titles in frontend pages
add_filter( 'private_title_format', function ( $format ) {
    return '%s';
} );

// Add a small h6 title before the title of private pages
add_filter('the_title', function($title, $post_id) {
    if (!is_admin() && is_singular() && in_the_loop() && get_post_status($post_id) === 'private') {
        // Output only once, before main title
        static $printed = false;
        if (!$printed) {
            echo '<h6 class="private-page headflat fs-2 d-inline mb-0">' . __('My account', 'wacp') . '</h6>';
            $printed = true;
        }
    }
    return $title;
}, 9, 2);
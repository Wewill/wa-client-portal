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
	$post = get_post($post_id);
	if ($post && $post->post_status === 'private' && !is_admin()) {
		$title = '<h6 class="private-page headline">' . __('My account', 'wacp') . '</h6>' . $title;
	}
	return $title;
}, 10, 2);
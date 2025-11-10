<?php
/**
 * Global utility functions for WA Client Portal
 *
 * This file contains global helper functions that are available
 * throughout the entire plugin (admin, public, and frontend).
 *
 * @link       https://www.wilhemarnoldy.fr
 * @since      1.3.0
 * @package    Wa_Client_Portal
 * @subpackage Wa_Client_Portal/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Get the URL of the client portal page.
 *
 * Finds the page using the client portal template and returns its permalink.
 * Falls back to a default URL if no portal page is found.
 *
 * @since  1.3.0
 * @param  string $fallback_url Optional. The fallback URL if no portal page is found. Default is home_url('/client-portal/').
 * @return string The URL of the client portal page.
 */
function wacp_get_portal_page_url( $fallback_url = '' ) {
	$args = array(
		'meta_key'    => '_wp_page_template',
		'meta_value'  => '../templates/template-client-portal.php',
		'post_type'   => 'page',
		'post_status' => 'publish',
		'numberposts' => 1,
	);
	$portal_page = get_posts( $args );
	if ( ! empty( $portal_page ) && isset( $portal_page[0]->ID ) ) {
		return get_permalink( $portal_page[0]->ID );
	}

	// Use provided fallback or default to home_url('/client-portal/')
	if ( empty( $fallback_url ) ) {
		$fallback_url = home_url(); //home_url( '/client-portal/' );
	}

	return $fallback_url;
}

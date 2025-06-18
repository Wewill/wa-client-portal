<?php
/**
 * Handles custom roles for WA Client Portal plugin.
 */

/**
 * Set default role to "client-portal" on registration
 */
function wa_client_portal_set_default_role($user_id) {
	$user = new WP_User($user_id);
	$user->set_role('client-portal');
}
add_action('user_register', 'wa_client_portal_set_default_role');

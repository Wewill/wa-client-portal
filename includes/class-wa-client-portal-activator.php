<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.wilhemarnoldy.fr
 * @since      1.0.0
 *
 * @package    Wa_Client_Portal
 * @subpackage Wa_Client_Portal/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wa_Client_Portal
 * @subpackage Wa_Client_Portal/includes
 * @author     Wilhem Arnoldy <contact@wilhemarnoldy.fr>
 */
class Wa_Client_Portal_Activator {

	/**
	 * Plugin activation handler
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Remove the role if it exists to ensure clean capabilities
		if (get_role('client-portal')) {
			remove_role('client-portal');
		}

		// Base capabilities from subscriber
		$subscriber = get_role('subscriber');
		$capabilities = $subscriber ? $subscriber->capabilities : array();

		// Add custom capabilities for client-portal role
		$capabilities = array_merge($capabilities, array(
			'read' => true,
			'read_private_posts' => true,
			'read_private_pages' => true,
			'client_portal_access' => true
		));

		// Add the role with updated capabilities
		add_role(
			'client-portal',
			__('Client Portal', 'wacp'),
			$capabilities
		);

		// Force refresh of permalinks
		flush_rewrite_rules();
	}

}

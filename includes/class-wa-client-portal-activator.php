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
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		// Add custom role for clients (base on subscriber capabilities)
		$subscriber = get_role( 'subscriber' );
		$caps = $subscriber ? $subscriber->capabilities : array( 'read' => true );

		// Grant read-related capabilities for custom post types.
		// Depending on how your CPTs were registered, you may need singular or plural capability keys.
		$caps['read_film']             = true;
		$caps['read_films']            = true;
		// $caps['read_private_film']     = true;
		// $caps['read_private_films']    = true;

		$caps['read_projection']       = true;
		$caps['read_projections']      = true;
		// $caps['read_private_projection']= true;
		// $caps['read_private_projections']= true;

		// Grant taxonomy assignment capabilities (names must match the capabilities used when registering the taxonomy).
		// Common capability keys are 'assign_<taxonomy>' or 'assign_<taxonomy>s' depending on registration.
		$caps['assign_section'] = true;
		$caps['assign_sections'] = true;
		$caps['assign_room'] = true;
		$caps['assign_rooms'] = true;

		// Create the role if it doesn't exist, otherwise update its capabilities.
		if ( ! get_role( 'client-portal' ) ) {
			add_role(
				'client-portal',
				__( 'Client Portal', 'wacp' ),
				$caps
			);
		} else {
			$role = get_role( 'client-portal' );
			foreach ( $caps as $cap => $grant ) {
				if ( $grant ) {
					$role->add_cap( $cap );
				} else {
					$role->remove_cap( $cap );
				}
			}
		}

	}

}

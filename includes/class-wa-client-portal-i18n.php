<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.wilhemarnoldy.fr
 * @since      1.0.0
 *
 * @package    Wa_Client_Portal
 * @subpackage Wa_Client_Portal/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wa_Client_Portal
 * @subpackage Wa_Client_Portal/includes
 * @author     Wilhem Arnoldy <contact@wilhemarnoldy.fr>
 */
class Wa_Client_Portal_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wa-client-portal',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}

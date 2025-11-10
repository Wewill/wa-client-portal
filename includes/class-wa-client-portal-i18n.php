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

		// Get the locale and build the path explicitly
		$locale = determine_locale();
		$mofile = plugin_dir_path( dirname( __FILE__ ) ) . 'languages/wacp-' . $locale . '.mo';

		// Load the .mo file directly if it exists
		if ( file_exists( $mofile ) ) {
			load_textdomain( 'wacp', $mofile );
		}

		// Also use the standard method as fallback
		load_plugin_textdomain(
			'wacp',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}

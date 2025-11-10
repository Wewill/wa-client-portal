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

		// Debug: Log translation loading attempt
		$this->log_translation_debug( 'Starting translation load' );

		// Get the locale
		$locale = determine_locale();
		$this->log_translation_debug( 'Locale detected: ' . $locale );

		// Build the path
		$mofile_local = dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/';
		$mofile_global = WP_LANG_DIR . '/plugins/';
		$domain = 'wacp';

		$this->log_translation_debug( 'Local path: ' . $mofile_local );
		$this->log_translation_debug( 'Plugin dir path: ' . plugin_dir_path( dirname( __FILE__ ) ) );

		// Check if local .mo file exists
		$local_mo_file = plugin_dir_path( dirname( __FILE__ ) ) . 'languages/' . $domain . '-' . $locale . '.mo';
		$this->log_translation_debug( 'Checking local .mo file: ' . $local_mo_file );
		$this->log_translation_debug( 'Local .mo file exists: ' . ( file_exists( $local_mo_file ) ? 'YES' : 'NO' ) );

		if ( file_exists( $local_mo_file ) ) {
			$this->log_translation_debug( 'Local .mo file is readable: ' . ( is_readable( $local_mo_file ) ? 'YES' : 'NO' ) );
			$this->log_translation_debug( 'Local .mo file size: ' . filesize( $local_mo_file ) . ' bytes' );
		}

		// Check if global .mo file exists
		$global_mo_file = $mofile_global . $domain . '-' . $locale . '.mo';
		$this->log_translation_debug( 'Checking global .mo file: ' . $global_mo_file );
		$this->log_translation_debug( 'Global .mo file exists: ' . ( file_exists( $global_mo_file ) ? 'YES' : 'NO' ) );

		// Attempt to load
		$loaded = load_plugin_textdomain(
			$domain,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

		$this->log_translation_debug( 'load_plugin_textdomain returned: ' . ( $loaded ? 'TRUE' : 'FALSE' ) );

		// Test a translation
		$test_string = __( 'Access denied.', 'wacp' );
		$this->log_translation_debug( 'Test translation of "Access denied.": ' . $test_string );

		// Check if domain is loaded
		global $l10n;
		$this->log_translation_debug( 'Is domain "wacp" loaded in $l10n: ' . ( isset( $l10n['wacp'] ) ? 'YES' : 'NO' ) );

		if ( isset( $l10n['wacp'] ) ) {
			$mo = $l10n['wacp'];
			$this->log_translation_debug( 'MO object class: ' . get_class( $mo ) );

			// Try to get some info about loaded translations
			if ( method_exists( $mo, 'get_header' ) ) {
				$this->log_translation_debug( 'MO Project-Id-Version: ' . $mo->get_header( 'Project-Id-Version' ) );
			}
		}

		$this->log_translation_debug( '=== Translation loading completed ===' );

	}

	/**
	 * Log translation debug information.
	 *
	 * @since    1.0.0
	 * @param    string    $message    The debug message to log.
	 */
	private function log_translation_debug( $message ) {
		// Only log if WP_DEBUG is enabled
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// Write to debug log
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[WACP i18n] ' . $message );
		}

		// Store in option for admin display
		$logs = get_option( 'wacp_i18n_debug_logs', array() );
		$logs[] = array(
			'timestamp' => current_time( 'mysql' ),
			'message' => $message
		);

		// Keep only last 50 logs
		if ( count( $logs ) > 50 ) {
			$logs = array_slice( $logs, -50 );
		}

		update_option( 'wacp_i18n_debug_logs', $logs );
	}

	/**
	 * Clear debug logs.
	 *
	 * @since    1.0.0
	 */
	public static function clear_debug_logs() {
		delete_option( 'wacp_i18n_debug_logs' );
	}



}

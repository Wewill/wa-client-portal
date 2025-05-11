<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.wilhemarnoldy.fr
 * @since             1.0.0
 * @package           Wa_Client_Portal
 *
 * @wordpress-plugin
 * Plugin Name:       WA Private Client Portal
 * Plugin URI:        https://www.wilhemarnoldy.fr
 * Description:       WordPress Client Portal Plugin that creates private pages for all users that only an administrator can edit.
 * Version:           1.0.0
 * Author:            Wilhem Arnoldy
 * Author URI:        https://www.wilhemarnoldy.fr/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wa-client-portal
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WA_CLIENT_PORTAL_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wa-client-portal-activator.php
 */
function activate_wa_client_portal() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wa-client-portal-activator.php';
	Wa_Client_Portal_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wa-client-portal-deactivator.php
 */
function deactivate_wa_client_portal() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wa-client-portal-deactivator.php';
	Wa_Client_Portal_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wa_client_portal' );
register_deactivation_hook( __FILE__, 'deactivate_wa_client_portal' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wa-client-portal.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wa_client_portal() {

	$plugin = new Wa_Client_Portal();
	$plugin->run();

}
run_wa_client_portal();

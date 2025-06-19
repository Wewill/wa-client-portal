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
 * Version:           1.1.0
 * Author:            Wilhem Arnoldy
 * Author URI:        https://www.wilhemarnoldy.fr/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wacp
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
define( 'WA_CLIENT_PORTAL_VERSION', '1.1.0' );

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

// Include template class for custom page templates.
require plugin_dir_path( __FILE__ ) . '/includes/class-wa-client-portal-template.php';

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

// Auto-generate username from first and last name on registration when user login is empty 
add_action('init', function() {
    if (
        isset($_POST['user_email'], $_POST['first_name'], $_POST['last_name']) &&
        isset($_GET['action']) && $_GET['action'] === 'register' &&
        empty($_POST['user_login'])
    ) {
        $first = sanitize_user(strtolower(trim($_POST['first_name'])));
        $last = sanitize_user(strtolower(trim($_POST['last_name'])));
        $username = $first . '.' . $last;
        $base_username = $username;
        $i = 1;
        while (username_exists($username)) {
            $username = $base_username . $i;
            $i++;
        }
        $_POST['user_login'] = $username;
    }
});

// Save extra registration fields to user meta
add_action('user_register', function($user_id) {
	$prefix = 'wacp-';
    if (isset($_POST['first_name'])) {
        update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
    }
    if (isset($_POST['last_name'])) {
        update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
    }
    if (isset($_POST['user_entity'])) {
        update_user_meta($user_id, $prefix.'entity', sanitize_text_field($_POST['user_entity']));
    }
    if (isset($_POST['user_media'])) {
        update_user_meta($user_id, $prefix.'media', sanitize_text_field($_POST['user_media']));
    }
    if (isset($_POST['user_phone'])) {
        update_user_meta($user_id, $prefix.'phone', sanitize_text_field($_POST['user_phone']));
    }
});

// Redirige les utilisateurs 'client-portal' vers la page portail après connexion
add_filter('login_redirect', function($redirect_to, $request, $user) {
    if (isset($user->roles) && in_array('client-portal', $user->roles)) {
        // Recherche la page qui utilise le template 'Client portal content'
		$args_template = [
			'meta_key'   => '_wp_page_template',
			'meta_value' => '../templates/template-client-portal.php',
			'post_type'  => 'page',
			'post_status'=> 'publish',
			'numberposts'=> 1,
		];
        $portal_page = get_posts($args);
        if (!empty($portal_page)) {
            return get_permalink($portal_page[0]->ID);
        }
    }
    return $redirect_to;
}, 10, 3);
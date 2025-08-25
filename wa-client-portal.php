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
// Not used anymore with magic link 
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

// // Save extra registration fields to user meta
// add_action('user_register', function($user_id) {
// 	$prefix = 'wacp-';
//     if (isset($_POST['first_name'])) {
//         update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
//     }
//     if (isset($_POST['last_name'])) {
//         update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
//     }
//     if (isset($_POST['user_entity'])) {
//         update_user_meta($user_id, $prefix.'entity', sanitize_text_field($_POST['user_entity']));
//     }
//     if (isset($_POST['user_media'])) {
//         update_user_meta($user_id, $prefix.'media', sanitize_text_field($_POST['user_media']));
//     }
//     if (isset($_POST['user_phone'])) {
//         update_user_meta($user_id, $prefix.'phone', sanitize_text_field($_POST['user_phone']));
//     }
// });


// Traitement du lien magique
function wacp_handle_magic_login() {
    // Find the page using the 'template-client-portal.php' template
    $args = [
        'meta_key'    => '_wp_page_template',
        'meta_value'  => '../templates/template-client-portal.php',
        'post_type'   => 'page',
        'post_status' => 'publish',
        'numberposts' => 1,
    ];
    $portal_page = get_posts($args);

	// If the user is already logged in, do nothing
	// We do not want logged-in users to be redirected to the portal
	if (is_user_logged_in()) return;

	// Check if the magic login cookie exists
	if (!empty($_COOKIE['magic_login_remember'])) {
		$data = json_decode(base64_decode($_COOKIE['magic_login_remember']), true);
		if (isset($data['user_id'], $data['token'])) {
			$expected_token = hash_hmac('sha256', $data['user_id'], AUTH_KEY);
			if (hash_equals($expected_token, $data['token'])) {
				$user = get_user_by('ID', $data['user_id']);
				if ($user && in_array('client-portal', $user->roles)) {
					// Automatic login
					wp_set_auth_cookie($user->ID, true);
					wp_set_current_user($user->ID);
				}
			}
		}
	}

	// Check if we have a magic link in the URL
	if (
		isset($_GET['magic_login'], $_GET['token'], $_GET['user_id']) &&
		$_GET['magic_login'] == '1'
	) {

		$user_id = intval($_GET['user_id']);
		$token = sanitize_text_field($_GET['token']);

		$saved_token = get_user_meta($user_id, 'magic_login_token', true);
		$expires = get_user_meta($user_id, 'magic_login_token_expires', true);

		if (!$saved_token || !$expires || time() > $expires) {
            $redirect_url = !empty($portal_page) ? get_permalink($portal_page[0]->ID) : esc_url(site_url());
            wp_die(sprintf(__("This link has expired. <a href='%s'>Resend a new link?</a>", 'wacp'), $redirect_url));
		}

		if (!hash_equals($saved_token, $token)) {
			wp_die(__("Invalid login link.", 'wacp'));
		}

		// Check that the user has ONLY the client-portal role (not admin, etc.)
		$user = get_user_by('ID', $user_id);
		if (!$user || !in_array('client-portal', (array)$user->roles) || count($user->roles) !== 1) {
			wp_die(__("Access denied.", 'wacp'));
		}

		// Login
		wp_set_auth_cookie($user_id, true);
		wp_set_current_user($user_id);

		// Create custom cookie (duration: 30 days)
		$cookie_value = base64_encode(json_encode([
			'user_id' => $user_id,
			'token' => hash_hmac('sha256', $user_id, AUTH_KEY), // Simple signature
		]));
		setcookie('magic_login_remember', $cookie_value, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
		update_user_meta($user_id, 'magic_login_cookie_expires', time() + (30 * DAY_IN_SECONDS));

		// (Optional) Invalidate token after 1 use:
		// delete_user_meta($user_id, 'magic_login_token');
		// delete_user_meta($user_id, 'magic_login_token_expires');

		// Redirect to the client portal page 
        // Find the page using the 'template-client-portal.php' template
        $args = [
            'meta_key'    => '_wp_page_template',
            'meta_value'  => '../templates/template-client-portal.php',
            'post_type'   => 'page',
            'post_status' => 'publish',
            'numberposts' => 1,
        ];

        $portal_page = get_posts($args);
        if (!empty($portal_page)) {
            wp_redirect(get_permalink($portal_page[0]->ID));
        } else {
            wp_redirect(home_url());
        }
		exit;
	}
}
add_action('init', 'wacp_handle_magic_login');

// Uncomment to clear the magic login cookie on logout
add_action('wp_logout', function () {
    setcookie('magic_login_remember', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
	// Also clear the cookie expire ts in user meta
	if (is_user_logged_in()) {
		delete_user_meta(get_current_user_id(), 'magic_login_cookie_expires');
	}
});

// Redirect 'client-portal' users to the portal page after login
// Not REALLY used anymore with magic link 
add_filter('login_redirect', function($redirect_to, $request, $user) {
    if (is_wp_error($user) || !isset($user->roles) || !in_array('client-portal', $user->roles)) {
        return $redirect_to;
    }

    // Find the page using the 'template-client-portal.php' template
    $args = [
        'meta_key'    => '_wp_page_template',
		'meta_value' => '../templates/template-client-portal.php',
        'post_type'   => 'page',
        'post_status' => 'publish',
        'numberposts' => 1,
    ];

    $portal_page = get_posts($args);
    if (!empty($portal_page)) {
        return get_permalink($portal_page[0]->ID);
    }

    return $redirect_to;
}, 10, 3);

// Block frontend access to register action 
// but do not block registration
add_action('login_form_register', function() {
    if (isset($_GET['action']) && $_GET['action'] === 'register') {
		wp_die("Access denied.");
        wp_redirect(home_url()); // redirection vers l'accueil
        exit;
    }
});

// Display an admin notice to inform if resgistration is not allowed in this website 
add_action('admin_notices', function() {
	echo get_option('users_can_register');
	if (get_option('users_can_register') != 1) {
		echo '<div class="notice notice-warning is-dismissible">
			<p>' . __('Warning: User registration is disabled. Please enable it in Settings > General to allow users to register.', 'wacp') . '</p>
		</div>';
	}
});
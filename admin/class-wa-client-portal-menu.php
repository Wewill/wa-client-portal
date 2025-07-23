<?php
/**
 * Add admin menu for client users
 */
function wa_client_portal_admin_menu() {
	add_menu_page(
		__('Members', 'wacp'),
		__('Members', 'wacp'),
		'add_menu_page',
		'wa-client-portal-clients',
		'wa_client_portal_clients_page',
		'dashicons-editor-table',
		71 // 71 place l'item juste après users.php (utilisateurs)
	);
}
add_action('admin_menu', 'wa_client_portal_admin_menu');

/**
 * Admin page callback
 */
function wa_client_portal_clients_page() {
	require plugin_dir_path( __FILE__ ) . '/wa-client-portal-clients-page.php';
}
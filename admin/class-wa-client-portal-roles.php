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

// Cache tous les metaboxes du dashboard et ajoute un metabox personnalisé pour les utilisateurs 'client-portal'
add_action('wp_dashboard_setup', function() {
	if (current_user_can('client-portal')) {
		global $wp_meta_boxes;
		// Supprime tous les widgets du dashboard
		$wp_meta_boxes['dashboard'] = array();
		// Ajoute un widget personnalisé
		wp_add_dashboard_widget('client_portal_dashboard_widget', __('Client portal', 'wacp'), function() {
			// Recherche la page avec le template client portal
			$args = [
				'meta_key'   => '_wp_page_template',
				'meta_value' => '../templates/template-client-portal.php',
				'post_type'  => 'page',
				'post_status'=> 'publish',
				'numberposts'=> 1,
			];
			$pages = get_posts($args);
			if (!empty($pages)) {
				$url = get_permalink($pages[0]->ID);
				echo '<a href="' . esc_url($url) . '" class="button button-primary">' . esc_html__('Access my client portal', 'wacp') . '</a>';
			} else {
				echo esc_html__('No client portal page found.', 'wacp');
			}
		});
	}
});
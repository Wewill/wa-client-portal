<?php
/**
 * Handles admin notices for the plugin
 */

// Display an admin notice to inform if registration is not allowed in this website 
add_action('admin_notices', function() {
	if (get_option('users_can_register') != 1) {
		echo '<div class="notice notice-warning is-dismissible">
			<p><strong>' . __('WA Client Portal Plugin', 'wacp') . ' </strong>' . __('Warning: User registration is disabled. Please enable it in Settings > General to allow users to register.', 'wacp') . '</p>
		</div>';
	}
});
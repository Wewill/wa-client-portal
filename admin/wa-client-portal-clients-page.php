<?php
// List all users with the "client" role
$clients = get_users(['role' => 'client-portal']);
$prefix = 'wacp-';
?>
<div class="wrap">
	<h1><?php esc_html_e('Members', 'wacp'); ?></h1>
	<table class="widefat fixed striped" style="margin-top: 20px;">
		<thead>
			<tr>
				<th><?php esc_html_e('#', 'wacp'); ?></th>
				<th><?php esc_html_e('Lastname', 'wacp'); ?></th>
				<th><?php esc_html_e('Firstname', 'wacp'); ?></th>
				<th><?php esc_html_e('Entity', 'wacp'); ?></th>
				<th><?php esc_html_e('Media', 'wacp'); ?></th>
				<th><?php esc_html_e('Phone', 'wacp'); ?></th>
				<th><?php esc_html_e('E-mail', 'wacp'); ?></th>
				<th><?php esc_html_e('Magic Login', 'wacp'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($clients as $client): ?>
				<tr>
					<td>
						<?php if ( current_user_can('list_users') ) : ?>
							<a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $client->ID)); ?>" class="button button-primary button-small">
								<?php echo esc_html($client->ID); ?>
							</a>
						<?php else : ?>
							<button class="button button-small" disabled>
								<?php echo esc_html($client->ID); ?>
							</button>
						<?php endif; ?>	
					</td>
					<td><?php echo esc_html($client->last_name); ?></td>
					<td><?php echo esc_html($client->first_name); ?></td>
					<td><?php echo esc_html(get_user_meta($client->ID, $prefix.'entity', true)); ?></td>
					<td><?php echo esc_html(get_user_meta($client->ID, $prefix.'media', true)); ?></td>
					<td><?php echo esc_html(get_user_meta($client->ID, $prefix.'phone', true)); ?></td>
					<td><?php echo esc_html($client->user_email); ?></td>
					<td>
						<?php
							$token = get_user_meta($client->ID, 'magic_login_token', true);
							$token_expires = get_user_meta($client->ID, 'magic_login_token_expires', true);
							$cookie_expires = get_user_meta($client->ID, 'magic_login_cookie_expires', true);
							$code_style = $cookie_expires ? 'style="color: green;"' : '';
						?>
						<?php if (empty($token)): ?>
							-
						<?php else: ?>
							<code <?php echo $code_style; ?>><small>
								<strong><?php echo esc_html($token); ?></strong><br>
								<strong><?php esc_html_e('Token Expires:', 'wacp'); ?></strong>
								<?php echo $token_expires ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), intval($token_expires))) : '-'; ?><br>
								<strong><?php esc_html_e('Cookie Expires:', 'wacp'); ?></strong>
								<?php echo $cookie_expires ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), intval($cookie_expires))) : '-'; ?>
							</small></code>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

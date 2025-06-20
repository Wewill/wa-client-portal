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
			</tr>
		</thead>
		<tbody>
			<?php foreach ($clients as $client): ?>
				<tr>
					<td>
						<a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $client->ID)); ?>" class="button button-primary button-small">
							<?php echo esc_html($client->ID); ?>
						</a>
					</td>
					<td><?php echo esc_html($client->last_name); ?></td>
					<td><?php echo esc_html($client->first_name); ?></td>
					<td><?php echo esc_html(get_user_meta($client->ID, $prefix.'entity', true)); ?></td>
					<td><?php echo esc_html(get_user_meta($client->ID, $prefix.'media', true)); ?></td>
					<td><?php echo esc_html(get_user_meta($client->ID, $prefix.'phone', true)); ?></td>
					<td><?php echo esc_html($client->user_email); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

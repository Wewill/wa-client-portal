<?php
// List all users with the "client" role
$clients = get_users(['role' => 'client-portal']);
$prefix = 'wacp-';

// Calculate statistics
$total_clients = count($clients);
$clients_with_favorite_films = 0;
$clients_who_clicked_login = 0;
$film_favorites_count = [];

foreach ($clients as $client) {
	$favorite_films = get_user_meta($client->ID, $prefix . 'favorite_films', true);
	if (!empty($favorite_films) && is_array($favorite_films)) {
		$clients_with_favorite_films++;

		// Count each film
		foreach ($favorite_films as $film_id) {
			if (!isset($film_favorites_count[$film_id])) {
				$film_favorites_count[$film_id] = 0;
			}
			$film_favorites_count[$film_id]++;
		}
	}

	$cookie_expires = get_user_meta($client->ID, 'magic_login_cookie_expires', true);
	if (!empty($cookie_expires)) {
		$clients_who_clicked_login++;
	}
}

// Find the most favorited film
$most_favorited_film_id = null;
$most_favorited_film_count = 0;
foreach ($film_favorites_count as $film_id => $count) {
	if ($count > $most_favorited_film_count) {
		$most_favorited_film_id = $film_id;
		$most_favorited_film_count = $count;
	}
}
$most_favorited_film_title = $most_favorited_film_id ? get_the_title($most_favorited_film_id) : '-';
?>
<div class="wrap">
	<h1><?php esc_html_e('Members', 'wacp'); ?></h1>

	<!-- Statistics Section -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; margin-bottom: 20px;">
		<div style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
			<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e('Total Members', 'wacp'); ?></h3>
			<p style="margin: 0; font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo esc_html($total_clients); ?></p>
		</div>

		<div style="background: #fff; padding: 20px; border-left: 4px solid #72aee6; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
			<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e('With Favorite Films', 'wacp'); ?></h3>
			<p style="margin: 0; font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo esc_html($clients_with_favorite_films); ?></p>
		</div>

		<div style="background: #fff; padding: 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
			<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e('Clicked Login Link', 'wacp'); ?></h3>
			<p style="margin: 0; font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo esc_html($clients_who_clicked_login); ?></p>
		</div>

		<div style="background: #fff; padding: 20px; border-left: 4px solid #40ff00ff; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
			<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e('Most Favorited Film', 'wacp'); ?></h3>
			<p style="margin: 0; font-size: 16px; font-weight: 600; color: #1d2327; line-height: 1.4;">
				<?php if ($most_favorited_film_id): ?>
					<?php echo esc_html($most_favorited_film_title); ?>
					<br>
					<span style="font-size: 14px; color: #646970; font-weight: 400;">
						<?php
						/* translators: %d: number of times */
						echo esc_html(sprintf(_n('%d time', '%d times', $most_favorited_film_count, 'wacp'), $most_favorited_film_count));
						?>
					</span>
				<?php else: ?>
					-
				<?php endif; ?>
			</p>
		</div>
	</div>

	<table class="widefat fixed striped" style="margin-top: 20px;">
		<thead>
			<tr>
				<th><?php esc_html_e('#', 'wacp'); ?></th>
				<th><?php esc_html_e('Lastname', 'wacp'); ?></th>
				<th><?php esc_html_e('Firstname', 'wacp'); ?></th>
				<th><?php esc_html_e('Favorite films', 'wacp'); ?></th>
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
					<td>
						<?php
							$favorite_films = get_user_meta($client->ID, $prefix . 'favorite_films', true);
							if ( ! empty( $favorite_films ) && is_array( $favorite_films ) ) {
								$film_count = count( $favorite_films );
								/* translators: %d: number of films */
								echo '<strong>' . esc_html( sprintf( _n( '%d film', '%d films', $film_count, 'wacp' ), $film_count ) ) . '</strong> • ';
								$film_titles = [];
								foreach ( $favorite_films as $film_id ) {
									$film = get_post( $film_id );
									if ( $film ) {
										$film_titles[] = get_the_title( $film_id );
									}
								}
								echo esc_html( implode( ', ', $film_titles ) );
							} else {
								echo '-';
							}
						?>
					</td>
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

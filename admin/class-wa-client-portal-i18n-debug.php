<?php

/**
 * The i18n debug page functionality
 *
 * @link       https://www.wilhemarnoldy.fr
 * @since      1.3.0
 *
 * @package    Wa_Client_Portal
 * @subpackage Wa_Client_Portal/admin
 */

/**
 * The i18n debug page functionality
 *
 * @package    Wa_Client_Portal
 * @subpackage Wa_Client_Portal/admin
 * @author     Wilhem Arnoldy <contact@wilhemarnoldy.fr>
 */
class Wa_Client_Portal_i18n_Debug {

	/**
	 * Initialize the class.
	 *
	 * @since    1.3.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_debug_page' ) );
		add_action( 'admin_post_wacp_clear_i18n_logs', array( $this, 'handle_clear_logs' ) );
	}

	/**
	 * Add debug page to admin menu.
	 *
	 * @since    1.3.0
	 */
	public function add_debug_page() {
		add_submenu_page(
			'tools.php',
			__( 'WACP Translation Debug', 'wacp' ),
			__( 'WACP i18n Debug', 'wacp' ),
			'manage_options',
			'wacp-i18n-debug',
			array( $this, 'render_debug_page' )
		);
	}

	/**
	 * Handle clear logs request.
	 *
	 * @since    1.3.0
	 */
	public function handle_clear_logs() {
		// Check nonce
		if ( ! isset( $_POST['wacp_clear_logs_nonce'] ) || ! wp_verify_nonce( $_POST['wacp_clear_logs_nonce'], 'wacp_clear_i18n_logs' ) ) {
			wp_die( __( 'Security check failed', 'wacp' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wacp' ) );
		}

		// Clear logs
		Wa_Client_Portal_i18n::clear_debug_logs();

		// Redirect back
		wp_redirect( admin_url( 'tools.php?page=wacp-i18n-debug&cleared=1' ) );
		exit;
	}

	/**
	 * Render debug page.
	 *
	 * @since    1.3.0
	 */
	public function render_debug_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( isset( $_GET['cleared'] ) && $_GET['cleared'] == '1' ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php _e( 'Logs cleared successfully.', 'wacp' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="card">
				<h2><?php _e( 'Current Configuration', 'wacp' ); ?></h2>
				<table class="widefat">
					<tbody>
						<tr>
							<td><strong><?php _e( 'WordPress Locale', 'wacp' ); ?></strong></td>
							<td><code><?php echo esc_html( get_locale() ); ?></code></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Determined Locale', 'wacp' ); ?></strong></td>
							<td><code><?php echo esc_html( determine_locale() ); ?></code></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Site Language', 'wacp' ); ?></strong></td>
							<td><code><?php echo esc_html( get_bloginfo( 'language' ) ); ?></code></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'WP_DEBUG', 'wacp' ); ?></strong></td>
							<td><code><?php echo defined( 'WP_DEBUG' ) && WP_DEBUG ? 'TRUE' : 'FALSE'; ?></code></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'WP_DEBUG_LOG', 'wacp' ); ?></strong></td>
							<td><code><?php echo defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? 'TRUE' : 'FALSE'; ?></code></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Plugin Directory', 'wacp' ); ?></strong></td>
							<td><code><?php echo esc_html( plugin_dir_path( dirname( __FILE__ ) ) ); ?></code></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'Languages Directory', 'wacp' ); ?></strong></td>
							<td><code><?php echo esc_html( plugin_dir_path( dirname( __FILE__ ) ) . 'languages/' ); ?></code></td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="card" style="margin-top: 20px;">
				<h2><?php _e( 'Translation Files', 'wacp' ); ?></h2>
				<?php
				$locale = determine_locale();
				$languages_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'languages/';
				$mo_file = $languages_dir . 'wacp-' . $locale . '.mo';
				$po_file = $languages_dir . 'wacp-' . $locale . '.po';
				$l10n_file = $languages_dir . 'wacp-' . $locale . '.l10n.php';
				?>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php _e( 'File', 'wacp' ); ?></th>
							<th><?php _e( 'Path', 'wacp' ); ?></th>
							<th><?php _e( 'Exists', 'wacp' ); ?></th>
							<th><?php _e( 'Readable', 'wacp' ); ?></th>
							<th><?php _e( 'Size', 'wacp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><strong>.mo</strong></td>
							<td><code><?php echo esc_html( $mo_file ); ?></code></td>
							<td><?php echo file_exists( $mo_file ) ? '<span style="color:green;">✓ YES</span>' : '<span style="color:red;">✗ NO</span>'; ?></td>
							<td><?php echo file_exists( $mo_file ) && is_readable( $mo_file ) ? '<span style="color:green;">✓ YES</span>' : '<span style="color:red;">✗ NO</span>'; ?></td>
							<td><?php echo file_exists( $mo_file ) ? size_format( filesize( $mo_file ) ) : 'N/A'; ?></td>
						</tr>
						<tr>
							<td><strong>.po</strong></td>
							<td><code><?php echo esc_html( $po_file ); ?></code></td>
							<td><?php echo file_exists( $po_file ) ? '<span style="color:green;">✓ YES</span>' : '<span style="color:red;">✗ NO</span>'; ?></td>
							<td><?php echo file_exists( $po_file ) && is_readable( $po_file ) ? '<span style="color:green;">✓ YES</span>' : '<span style="color:red;">✗ NO</span>'; ?></td>
							<td><?php echo file_exists( $po_file ) ? size_format( filesize( $po_file ) ) : 'N/A'; ?></td>
						</tr>
						<tr>
							<td><strong>.l10n.php</strong></td>
							<td><code><?php echo esc_html( $l10n_file ); ?></code></td>
							<td><?php echo file_exists( $l10n_file ) ? '<span style="color:green;">✓ YES</span>' : '<span style="color:red;">✗ NO</span>'; ?></td>
							<td><?php echo file_exists( $l10n_file ) && is_readable( $l10n_file ) ? '<span style="color:green;">✓ YES</span>' : '<span style="color:red;">✗ NO</span>'; ?></td>
							<td><?php echo file_exists( $l10n_file ) ? size_format( filesize( $l10n_file ) ) : 'N/A'; ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="card" style="margin-top: 20px;">
				<h2><?php _e( 'Translation Test', 'wacp' ); ?></h2>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php _e( 'Original String', 'wacp' ); ?></th>
							<th><?php _e( 'Translated String', 'wacp' ); ?></th>
							<th><?php _e( 'Status', 'wacp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$test_strings = array(
							'Access denied.',
							'Invalid login link.',
							'Add to favorites',
							'Remove from favorites',
						);
						foreach ( $test_strings as $test_string ) {
							$translated = __( $test_string, 'wacp' );
							$is_translated = $translated !== $test_string;
							?>
							<tr>
								<td><code><?php echo esc_html( $test_string ); ?></code></td>
								<td><code><?php echo esc_html( $translated ); ?></code></td>
								<td><?php echo $is_translated ? '<span style="color:green;">✓ Translated</span>' : '<span style="color:orange;">⚠ Not translated</span>'; ?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

			<div class="card" style="margin-top: 20px;">
				<h2><?php _e( 'Loaded Text Domains', 'wacp' ); ?></h2>
				<?php
				global $l10n;
				?>
				<p><strong><?php _e( 'Is "wacp" domain loaded?', 'wacp' ); ?></strong> <?php echo isset( $l10n['wacp'] ) ? '<span style="color:green;">✓ YES</span>' : '<span style="color:red;">✗ NO</span>'; ?></p>
				<?php if ( isset( $l10n['wacp'] ) ) : ?>
					<p><strong><?php _e( 'MO Object Class:', 'wacp' ); ?></strong> <code><?php echo esc_html( get_class( $l10n['wacp'] ) ); ?></code></p>
					<?php if ( method_exists( $l10n['wacp'], 'get_header' ) ) : ?>
						<p><strong><?php _e( 'Project-Id-Version:', 'wacp' ); ?></strong> <code><?php echo esc_html( $l10n['wacp']->get_header( 'Project-Id-Version' ) ); ?></code></p>
					<?php endif; ?>
				<?php endif; ?>

				<details style="margin-top: 10px;">
					<summary><strong><?php _e( 'All Loaded Domains', 'wacp' ); ?></strong></summary>
					<ul>
						<?php
						if ( ! empty( $l10n ) ) {
							foreach ( array_keys( $l10n ) as $domain ) {
								echo '<li><code>' . esc_html( $domain ) . '</code></li>';
							}
						} else {
							echo '<li>' . __( 'No domains loaded', 'wacp' ) . '</li>';
						}
						?>
					</ul>
				</details>
			</div>

			<div class="card" style="margin-top: 20px;">
				<div style="display: flex; justify-content: space-between; align-items: center;">
					<h2><?php _e( 'Translation Loading Logs', 'wacp' ); ?></h2>
					<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="margin: 0;">
						<?php wp_nonce_field( 'wacp_clear_i18n_logs', 'wacp_clear_logs_nonce' ); ?>
						<input type="hidden" name="action" value="wacp_clear_i18n_logs">
						<button type="submit" class="button"><?php _e( 'Clear Logs', 'wacp' ); ?></button>
					</form>
				</div>

				<?php
				$logs = get_option( 'wacp_i18n_debug_logs', array() );
				if ( empty( $logs ) ) {
					echo '<p><em>' . __( 'No logs available. Make sure WP_DEBUG is enabled and reload a page to generate logs.', 'wacp' ) . '</em></p>';
				} else {
					echo '<table class="widefat">';
					echo '<thead><tr><th>' . __( 'Timestamp', 'wacp' ) . '</th><th>' . __( 'Message', 'wacp' ) . '</th></tr></thead>';
					echo '<tbody>';
					// Reverse to show newest first
					$logs = array_reverse( $logs );
					foreach ( $logs as $log ) {
						echo '<tr>';
						echo '<td><code>' . esc_html( $log['timestamp'] ) . '</code></td>';
						echo '<td><code>' . esc_html( $log['message'] ) . '</code></td>';
						echo '</tr>';
					}
					echo '</tbody></table>';
				}
				?>
			</div>

			<div class="card" style="margin-top: 20px;">
				<h2><?php _e( 'Instructions', 'wacp' ); ?></h2>
				<ol>
					<li><?php _e( 'Make sure WP_DEBUG and WP_DEBUG_LOG are enabled in your wp-config.php file:', 'wacp' ); ?>
						<pre style="background: #f5f5f5; padding: 10px; margin: 10px 0;">define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );</pre>
					</li>
					<li><?php _e( 'Reload any page on your site (front-end or admin) to trigger the translation loading.', 'wacp' ); ?></li>
					<li><?php _e( 'Come back to this page to see the logs.', 'wacp' ); ?></li>
					<li><?php _e( 'Check the "Translation Loading Logs" section above for detailed information about what happened during translation loading.', 'wacp' ); ?></li>
					<li><?php _e( 'Compare the logs between the site where translations work and the site where they don\'t work to identify the difference.', 'wacp' ); ?></li>
				</ol>
			</div>
		</div>

		<style>
			.card {
				background: white;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
				padding: 20px;
			}
			.card h2 {
				margin-top: 0;
			}
			.widefat td, .widefat th {
				padding: 8px 10px;
			}
		</style>
		<?php
	}
}

// Initialize the debug page
new Wa_Client_Portal_i18n_Debug();

<?php
/**
 * Template Name: Client portal content 
 * Display client portal page
 *
 * @package WCP
 */

defined('ABSPATH') || exit;

function wacp_enqueue_recaptcha_script() {
    if (wacp_get_recaptcha_site_key_from_setting_page()) { // Optional: load only when needed
        wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true);
    }
}
add_action('wp_enqueue_scripts', 'wacp_enqueue_recaptcha_script');

// Error messages array
$messages = [];

// Use Google reCAPTCHA v2 Checkbox
// Please check plugin settings : wacp_get_recaptcha_site_key_from_setting_page() &&  wacp_get_recaptcha_secret_key_from_setting_page()

// Function to verify reCAPTCHA
if ( empty(wacp_get_recaptcha_site_key_from_setting_page())  || empty(wacp_get_recaptcha_secret_key_from_setting_page()) ) {
	$messages[] = "<p style='margin:0;color:orange'>" . esc_html__('Admin notice : Google reCAPTCHA is not configured. Please check the portal plugin settings.', 'wacp') . "</p>";
} 

// Validate Google reCAPTCHA
$captcha_success = true;
$honeypot_success = true;
$resend_magic_email = isset($_POST['resend_magic_email']) ? intval($_POST['resend_magic_email']) : 0;
$create_magic_email = isset($_POST['create_magic_email']) ? intval($_POST['create_magic_email']) : 0;

if (!empty($_POST['magic_email']) && $create_magic_email === 1) {
	if ( isset($_POST['g-recaptcha-response'])) {

		$recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response']);
		$verify_response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
			'body' => [
				'secret' => wacp_get_recaptcha_secret_key_from_setting_page(),
				'response' => $recaptcha_response,
				'remoteip' => $_SERVER['REMOTE_ADDR']
			]
		]);

		if (!is_wp_error( $verify_response )) {
			$response_body = wp_remote_retrieve_body($verify_response);
			$result = json_decode($response_body);

			if (empty($result->success) ) {
				$messages[] = "<p style='margin:0;color:red'>" . esc_html__('Captcha verification failed. Please try again.', 'wacp') . "</p>";
				$captcha_success = false;
			}
		}

		if (is_wp_error( $verify_response )) {
			$messages[] = "<p style='margin:0;color:red'>" . esc_html__('Captcha response failed. Please try again.', 'wacp') . "</p>";
			$captcha_success = false;
		}
	} else {
		$messages[] = "<p style='margin:0;color:red'>" . esc_html__('Captcha missing. Please complete the captcha.', 'wacp') . "</p>";
		$captcha_success = false;
	}
}

// Check for spam bots using honeypot field
if (!empty($_POST['magic_email']) && !empty($_POST['hp_message'])) {
	$messages[] = "<p style='margin:0;color:red'>" . esc_html__('Spam detected. Please try again.', 'wacp') . "</p>";
	$honeypot_success = false;
}

// Form processing : we got an email from the form and no honeypot field filled & reCAPTCHA is valid
if (!empty($_POST['magic_email']) && ($captcha_success && $honeypot_success) ) {

	// Check if the email is valid
	if ( is_email($_POST['magic_email']) ) {

		$email = sanitize_email($_POST['magic_email']);
		$limit_key = 'magic_login_attempts_' . md5($email);
		$attempts = get_transient($limit_key) ?: 0;

		// Limit to 10 attempts / 24h
		if ($attempts >= 10) {
			$messages[] = "<p style='margin:0;color:red'>" . esc_html__("Too many requests today. Please try again tomorrow.", 'wacp') . "</p>";
		} else {
			$user = get_user_by('email', $email);

			// If coming from the registration form and the user already exists
			if ($create_magic_email === 1 && $user) {
				$messages[] = "<p style='margin:0;color:orange'>" . sprintf(
					__('An account already exists with the address <strong>%s</strong>.', 'wacp'),
					esc_html($email)
				) . "</p>";
				// Continue to send the magic link
			}

			// Create user if not existing and not a resend link request
			if (!$user && $resend_magic_email === 0) {
				// Create an automatic username from first and last name
				if (
					isset($_POST['first_name']) && isset($_POST['last_name']) &&
					trim(sanitize_text_field($_POST['first_name'])) !== '' &&
					trim(sanitize_text_field($_POST['last_name'])) !== ''
				) {
					// Sanitize first and last name
					$first_name = sanitize_key(sanitize_user(strtolower(sanitize_text_field($_POST['first_name']))));
					$last_name = sanitize_key(sanitize_user(strtolower(sanitize_text_field($_POST['last_name']))));
					$username = $first_name . '.' . $last_name;

					// Check if username already exists
					$base_username = $username;
					$i = 1;
					while (username_exists($username)) {
						$username = $base_username . $i;
						$i++;
					}

					// Create user with generated username
					$user_id = wp_create_user($username, wp_generate_password(), $email);

					// Update first and last name
					wp_update_user([
						'ID' => $user_id,
						'first_name' => $first_name,
						'last_name' => $last_name,
					]);
				} else {
					// If first and last name are not provided, use the email as username
					$username = sanitize_user($email);
					$user_id = wp_create_user($username, wp_generate_password(), $email);
				}

				// Set default role to 'client-portal'
				$user = new WP_User($user_id);
				$user->set_role('client-portal');

				// Save additional user meta
				$prefix = 'wacp-';
				if (isset($_POST['first_name'])) {
					update_user_meta($user_id, 'first_name', sanitize_user($_POST['first_name']));
				}
				if (isset($_POST['last_name'])) {
					update_user_meta($user_id, 'last_name', sanitize_user($_POST['last_name']));
				}
				if (isset($_POST['user_entity'])) {
					update_user_meta($user_id, $prefix . 'entity', sanitize_text_field($_POST['user_entity']));
				}
				if (isset($_POST['user_media'])) {
					update_user_meta($user_id, $prefix . 'media', sanitize_text_field($_POST['user_media']));
				}
				if (isset($_POST['user_phone'])) {
					update_user_meta($user_id, $prefix . 'phone', sanitize_text_field($_POST['user_phone']));
				}

				$user = get_user_by('ID', $user_id);
			}

			// If the user does not exist and this is a resend magic link request, show error
			if (!$user && $resend_magic_email === 1) {
				$messages[] = "<p style='margin:0;color:red'>" . sprintf(
					__('No user found with the email <strong>%s</strong>.', 'wacp'),
					esc_html($email)
				) . "</p>";
				$unknown_user = true;
			}

			// If this is a resend magic email request, flush the magic_login_remember cookie
			if ($resend_magic_email === 1) {
				setcookie('magic_login_remember', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
				// Also clear the cookie expire ts in user meta
				if ($user) {
					delete_user_meta($user->ID, 'magic_login_cookie_expires');
				}
			}

			// If the user exists and has the 'client-portal' role, proceed to send the magic login link
			if ( $user && in_array('client-portal', (array)$user->roles) ) {
				$user_id = $user->ID;


				// Find the page using the 'template-client-portal.php' template
				$args = [
					'meta_key'    => '_wp_page_template',
					'meta_value'  => '../templates/template-client-portal.php',
					'post_type'   => 'page',
					'post_status' => 'publish',
					'numberposts' => 1,
				];
				$portal_page = get_posts($args);


				// Generate token
				$token = bin2hex(random_bytes(32));
				update_user_meta($user_id, 'magic_login_token', $token);
				update_user_meta($user_id, 'magic_login_token_expires', time() + (6 * 30 * 24 * 60 * 60)); // 6 months

				// Send by email
				$url = add_query_arg([
					'magic_login' => 1,
					'token' => $token,
					'user_id' => $user_id,
				], site_url());

				// Préparer le contenu HTML du mail personnalisé
				$subject = esc_html__('Client Portal : your magic login link', 'wacp');
				$message = '<html><body>';
				$message .= '<div style="text-align:center;">';
				// Add centered logo
				$message .= '<div style="text-align:center;margin-bottom:50px;"><img src="https://www.artetjardins-hdf.com/wp-content/uploads/2018/03/Logotype_AJ_175px_x2.png" alt="Logo" style="max-width:175px;height:auto;"></div>';
				$message .= '<h2 style="color:#0d1724;">' . esc_html__('Your Magic Login Link', 'wacp') . '</h2>';
				$message .= '<p style="color:#0d1724;">' . esc_html__('Click the link below to log in securely to your client portal:', 'wacp') . '</p>';
				$message .= '<div style="text-align:center;margin-top:30px;margin-bottom:60px;"><p><a href="' . esc_url($url) . '" style="font-size:16px;background:#acb43f;color:#fff;padding:10px 20px;margin-top:10px;text-decoration:none;border-radius:4px;">' . esc_html__('Log in now', 'wacp') . '</a></p></div>';
				$message .= '<p style="color:#888;font-size:10px;margin:0px;">' . esc_html__('If you did not request this email, you can ignore it.', 'wacp') . '</p>';
				
				// Ajout du texte en petit avec lien vers la page client-portal
				$client_portal_url = !empty($portal_page) ? get_permalink($portal_page[0]->ID) : esc_url(site_url());

				$message .= '<p style="color:#888;font-size:10px;margin:0px;">' . sprintf(
					esc_html__('This login link may have expired. %s', 'wacp'),
					'<a href="' . esc_url($client_portal_url) . '">' . esc_html__('Resend a new link by email ?', 'wacp') . '</a>'
				) . '</p>';
				$message .= '</div>'; // End center
				$message .= '</body></html>';

				// Headers pour envoyer un mail HTML
				$headers = [
					'Content-Type: text/html; charset=UTF-8',
					'From: ' . html_entity_decode(get_bloginfo('name'), ENT_QUOTES, 'UTF-8') . ' <contact@artetjardins-hdf.com>'
				];

				// Envoi du mail personnalisé sans affecter les autres envois
				wp_mail($email, $subject, $message, $headers);

				$messages[] = "<p style='margin:0;color:green'>" . sprintf(
					__('A login link has been sent to <strong>%s</strong>. Check your inbox.', 'wacp'),
					esc_html($email)
				) . "</p>";

				// Increment counter
				set_transient($limit_key, $attempts + 1, DAY_IN_SECONDS);
			}
		}

	// Not valid email 
	} else {
		$messages[] = "<p style='margin:0;color:red'>" . __('Please enter a valid <b>e-mail</b> address.', 'wacp') . "</p>";
	}

// No email from form 
}

get_header();
?>

<div class="container">

<?php 
// Start the Loop.
while ( have_posts() ) :
	// echo '<h2>' . get_the_title() . '</h2>';
	the_post();

	        if ( !is_user_logged_in() ) {

				// Display introduction content meta fields page if existing : waff_page_public_content
				$public_content = get_post_meta( get_the_ID(), 'waff_page_public_content', true );
				if ( !empty( $public_content ) ) {
					echo apply_filters( 'the_content', do_shortcode( $public_content ) );
				}
						
				if (!empty($messages)) {
					echo '<div class="client-portal-messages" style="margin: 1rem 0; padding: 1rem 2rem; border: 1px solid var(--waff-color-layout-trans-4); border-radius: 5px; background: var(--waff-color-layout-trans-2)">';
					// Print messages if any.
					foreach ($messages as $msg) {
						echo wp_kses_post($msg);
					}
					echo '</div>'; // Messages end 
				}

				// Display the login form in a styled container.
				echo '<div class="client-portal-forms" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 0 auto;">';

				// Display a register form container 
				echo '<div class="register-form-container" style="margin: 1rem 0; padding: 2rem; border: 1px solid var(--waff-color-layout-trans-4); border-radius: 5px; background: var(--waff-color-layout-trans-2)">';
				echo '<i class="bi bi-key fs-1"></i>';
				echo '<h6 style="text-align: left;">' . esc_html__( 'New, register', 'wacp' ) . '</h6>';
				if ( get_option( 'users_can_register' ) ) {
					echo '<form  method="post" enctype="multipart/form-data">';
					// Username field removed, will be auto-generated
					// Email
					echo '<p><label for="magic_email">' . esc_html__( 'E-mail', 'wacp' ) . '</label><input type="email" name="magic_email" id="magic_email" class="input" required></p>';

					// Firstname and Lastname fields in two columns
					echo '<div style="display: flex; gap: 1rem;">';
					echo '<div style="flex:1;">';
					echo '<p><label for="first_name">' . esc_html__( 'Firstname', 'wacp' ) . '</label><input type="text" name="first_name" id="first_name" class="input" required></p>';
					echo '</div>';
					echo '<div style="flex:1;">';
					echo '<p><label for="last_name">' . esc_html__( 'Lastname', 'wacp' ) . '</label><input type="text" name="last_name" id="last_name" class="input" required></p>';
					echo '</div>';
					echo '</div>';

					// Phone field
					echo '<p><label for="user_phone">' . esc_html__( 'Phone', 'wacp' ) . '</label><input type="tel" name="user_phone" id="user_phone" class="input" required></p>';

					// Entity and Media fields in two columns
					echo '<div style="display: flex; gap: 1rem;">';
					echo '<div style="flex:1;">';
					echo '<p><label for="user_entity">' . esc_html__( 'Entity', 'wacp' ) . '</label><input type="text" name="user_entity" id="user_entity" class="input" required></p>';
					echo '</div>';
					echo '<div style="flex:1;">';
					echo '<p><label for="user_media">' . esc_html__( 'Media', 'wacp' ) . '</label><input type="text" name="user_media" id="user_media" class="input" required></p>';
					echo '</div>';
					echo '</div>';

					// reCAPTCHA field
					do_action('anr_captcha_form_field');
					if ( wacp_get_recaptcha_site_key_from_setting_page() ) {
						echo '<div class="g-recaptcha" data-sitekey="'.wacp_get_recaptcha_site_key_from_setting_page().'"></div>';
					}

					// Hidden field to prevent spam bots = honeypot
					echo '<p style="display:none;">';
					echo '<label for="hp_message">' . esc_html_e( 'Your message', 'wacp'). '</label>';
					echo '<input type="text" name="hp_message" id="hp_message" class="input">';
					echo '</p>';

					do_action( 'register_form' );
					echo '<input type="hidden" name="create_magic_email" value="1">';
					echo '<p><input type="submit" name="wp-submit" id="wp-submit" class="button button-secondary" value="' . esc_attr__( 'Register', 'wacp' ) . '"></p>';
					echo '</form>';
				} else {
					echo '<p>' . esc_html__( 'Registration is currently disabled.', 'wacp' ) . '</p>';
				}
				echo '</div>'; // Register form end 


				// Display a Receive login link form container
				echo '<div class="login-form-container" style="margin: 1rem 0; padding: 2rem; border: 1px solid var(--waff-color-layout-trans-4); border-radius: 5px; background: var(--waff-color-layout-trans-2)">';
                echo '<i class="bi bi-person-lock fs-1"></i>';
				echo '<h6 style="text-align: left;">' . esc_html__( 'Already been there ?', 'wacp' ) . '</h6>';
				// Display a login form without the password field.
				echo '<form name="loginform" id="loginform" method="post">';
				echo '<p><label for="magic_email">' . esc_html__( 'E-mail', 'wacp' ) . '</label><input type="text" name="magic_email" id="magic_email" class="input" required></p>';
				echo '<input type="hidden" name="resend_magic_email" value="1">';
				echo '<p><input type="submit" name="magic_submit" id="magic_submit" class="button button-primary" value="' . esc_attr__( 'Receive login link', 'wacp' ) . '"></p>';
				echo '</form>';
				// Display a link to the password recovery page.
				// echo '<p><a href="' . wp_lostpassword_url() . '">' . esc_html__( 'Lost your password?', 'wacp' ) . '</a></p>';
                echo '</div>'; // Login form end 


				echo '</div>'; // Client portal forms end

        } else {

			echo '<div class="row client-portal-content">';
			echo '<div class="col">';

			the_content();

			echo '</div>'; // End of col
			echo '<aside class="col-3">';

					// List all pages that are private and accessible to the user.
					$private_pages = get_posts( array(
						'post_type'   => 'page',
						'post_status' => 'private',
						'numberposts' => -1,
					) );

					$current_user = wp_get_current_user();
					if ( !empty( $current_user->display_name ) ) {
						echo '<p>' . esc_html__( 'Hello', 'wacp' ) . ' ' . esc_html( $current_user->display_name ) . '!</p>';
					}
					echo '<p class="subline">' . esc_html__( 'Welcome to the client portal!', 'wacp' ) . '</p>';
					if ( ! empty( $private_pages ) ) {
						echo '<ul>';
						foreach ( $private_pages as $page ) {
							// Check if the current user can read the private page.
							if ( current_user_can( 'read_private_pages', $page->ID ) ) {
								echo '<li><a href="' . get_permalink( $page->ID ) . '">' . esc_html( $page->post_title ) . '</a></li>';
							}
						}
						echo '</ul>';
					} else {
						echo '<p>' . esc_html__( 'No private pages available.', 'wacp' ) . '</p>';
					}

					// Display a logout link.
					echo '<p><a class="button button-primary" href="' . wp_logout_url( get_permalink() ) . '">' . esc_html__( 'Logout', 'wacp' ) . '</a></p>';

			echo '</aside>'; // End of aside


		}	


	// If comments are open or we have at least one comment, load up the comment template.
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}

endwhile;
?>

</div>

<?php
get_footer();
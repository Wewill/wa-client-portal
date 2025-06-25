<?php
/**
 * Template Name: Client portal content 
 * Display client portal page
 *
 * @package WCP
 */

defined('ABSPATH') || exit;

	echo '—————————— ICI ?  ——————————<br>';


$messages = [];

// Traitement du formulaire
if (!empty($_POST['magic_email'])) {

	// Vérifier si l'email est valide
	echo '—————————— POST magic_email ——————————<br>';
	if ( is_email($_POST['magic_email']) ) {

		echo '—————————— magic_email ——————————<br>';

		$email = sanitize_email($_POST['magic_email']);
		$resend_magic_email = isset($_POST['resend_magic_email']) ? intval($_POST['resend_magic_email']) : 0;
		$create_magic_email = isset($_POST['create_magic_email']) ? intval($_POST['create_magic_email']) : 0;
		$limit_key = 'magic_login_attempts_' . md5($email);
		$attempts = get_transient($limit_key) ?: 0;

		// Limite à 5 tentatives / 24h
		if ($attempts >= 10) {
			$messages[] = "<p style='color:red'>Trop de demandes aujourd'hui. Réessayez demain.</p>";
		} else {
			$user = get_user_by('email', $email);

			// Si on vient du formulaire de création et que l'utilisateur existe déjà
			if ($create_magic_email === 1 && $user) {
				$messages[] = "<p style='color:orange'>Un compte existe déjà avec l'adresse <strong>{$email}</strong>.</p>";
				// On continue pour envoyer le lien magique
			}

			// Créer utilisateur si inexistant et ce n'est pas une demande de renvoi de lien
			if (!$user && $resend_magic_email === 0) {
				// Create an automatic username from first and last name
				if (
					isset($_POST['first_name']) && isset($_POST['last_name']) &&
					trim(sanitize_text_field($_POST['first_name'])) !== '' &&
					trim(sanitize_text_field($_POST['last_name'])) !== ''
				) {
					echo '—————————— first_name +  last_name ——————————<br>';

					$first_name = sanitize_key(sanitize_user(strtolower(sanitize_text_field($_POST['first_name']))));
					$last_name = sanitize_key(sanitize_user(strtolower(sanitize_text_field($_POST['last_name']))));
					$username = $first_name . '.' . $last_name;

					echo '—————————— '.$username.' ——————————<br>';

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
					echo '—————————— email ——————————<br>';

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

			// Si l'utilisateur n'existe pas et qu'on ne renvoie pas le lien magique, on demande l'inscription
			if (!$user && $resend_magic_email === 1) {
				$messages[] = "<p style='color:red'>Aucun utilisateur trouvé avec l'email <strong>{$email}</strong>.</p>";
				$unknown_user = true;
			}

			if ( $user ) {
				$user_id = $user->ID;

				// Générer token
				$token = bin2hex(random_bytes(32));
				update_user_meta($user_id, 'magic_login_token', $token);
				update_user_meta($user_id, 'magic_login_token_expires', time() + (6 * 30 * 24 * 60 * 60)); // 6 mois

				// Envoi par email
				$url = add_query_arg([
					'magic_login' => 1,
					'token' => $token,
					'user_id' => $user_id,
				], site_url());

				wp_mail($email, 'Votre lien magique de connexion', "Cliquez ici pour vous connecter : $url");

					$messages[] = "<p style='color:green'>Un lien de connexion a été envoyé à <strong>{$email}</strong>. Vérifiez votre boîte mail.</p>";

				// Incrémenter compteur
				set_transient($limit_key, $attempts + 1, DAY_IN_SECONDS);
			}
		}

	// Not valid email 
	} else {
		$messages[] = "<p style='color:red'>Veuillez entrer une adresse <b>e-mail</b> valide.</p>";
	}

// No email from form 
}

// Traitement du lien magique
add_action('init', function () {

	echo '—————————— INIT ——————————<br>';

	// Si l'utilisateur est déjà connecté, on ne fait rien
	// On ne veut pas que les utilisateurs connectés soient redirigés vers le portail
	if (is_user_logged_in()) return;

	// Vérifier si le cookie de connexion magique existe
    if (!empty($_COOKIE['magic_login_remember'])) {
        $data = json_decode(base64_decode($_COOKIE['magic_login_remember']), true);
        if (isset($data['user_id'], $data['token'])) {
            $expected_token = hash_hmac('sha256', $data['user_id'], AUTH_KEY);
            if (hash_equals($expected_token, $data['token'])) {
                $user = get_user_by('ID', $data['user_id']);
                if ($user && in_array('subscriber', $user->roles)) {
                    // Connexion automatique
                    wp_set_auth_cookie($user->ID, true);
                    wp_set_current_user($user->ID);
                }
            }
        }
    }

	// Vérifier si on a un lien magique dans l'URL
    if (
        isset($_GET['magic_login'], $_GET['token'], $_GET['user_id']) &&
        $_GET['magic_login'] == '1'
    ) {

			echo '—————————— magic_login ——————————<br>';

        $user_id = intval($_GET['user_id']);
        $token = sanitize_text_field($_GET['token']);

        $saved_token = get_user_meta($user_id, 'magic_login_token', true);
        $expires = get_user_meta($user_id, 'magic_login_token_expires', true);

        if (!$saved_token || !$expires || time() > $expires) {
            wp_die('Ce lien a expiré. <a href="' . esc_url(site_url()) . '">Renvoyer un nouveau lien ?</a>');
        }

        if (!hash_equals($saved_token, $token)) {
            wp_die('Lien de connexion invalide.');
        }

        // Connexion
        wp_set_auth_cookie($user_id, true);
        wp_set_current_user($user_id);

		// Créer cookie custom (durée : 30 jours)
		$cookie_value = base64_encode(json_encode([
			'user_id' => $user_id,
			'token' => hash_hmac('sha256', $user_id, AUTH_KEY), // Signature simple
		]));
		setcookie('magic_login_remember', $cookie_value, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
		update_user_meta($user_id, 'magic_login_cookie_expires', time() + (30 * DAY_IN_SECONDS));

        // (Optionnel) Invalider token après 1 usage :
        // delete_user_meta($user_id, 'magic_login_token');
        // delete_user_meta($user_id, 'magic_login_token_expires');

		// Do not redirect to the client portal page 
        // wp_redirect(home_url());
        // exit;
    }
});

// Uncomment to clear the magic login cookie on logout
// add_action('wp_logout', function () {
//     setcookie('magic_login_remember', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
// });


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
						
				// Print messages if any.
				foreach ($messages as $msg) {
					echo $msg;
				}

				// Display the login form in a styled container.
				echo '<div class="client-portal-forms" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin: 0 auto;">';

				// Display a register form container 
				echo '<div class="register-form-container" style="margin: 1rem 0; padding: 3rem; border: 1px solid var(--waff-color-layout-trans-4); border-radius: 5px; background: var(--waff-color-layout-trans-2)">';
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


					do_action( 'register_form' );
					echo '<input type="hidden" name="create_magic_email" value="1">';
					echo '<p><input type="submit" name="wp-submit" id="wp-submit" class="button button-secondary" value="' . esc_attr__( 'Register', 'wacp' ) . '"></p>';
					echo '</form>';
				} else {
					echo '<p>' . esc_html__( 'Registration is currently disabled.', 'wacp' ) . '</p>';
				}
				echo '</div>'; // Register form end 


				// Display a Receive login link form container
				echo '<div class="login-form-container" style="margin: 1rem 0; padding: 3rem; border: 1px solid var(--waff-color-layout-trans-4); border-radius: 5px; background: var(--waff-color-layout-trans-2)">';
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
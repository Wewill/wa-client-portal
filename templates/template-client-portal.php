<?php
/**
 * Template Name: Client portal content 
 * Display client portal page
 *
 * @package WCP
 */

get_header();
?>

<?php 
// Start the Loop.
while ( have_posts() ) :
	echo '<h2>' . get_the_title() . '</h2>';
	the_post();
	the_content();

	        if ( !is_user_logged_in() ) {
                // Display the login form in a styled container.

				echo '<div class="client-portal-forms" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin: 0 auto;">';

                echo '<div class="login-form-container" style="margin: 1rem 0; padding: 3rem; border: 1px solid var(--waff-color-layout-trans-4); border-radius: 5px; background: var(--waff-color-layout-trans-2)">';
                echo '<i class="bi bi-person-lock fs-1"></i>';
				echo '<h6 style="text-align: left;">Login Required</h6>';
                echo wp_login_form( [ 'echo' => false ] );
				// Display a link to the password recovery page.
				echo '<p><a href="' . wp_lostpassword_url() . '">Lost your password?</a></p>';
                echo '</div>'; // Login form end 

			// Display a register form container 
				echo '<div class="register-form-container" style="margin: 1rem 0; padding: 3rem; border: 1px solid var(--waff-color-layout-trans-4); border-radius: 5px; background: var(--waff-color-layout-trans-2)">';
                echo '<i class="bi bi-key fs-1"></i>';
				echo '<h6 style="text-align: left;">Register</h6>';
				if ( get_option( 'users_can_register' ) ) {
					echo '<form action="' . esc_url( site_url( 'wp-login.php?action=register', 'login_post' ) ) . '" method="post">';
					echo '<p><label for="user_login">Username</label><input type="text" name="user_login" id="user_login" class="input" required></p>';
					echo '<p><label for="user_email">Email</label><input type="email" name="user_email" id="user_email" class="input" required></p>';
					do_action( 'register_form' );
					echo '<p><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="Register"></p>';
					echo '</form>';
				} else {
					echo '<p>Registration is currently disabled.</p>';
				}
				echo '</div>'; // Register form end 

				echo '</div>'; // Client portal forms end

        } else {
				// Display a message for logged-in users.
				echo '<div class="client-portal-contents" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin: 0 auto;">';
                echo '<div class="page-container" style="margin: 1rem 0; padding: 3rem; border: 1px solid var(--waff-color-layout-trans-4); border-radius: 5px; background: var(--waff-color-layout-trans-2)">';


				// List all pages that are private and accessible to the user.
				$private_pages = get_posts( array(
					'post_type'   => 'page',
					'post_status' => 'private',
					'numberposts' => -1,
				) );

				echo '<p class="lead">Welcome to the client portal!</p>';
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
					echo '<p>No private pages available.</p>';
				}
			// Display a logout link.
			echo '<p><a href="' . wp_logout_url( get_permalink() ) . '">Logout</a></p>';

			echo '</div>'; // Register form end 
			echo '</div>'; // Client portal forms end

		}	


	// If comments are open or we have at least one comment, load up the comment template.
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}

endwhile;

get_footer();
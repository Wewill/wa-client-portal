<?php
/**
 * Shortcode to add a film id to a client user's favorite films
 * features :
 * — Print an icon of a star in a span
 * - if user is logged in and has the film in his favorites, the star is filled. On click, the film is removed from favorites and the star is emptied. 
 * - if user is logged in and doesn't have the film in his favorites, the star is empty. On click, the film is added to favorites and the star is filled
 * - if user is not logged in, the star is empty and a tooltip invites to log in. Then, a popup appears on click to log in (links to content of template-client-portal.php). When logged in, film is added to favorites and star is filled
 * - ajax is used to add/remove film from favorites without reloading the page
 * - the shortcode takes one attribute : film_id
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'wacp_enqueue_front_assets' );
add_action( 'wp_ajax_wacp_toggle_favorite', 'wacp_toggle_favorite_ajax' );
// Note: we do not allow non-logged users to add favorites via ajax; they must log in first.

// Register shortcode [wacp_favorite_star film_id="123"] ( no action, because always registered in a init action : add_action( 'init', 'wacp_register_shortcodes' );)
add_shortcode( 'wacp_favorite_star', 'wacp_favorite_star_shortcode' );

function wacp_favorite_star_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'film_id' => 0,
	), $atts, 'wacp_favorite_star' );

	$film_id = intval( $atts['film_id'] );
	if ( $film_id <= 0 ) {
		return ''; // invalid film id
	}

	$user_id = get_current_user_id();
	$favorited = false;
	if ( $user_id ) {
		$favorited = wacp_user_has_favorite( $user_id, $film_id );
	}

	$nonce = wp_create_nonce( 'wacp_fav_nonce' );

	// Minimal accessible markup: a span acting as button with data attributes
	$classes = 'wacp-favorite-film' . ( $favorited ? ' favorited' : '' );
	$title = $favorited ? esc_attr__( 'Remove from favorites', 'wacp' ) : esc_attr__( 'Add to favorites', 'wacp' );
	$aria_pressed = $favorited ? 'true' : 'false';

	// Icon for empty and filled star
	$html = '<span class="' . esc_attr( $classes ) . '" role="button" tabindex="0" data-bs-toggle="tooltip" data-toggle="tooltip" title="' . esc_attr( $title ) . '" aria-pressed="' . $aria_pressed . '" data-film-id="' . esc_attr( $film_id ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
	$html .= '<i class="wacp-star-icon bi bi-star empty" style="display:' . ( $favorited ? 'none' : 'inline' ) . ';"></i>';
	$html .= '<i class="wacp-star-icon bi bi-star-fill filled" style="display:' . ( $favorited ? 'inline' : 'none' ) . ';"></i>';
	$html .= '</span>';

	return $html;
}

/* -------------------------
   User meta helpers
   ------------------------- */

function wacp_user_get_favorites( $user_id ) {
	$prefix = 'wacp-';
	$favs = get_user_meta( $user_id, $prefix . 'favorite_films', true );
	if ( ! is_array( $favs ) ) {
		$favs = array();
	}
	// normalize ints
	return array_map( 'intval', $favs );
}

function wacp_user_has_favorite( $user_id, $film_id ) {
	$favs = wacp_user_get_favorites( $user_id );
	return in_array( intval( $film_id ), $favs, true );
}

function wacp_user_add_favorite( $user_id, $film_id ) {
	$prefix = 'wacp-';
	$favs = wacp_user_get_favorites( $user_id );
	$fid = intval( $film_id );
	if ( ! in_array( $fid, $favs, true ) ) {
		$favs[] = $fid;
		update_user_meta( $user_id, $prefix . 'favorite_films', $favs );
	}
	return true;
}

function wacp_user_remove_favorite( $user_id, $film_id ) {
	$prefix = 'wacp-';
	$favs = wacp_user_get_favorites( $user_id );
	$fid = intval( $film_id );
	if ( in_array( $fid, $favs, true ) ) {
		$favs = array_values( array_diff( $favs, array( $fid ) ) );
		update_user_meta( $user_id, $prefix . 'favorite_films', $favs );
	}
	return true;
}

/* -------------------------
   AJAX handler
   ------------------------- */

function wacp_toggle_favorite_ajax() {
	// Check nonce
	check_ajax_referer( 'wacp_fav_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'wacp' ) ), 403 );
	}

	$user_id = get_current_user_id();
	$film_id = isset( $_POST['film_id'] ) ? intval( $_POST['film_id'] ) : 0;
	if ( $film_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Invalid film id.', 'wacp' ) ), 400 );
	}

	$already = wacp_user_has_favorite( $user_id, $film_id );
	if ( $already ) {
		wacp_user_remove_favorite( $user_id, $film_id );
		wp_send_json_success( array( 'action' => 'removed', 'film_id' => $film_id ) );
	} else {
		wacp_user_add_favorite( $user_id, $film_id );
		wp_send_json_success( array( 'action' => 'added', 'film_id' => $film_id ) );
	}
}

/* -------------------------
   Front assets + modal
   ------------------------- */

function wacp_enqueue_front_assets() {
	// Styles see waff-theme > specific-fifam
	
	// Register an empty script handle to attach inline script
	wp_register_script( 'wacp-fav-script', '' , array( 'jquery' ), null, true );
	wp_enqueue_script( 'wacp-fav-script' );

	$logged_in = is_user_logged_in() ? 1 : 0;
	$portal_url = wacp_get_portal_page_url();

	$inline_js = <<<JS
	(function($){
		var ajaxUrl = '{ajax_url}';
		var loggedIn = {logged_in};
		var globalNonce = '{nonce}';

		// Click handler
		$(document).on('click', '.wacp-favorite-film', function(e){
			e.preventDefault();
			var el = $(this);
			var filmId = el.data('film-id');
			if (!filmId) return;
			if (!loggedIn) {
				// Store pending favorite, open modal
				try { localStorage.setItem('wacp_pending_fav', filmId); } catch(e){}
				$('#wacp-login-modal').fadeIn(150).attr('aria-hidden','false');
				return;
			}
			var nonce = el.data('nonce') || globalNonce;
			// ajax toggle
			$.post(ajaxUrl, {
				action: 'wacp_toggle_favorite',
				film_id: filmId,
				nonce: nonce
			}, function(resp){
				if (resp && resp.success) {
					if (resp.data.action === 'added') {
						el.addClass('favorited').attr('aria-pressed','true');
						el.find('.wacp-star-icon.empty').hide();
						el.find('.wacp-star-icon.filled').show();
						el.attr('title','Remove from favorites');
					} else {
						el.removeClass('favorited').attr('aria-pressed','false');
						el.find('.wacp-star-icon.empty').show();
						el.find('.wacp-star-icon.filled').hide();
						el.attr('title','Add to favorites');
					}
				} else {
					console && console.warn(resp);
					alert('Error toggling favorite.');
				}
			});
		});

		// close modal: allow clicks on the overlay (only when clicking the overlay itself)
		// and allow clicks on the close button (or its inner children)
		$(document).on('click', '#wacp-login-modal, #wacp-login-modal .wacp-modal-close', function(e){
			var current = e.currentTarget;
			// If current target is the overlay, ensure the direct overlay was clicked (not its children)
			if ( $(current).is('#wacp-login-modal') && e.target !== current ) return;
			$('#wacp-login-modal').fadeOut(120, function(){ $(this).attr('aria-hidden','true'); });
		});

		// If user just logged in (page loaded and loggedIn true), check pending fav
		$(function(){
			if (loggedIn) {
				var pending = null;
				try { pending = localStorage.getItem('wacp_pending_fav'); } catch(e){}
				if (pending) {
					// attempt to add favorite automatically
					$.post(ajaxUrl, {
						action: 'wacp_toggle_favorite',
						film_id: pending,
						nonce: globalNonce
					}, function(resp){
						// On success, reflect UI for any star present on page
						if (resp && resp.success && resp.data.action === 'added') {
							$('.wacp-favorite-film[data-film-id=\"'+pending+'"]').each(function(){
								var el = $(this);
								el.addClass('favorited').attr('aria-pressed','true');
								el.find('.wacp-star-icon.empty').hide();
								el.find('.wacp-star-icon.filled').show();
								el.attr('title','Remove from favorites');
							});
						}
						try { localStorage.removeItem('wacp_pending_fav'); } catch(e){}
					});
				}
			}
		});
	})(jQuery);
	JS;

	// Replace placeholders
	$inline_js = str_replace('{ajax_url}', esc_js( admin_url( 'admin-ajax.php' ) ), $inline_js );
	$inline_js = str_replace('{logged_in}', $logged_in ? '1' : '0', $inline_js );
	$inline_js = str_replace('{nonce}', wp_create_nonce( 'wacp_fav_nonce' ), $inline_js );

	wp_add_inline_script( 'wacp-fav-script', $inline_js );

	// Print modal in footer via action (ensures present once)
	add_action( 'wp_footer', 'wacp_print_login_modal' );
}

function wacp_get_portal_page_url() {
	$args = array(
		'meta_key'    => '_wp_page_template',
		'meta_value'  => '../templates/template-client-portal.php',
		'post_type'   => 'page',
		'post_status' => 'publish',
		'numberposts' => 1,
	);
	$portal_page = get_posts( $args );
	if ( ! empty( $portal_page ) && isset( $portal_page[0]->ID ) ) {
		return get_permalink( $portal_page[0]->ID );
	}
	return site_url( '/client-portal/' ); // fallback
}

function wacp_print_login_modal() {
	$portal_url = esc_url( wacp_get_portal_page_url() );
	?>
	<div id="wacp-login-modal" aria-hidden="true">
		<div class="wacp-modal-container">
			<div class="wacp-modal-box" role="dialog" aria-modal="true">
				<span class="wacp-modal-close" title="<?php echo esc_attr__( 'Close', 'wacp' ); ?>"><i class="bi bi-x-circle-fill"></i></span>
				<i class="bi bi-star-half fs-1"></i>
				<h4 class=""><?php echo esc_html__( 'Please log in to add favorites', 'wacp' ); ?></h4>
				<p><?php echo esc_html__( 'You must be logged in to save favorites film into your Fifam account. Click below to open the account portal and log in or register.', 'wacp' ); ?></p>
				<a class="wacp-portal-btn btn btn-action-1" href="<?php echo $portal_url; ?>"><?php echo esc_html__( 'Create my fifam account', 'wacp' ); ?></a> – <?php echo esc_html__( 'or', 'wacp' ); ?> –
				<a class="wacp-portal-btn btn btn-dark" href="<?php echo $portal_url; ?>"><?php echo esc_html__( 'Log in to my fifam account', 'wacp' ); ?></a>
			</div>
		</div>
	</div>
	<?php
}


/**
 * Shortcode to print a list of films favorited by the current logged in user
 */

add_shortcode( 'wacp_favorite_films_list', 'wacp_favorite_films_list_shortcode' );

function wacp_favorite_films_list_shortcode() {
	if ( ! is_user_logged_in() ) {
		return esc_html__( 'You must be logged in to view your favorite films.', 'wacp' );
	}

	$user_id = get_current_user_id();
	$fav_films = wacp_user_get_favorites( $user_id );

	if ( empty( $fav_films ) ) {
		return esc_html__( 'You have no favorite films yet.', 'wacp' );
	}

	// For simplicity, assume film IDs correspond to post IDs of a custom post type 'film'
	$html = '<ul class="wacp-favorite-films-list">';
	foreach ( $fav_films as $film_id ) {
		$film_post = get_post( $film_id );
		if ( $film_post && $film_post->post_type === 'film' ) {
			$film_title = get_the_title( $film_post );
			$film_link = get_permalink( $film_post );
			$html .= '<li><a href="' . esc_url( $film_link ) . '">' . esc_html( $film_title ) . '</a></li>';
		}
	}
	$html .= '</ul>';

	return $html;
}


/**
 * Shortcode wacp_account to print account informations such as First name, Last name, Email, etc.
 */
add_shortcode( 'wacp_account', 'wacp_account_shortcode' );

function wacp_account_shortcode() {
	if ( ! is_user_logged_in() ) {
		return esc_html__( 'You must be logged in to view your account information.', 'wacp' );
	}

	$user = wp_get_current_user();
	$html = '<div class="wacp-account-info">';
	$html .= '<p><strong>' . esc_html__( 'First Name', 'wacp' ) . '</strong> ' . esc_html( $user->first_name ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Last Name', 'wacp' ) . '</strong> ' . esc_html( $user->last_name ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Email', 'wacp' ) . '</strong> ' . esc_html( $user->user_email ) . '</p>';
	// Add more fields as needed
	$html .= '</div>';

	return $html;
}
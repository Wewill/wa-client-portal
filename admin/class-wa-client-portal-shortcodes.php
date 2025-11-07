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
$prefix = 'wacp-';


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
	global $prefix;
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
	global $prefix;
	$favs = wacp_user_get_favorites( $user_id );
	$fid = intval( $film_id );
	if ( ! in_array( $fid, $favs, true ) ) {
		$favs[] = $fid;
		update_user_meta( $user_id, $prefix . 'favorite_films', $favs );
	}
	return true;
}

function wacp_user_remove_favorite( $user_id, $film_id ) {
	global $prefix;
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
	// Minimal style
	wp_register_style( 'wacp-fav-style', false );
	$css = '
	.wacp-favorite-film { cursor: pointer; display:inline-flex; align-items:center; color: black; transition: color .2s; }
	.wacp-favorite-film:hover { color: var(--waff-action-1); }
	.wacp-favorite-film.favorited { color: var(--waff-action-1); }
	/* simple modal styles */
	#wacp-login-modal { display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; }
	#wacp-login-modal .wacp-modal-box { background:#fff; max-width:560px; width:90%; padding:20px; border-radius:8px; box-shadow:0 6px 24px rgba(0,0,0,0.2); }
	#wacp-login-modal .wacp-modal-close { float:right; cursor:pointer; font-weight:bold; }
	#wacp-login-modal a.wacp-portal-btn { display:inline-block; margin-top:12px; padding:10px 14px; background:#9600ff;color:#fff;border-radius:4px;text-decoration:none; }
	';
	wp_add_inline_style( 'wacp-fav-style', $css );
	wp_enqueue_style( 'wacp-fav-style' );

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
				$('#wacp-login-modal').fadeIn(150);
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

		// close modal
		$(document).on('click', '#wacp-login-modal, #wacp-login-modal .wacp-modal-close', function(e){
			if ( e.target !== this ) return;
			$('#wacp-login-modal').fadeOut(120);
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
		<div class="wacp-modal-box" role="dialog" aria-modal="true">
			<span class="wacp-modal-close" title="<?php echo esc_attr__( 'Close', 'wacp' ); ?>">×</span>
			<h3><?php echo esc_html__( 'Please log in to add favorites', 'wacp' ); ?></h3>
			<p><?php echo esc_html__( 'You must be logged in to save favorites. Click below to open the client portal and log in or register.', 'wacp' ); ?></p>
			<a class="wacp-portal-btn" href="<?php echo $portal_url; ?>"><?php echo esc_html__( 'Open Client Portal', 'wacp' ); ?></a>
		</div>
	</div>
	<?php
}



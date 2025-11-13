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
 * 
 * ┌────────────────────────────────────────────────┐
 * │ CHARGEMENT PAGE (Request #1)                   │
 * ├────────────────────────────────────────────────┤
 * │ 1. Cache vide                                  │
 * │ 2. Shortcode #1 → SQL Query → Cache rempli     │
 * │ 3. Shortcode #2-100 → Lit depuis cache         │
 * │ 4. HTML envoyé au navigateur                   │
 * │ 5. Fin requête → Cache détruit                 │
 * └────────────────────────────────────────────────┘
 *          ↓
 * ┌────────────────────────────────────────────────┐
 * │ CLIC AJAX (Request #2)                         │
 * ├────────────────────────────────────────────────┤
 * │ 1. Nouveau cache vide (nouvelle requête PHP)   │
 * │ 2. wacp_toggle_favorite_ajax() s'exécute       │
 * │ 3. update_user_meta() → DB modifiée            │
 * │ 4. JSON envoyé au navigateur                   │
 * │ 5. JavaScript met à jour le DOM                │
 * │ 6. Fin requête → Cache détruit                 │
 * └────────────────────────────────────────────────┘
 *          ↓
 * ┌────────────────────────────────────────────────┐
 * │ RECHARGEMENT PAGE (Request #3)                 │
 * ├────────────────────────────────────────────────┤
 * │ 1. Nouveau cache vide                          │
 * │ 2. SQL Query → Récupère les nouvelles données  │
 * │ 3. Shortcodes affichent l'état mis à jour      │
 * └────────────────────────────────────────────────┘
 * Optimise le rendu initial (100 shortcodes = 1 SQL au lieu de 100)
 * ✅ N'interfère pas avec l'AJAX (contextes séparés)
 * ✅ Toujours synchronisé avec la DB (nouveau cache à chaque requête)
 * ✅ Pas de problème de stale data (données obsolètes)
 * ✅ Zéro configuration, zéro maintenance
 */

defined( 'ABSPATH' ) || exit;
global $current_edition, $previous_editions, $current_edition_id, $current_edition_films_are_online;

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
		// Use cached favorites instead of individual database calls
		$favorited = wacp_user_has_favorite_cached( $user_id, $film_id );
	}

	// No need to generate a nonce per shortcode - the global nonce in JS will be used
	// This saves memory and improves performance with many shortcodes

	// Minimal accessible markup: a span acting as button with data attributes
	$classes = 'wacp-favorite-film' . ( $favorited ? ' favorited' : '' );
	$title = $favorited ? esc_attr__( 'Remove from favorites', 'wacp' ) : esc_attr__( 'Add to favorites', 'wacp' );
	$aria_pressed = $favorited ? 'true' : 'false';

	// Icon for empty and filled star (removed data-nonce as it's redundant with global nonce)
	$html = '<span class="' . esc_attr( $classes ) . '" role="button" tabindex="0" data-bs-toggle="tooltip" data-toggle="tooltip" title="' . esc_attr( $title ) . '" aria-pressed="' . $aria_pressed . '" data-film-id="' . esc_attr( $film_id ) . '">';
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

/**
 * Cached version of wacp_user_has_favorite to avoid multiple DB queries per page load
 * This is critical when multiple shortcodes are used on the same page
 */
function wacp_user_has_favorite_cached( $user_id, $film_id ) {
	static $favorites_cache = array();

	// Check if we already loaded favorites for this user in this request
	if ( ! isset( $favorites_cache[ $user_id ] ) ) {
		// Load once and cache for the entire request
		$favorites_cache[ $user_id ] = wacp_user_get_favorites( $user_id );
	}

	return in_array( intval( $film_id ), $favorites_cache[ $user_id ], true );
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
	// Prevent multiple enqueues - only run once per page load
	static $enqueued = false;
	if ( $enqueued ) {
		return;
	}
	$enqueued = true;

	// Styles see wacp-theme > specific-fifam

	// Register an empty script handle to attach inline script
	wp_register_script( 'wacp-fav-script', '' , array( 'jquery' ), null, true );
	wp_enqueue_script( 'wacp-fav-script' );

	$logged_in = is_user_logged_in() ? 1 : 0;
	$portal_url = wacp_get_portal_page_url();

	// Prepare user favorites list for JS synchronization
	$user_favorites_json = '[]';
	if ( $logged_in ) {
		$user_id = get_current_user_id();
		$user_favorites = wacp_user_get_favorites( $user_id );
		$user_favorites_json = wp_json_encode( $user_favorites );
	}

	// Localize script with translated strings
	wp_localize_script( 'wacp-fav-script', 'wacpFavStrings', array(
		'addToFavorites'    => __( 'Add to favorites', 'wacp' ),
		'removeFromFavorites' => __( 'Remove from favorites', 'wacp' ),
	) );

	$inline_js = <<<JS
	(function($){
		var ajaxUrl = '{ajax_url}';
		var loggedIn = {logged_in};
		var globalNonce = '{nonce}';
		var userFavorites = {user_favorites};

		/**
		 * Synchronize favorites UI state for cached HTML content
		 * This function updates the favorite stars when HTML is loaded from cache
		 * (e.g., programmation modal) where PHP couldn't determine user's favorites
		 */
		function syncFavoritesUI() {
			if (!loggedIn || !userFavorites || userFavorites.length === 0) return;

			$('.wacp-favorite-film').each(function(){
				var el = $(this);
				var filmId = parseInt(el.data('film-id'), 10);
				if (!filmId) return;

				var isFavorited = userFavorites.indexOf(filmId) !== -1;

				// Update UI to match user's actual favorites
				if (isFavorited) {
					el.addClass('favorited').attr('aria-pressed','true');
					el.find('.wacp-star-icon.empty').hide();
					el.find('.wacp-star-icon.filled').show();
					el.attr('title', wacpFavStrings.removeFromFavorites);
				} else {
					el.removeClass('favorited').attr('aria-pressed','false');
					el.find('.wacp-star-icon.empty').show();
					el.find('.wacp-star-icon.filled').hide();
					el.attr('title', wacpFavStrings.addToFavorites);
				}
			});
		}

		/**
		 * Update a single favorite star after AJAX toggle
		 */
		function updateFavoriteStar(filmId, action) {
			$('.wacp-favorite-film[data-film-id="'+filmId+'"]').each(function(){
				var el = $(this);
				if (action === 'added') {
					el.addClass('favorited').attr('aria-pressed','true');
					el.find('.wacp-star-icon.empty').hide();
					el.find('.wacp-star-icon.filled').show();
					el.attr('title', wacpFavStrings.removeFromFavorites);
					// Update local favorites array
					if (userFavorites.indexOf(filmId) === -1) {
						userFavorites.push(filmId);
					}
				} else {
					el.removeClass('favorited').attr('aria-pressed','false');
					el.find('.wacp-star-icon.empty').show();
					el.find('.wacp-star-icon.filled').hide();
					el.attr('title', wacpFavStrings.addToFavorites);
					// Update local favorites array
					var idx = userFavorites.indexOf(filmId);
					if (idx !== -1) {
						userFavorites.splice(idx, 1);
					}
				}
			});
		}

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

			// Show loading spinner
			el.addClass('loading');
			el.find('.wacp-star-icon').hide();
			el.append('<span class="spinner-border spinner-border-sm wacp-loading-spinner" role="status"><span class="visually-hidden">Loading...</span></span>');

			var nonce = el.data('nonce') || globalNonce;
			// ajax toggle
			$.post(ajaxUrl, {
				action: 'wacp_toggle_favorite',
				film_id: filmId,
				nonce: nonce
			}, function(resp){
				// Remove spinner
				el.removeClass('loading');
				el.find('.wacp-loading-spinner').remove();

				if (resp && resp.success) {
					updateFavoriteStar(filmId, resp.data.action);
				} else {
					// Restore previous state on error
					el.find('.wacp-star-icon').show();
					console && console.warn(resp);
					alert('Error toggling favorite.');
				}
			}).fail(function(){
				// Remove spinner and restore state on network error
				el.removeClass('loading');
				el.find('.wacp-loading-spinner').remove();
				el.find('.wacp-star-icon').show();
				alert('Network error. Please try again.');
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
							updateFavoriteStar(parseInt(pending, 10), 'added');
						}
						try { localStorage.removeItem('wacp_pending_fav'); } catch(e){}
					});
				}
			}
		});

		// Listen for custom event dispatched when programmation cache HTML is loaded
		$(document).on('wacp:programmation-html-loaded', function(){
			syncFavoritesUI();
		});

		// Initial sync on page load (for any cached content already in DOM)
		$(document).ready(function(){
			syncFavoritesUI();
		});

	})(jQuery);
	JS;

	// Replace placeholders
	$inline_js = str_replace('{ajax_url}', esc_js( admin_url( 'admin-ajax.php' ) ), $inline_js );
	$inline_js = str_replace('{logged_in}', $logged_in ? '1' : '0', $inline_js );
	$inline_js = str_replace('{nonce}', wp_create_nonce( 'wacp_fav_nonce' ), $inline_js );
	$inline_js = str_replace('{user_favorites}', $user_favorites_json, $inline_js );

	wp_add_inline_script( 'wacp-fav-script', $inline_js );

	// Print modal in footer via action (ensures present once)
	add_action( 'wp_footer', 'wacp_print_login_modal' );
}

function wacp_print_login_modal() {
	$portal_url = esc_url( wacp_get_portal_page_url() );
	?>
	<div id="wacp-login-modal" aria-hidden="true">
		<div class="wacp-modal-container">
			<div class="wacp-modal-box" role="dialog" aria-modal="true">
				<span class="wacp-modal-close" title="<?php echo esc_attr__( 'Close', 'wacp' ); ?>"><i class="bi bi-x-circle-fill"></i></span>
				<i class="bi bi-star-half fs-1"></i>
				<h4 class="mt-2"><?php echo esc_html__( 'Please log in to add favorites', 'wacp' ); ?></h4>
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
 * Shortocode to print a list of film-cards favorited by the current logged in user
 */

add_shortcode( 'wacp_favorite_films_cards', 'wacp_favorite_films_cards_shortcode' );

function wacp_favorite_films_cards_shortcode() {
	global $current_edition_slug;

	if ( ! is_user_logged_in() ) {
		return esc_html__( 'You must be logged in to view your favorite films.', 'wacp' );
	}

	$user_id = get_current_user_id();
	$fav_films = wacp_user_get_favorites( $user_id );

	if ( empty( $fav_films ) ) {
		return esc_html__( 'You have no favorite films yet.', 'wacp' );
	}

	?>
	<!-- Get counts -->
	<?php if ( !empty( $fav_films ) ) : 
	if ( function_exists('get_counts') )
		$counts = get_counts('', array(), array(), $fav_films);
	// print_r( var_dump( $counts ) );

	$random_sentence = array(
		'**Une sélection affûtée !** Entre les rencontres et les débats, on sent que tu sais flairer le bon cinéma.',
		'**Tes choix respirent le festival !** Des salles aux rencontres, tu as visé juste.',
		'**Sélection premium !** On dirait bien que ton œil de cinéphile ne rate rien d’essentiel.',
		'**Coup de cœur validé !** Ton programme reflètent parfaitement l’esprit du festival.',
		'**Une sélection inspirée et inspirante !** Tu navigues entre les sections avec une vraie curiosité cinéphile.',
		'**Bravo pour ton sens du cadre !** Entre pépites et découvertes, ta liste est un bijou de programmation.'
	)
	?> 
	<section class="private-content alignwide mb-3">
		<div class="row">
			<div class="col-sm-2" data-aos="fade-right">
				<p class="subline text-left opacity-75 mb-0">Édition <?= $current_edition_slug ?></p>
				<?php if ( count($fav_films) > 0 ) {
						print( '<span class="heading-4 mt-0"><strong class="count">' . sprintf( _n( '%s', '%s', count($fav_films), 'wacp' ),  count($fav_films) ) . '</strong></span>');
						print( '<p class="w-50">' . _n( 'favorite film in your selection', 'favorites films in your selection', $counts['projections'], 'wacp' ) . '</p>');
				} ?> 
				<?php /* if ( isset($counts['films']) && $counts['films'] != '0' ) {
						print( '<span class="heading-3 mt-0"><strong class="count">' . sprintf( _n( '%s', '%s', $counts['films'], 'wacp' ), $counts['films'] ) . '</strong></span>');
						print( '<p class="w-50">' . _n( 'film screened in this room', 'films screened in this room', $counts['films'], 'wacp' ) . '</p>');
				} */ ?> 
			</div>

			<div class="col-sm-2" data-aos="fade-right">
				<p class="--text-muted text-black position-sticky sticky-top --mb-0">
						<!-- <small class="d-block"><strong><?= $wp_query->post_count ?> films</strong></small> -->
						<?php if ( isset($counts['films']) && $counts['films'] != '0' ) 
							print( '<small class="d-block"><strong>' . sprintf( _n( '%s film', '%s films', $counts['films'], 'waff' ), $counts['films'] ) . '</strong></small>'); ?>
						<?php /*if ( isset($counts['projections']) && $counts['projections'] != '0' ) 
							print( '<small class="d-block"><strong>' . sprintf( _n( '%s projection', '%s projections', $counts['projections'], 'waff' ), $counts['projections'] ) . '</strong></small>'); */ ?>
						<?php if ( isset($counts['events']) && $counts['events'] != '0' ) 
							print( '<small class="d-block"><strong>' . sprintf( _n( '%s event', '%s events', $counts['events'], 'waff' ), $counts['events'] ) . '</strong></small>'); ?>
						<?php if ( isset($counts['programs']) && $counts['programs'] != '0' ) 
							print( '<small class="d-block"><strong>' . sprintf( _n( '%s program', '%s programs', $counts['programs'], 'waff' ), $counts['programs'] ) . '</strong></small>'); ?>
						<?php if ( isset($counts['wpcf-p-is-guest']) && $counts['wpcf-p-is-guest'] != '0' ) 
							print( '<small class="d-block"><i class="icon icon-guest mr-1 f-12"></i> ' . sprintf( _n( '%s with guest', '%s with guest\'s', $counts['wpcf-p-is-guest'], 'waff' ), $counts['wpcf-p-is-guest'] ) . '</small>'); ?>
						<?php if ( isset($counts['wpcf-p-is-debate']) && $counts['wpcf-p-is-debate'] != '0' ) 
							print( '<small class="d-block"><i class="icon icon-mic mr-1 f-12"></i> ' . sprintf( _n( '%s with debate', '%s with debate\'s', $counts['wpcf-p-is-debate'], 'waff' ), $counts['wpcf-p-is-debate'] ) . '</small>'); ?>
						<?php if ( isset($counts['wpcf-p-young-public']) && $counts['wpcf-p-young-public'] != '0' ) 
							print( '<small class="d-block"><i class="icon icon-young mr-1 f-12"></i> ' . sprintf( _n( '%s parent-children', '%s parent-children\'s', $counts['wpcf-p-young-public'], 'waff' ), $counts['wpcf-p-young-public'] ) . '</small>'); ?>
						<?php if ( isset($counts['wpcf-p-highlights']) && $counts['wpcf-p-highlights'] != '0' ) 
							print( '<small class="d-block"><i class="icon icon-sun mr-1 f-12"></i> ' . sprintf( _n( '%s highlight', '%s highlights', $counts['wpcf-p-highlights'], 'waff' ), $counts['wpcf-p-highlights'] ) . '</small>'); ?>
						<?php if ( isset($counts['wpcf-f-promote']) && $counts['wpcf-f-promote'] != '0' ) 
							print( '<small class="d-block"><i class="icon icon-ok mr-1 f-12"></i> ' . sprintf( _n( '%s favorite', '%s favorites', $counts['wpcf-f-promote'], 'waff' ), $counts['wpcf-f-promote'] ) . '</small>'); ?>
						<!-- #44 -->
						<?php if ( isset($counts['wpcf-f-premiere']) && $counts['wpcf-f-premiere'] != '0' ) 
							print( '<small class="d-block"><i class="icon icon-premiere mr-1 f-12"></i> ' . sprintf( _n( '%s premiere', '%s premieres', $counts['wpcf-f-premiere'], 'waff' ), $counts['wpcf-f-premiere'] ) . '</small>'); ?>
						<?php if ( isset($counts['wpcf-f-avant-premiere']) && $counts['wpcf-f-avant-premiere'] != '0' ) 
							print( '<small class="d-block"><i class="icon icon-avantpremiere mr-1 f-12"></i> ' . sprintf( _n( '%s avant-premiere', '%s avant-premieres', $counts['wpcf-f-avant-premiere'], 'waff' ), $counts['wpcf-f-avant-premiere'] ) . '</small>'); ?>
						<!-- EX: <small class="d-block">6 compétitons</small>-->
				</p>
			</div>			

			<div class="col-sm-7" data-aos="fade-left">
				<p class="lead">
					<?php 
					$rand_index = array_rand( $random_sentence );
					echo WaffTwo\Core\waff_do_markdown(esc_html( $random_sentence[ $rand_index ] )); 
					?>
				</p>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php		
	global $attributes;
	// For simplicity, assume film IDs correspond to post IDs of a custom post type 'film'
	$html = '<!-- FILM CARD --><section class="wacp-favorite-films-cards g-0 row --align-items-center py-2 --offset-md-2 col-12 --col-sm-10 alignwide">';
	foreach ( $fav_films as $film_ID ) :
		// Start the Loop.
		// $film_post = get_post( $film_ID );
		$promote 	= get_post_meta($film_ID, 'wpcf-f-promote', true);
		$film_color = rwmb_meta( 'wacp_film_color', array(), $film_ID );
		$film_color_class = 'contrast--light card-dark';
		if ( isset($promote) && $promote=='1' && isset($film_color) && $film_color != '' ) {
			$rgb = WaffTwo\Core\wacp_HTMLToRGB($film_color);
			$hsl = WaffTwo\Core\wacp_RGBToHSL($rgb);
			if($hsl->lightness < $lightness_threshold)
				$film_color_class = 'contrast--dark card-light';
		}
	
		// print_r(var_dump($promote));
		// print_r(var_dump($film_color));
		$attributes = array(
			'wrapper' 		=> 'div', // div / li
			'title_wrapper' => (($promote=='1')?'h3':'h5'), // h5 / h6
			// section + projection : div
			// Related-sections : li
			'parent' 		=> 'film', // film / projection
			// section : film
			// Projection in fiche film : projection
			// Related-sections : film
			'class' 		=> 'card film-card flex-row flex-wrap '.(($promote=='1')?'col-md-12 h-520-px':'col-md-6 h-280-px').' bg-light my-2 border-0 shadow-sm '.$film_color_class,
			// section : card film-card flex-row flex-wrap col-md-6 bg-light my-2 border-0 h-280-px shadow-sm card-dark
			// Projection in fiche film : card film-card flex-row flex-wrap col-4 --bg-custom mx-2 my-0 border-0 h-300-px shadow-sm --card-white --p-0
			// Related-sections : card film-card --flex-row flex-wrap bg-light border-0 h-200-px shadow-sm card-dark
			'image_class' => '--w-100 '.(($promote=='1')?'h-520-px':'h-280-px').' fit-image',
			// section : w-100 h-280-px fit-image
			// Projection in fiche film : w-100 h-600-px fit-image
			// Related-sections : w-100 --h-100 h-200-px fit-image
			'image_width' => 'w-60',
			// section : w-60
			// Projection in fiche film : w-50 float-left
			// Related-sections : w-150-px
			'body_width' => 'w-40',
			// section : w-40
			// Projection in fiche film : w-50 h-100
			// Related-sections : w-250-px
			'show_sections' => 'false', // string = false / true
			'show_cats' 	=> 'true', // string = false / true
			'show_excerpt' 	=> 'true', // string = false / true
			'excerpt_length' => '100',
			// section = room : 100
			// Projection in fiche film : 80
			// Related-sections : 60
			'show_rooms' 	=> 'false', // string = false / true
			'items' 		=> '', // string = @film_projection.parent / empty
			// Parent items 
			// Color
			'film_color'	=> (($promote=='1' && $film_color != '')?$film_color:''),
		);
		$subdomain = substr($_SERVER['SERVER_NAME'],0,4);
		$view_id = ( $subdomain == 'dev2.' || $subdomain == 'www.' )?54057:44405;
		if ( defined('wacp_THEME') && wacp_THEME == 'DINARD' )
			$view_id = 670;
		$html .= render_view_template( $view_id, $film_ID ); // ID de la vue Film card / film-card
	endforeach;
	
	$html .= '</section><!-- END FILM CARD -->'; // wacp-favorite-films-cards
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

/**
 * Shortcode to display favorite sections from current edition
 * This shortcode renders the meta-box/wa-sections block from the theme
 * with sections displayed at 15% opacity if they don't contain any favorite films
 * Usage: [wacp_favorite_sections]
 */
add_shortcode( 'wacp_favorite_sections', 'wacp_favorite_sections_shortcode' );

function wacp_favorite_sections_shortcode( $atts ) {
	global $current_edition_id;

	// Parse shortcode attributes
	$atts = shortcode_atts( array(
		'align' => 'wide', // Alignment: wide, full, left, right, center
	), $atts, 'wacp_favorite_sections' );

	// Get user favorites films
	$user_id = get_current_user_id();
	$user_favorites = array();
	if ( $user_id ) {
		$user_favorites = wacp_user_get_favorites( $user_id );
	}

	// Get sections which have thoses favorites to $sections__in
	// Get all sections for the edition to determine which ones have favorite films
	$parent_section_args = array(
		'taxonomy' => 'section',
		'posts_per_page' => -1,
		'hide_empty' => false,
		'parent' => 0,
		'number' => 1,
		'meta_query' => array(
			array(
				'key' => 'wpcf-select-edition',
				'compare' => '=',
				'value' => $current_edition_id,
			),
		),
	);
	$parent_sections = get_terms( $parent_section_args );

	$parent_section_id = 0;
	if ( ! empty( $parent_sections ) && ! is_wp_error( $parent_sections ) ) {
		$parent_section_id = $parent_sections[0]->term_id;
	}

	// Get all child sections
	$all_section_args = array(
		'taxonomy' => 'section',
		'posts_per_page' => -1,
		'hide_empty' => false,
		'parent' => $parent_section_id,
		'meta_query' => array(
			array(
				'key' => 'wpcf-select-edition',
				'compare' => '=',
				'value' => $current_edition_id,
			),
		),
	);
	$sections = get_terms( $all_section_args );

	// Build array of section IDs that contain favorite films
	$sections_with_favorites = array();
	if ( ! empty( $sections ) && ! is_wp_error( $sections ) ) {
		foreach ( $sections as $section ) {
			// Check if any favorite film belongs to this section
			foreach ( $user_favorites as $film_id ) {
				$film_sections = wp_get_post_terms( $film_id, 'section', array( 'fields' => 'ids' ) );
				if ( ! is_wp_error( $film_sections ) && in_array( $section->term_id, $film_sections, true ) ) {
					$sections_with_favorites[] = $section->term_id;
					break; // No need to check other films for this section
				}
			}
		}
	}

	// Build attributes array to pass to the block callback
	$block_attributes = array(
		'id' => 'wacp-favorite-sections-' . wp_generate_uuid4(),
		'name' => 'meta-box/wa-sections',
		'className' => 'wacp-favorite-sections-block',
		'align' => $atts['align'],
		'data' => array(
			'waff_sl_title' => __( 'My favorite sections', 'wacp' ),
			'waff_sl_content' => __( 'My favorite films are in these sections...', 'wacp' ),
			'waff_sl_edition' => $current_edition_id,
			'waff_sl_show_introduction' => 0,
			'waff_sl_show_parent_section' => 0,
			'waff_sl_show_tiny_list' => 1,
			'waff_sl_sections_in' => $sections_with_favorites,
		),
	);

	// Check if the theme function exists
	if ( ! function_exists( 'WaffTwo\Blocks\Block\wa_sections_callback' ) ) {
		return '<p>' . esc_html__( 'The wa-sections block is not available in your theme.', 'wacp' ) . '</p>';
	}

	// Start output buffering to capture the block output
	ob_start();

	// Call the theme's block callback function
	\WaffTwo\Blocks\Block\wa_sections_callback( $block_attributes );

	// Get the output
	$output = ob_get_clean();

	// If no favorites, return the output as-is (all sections visible normally)
	if ( empty( $user_favorites ) ) {
		return $output;
	}

	return $output;
}

/**
 * Shortcode to display login/register or logout links
 * Usage: [wacp_login_links]
 * Attributes:
 *   - class: Additional CSS classes (optional)
 *   - show_icon: Show icon (true/false, default: true)
 *   - separator: Text separator between links (default: ' – ')
 */
add_shortcode( 'wacp_login_links', 'wacp_login_links_shortcode' );

function wacp_login_links_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'class'     => '',
		'separator' => ' – ',
	), $atts, 'wacp_login_links' );

	$separator = esc_html( $atts['separator'] );
	$extra_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';

	$portal_url = esc_url( wacp_get_portal_page_url() );

	$html = '<div class="wacp-login-links' . $extra_class . '">';

	if ( is_user_logged_in() ) {
		// User is logged in: show link to account and logout
		$user = wp_get_current_user();
		$logout_url = wp_logout_url( home_url() );

		$html .= '<a class="wacp-portal-link" href="' . $portal_url . '">';
		$html .= sprintf( esc_html__( 'My account (%s)', 'wacp' ), esc_html( $user->display_name ) );
		$html .= '</a>';
		$html .= '<span class="wacp-separator">' . $separator . '</span>';
		$html .= '<a class="wacp-logout-link" href="' . esc_url( $logout_url ) . '">';
		$html .= esc_html__( 'Log out', 'wacp' );
		$html .= '</a>';
	} else {
		// User is not logged in: show register and login links
		$html .= '<a class="wacp-register-link btn btn-light text-dark-action-1" href="' . $portal_url . '">';
		$html .= esc_html__( 'Create my account', 'wacp' );
		$html .= '</a>';
		$html .= '<span class="wacp-separator">' . $separator . '</span>';
		$html .= '<span>' . esc_html__( 'or', 'wacp' ) . '</span>';
		$html .= '<span class="wacp-separator">' . $separator . '</span>';
		$html .= '<a class="wacp-login-link btn btn-outline-light" href="' . $portal_url . '">';
		$html .= esc_html__( 'Log in', 'wacp' );
		$html .= '</a>';
	}

	$html .= '</div>';

	return $html;
}
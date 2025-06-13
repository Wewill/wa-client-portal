<?php
/**
 * Handles private page functionality for the plugin.
 *
 * @package Wa_Client_Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Wa_Client_Portal_Private_Pages {

    /**
     * Initialize hooks for private page functionality.
     */
    public static function init() {
        add_action( 'template_redirect', [ __CLASS__, 'restrict_private_pages' ], 99 );
    }

    /**
     * Restrict access to private pages.
     */
    public static function restrict_private_pages() {
        if ( !is_user_logged_in() ) {
            global $post;

            // Retrieve the current post object if $post is not set.
            if ( ! isset( $post ) ) {
                $post = get_queried_object();
            }

            // Check if the page is marked as private.
            if ( isset( $post->post_status ) && $post->post_status === 'private' ) {
                // Load the header.
                get_header();

                // Display the login form in a styled container.
                echo '<div class="login-form-container" style="max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; background: #f9f9f9;">';
                echo '<h2 style="text-align: center;">Login Required</h2>';
                echo wp_login_form( [ 'echo' => false ] );
                echo '</div>';

                // Load the footer.
                get_footer();
                exit;
            }
        }
    }
}

// Initialize the private page functionality.
Wa_Client_Portal_Private_Pages::init();

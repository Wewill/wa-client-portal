<?php
/**
 * Handles authentication-related functionality for the plugin.
 *
 * @package Wa_Client_Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Wa_Client_Portal_Auth {

    /**
     * Register shortcodes for login, register, and password recovery.
     */
    public static function register_shortcodes() {
        add_shortcode( 'wa_login_form', [ __CLASS__, 'render_login_form' ] );
        add_shortcode( 'wa_register_form', [ __CLASS__, 'render_register_form' ] );
        add_shortcode( 'wa_password_recovery_form', [ __CLASS__, 'render_password_recovery_form' ] );
    }

    /**
     * Render the login form.
     */
    public static function render_login_form() {
        if ( is_user_logged_in() ) {
            return '<p>You are already logged in.</p>';
        }

        ob_start();
        wp_login_form();
        return ob_get_clean();
    }

    /**
     * Render the registration form.
     */
    public static function render_register_form() {
        if ( is_user_logged_in() ) {
            return '<p>You are already registered and logged in.</p>';
        }

        ob_start();
        // Add custom registration form here.
        echo '<p>Registration form goes here.</p>';
        return ob_get_clean();
    }

    /**
     * Render the password recovery form.
     */
    public static function render_password_recovery_form() {
        ob_start();
        // Add custom password recovery form here.
        echo '<p>Password recovery form goes here.</p>';
        return ob_get_clean();
    }
}

// Register shortcodes.
Wa_Client_Portal_Auth::register_shortcodes();

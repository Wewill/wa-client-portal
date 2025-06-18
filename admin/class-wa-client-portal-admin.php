<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.wilhemarnoldy.fr
 * @since      1.0.0
 *
 * @package    Wa_Client_Portal
 * @subpackage Wa_Client_Portal/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wa_Client_Portal
 * @subpackage Wa_Client_Portal/admin
 * @author     Wilhem Arnoldy <contact@wilhemarnoldy.fr>
 */
class Wa_Client_Portal_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wa_Client_Portal_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wa_Client_Portal_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wa-client-portal-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wa_Client_Portal_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wa_Client_Portal_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wa-client-portal-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Load the required dependencies for admin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Include the menu class.
		require_once plugin_dir_path( dirname( __FILE__ ) )  . 'admin/class-wa-client-portal-menu.php';

		// Include user custom fields register 
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wa-client-portal-user.php';

		// Include user custom role 
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wa-client-portal-roles.php';

		// Include members export
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wa-client-portal-export.php';

	}

	/**
	 * Run the required dependencies for admin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function run_dependencies() {
		// After init hooks
	}

	/**
	 * Init plugin
	 *
	 * @since    1.1.0
	 */
	public function init_plugin() {
		$this->load_dependencies();
		$this->run_dependencies();
	}

	/**
	 * Init admin
	 *
	 * @since    1.2.0
	 */
	public function init_admin() {
		//$screen = get_current_screen(); //$screen->id
		global $pagenow;

		// Check dependencies
		if ( !is_login() && is_admin() && !in_array( $pagenow, array( 'plugins.php' ) ) && !function_exists('rwmb_meta') ) {
			wp_die('Error : please install Meta Box plugin.');
		}

		// if ( !is_login() && is_admin() && !in_array( $pagenow, array( 'plugins.php' ) ) && !function_exists('mb_term_meta_load') ) {
		// 	wp_die('Error : please install Meta Box Term meta plugin.');
		// }

		if ( !is_login() && is_admin() && !in_array( $pagenow, array( 'plugins.php' ) ) && !function_exists('mb_settings_page_load') ) {
			wp_die('Error : please install Meta Box Settings plugin.');
		}

		// if ( !is_login() && is_admin() && !in_array( $pagenow, array( 'plugins.php' ) ) && !class_exists('MB_Text_Limiter') ) {
		// 	wp_die('Error : please install Meta Box Text limiter plugin.');
		// }

		if ( !is_login() && is_admin() && !in_array( $pagenow, array( 'plugins.php' ) ) && !function_exists('mb_user_meta_load') ) {
			wp_die('Error : please install Meta Box User meta plugin.');
		}


	}

}

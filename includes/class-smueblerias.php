<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://codigonube.com/urano-gonzalez/
 * @since      1.0.0
 *
 * @package    Smueblerias
 * @subpackage Smueblerias/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Smueblerias
 * @subpackage Smueblerias/includes
 * @author     Urano G <urano@codigonube.com>
 */
class Smueblerias {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Smueblerias_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;
	protected $updater;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'SMUEBLERIAS_VERSION' ) ) {
			$this->version = SMUEBLERIAS_VERSION;
		} else {
			$this->version = '1.0.1';
		}
		$this->plugin_name = 'smueblerias';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Smueblerias_Loader. Orchestrates the hooks of the plugin.
	 * - Smueblerias_i18n. Defines internationalization functionality.
	 * - Smueblerias_Admin. Defines all hooks for the admin area.
	 * - Smueblerias_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smueblerias-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smueblerias-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-smueblerias-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-smueblerias-public.php';

		/**
		 * Define los menúes y campos de Admin Config
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-smueblerias-settings.php';
		$this->loader = new Smueblerias_Loader();

		/**
		 * Código para pantallas de producto y operaciones de sync
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/ugdev.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Smueblerias_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Smueblerias_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Smueblerias_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		//$this->loader->add_action('woocommerce_product_options_stock_fields', $plugin_admin, 'ug_stock_id_product_field');
		//$this->loader->add_action('woocommerce_process_product_meta', $plugin_admin, 'ug_stock_id_product_field_save');
		//$this->loader->add_action('woocommerce_get_stock_quantity', $plugin_admin, 'ug_stock_get_stock_quantity', 10, 2);
		//$this->loader->add_action('woocommerce_product_get_stock_quantity', $plugin_admin, 'ug_stock_get_stock_quantity', 10, 2);
		//$this->loader->add_action('save_post_product', $plugin_admin, 'ug_reset_product_erp', 10, 2);//esto se dispara cuando borras.. revisar
		$this->loader->add_action('admin_head', $plugin_admin, 'ug_hide_wc_refund_button');
		
		
		

		$plugin_settings = new Smuebleria_Admin_Settings ($this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $plugin_settings, 'setup_plugin_options_menu' );
		$this->loader->add_action( 'admin_init', $plugin_settings, 'initialize_config_options' );
		$this->updater = Puc_v4_Factory::buildUpdateChecker(
			'https://codigonube.com/p/update/?action=get_metadata&slug='. $this->plugin_name,
			//__FILE__, //Full path to the main plugin file or functions.php.
			plugin_dir_path( dirname( __FILE__ ) )."smueblerias.php", //Full path to the main plugin file or functions.php.
			$this->plugin_name
		);

	
	}

	/**
	 * Register all the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Smueblerias_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action('woocommerce_created_customer', $plugin_public, 'create_erp_customer', 10, 3);
		$this->loader->add_action('woocommerce_checkout_update_order_meta', $plugin_public, 'create_erp_order', 10, 2);
		$this->loader->add_action('woocommerce_payment_complete', $plugin_public, 'send_payment', 10, 1);
		//TODO Borrar esta sección. Extraía los 4 dígitos de tarjeta. No funcionó
		/* $this->loader->add_action('wc_gateway_stripe_process_response', $plugin_public, 'save_payment_data', 10, 2);
		$this->loader->add_action('wc_gateway_stripe_process_payment', $plugin_public, 'save_payment_data', 10, 2); */
		$this->loader->add_action('woocommerce_checkout_process', $plugin_public, 'validate_stock_erp', 10, 2);
		$this->loader->add_filter( 'woocommerce_locate_template', $plugin_public, 'woocommerce_locate_template', 10, 3 );

		//Set the quantity of each child product in Grouped Products
		$this->loader->add_filter( 'woocommerce_grouped_product_list_before_quantity', $plugin_public, 'ug_grouped_product_list_before_quantity', 10, 2 );
	}

	/**
	 * Run the loader to execute all the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Smueblerias_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * "pinta" el IdProducto
	 *
	 * @return void
	 */
	public function ug_stock_id_product_field() {
		$description = sanitize_text_field( __('Este IdProducto es usado para sincronizar con el ERP') );
		$placeholder = sanitize_text_field( __('Enter the Service ID') );
		$args = array(
		'id' => '_IdProducto',
		'label' => sanitize_text_field( 'Service ID' ),
		'placeholder' => $placeholder,
		'desc_tip' => true,
		'description' => $description,
		);
		woocommerce_wp_text_input( $args );
	}

	public function ug_stock_id_product_field_save( $post_id ) {
		if ( ! ( isset( $_POST['woocommerce_meta_nonce'], $_POST[ '_IdProducto' ] ) 
			|| wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) ) {
		return false;
		}
		$service_product_id = sanitize_text_field(wp_unslash( $_POST[ '_IdProducto' ] )	);
		update_post_meta($post_id,'_IdProducto',esc_attr( $service_product_id )	);
	}

}

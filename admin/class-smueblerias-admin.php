<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://codigonube.com/urano-gonzalez/
 * @since      1.0.0
 *
 * @package    Smueblerias
 * @subpackage Smueblerias/admin
 */

require plugin_dir_path(dirname(__FILE__)) . 'updater/plugin-update-checker.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Smueblerias
 * @subpackage Smueblerias/admin
 * @author     Urano G <urano@codigonube.com>
 */
class Smueblerias_Admin
{

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
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->load_dependencies();
	}


	/**
	 * Load the requiered dependencies for admin face
	 */
	private function load_dependencies()
	{
		require_once plugin_dir_path(dirname(__FILE__)) .  'admin/class-smueblerias-settings.php';
		//require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/ugdev.php';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smueblerias_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smueblerias_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/smueblerias-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smueblerias_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smueblerias_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/smueblerias-admin.js', array('jquery'), $this->version, false);
	}

	//@todo eliminar esta función
	/* public function ug_stock_get_stock_quantity( $amount, $product ){
		$product_id = $product->get_id();
		//Set transient for avoid update many times
		$updating_product_id = 'update_product_' . $product_id;
		if ( true === get_transient($updating_product_id) ) {
			return $amount;
		}

		set_transient($updating_product_id , $product_id, 3); // change 3 seconds if not enough

		$stock_amount = $this->ug_stock_get_level_for_product ($product->get_id());
		if( is_wp_error( $stock_amount ) ) {
			// API returned an Error, what do to? You can return the current amount or return 0 to be sure
			// I will return 0 because I don't want to sell something that I can't ship
			error_log('saliendo por error...');
			return 0;
		}
		if( false === $stock_amount ) {
			// The product is not a service product
			error_log("saliendo por false $amount...");
			return $amount;
		}
		// Perform Other checks if needed
		
		//$product->set_stock_quantity($stock_amount);
		error_log("saliendo por data actualizada $stock_amount-> $product_id...");
		return $stock_amount;
	} */

	//@todo eliminar esta función	
	/* public function ug_stock_get_level_for_product($id_product){
		$service = 'ObtenerDatosProductosPorIdProducto';
		$options=get_option('smu_config_options');
		$id_producto = get_post_meta($id_product, '_IdProducto', true);
		$url = $options['smu_url'] . $service . '?' . 'claveServicio=' . $options['smu_clave_servicio'] . 
		'&idEmpresa=' . $options['smu_empresa'] . '&idUsuario=' .  $options['smu_usuario'] .
		'&idProducto=' . $id_producto;

		error_log("URL para llamado: $url" );
		$response = wp_remote_get($url);
		$response = json_decode($response['body']);
		$stock = $response[0]->Existensia;
		//error_log("respuesta del servicio para stock ($stock): " . print_r($response, true));
		if (is_wp_error($response)){
			return 0;
		}else{
			return $stock;
		}
	} */


	public function ug_reset_product_erp($product_id, $product) //esto se dispara cuando borras.. revisar el hook que lo invoca
	{
		//Set transient for avoid update many times, too often
		$updating_product_id = 'update_product_' . $product_id;
		if (!(false === get_transient($updating_product_id))) {
			return;
		}
		set_transient($updating_product_id, $product_id, 5); // change 5 seconds if not enough

		$erp_product = $this->ug_get_product_erp($product_id);
		if (false === $erp_product) {
			// API REST returned an Error, what do to? You can return the current amount or return 0 to be sure
			// I will return 0 because I don't want to sell something that I can't ship
			return;
		}
		ug_create_product($erp_product);
		error_log("reset Producto actualizado $product_id..." . print_r($product,true));
		return;
	}

	public function ug_get_product_erp($product_id)
	{
		$service = 'ObtenerDatosProductosPorIdProducto';
		$options = get_option('smu_config_options');
		$id_producto = get_post_meta($product_id, '_IdProducto', true);
		if ('' === $id_producto) {
			return false;
		}
		$url = $options['smu_url'] . $service . '?' . 'claveServicio=' . $options['smu_clave_servicio'] .
			'&idEmpresa=' . $options['smu_empresa'] . '&idUsuario=' .  $options['smu_usuario'] .
			'&idProducto=' . $id_producto;

		error_log("URL para llamado: $url");
		$response = wp_remote_get($url);
		if (is_wp_error($response)) {
			error_log("Respuesta ($id_producto): " . print_r($response, true));
			return false;
		}
		$response = json_decode($response['body']);
		error_log("Service $service, respuesta ($id_producto): " . print_r($response, true));
		if (is_wp_error($response)) {
			return false;
		} else {
			return $response[0];
		}
	}

	public function ug_hide_wc_refund_button()
	{
		global $post;

		if (!current_user_can('administrator') && !current_user_can('editor')) {
			return;
		}
		if (strpos($_SERVER['REQUEST_URI'], 'post.php?post=') === false) {
			return;
		}

		if (empty($post) || $post->post_type != 'shop_order') {
			return;
		}
?>
		<script>
			jQuery(function() {
				jQuery('.refund-items').hide();
				jQuery('.order_actions option[value=send_email_customer_refunded_order]').remove();
				if (jQuery('#original_post_status').val() == 'wc-refunded') {
					jQuery('#s2id_order_status').html('Refunded');
				} else {
					jQuery('#order_status option[value=wc-refunded]').remove();
				}
			});
		</script>
<?php

	}
}

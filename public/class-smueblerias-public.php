<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://codigonube.com/urano-gonzalez/
 * @since      1.0.0
 *
 * @package    Smueblerias
 * @subpackage Smueblerias/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Smueblerias
 * @subpackage Smueblerias/public
 * @author     Urano G <urano@codigonube.com>
 */
class Smueblerias_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/smueblerias-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/smueblerias-public.js', array( 'jquery' ), $this->version, false );

	}

	function woocommerce_locate_template ($template, $template_name, $template_path){
		global $woocommerce;
		$_template = $template;

		if ( ! $template_path ) $template_path = $woocommerce->template_url;
		
		$plugin_path  = plugin_dir_path( dirname( __FILE__ ) ) . 'woocommerce/';

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
			$template_path . $template_name,
			$template_name
			)
		);
		// Modification: Get the template from this plugin, if it exists
		if ( ! $template && file_exists( $plugin_path . $template_name ) ){
			$template = $plugin_path . $template_name;
		}

		// Use default template
		if ( ! $template )
			$template = $_template;

		// Return what we found
		return $template;
	}
	//@todo Something Eliminar, no se utilizará actualizar en Public
	//si se va a usar... directamente 
	//funcionalidad repetido en class-mueblerias-admin.php
	public function get_existencias_erp($product_id_erp){

		$service = 'ObtenerDatosProductosPorIdProducto';
		$options=get_option('smu_config_options');
		$id_producto = $product_id_erp;
		error_log ("Pasando para actualziar esistencias producto ($id_producto)");
		if ($id_producto === ''){
			return 0;
		}
 		$url = $options['smu_url'] . $service . '?' . 'claveServicio=' . $options['smu_clave_servicio'] . 
			'&idEmpresa=' . $options['smu_empresa'] . '&idUsuario=' .  $options['smu_usuario'] .
			'&idProducto=' . $id_producto;
		error_log ("URL para llamar servicio $url");
		$response = wp_remote_get($url);
		$producto = json_decode($response['body']);
		error_log("Service $service, respuesta ($id_producto): " . print_r($response, true));
		
		//update_post_meta( $id_producto, '_stock', $producto[0]->Existensia );
		return $producto[0]->Existensia;
	}

	function create_erp_customer ($customer_id, $new_customer_data, $password_generated){
		error_log ("creando customer" . $customer_id);
		// Get the New Customer's data
		$username   = $new_customer_data['user_login'];
		$password   = $new_customer_data['user_pass'];
		$correo      = $new_customer_data['user_email'];
		$role       = $new_customer_data['role'];

		// Getting the rest of the info for this customer
		$user = get_user_by( 'id', $customer_id );
		$nombre = $user->first_name;
		$apellidos = $user->last_name;
		// Continue and send the information to ERP now
		$erp_cliente_id = $this->insertar_cliente($nombre, $apellidos, $correo, $password, '5555555555');
		update_user_meta($customer_id, '_erp_id_cliente', $erp_cliente_id);
	}

	function insertar_cliente($nombre, $apellidos, $correo, $password, $telefono){
		$service = 'InsertarCliente';
		$options=get_option('smu_config_options');
		$url = $options['smu_url'] . $service . '?' . 'claveServicio=' . $options['smu_clave_servicio'] . 
			'&idEmpresa=' . $options['smu_empresa'] . '&idUsuario=' .  $options['smu_usuario'] .
			"&nombre=". str_replace(" ", "%20", $nombre) . "&apellidos=" . str_replace(" ", "$20", $apellidos) . "&correo=$correo" . "&password=$password" . 
			"&telefono=$telefono"; 
		$response = wp_remote_get($url);
		error_log("respuesta del servicio: " . print_r($response, true));
		$cte_id = json_decode($response['body']);
		error_log ("Cliente creado:" . print_r($cte_id, true));
		return $cte_id;
	}

	function insertar_direccion ($id_order, $type){
		$service = 'InsertarDireccionCliente';
		$options=get_option('smu_config_options');
		// Get an instance of the WC_Order object (same as before)
		$order = wc_get_order($id_order);
		$id_user = $order->get_user_id();

		$add_billing = $order->get_address($type);
		error_log ("DEBUG: Dir $type: " . print_r($add_billing,true));
		if ($add_billing['address_a'] = ''){
			error_log("Function " . __FUNCTION__ .". Order $id_order, type: $type, no found");
			return;
		}
		$data['idCliente'] = get_user_meta($id_user, '_erp_id_cliente', true);
		$data['idDireccion'] = 0;
		$data['calle'] = str_replace (" ", "%20", $add_billing['address_1']);
		$data['CP'] = str_replace (" ", "%20", $add_billing['postcode']);
		$data['telefono'] = str_replace (" ", "%20", isset($add_billing['phone'])?$add_billing['phone']:'');
		$data['RFC'] = '';
		$data['razonSocial'] = str_replace (" ", "%20", $add_billing['company']);
		$data['colonia'] = str_replace (" ", "%20", $add_billing['address_2']);
		$data['municipio'] = str_replace (" ", "%20", $add_billing['city']);
		$data['ciudad'] =  str_replace (" ", "%20", $add_billing['city']);
		$data['estado'] = str_replace (" ", "%20", $add_billing['state']);
		$data['referencias'] = '';

		$url = $options['smu_url'] . $service . '?' . 'claveServicio=' . $options['smu_clave_servicio'] . 
		'&idEmpresa=' . $options['smu_empresa'] . '&idUsuario=' .  $options['smu_usuario'];
		foreach ($data as $key => $value){
			$url .= "&$key=$value";
		}
		
		$response = wp_remote_get($url);
		$dir_id = json_decode($response['body']);
		error_log("Service: $service, Dir_id: $dir_id. Data: " . print_r($data, true).", url: para insertar direccion $url");
		update_post_meta($id_order, "_erp_direccion_$type", $dir_id);
		return $dir_id;		
	}

	function insertar_venta ($id_order){
		$service = 'InsertarVentas';
		$chosen_payment_method = WC()->session->get('chosen_payment_method'); //Get the selected payment method
		error_log ("gateway usado para orden $chosen_payment_method");
		$venta_id = get_post_meta($id_order, "_erp_venta_id", true);
		if ($venta_id > 0){
			return $venta_id;
		}
		$options=get_option('smu_config_options');
		// Get an instance of the WC_Order object (same as before)
		$order = wc_get_order($id_order);
		$id_user = $order->get_user_id();
		$prices_include_tax    = get_option( 'woocommerce_prices_include_tax' );

		$url = $options['smu_url'] . $service . '?' . 'claveServicio=' . $options['smu_clave_servicio'] . 
		'&idEmpresa=' . $options['smu_empresa'] . '&idUsuario=' .  $options['smu_usuario'];

		$data = array();
		if ($chosen_payment_method == 'cotzn-gateway'){
			$data['idTipodeOrden'] = 2;
		} else {
			$data['idTipodeOrden'] = 3;
		}
		
		
		$data['idCliente'] = get_user_meta($id_user, '_erp_id_cliente', true);
		$data['direccion'] = get_post_meta($id_order, "_erp_direccion_shipping", true);
		if ($data['direccion'] == ''){
			$data['direccion'] = get_post_meta($id_order, "_erp_direccion_billing", true);
		}
		$data['direccionFacturacion'] = get_post_meta($id_order, "_erp_direccion_billing", true);
		$data['subtotal'] = $order->get_subtotal();
		$data['iva']= $order->get_total() - $order->get_subtotal();
		$data['total'] = $order->get_total();
		$data['fechaEntrega'] = date('Y-m-d', strtotime('+15 days'));
		$data['comentarios'] =  '"' . $order->get_customer_note() . '"';
		$data['descuento'] = $order->get_discount_total();
		$items = $order->get_items();
		$s ='';
		$list_products = array();
		$i = 0;
		foreach ($items as $item){
			$list_products[$i]['Cantidad'] = $item->get_quantity();
			$list_products[$i]['IdProducto'] = get_post_meta ($item->get_product_id(), '_IdProducto', true);;
			$the_wc_product = new WC_Product($item->get_product_id());  
			$list_products[$i]['Precio'] = $the_wc_product->get_price();
			$list_products[$i]['PrecioOriginal'] = $the_wc_product->get_regular_price();
			$list_products[$i]['Total'] = $item->get_total();
			error_log("Service $service, loop:" . print_r($item,true) . "newprod->".print_r($list_products[$i],true));
			$i++;
		}
		error_log ("productos a enviar: " . print_r($list_products,true));
		$data['productos'] = urlencode(json_encode($list_products));
		error_log('mensaje a enviar ' . print_r($data, true));
		
		foreach ($data as $key => $value){
			
				$url .= "&$key=$value";
			
		}
		//$url = str_replace(' ', '%20', $url);
		error_log('venta url a usar ' . $url);
		/* $url = rawurlencode($url);
		error_log('venta url codificada a usar ' . $url); */
		$response = wp_remote_get($url);
		error_log(print_r($response,true));
		$venta_resp = json_decode($response['body']);
		error_log ("resultado de body:". print_r($venta_resp,true));
		$venta_id = $venta_resp[0]->IdVenta;
		$folio_venta = $venta_resp[0]->FolioVenta;
		error_log(print_r("El id de la venta es ".print_r($venta_id,true),true));
		if (!is_integer($venta_id)){
			$venta_id = 0;
		}else{
			error_log ("Venta creada:" . print_r($venta_id, true));
			update_post_meta($id_order, "_erp_venta_id", $venta_id);
			update_post_meta ($id_order, '_erp_folio_venta', $folio_venta);
		}
		return $venta_id;
	}

	public function create_erp_order($order_id, $data){
		error_log ("Create erp Order $order_id, Data: ".print_r($data, true));
		$this->insertar_direccion($order_id, "billing");
		$this->insertar_direccion($order_id, "shipping");
		$this->insertar_venta($order_id);
	}

	public function send_payment($order_id){
		//Si ya registramos un pago, no lo reeenviamos, para no repetir
		$service = 'InsertarPagosClientesPorVenta';
		$payment_id = get_post_meta($order_id, "_erp_payment_id", true);
		if (is_integer($payment_id)){
			error_log("Servicio $service, pago ya registrado cod id = $payment_id, orden Woo $order_id");
			return 0;
		}
		//Validamos que haya registrada una venta en esta orden
		$venta_id = get_post_meta($order_id, "_erp_venta_id", true);
		if ($venta_id ===''){
			error_log("Servicio $service, sin venta asociada, orden Woo $order_id");
			return 0;
		}
		
		$options=get_option('smu_config_options');
		$order = wc_get_order($order_id);
		$id_user = $order->get_user_id();
		// Get an instance of the WC_Order object (same as before)
		$order = wc_get_order($order_id);
		$data = array();
		$data['claveServicio'] = $options['smu_clave_servicio'];
		$data['idEmpresa'] = $options['smu_empresa'];
		$data['idUsuario'] = $options['smu_usuario'];
		$data['IdCliente'] = get_user_meta($id_user, '_erp_id_cliente', true);
		$data['IdVenta'] = $venta_id;
		$data['Monto'] = $order->get_total();

		$s = get_post_meta($order_id, '_payment_method',true);
		//Forma de pago: Otra
		$data['FormaPago'] = 11805;
		//Stripe
		if (strpos($s, 'stripe')){
			$data['FormaPago'] = 11802;	
		}
		// Openpay
		if (strpos($s, 'openpay')){
			$data['FormaPago'] = 11803;	
		}
		//Conekta
		if (strpos($s, 'conekta')){
			$data['FormaPago'] = 11804;	
		}
		$data['Voucher'] = $order->get_transaction_id();
		
		$data['NoCuenta'] = '    ';

		$url = $options['smu_url'] . $service . '?';
		foreach ($data as $key => $value){
			$url .= "&$key=$value";
		}
		$url = str_replace(' ', '%20', $url);
		$response = wp_remote_get($url);
		$json = json_decode($response['body']);
		$payment_id = $json;
		error_log ("Método de pago: " . $s);
		error_log ("La data del mensaje enviado: " . print_r($data,true));
		error_log("Servicio $service, la venta asociada es $venta_id, respuesta $json, url: $url");
		update_post_meta($order_id, "_erp_payment_id", $payment_id);
		return $payment_id;
	}

	//This works only for Stripe
	public function save_payment_data($response, $order){
		error_log("saving info from Stripe" . print_r($response,true));
		error_log('orden '. $order->get_id());
		$last4 = $response->payment_method_details->card->last4;
		$order_id = $order->get_id();
		update_post_meta($order_id, "_erp_last4", $last4);
	}

	//Function to bee hooked in woocommerce_checkout_process, podría ser woocommerce_before_checkout_process
	public function validate_stock_erp (){
		$items = WC()->cart->get_cart_contents();
		foreach ($items as $item){
			$product_id = $item['product_id'];
			$qty = $item['quantity'];
			$stock_quantity = $item['data']->get_stock_quantity();
			$sku = $item['data']->get_sku();
			error_log ("id: {$item['product_id']}, qty: {$item['quantity']}, stock: {$stock_quantity}, SKU: {$sku}");
			$product_id_erp = get_post_meta ($product_id, '_IdProducto', true);
			$qty_erp = $this->get_existencias_erp($product_id_erp);
			$stock_control = get_option('woocommerce_manage_stock','no');
			if (($qty_erp<$qty)&& 'yes' === $stock_control){
				throw new Exception( sprintf( __( 'Stock insuficiente para ' . $item[data]->get_name() . " tengo $qty_erp " . '... <a href="%s" class="wc-backward">Regresar al carrito</a>', 'woocommerce' ), esc_url( wc_get_page_permalink( 'cart' ) ) ) );		
			}

			//error_log (print_r($item['data'],true));
		}
	}

}

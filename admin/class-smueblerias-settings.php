<?php

/**
 * The settings of the plugin.
 *
 * @link       https://codigonube.com
 * @since      1.0.0
 *
 * @package    Smuebleria_Plugin
 * @subpackage Smuebleria_Plugin/admin
 */

/**
 * Class WordPress_Plugin_Template_Settings
 *
 */
class Smuebleria_Admin_Settings {

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
	 * The template path
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	 private $template_path;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $template_path ='admin/partials' ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->template_path = rtrim($template_path, '/');

	}

	/**
     * Get the capability required to view the admin page.
     *
     * @return string
     */
    public function get_capability()
    {
        return 'manage_options';
    }
 
    /**
     * Get the title of the admin page in the WordPress admin menu.
     *
     * @return string
     */
    public function get_menu_title()
    {
        return __( 'SMuebleria Configuración', 'smuebleria_plugin' );
    }
 
    /**
     * Get the title of the admin page.
     *
     * @return string
     */
    public function get_page_title()
    {
        return  __( 'SMuebleria Admin Configuración', 'smuebleria_plugin' );
    }
 
    /**
     * Get the parent slug of the admin page.
     *
     * @return string
     */
    public function get_parent_slug()
    {
        return 'smuebleria-config';
    }
 
    /**
     * Get the slug used by the admin page.
     *
     * @return string
     */
    public function get_slug()
    {
        return 'smuebleria';
	}
	
	public function get_name_options(){
		return 'smu_config_options';
	}

	/**
	 * render template
	 *
	 * @return void
	 */
	function render_template($template = 'smueblerias-admin-display'){
		$s = plugin_dir_path( dirname( __FILE__ ) ) . $this->template_path . '/' . $template . '.php';
		
		if (!is_readable($s)) {
			error_log("no encontré template [$s]");
            return;
		}
		include $s;
	}

	/**
	 * Render clave_id Field
	 *
	 * @return void
	 */
	public function render_clave_id(){
		$s = $this->get_name_options();
		$options = get_option($s, $this->default_config_options());
		$options = wp_parse_args($options, $this->default_config_options());
		echo '<input type="text" id="input_example" name="'. $s . '[smu_clave_servicio]" value="' . $options['smu_clave_servicio'] . '" />';
	}

	public function render_url(){
		$s = $this->get_name_options();
		$options = get_option($s, $this->default_config_options());
		$options = wp_parse_args($options, $this->default_config_options());
		echo '<input type="text" size="100" id="input_example" name="'. $s . '[smu_url]" value="' . $options['smu_url'] . '" />';
	}
	
	public function render_empresa_id(){
		$s = $this->get_name_options();
		$options = get_option($s, $this->default_config_options());
		$options = wp_parse_args($options, $this->default_config_options());
		echo '<input type="text" id="input_example" name="'. $s . '[smu_empresa]" value="' . $options['smu_empresa'] . '" />';
	}

	public function render_user_id(){
		$s = $this->get_name_options();
		$options = get_option($s, $this->default_config_options());
		$options = wp_parse_args($options, $this->default_config_options());
		echo '<input type="text" id="input_example" name="'. $s . '[smu_usuario]" value="' . $options['smu_usuario'] . '" />';
	}
	
	public function render_check_inventory (){
		$s = $this->get_name_options();
		$options = get_option($s, $this->default_config_options());
		$options = wp_parse_args($options, $this->default_config_options());
		$stock_control = get_option('woocommerce_manage_stock','no');
		$options['smu_inventario'] = $stock_control;
		//echo '<input type="checkbox" id="inventory" name="'.$s.'[smu_inventario]" value="' .$options['smu_inventario'] . '"'.  (($options['smu_inventario']=="yes")? 'checked':"") . '/>';
		echo '<input type="checkbox" id="inventory" name="'.$s.'[smu_inventario]" value="yes"'. checked('yes',$options['smu_inventario'], false). '/>';
	}

	public function render_check_disable_load_description(){
		$s = $this->get_name_options();
		$options = get_option($s, $this->default_config_options());
		$options = wp_parse_args($options, $this->default_config_options());
		echo '<input type="checkbox" id="disable_description_id" name="'.$s.'[smu_disable_description]" value="yes"'. checked('yes',$options['smu_disable_description'], false). '/>';
	}

	public function render_check_disable_load_images(){
		$s = $this->get_name_options();
		$options = get_option($s, $this->default_config_options());
		$options = wp_parse_args($options, $this->default_config_options());
		echo '<input type="checkbox" id="disable_description_images" name="'.$s.'[smu_disable_images]" value="yes"'. checked('yes',$options['smu_disable_images'], false). '/>';
	}


	/**
	 * Render Page
	 *
	 * @return void
	 */
	public function render_page(){
		$this->render_template ('page');
	}
	/**
	 * Render section
	 *
	 * @return void
	 */
	public function render_section(){
		$this->render_template ('section');
	}

	/**
	 * Add menu for plugin config
	 */
	public function setup_plugin_options_menu() {
		add_menu_page(
			$this->get_page_title(),
			$this->get_menu_title(),
			$this->get_capability(),
			$this->get_slug(),
			array($this, 'render_page')
		);
		add_submenu_page (
			$this->get_parent_slug(),
			$this->get_page_title(),
			$this->get_menu_title(),
			$this->get_capability(),
			$this->get_slug(),
			array($this, 'render_page')
		);

		add_submenu_page (
			$this->get_parent_slug(),
			$this->get_page_title(),
			$this->get_menu_title(),
			$this->get_capability(),
			$this->get_slug(),
			array($this, 'render_page')
		);
	}

	/**
	 * Provides default values for the Display Configuration.
	 *
	 * @return array
	 */
	public function default_config_options() {

		$defaults = array(
			'smu_url'				=>	'https://sistema.smuebleria.com/ServicePaginas/SMuebleriaPaginas.svc',
			'smu_clave_servicio' 	=>	1,
			'smu_empresa'			=>	2,
			'smu_usuario'			=>	3,
			'smu_inventario'		=> "yes",
			'smu_disable_description' => "no",
		);
		return $defaults;
	}

	public function validate_fields($input){
		// Create our array for storing the validated options
		$output = array();

		// Loop through each of the incoming options
		foreach( $input as $key => $value ) {
			// Check to see if the current option has a value. If so, process it.
			if( isset( $input[$key] ) ) {
				// Strip all HTML and PHP tags and properly handle quoted strings
				$output[$key] = strip_tags( stripslashes( $input[ $key ] ) );
			} 
		}
		error_log("POST: ".print_r($_POST,true));
		error_log("input: ".print_r($input,true));
		error_log("output: ".print_r($output,true));

		/* error_log("validae fields " . print_r($_POST, true));
		error_log("OUTPUT fields " . print_r($output, true)); */
		if (isset($output['smu_inventario'])){
			update_option('woocommerce_manage_stock', 'yes');
		} else{
			update_option('woocommerce_manage_stock', 'no');
		}
		//url ya no se pmuestra en la config, lo agregamos aquí
		if (!isset($output['smu_url'])){
			$output['smu_url'] = 'https://sistema.smuebleria.com/ServicePaginas/SMuebleriaPaginas.svc';
		}
		$output['smu_url'] = rtrim($output['smu_url'], '/') . "/";
		if (isset($_POST['cargar'])){
			$servicio = "ObtenerDatosProductos2";
		} else {
			$servicio = 'ValidarClavesUsuario';
		}

		$url = $output['smu_url'] . $servicio . '?' . 'claveServicio=' . $output['smu_clave_servicio'] . 
			'&idEmpresa=' . $output['smu_empresa'] . '&idUsuario=' .  $output['smu_usuario'];
		
		error_log(__FUNCTION__ . " url " . print_r($url, true));
		$response = wp_remote_get($url,array('timeout' => 65));
		
		if (is_wp_error($response)){
			add_settings_error($this->get_slug(),'xxx', "ERROR Respuesta HTTP (" . print_r($response->get_error_codes(), true) . ") Conexion intentada con $url", 'info');
			error_log(__FUNCTION__ . " error respons " . print_r($response, true));
		}else{
			if (isset($_POST['cargar'])){
				error_log("respuesta de cargar: ". print_r($response['body'],true));
				$productos = json_decode($response['body']);
				error_log("respuesta en decodificada: ". print_r($productos,true));
				$n = count($productos);
				error_log ("Recibí $n productos");
				if ($n==0){
					add_settings_error($this->get_slug(),'xxx', "ERROR no hay productos para carga");
				}else{
					$i = 0;
					foreach ($productos as $producto){
						$i++;
						//ug_create_product($producto);
					}
					add_settings_error($this->get_slug(),'xxx', "Re carga exitosa de productos desde ERP ", 'success');
				}
			}else {
				$json = json_decode($response['body']);
				if ($json===401){
					add_settings_error($this->get_slug(),'xxx', "ERROR en datos para conexión");
				} else{
					add_settings_error($this->get_slug(),'xxx', "Conexión exitosa con ERP ", 'success');
				}
			}
		}
		
		//error_log(__FUNCTION__ . " response " . print_r($response, true));

		//return apply_filters( 'validate_input_examples', $output, $input );
		return $output;
	}
	
	

	/**
	 * Initializes the display options page by registering the Sections,
	 * Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 */
	public function initialize_config_options() {
		// If the options don't exist, create them.
		if( false === get_option( $this->get_name_options()) ) {
			$default_array = $this->default_config_options();
			add_option( $this->get_name_options(), $default_array );
		}

		add_settings_section(
			$this->get_slug() . '-section',		// ID used to identify this section and with which to register options
			__( 'Configuración SMuebleria', 'smuebleria_plugin' ),		// Title to be displayed on the administration page
			array( $this, 'render_section'),			// Callback used to render the description of the section
			$this->get_slug()					// Page on which to add this section of options
		);

		// Next, we'll introduce the fields for toggling the visibility of content elements.
		/* add_settings_field(
			$this->get_slug() . 'url',			// ID used to identify the field throughout the theme
			__( 'URL de API', 'smuebleria_plugin' ),	// The label to the left of the option interface element
			array( $this, 'render_url'),	// The name of the function responsible for rendering the option interface
			$this->get_slug(),							// The page on which this option will be displayed
			$this->get_slug() . '-section'				// The name of the section to which this field belongs
		); */

		// Next, we'll introduce the fields for toggling the visibility of content elements.
		add_settings_field(
			$this->get_slug() . 'clave_id',		        // ID used to identify the field throughout the theme
			__( 'Clave para ERP', 'smuebleria_plugin' ),			// The label to the left of the option interface element
			array( $this, 'render_clave_id'),			// The name of the function responsible for rendering the option interface
			$this->get_slug(),							// The page on which this option will be displayed
			$this->get_slug() . '-section'				// The name of the section to which this field belongs
		);

		// Next, we'll introduce the fields for toggling the visibility of content elements.
		add_settings_field(
			$this->get_slug() . 'empresa_id',	        // ID used to identify the field throughout the theme
			__( 'Empresa para ERP', 'smuebleria_plugin'),	// The label to the left of the option interface element
			array( $this, 'render_empresa_id'),		// The name of the function responsible for rendering the option interface
			$this->get_slug(),							// The page on which this option will be displayed
			$this->get_slug() . '-section'				// The name of the section to which this field belongs
		);

		// Next, we'll introduce the fields for toggling the visibility of content elements.
		add_settings_field(
			$this->get_slug() . 'usuario_id',			// ID used to identify the field throughout the theme
			__( 'Usuario para ERP', 'smuebleria_plugin' ),			// The label to the left of the option interface element
			array( $this, 'render_user_id'),		// The name of the function responsible for rendering the option interface
			$this->get_slug(),							// The page on which this option will be displayed
			$this->get_slug() . '-section'				// The name of the section to which this field belongs
		);

		//control de Inventario
		add_settings_field(
			$this->get_slug() . 'inventory_id',
			__('Esta tienda tiene control de Inventario', 'smuebleria_plugin'),
			array($this, 'render_check_inventory'),
			$this->get_slug(),							// The page on which this option will be displayed
			$this->get_slug() . '-section'				// The name of the section to which this field belongs
		);
		
		//Deshabilitar la descarga de Description
		add_settings_field(
			$this->get_slug() . 'disable_description_id',
			__('Deshabilitar la carga de Descripción', 'smuebleria_plugin'),
			array($this, 'render_check_disable_load_description'),
			$this->get_slug(),							// The page on which this option will be displayed
			$this->get_slug() . '-section'				// The name of the section to which this field belongs
		);

		//Deshabilitar la descarga de Imágenes
		add_settings_field(
			$this->get_slug() . 'disable_description_images',
			__('Deshabilitar la carga de Imágenes', 'smuebleria_plugin'),
			array($this, 'render_check_disable_load_images'),
			$this->get_slug(),							// The page on which this option will be displayed
			$this->get_slug() . '-section'				// The name of the section to which this field belongs
		);


		// seccíón de racarga de recarga de productos
		add_settings_section(
			$this->get_slug() . '-section-recarga',		// ID used to identify this section and with which to register options
			__( 'Recargar Productos', 'smuebleria_plugin' ),		// Title to be displayed on the administration page
			array( $this, 'render_section'),			// Callback used to render the description of the section
			$this->get_slug()					// Page on which to add this section of options
		);

		// Finally, we register the fields with WordPress
		$args = array(
			'type' => 'array',
			'sanitize_callback' => array($this, 'validate_fields')
		);
		register_setting(
			$this->get_slug(),
			$this->get_name_options(),
			$args
		);

	} // end wppb-demo_initialize_theme_options
}
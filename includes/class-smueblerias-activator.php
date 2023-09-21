<?php

/**
 * Fired during plugin activation
 *
 * @link       https://codigonube.com/urano-gonzalez/
 * @since      1.0.0
 *
 * @package    Smueblerias
 * @subpackage Smueblerias/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Smueblerias
 * @subpackage Smueblerias/includes
 * @author     Urano G <urano@codigonube.com>
 */
class Smueblerias_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::create_db();
	}
	
	public static function create_db() {
		global $wpdb;
		$table_name = $wpdb->prefix . "queue_products"; 
		$charset_collate = $wpdb->get_charset_collate();
	  
		$sql[] = "CREATE TABLE " . $table_name . " ( 
		  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT, 
		  idProducto int(11) UNSIGNED NOT NULL,
		  idCategoria int(11) UNSIGNED NOT NULL,
		  categoria varchar(10),
		  modelo varchar(100),
		  nombre varchar(100),
		  clave varchar(25),
		  descripcion varchar(2500),
		  precio decimal(8,2),
		  precioLista decimal(8,2),
		  rutaImagenes varchar(12800),
		  stockIndicador char(01),
		  existencias int(11),
		  descontinuado int(2),
		  peso decimal(8,2),
		  largo decimal(8,2),
		  ancho decimal(8,2),
		  alto decimal(8,2),
		  ultima_actualizacion datetime DEFAULT '2020-01-01 00:00:00', 
		  procesado int(1),
		  INDEX (id),
		  PRIMARY KEY (idProducto)) $charset_collate"; 
	  
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); 
	  
		dbDelta( $sql );
	}


}

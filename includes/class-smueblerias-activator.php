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
		id int(11) unsigned NOT NULL AUTO_INCREMENT,
  idProducto int(11) unsigned NOT NULL,
  idCategoria int(11) unsigned NOT NULL,
  categoria varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  modelo varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  nombre varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  clave varchar(25) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  descripcion varchar(2500) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  precio decimal(8,2) DEFAULT NULL,
  precioLista decimal(8,2) DEFAULT NULL,
  rutaImagenes varchar(12800) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  stockIndicador char(1) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  existencias int(11) DEFAULT NULL,
  descontinuado int(2) DEFAULT NULL,
  peso decimal(8,2) DEFAULT NULL,
  largo decimal(8,2) DEFAULT NULL,
  ancho decimal(8,2) DEFAULT NULL,
  alto decimal(8,2) DEFAULT NULL,
  ultima_actualizacion datetime DEFAULT '2020-01-01 00:00:00',
  procesado int(1) DEFAULT NULL,
  PRIMARY KEY (idProducto),
  KEY id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 " . $charset_collate;
	  
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	  
		dbDelta( $sql );
	}


}

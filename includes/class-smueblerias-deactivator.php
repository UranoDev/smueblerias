<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://codigonube.com/urano-gonzalez/
 * @since      1.0.0
 *
 * @package    Smueblerias
 * @subpackage Smueblerias/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Smueblerias
 * @subpackage Smueblerias/includes
 * @author     Urano G <urano@codigonube.com>
 */
class Smueblerias_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		self::drop_db();
	}

	public static function drop_db() {
		global $wpdb; 
		$table_name = $wpdb->prefix . "queue_products";
		$sql = "DROP TABLE IF EXISTS " . $table_name; 
		$wpdb->query($sql);
	}
}

<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://codigonube.com/urano-gonzalez/
 * @since             1.1.0
 * @package           Smuebleria
 *
 * @wordpress-plugin
 * Plugin Name:       SMuebleria
 * Plugin URI:        codigonube.com/plugin/mueblerias
 * Description:       Plugin para sMuebleria, sync con el ERP 2025-05-14
 * Version:           2.5.1
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            Urano G
 * Author URI:        https://codigonube.com/urano-gonzalez/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       smueblerias
 * Domain Path:       /languages
 * WC requires at least: 8.2.0
 * WC tested up to: 11.2.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SMUEBLERIAS_VERSION', '2.5.1' );

/**
 * Compatibility with WooCommerce features: orders tables (HPOS) yes, block checkout no.
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
	}
} );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-smueblerias-activator.php
 */
function activate_smueblerias() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-smueblerias-activator.php';
	Smueblerias_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-smueblerias-deactivator.php
 */
function deactivate_smueblerias() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-smueblerias-deactivator.php';
	Smueblerias_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_smueblerias' );
register_deactivation_hook( __FILE__, 'deactivate_smueblerias' );

require_once plugin_dir_path( __FILE__ ) . 'includes/smueblerias-log.php';

/**
 * Versión de la tabla de la cola. Al cambiar, maybe_upgrade_db() la actualiza al cargar el plugin.
 */
define( 'SMUEBLERIAS_DB_VERSION', '2' );
function smueblerias_upgrade_db() {
	if ( get_option( 'smu_db_version' ) !== SMUEBLERIAS_DB_VERSION ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-smueblerias-activator.php';
		Smueblerias_Activator::maybe_upgrade_db();
	}
}
add_action( 'plugins_loaded', 'smueblerias_upgrade_db' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-smueblerias.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_smueblerias() {

	$plugin = new Smueblerias();
	$plugin->run();
}
run_smueblerias();

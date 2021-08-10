<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since             1.0.0
 * @package           Raven_woo_ordering
 *
 * @wordpress-plugin
 * Plugin Name:       Raven - Woo Ordering 
 * Description:       Add additional ordering fields to items in Woocommerce
 * Version:           1.0.0
 * Author:            Raven Designs
 * Text Domain:       raven-woo-ordering
 * WC tested up to:   5.0
 */

 defined( 'ABSPATH' ) || exit;

 if ( ! defined( 'RV_PLUGIN_FILE' ) ) {
	define( 'RV_PLUGIN_FILE', __FILE__ );
}

// Include main class
if( ! class_exists( 'Raven_woo_ordering' ) ) {
	include_once dirname( RV_PLUGIN_FILE ) . '/includes/class-woo-ordering.php';
}

/**
 *  Returns the main instance
 * 
 * @since 1.0.0
 */
function Raven_woo_ordering() {
	return Raven_woo_ordering::instance();
}

Raven_woo_ordering();
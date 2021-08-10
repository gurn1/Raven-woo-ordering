<?php
/**
 *  Class for admin functions
 * 
 * @package Raven_woo_ordering
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class RPO_admin extends Raven_woo_ordering {

     /**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 */
	protected static $_instance = null;
    
    /**
	 * Main Instance.
	 *
	 * Ensures only one instance of WooCommerce is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

    public function __construct() {
    }


}
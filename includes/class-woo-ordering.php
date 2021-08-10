<?php
/**
 *  Main class for Raven Woo Ordering
 * 
 * @package Raven_woo_ordering
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class Raven_woo_ordering {

    /**
     *  Plugin version
     */
    public $version = '1.0.0';

    /**
     * Set option name
     */
    public $option_name = 'rvo-woo-ordering';

    /**
     * Admin class
     *
     * @var admin
     */
    public $admin = null;

    /**
     * Frontend class
     * 
     * @var frontend
     */
    public $frontend = null;

    /**
	 * Main Instance.
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

    /**
     *  Constructor
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
    }

    /**
     * Define constants
     */
    private function define_constants() {
        define( 'RVO_ABSPATH', dirname( RVO_PLUGIN_FILE ) . '/' );
        define( 'RVO_URL', plugin_dir_url( RVO_PLUGIN_FILE ) );
        define( 'RVO_VERSION', $this->version );
    }

    /**
     * Include the required core files
     * 
     * @since 1.0.0
     */
    public function includes() {
        
    }

    /**
     * Admin class
     *
     * @since 1.0.0
     */
    public function admin() {
        return RPO_admin::instance();
    }

    /**
     * Frontend class
     * 
     * @since 1.0.0
     */
    public function frontend() {
        return RPO_frontend::instance();
    }

}
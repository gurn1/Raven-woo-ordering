<?php
/**
 *  Class for frontend functions
 * 
 * @package Raven_woo_ordering
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class RPO_frontend extends Raven_woo_ordering {

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

    /** 
     *  Constructor
     */
    public function __construct() {
		add_action( 'pre_get_posts', array( $this, 'the_loop_order' ) );

		// Add sorting option to shop page / WC Product Settings
		add_filter( 'woocommerce_get_catalog_ordering_args', array($this, 'sortby_field_query') );
		add_filter( 'woocommerce_default_catalog_orderby_options', array($this, 'sortby_field') );
		add_filter( 'woocommerce_catalog_orderby', array($this, 'sortby_field') );

		// Set default sortby option
		add_filter('woocommerce_default_catalog_orderby', array( $this, 'default_order' ), 90 );
    }

	/**
	 * Change product ordering on categories
	 * Currently disabled due to conflict with admin panel category section - needs frontpanel if statement
	 *
	 * @since 1.0.0
	 */
	public function the_loop_order( $query ) {
		
		if( ! is_admin() && is_product_category() && $query->is_main_query() ) {
			
			$current_category = $query->queried_object->term_id;	
			
			$query->set('meta_query', array(
				'relation' => 'OR',
				'without_order' => array(
					'key'     	=> '_rvo_product_order_'.$current_category,
					'compare' 	=> 'NOT EXISTS',
					'type'		=> 'numeric'
				),
				'with_order' => array(
					'key'  		=> '_rvo_product_order_'.$current_category,
					'compare'	=> 'EXISTS',
					'type'		=> 'numeric'
				),
			));
			$query->set('orderby', 'meta_value_num');	
			$query->set('meta_type', 'NUMERIC');
			$query->set('order', 'ASC');
				
		}
	}

	/**
		 * Add sorting option to shop page / WC Product Settings
		 *
		 * @sicne 1.0.0
		 */
		public function sortby_field_query( $sort_args ) {
			
			$orderby_value = isset( $_GET['orderby'] ) ? wc_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

			if ( 'rvo_default' == $orderby_value ) {
				$sort_args['orderby'] = 'meta_value_num';
				$sort_args['order'] = 'ASC';
			}

			return $sort_args;
		}
		
		
		/**
		 * Add sorting option to shop page / WC Product Settings
		 *
		 * @since 1.0.0
		 */
		public function sortby_field( $sortby ) {
			if(is_product_category() ) {
				$new_order = array( 'rvo_default' => 'Default' );
				return $new_order + $sortby;
			}

			return $sortby;
		}

		/**
		 * Default sort order
		 *
		 * @since 1.0.0
		 */
		public function default_order() {
			
			if(is_product_category() ) {
				return 'rvo_default';
			}

			return 'date';
		}

}
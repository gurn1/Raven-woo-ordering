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
     * Order panel name
     */
    static $order_panel_name = 'product_order';

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
        $this->hooks();
    }

    /**
     * Hooks
     * 
     * @since 1.0.0
     */
    public function hooks() {

        // enqueue scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // Add inline style to admin head
        add_action( 'admin_head', array( $this, 'inline_styles' ) );

        // Register other scripts 
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );

        // Get taxonomy terms 
		add_action( 'wp_ajax_ajax_get_terms', array( $this, 'ajax_get_terms' ) );

        // Update ordering post meta - uses ajax
        add_action( 'wp_ajax_ajax_update_ordering_meta', array( $this, 'ajax_update_ordering_meta' ) );

        // List Columns & Sort
		add_filter( 'manage_product_posts_columns', array( $this, 'add_table_head' ), 90 );
		add_action( 'manage_product_posts_custom_column', array( $this, 'add_table_content'), 10, 2 );
        add_filter( 'manage_edit-product_sortable_columns', array( $this, 'order_column_register_sortable') );
        add_filter( 'request', array( $this, 'multi_order_column_orderby' ) );
    
        // Save meta fields
		add_action( 'save_post', array( $this, 'save_meta' ), 10, 2 );

    }

    /**
	 * Get Ajax URL.
	 *
	 * @since 1.0.0
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

    /**
     * Register meta boxes
     *
     * @since version 1.0.0
     */
    public function meta_boxes() {	
        // Product order
        add_meta_box( self::$order_panel_name, __( 'Product categories order', RVO_DOMAIN ), array($this, 'product_ordering'), 'product', 'side' );
    }

     /**
     * Enqueue scripts
     * 
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        global $pagenow, $post_type;
        
        if( $pagenow == ( 'edit.php' || 'post.php' ) && $post_type == 'product' ) {
            // enqueue plugins ajax file
            wp_enqueue_script( 'rvo_woo_order_plugin', RVO_URL . 'assets/js/rvo-admin-ajax.js', array(), $this->version, true );
            wp_localize_script( 'rvo_woo_order_plugin', 'rvoObject', array(
				'ajaxurl'           => $this->ajax_url(),
                'order_panel_name'  => self::$order_panel_name,
                'template'          => $this->product_order_template(),
                'loaderURL'         => RVO_URL . 'assets/images/loader.png'
			));
        }

    }

    /**
     * Add inline styles
     * 
     * @since 1.0.0
     */
    public function inline_styles() {
        global $pagenow, $post_type;

        if( $pagenow == ( 'edit.php' || 'post.php' ) && $post_type == 'product' ) {
            ?>
            <style type="text/css">
                .column-rvo_multi_order {
                    position: relative;
                    width: 200px;
                }
                .column-rvo_multi_order .category-item {
                    background: #e2ecf7;
                    border-radius: 2px;
                    color: #2c3338;
                    display: flex;
                    margin: 1px 0;
                } 
                .column-rvo_multi_order .current-category {
                    font-weight: 600;
                }
                .column-rvo_multi_order .category-name {
                    flex-grow: 1;
                    font-size: 13px;
                    padding: 2px 6px;
                }
                .column-rvo_multi_order .category-order {
                    background: #dae9f7;
                    flex-shrink: 0;
                    flex-grow: 0;
                    text-align: right;
                    font-weight: bold;
                    min-width: 45px;
                    padding: 2px 6px;
                }
                .rvo-overlay {
                    background: rgba(0,0,0,0.48);
                    bottom: 0;
                    height: 100%;
                    left: 0;
                    position: absolute;
                    right: 0;
                    top: 0;
                    width: 100%;
                    z-index: 99998;
                }
                .rvo-overlay .rvo-inline-icon {
                    height: auto;
                    position: absolute;
                    top: 50%; left: 50%;
                    width: 55px;
                    transform: translateX(-50%) translateY(-50%);
                }
                .rvo-overlay .success-icon {
                    color: #169f3d;
                    font-size: 55px;
                }
                .rvo-overlay .error-icon {
                    color: #8f0d29;
                    font-size: 55px;
                }

                input.rvo-inline-field {
                    font-size: 10px;
                    min-height: 22px;
                    padding: 0 2px;
                    max-width: 40px;
                }
            </style>
            <?php
        }
    }

    /**
     * Add table header columns to the post type list screen
     *
     * @since 1.0.0
     */
    static public function add_table_head( $columns ) {
        if ( empty( $columns ) && ! is_array( $columns ) ) {
			$columns = array();
		}

        // Maybe add an admin option to toggle the removal of these
        unset( $columns['featured'], $columns['product_tag']);

        $show_columns          = array();
		$show_columns['cb']    = '<input type="checkbox" />';
		$show_columns['thumb'] = '<span class="wc-image tips" data-tip="' . esc_attr__( 'Image', 'woocommerce' ) . '">' . __( 'Image', 'woocommerce' ) . '</span>';
		$show_columns['name']  = __( 'Name', 'woocommerce' );

		if ( wc_product_sku_enabled() ) {
			$show_columns['sku'] = __( 'SKU', 'woocommerce' );
		}

		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			$show_columns['is_in_stock'] = __( 'Stock', 'woocommerce' );
		}

		$show_columns['price']        = __( 'Price', 'woocommerce' );
		$show_columns['product_cat']  = __( 'Categories', 'woocommerce' );
        $show_columns['rvo_multi_order'] = __('Multi Order', RVO_DOMAIN);
		$show_columns['date']         = __( 'Date', 'woocommerce' );

        
        return array_merge( $show_columns, $columns );

    }

    /**
     * Add table content columns to the post type list screen
     *
     * @since 1.0.0
     */
    public function add_table_content( $column_name, $post_id ) {

        if( $column_name == 'rvo_multi_order' ) {
            $current_terms = get_the_terms( $post_id, 'product_cat');
            $current_terms_ids = wp_get_post_terms( $post_id, 'product_cat', array( 'fields' => 'ids'));
                
            if( is_array($current_terms) ) {
                $categories = array_reverse($current_terms);

                foreach($categories as $category) {
                    if( $category->parent == 0 ) { 
                        $value = get_post_meta( $post_id, '_rvo_product_order_'.$category->term_id, true );
    
                        echo $this->product_order_list_template($category, $value);
                        $this->get_children($category->term_id, $post_id);
                    }
                }

                echo $this->inline_edit_buttons();
        
            } else {
                echo 'No Categories Selected';
            }

        }

    }

    /**
     *  Register sortable fields
     * 
     * @since 1.0.0
     */
    public function order_column_register_sortable($columns) {

        if( isset($_GET['product_cat']) && $_GET['product_cat'] != null ) {
            $columns['rvo_multi_order'] = 'rvo_multi_order';
        }

        return $columns;
    }

    /**
     * Sort by order number
     * 
     * @since 1.0.0
     */
    public function multi_order_column_orderby($vars) {

        $new_order = array();
 
        if( isset($vars['orderby']) && $vars['orderby'] == 'rvo_multi_order' ) {

            $current_category_slug = isset($_GET['product_cat']) ? $_GET['product_cat'] : '';
            $order  = isset($_GET['order']) ? $_GET['order'] : '';
		
            if( $current_category_slug ) {
                $current_category = get_term_by( 'slug', $current_category_slug, 'product_cat' );
                
                $new_order = array(
                    'meta_query'    => array(
                        'relation' => 'OR',
                        'without_order' => array(
                            'key'     	=> '_rvo_product_order_'.$current_category->term_id,
                            'compare' 	=> 'NOT EXISTS',
                            'type'		=> 'numeric'
                        ),
                        'with_order' => array(
                            'key'  		=> '_rvo_product_order_'.$current_category->term_id,
                            'compare'	=> 'EXISTS',
                            'type'		=> 'numeric'
                        ),
                    ),
                    'orderby'       => 'meta_value_num',
                    'order'         => $order,
                    'meta_type'     => 'NUMERIC',
                );
                
            }

        }

        return array_merge($vars, $new_order);
    }

    /**
     * Get the term
     * 
     * @since 1.0.0
     */
    public function ajax_get_terms() {
            $post_id        = isset($_POST['post_id']) ? sanitize_text_field($_POST['post_id']) : '';
            $category_list  = isset($_POST['siblings']) ? array_map( 'sanitize_text_field', $_POST['siblings']) : array();
            $value = '';

            if( ! empty($category_list) ) {
                $categories = get_terms(array(
                    'taxonomy'      => 'product_cat',
                    'hide_empty'    => false,
                    'include'       => $category_list
                ));

                foreach($categories as $category) {
                    if( $category->parent == 0 ) { 
                        if($post_id) {
                            $value = get_post_meta( $post_id, '_rvo_product_order_'.$category->term_id, true );
                        }
                        
                        echo $this->product_order_template($category, $value);
                        echo $this->get_children($category->term_id, $post_id, array('is_ajax' => $category_list ));
                    }
                }
                
            } else {
                echo 'No Categories Selected';
            }
            
            exit;
    }



    /**
     * Product ordering
     *
     * @since 1.0.0
     */
    public function product_ordering() {
        global $post;
        
        //var_dump($this->ajax_get_term());
        $current_terms = get_the_terms( $post->ID, 'product_cat');
        $current_terms_ids = wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'ids'));
        
        
        echo '<style>
                .ordering-table th { padding: 10px 10px 10px 0 }
                .ordering-table td { padding: 0 10px }
            </style>';
        
        echo '<table class="form-table ordering-table">';
          
        if( is_array($current_terms) ) {
            $categories = array_reverse($current_terms);

            foreach($categories as $category) {
                if( $category->parent == 0 ) { 
                    $value = get_post_meta( $post->ID, '_rvo_product_order_'.$category->term_id, true );
   
                    echo $this->product_order_template($category, $value);
                    $this->get_children($category->term_id, $post->ID);
                }
            }
    
        } else {
            echo '<tr class="no-order-categories"><td>No Categories Selected</td></tr>';
        }

        echo '</table>';
        
    }


    /**
     * Get category children
     * 
     * @since 1.0.0
     */
    public function get_children($term_id, $post_id, $args = array()) {
        global $pagenow;

        $defaults = array(
            'node'      => ' -',
            'is_ajax'   => array()
        );
        $args = wp_parse_args( $args, $defaults );
        
        if( empty( $args['is_ajax'] ) ) {
            $current_terms_ids = wp_get_post_terms( $post_id, 'product_cat', array( 'fields' => 'ids'));
        } else {
            $current_terms_ids = $args['is_ajax'];
        }

        $term_children = get_term_children( $term_id, 'product_cat' );
        $has_children = array_intersect($current_terms_ids, $term_children);
        
        if( $has_children ) {
            $child_terms = get_terms(array(
                'taxonomy'      => 'product_cat',
                'hide_empty'    => false,
                'parent'        => $term_id,
                'include'       => $has_children
            ));

            foreach($child_terms as $children) {
                $value = get_post_meta( $post_id, '_rvo_product_order_'.$children->term_id, true );
                
                if( $pagenow == 'edit.php') {
                    echo $this->product_order_list_template($children, $value, $args['node']);
                } else {
                    echo $this->product_order_template($children, $value, $args['node']);
                }

                $node = $args['node'] . '-';

                echo $this->get_children($children->term_id, $post_id, array( 'node' => $node, 'is_ajax' => $has_children) );

                $node = ' -';
            }

        }

    }

    /**
     * Product order template
     * 
     * @since 1.0.0
     */
    public function product_order_template($term = array(), $value = '', $node = '') {

        $category_id        = '';
        $category_name      = '';

        if($term) {
            $category_id        = $term->term_id;
            $category_name      = $term->name;
        }

        return '
        <tr class="row-'.$category_id.'">
            <th>
                <label>'.$node.$category_name.'</label>
            </th>
            <td>
                <input type="text" class="regular-text" name="_rvo-product-order['.$category_id.']" value="'.$value.'" style="max-width: 50px;">
            </td>
        </tr>';
    }

    /**
     * Product order template for list
     * 
     * @since 1.0.0
     */
    public function product_order_list_template($term = array(), $value = '', $node = '') {

        $category_id = '';
        $category_name = '';

        if($term) {
            $category_id        = $term->term_id;
            $category_name      = $term->name;
        } 

        $current_category = isset($_GET['product_cat']) ? $_GET['product_cat'] : '';
        $selected = $current_category == $term->slug ? ' current-category' : '';  

        return '
        <div class="row-'.$category_id.$selected.' category-item">
            <label class="category-name">'.$node.$category_name.'</label>
            <div class="category-order">
                <span class="rvo-result result-'.$category_id.'"><b>'.$value.'</b></span>
                <span class="rvo-edit-result hidden">
                    <input type="text" class="rvo-inline-field regular-text" data-id="'.$category_id.'" name="_rvo-product-order['.$category_id.']" value="'.$value.'">
                </span>
            </div>
        </div>';
    }

    /**
     *  Inline edit buttons for list page
     * 
     * @since 1.0.0
     */
    public function inline_edit_buttons() {
        $security = wp_create_nonce('rvo_update_order_meta');

        ?>
        <div class="row-actions rvo-row-actions"><span class="inline hide-if-no-js"><a href="#" class="rvo-edit-post-order">Edit Ordering</a></span></div>
        <p class="inline-edit-save rvo-inline-edit-save hidden">
            <a href="#inline-edit" class="button-secondary rvo-cancel alignleft">Cancel</a>
            <a href="#inline-edit" class="button-primary rvo-save alignright" data-nonce="<?php echo esc_attr($security); ?>">Update</a>
        </p>
        <?php
    }

     /**
     * Update ordering post meta inline
     * 
     * @since 1.0.0
     */
    public function ajax_update_ordering_meta() {
        $nonce      = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']): '';
        $post_id    = isset($_POST['post_id']) ? sanitize_text_field($_POST['post_id']) : '';
        $values     = isset($_POST['values']) ? $_POST['values'] : array();
        $updated    = false;

        if( ! wp_verify_nonce( $nonce, 'rvo_update_order_meta' )) {
            echo 'Failed Secuirty';
            exit;
        }

        if( $values ) {
            foreach( $values as $value ) {
                if(update_post_meta( $post_id, '_rvo_product_order_'.$value['id'], sanitize_text_field($value['result']) ) ) {
                    $updated = true;
                }
            }
        }

        echo $updated;

        exit;
    }

    /**
     * Save meta data
     * 
     * @since 1.0.0
     */
    public function save_meta( $post_id, $post ) {
        if ( ! wp_is_post_revision( $post_id ) ) {

            $current_terms = get_the_terms( $post_id, 'product_cat');
            $current_term_ids = array();
            $new_ids = array();

            if( $current_terms ) {
                foreach( $current_terms as $term ) {
                    $current_term_ids[] = $term->term_id;
                }

                foreach( $current_term_ids as $term_id ) {
                    $parent_ids = get_ancestors( $term_id, 'product_cat' );

                    foreach( $parent_ids as $id ) {
                    
                        if( ! in_array($id, $current_term_ids) ) {
                            $new_ids[] = $id;
                        }
                    }

                }

                wp_set_post_terms( $post_id, $new_ids, 'product_cat', true );
            }
            
            $product_order = isset($_POST['_rvo-product-order']) ? $_POST['_rvo-product-order'] : '';
				
            if( $product_order != null && is_array($product_order) ) {
                foreach($product_order as $key => $order) {
                    update_post_meta( $post_id, '_rvo_product_order_'.$key, sanitize_text_field($order) );
                }
            }

        }
    }

}
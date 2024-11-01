<?php
/**
 * Plugin Name: AliExpress for WooCommerce
 * Description: Official Plugin - Export your products to AliExpress
 * Version: 1.8.0
 * Author: Wecomm Solutions
 * Author URI: https://wecomm.es
 * Plugin URI: https://wecomm.es/aliexpress-woocommerce-plugin
 * Text Domain: aliexpress
 * WC requires at least: 3.0
 * WC tested up to: 8.4.0
 * Required WP: 5.0
 * Tested WP: 6.4.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define('AEW_CHUNK_JOBS', get_option('aew_chunk_jobs', 200));
define('AEW_VERSION', '1.8.0');
define('AEW_TOKEN', get_option('aew_token_auth', ''));
define('AEW_ROOT_PATH', plugin_dir_path( __FILE__ ));
define('AEW_CLASSES_PATH', plugin_dir_path( __FILE__ ) . 'inc/classes/');
define('AEW_INCLUDES_PATH', plugin_dir_path( __FILE__ ) . 'inc/');
define('AEW_VIEW_PATH', plugin_dir_path( __FILE__ ) . 'views/');
define('AEW_AE_PATH', plugin_dir_path( __FILE__ ) . 'inc/classes/AE0/Version_22_07/');

if(!get_option('AEW_TOKEN_CRON')) {
    $token = openssl_random_pseudo_bytes(16);
    $token = bin2hex($token);
    update_option('AEW_TOKEN_CRON', $token);
}
if(is_admin()) {
   $iconUrl = plugins_url('/assets/icon.png', __FILE__);
}
require_once(AEW_CLASSES_PATH . 'class.api.php');
require_once(AEW_CLASSES_PATH . 'class.cron.api.php');
require_once(AEW_CLASSES_PATH . 'class.attributes.aliexpress.php');
require_once(AEW_CLASSES_PATH . 'class.category.aliexpress.php');
require_once(AEW_CLASSES_PATH . 'class.featured.aliexpress.php');
require_once(AEW_CLASSES_PATH . 'class.order.aliexpress.php');
require_once(AEW_CLASSES_PATH . 'class.product.aliexpress.php');
require_once(AEW_CLASSES_PATH . 'class.job.aliexpress.php');

if(!class_exists('AEW_MAIN')) {
    class AEW_MAIN {

        public $version = AEW_VERSION;
        public $aew_db_version = 2;
        public $errorMSGCarrier = '';
        public static $posiblesEstados;

        function __construct() {
            self::$posiblesEstados = array(
                'PLACE_ORDER_SUCCESS' => array(
                    'name' => __('Waiting for Payment','aliexpress'),
                    'internal_name' => 'pending',
                ),
                'IN_CANCEL' => array(
                    'name' => __('In Cancel','aliexpress'),
                    'internal_name' => 'cancelled',
                ),
                'WAIT_SELLER_SEND_GOODS' => array(
                    'name' => __('Waiting Shipping','aliexpress'),
                    'internal_name' => 'processing',
                ),
                'SELLER_PART_SEND_GOODS' => array(
                    'name' => __('Partially Delivered','aliexpress'),
                    'internal_name' => 'processing',
                ),
                'WAIT_BUYER_ACCEPT_GOODS' => array(
                    'name' => __('Waiting Confirmation','aliexpress'),
                    'internal_name' => 'processing',
                ),
                'FUND_PROCESSING' => array(
                    'name' => __('Processing Payment','aliexpress'),
                    'internal_name' => 'processing',
                ),
                'IN_ISSUE' => array(
                    'name' => __('In Dispute','aliexpress'),
                    'internal_name' => 'processing',
                ),
                'IN_FROZEN' => array(
                    'name' => __('Suspended','aliexpress'),
                    'internal_name' => 'cancelled',
                ),
                'WAIT_SELLER_EXAMINE_MONEY' => array(
                    'name' => __('Waiting for Review','aliexpress'),
                    'internal_name' => 'processing',
                ),
                'RISK_CONTROL' => array(
                    'name' => __('Risk Control','aliexpress'),
                    'internal_name' => 'processing',
                ),
                'FINISH' => array(
                    'name' => __('Finished','aliexpress'),
                    'internal_name' => 'completed',
                ),
            );
            add_action('admin_menu', array($this, 'create_menu'));
            add_action( 'admin_enqueue_scripts', array($this, 'load_style') );
            add_action('plugins_loaded', array($this, 'load_textdomain'));
            add_action('init', array($this, 'aew_init'));
            add_action( 'manage_product_posts_custom_column' , array($this, 'colum_product'), 10, 2 );
            add_filter( 'manage_product_posts_columns' , array($this, 'add_colum_products') );

            add_action( 'manage_shop_order_posts_custom_column' , array($this, 'colum_orders'), 10, 2 );
            
            register_activation_hook( __FILE__, array($this, 'create_tables') );
            add_action( 'add_meta_boxes', array($this, 'create_metabox_aliexpress_products') );
            add_action( 'add_meta_boxes', array($this, 'create_metabox_aliexpress_orders') );

            //add_action( 'init', array($this, 'register_order_status') );
            add_action( 'woocommerce_order_status_changed', array($this, 'order_status_changed'), 20, 3 );
            add_action( 'save_post_product', array($this, 'save_product'), 10, 3 );
            add_action( 'woocommerce_save_product_variation', array($this, 'product_updated'), 10, 2 ); 
            // add_filter( 'wc_order_statuses', array($this, 'add_order_status') );
            add_action( 'admin_notices', array($this, 'admin_notices') );

            add_action('woocommerce_update_product', array($this, 'product_updated'), 10, 1);
            add_action('woocommerce_variation_set_stock', array($this, 'product_updated'), 10, 1);
            add_action('woocommerce_product_set_stock', array($this, 'product_updated'), 10, 1);
            add_action('woocommerce_variation_set_stock_status', array($this, 'product_updated'), 10, 1);
            add_action('woocommerce_product_set_stock_status', array($this, 'product_updated'), 10, 1);
            add_filter('woocommerce_hidden_order_itemmeta', array($this, 'remove_meta_data_order'),10,1);
            add_action( 'woocommerce_save_product_variation', array($this, 'aew_save_variable_fields'), 10, 1 );
            
            //Display Fields
            add_action( 'woocommerce_product_after_variable_attributes', array($this, 'aew_variable_fields'), 10, 3 );
            //JS to add fields for new variations
            add_action( 'woocommerce_product_after_variable_attributes_js', array($this, 'aew_variable_fields_js') );
            //Save variation fields
            add_action( 'woocommerce_process_product_meta_variable', array($this, 'aew_save_variable_fields'), 10, 1 );
            

            add_filter( 'views_edit-product', array( $this, 'add_filter_link' ) );

            add_filter( 'posts_where', array( $this, 'filter_posts' ) );

            add_action( 'manage_posts_extra_tablenav', array( $this, 'add_button_cat' ) );
            add_action( 'woocommerce_update_order', array($this, 'aew_update_order'));

            //Shortcodes
            add_shortcode('AE_PRODUCT_NAME', array($this, 'aew_shortcode_product_name')); 
            add_shortcode('AE_PRODUCT_PRICE', array($this, 'aew_shortcode_price'));       
        }
        static public function aew_get_possible_images_size() {
            $wp_additional_image_sizes = wp_get_additional_image_sizes();

            $sizes = array();
            $get_intermediate_image_sizes = get_intermediate_image_sizes();

            // Create the full array with sizes and crop info
            foreach ($get_intermediate_image_sizes as $_size) {
            if (in_array($_size, array('thumbnail', 'medium', 'large'))) {
                $sizes[$_size]['width'] = get_option($_size . '_size_w');
                $sizes[$_size]['height'] = get_option($_size . '_size_h');
                $sizes[$_size]['crop'] = (bool) get_option($_size . '_crop');
            } elseif (isset($wp_additional_image_sizes[$_size])) {
                $sizes[$_size] = array(
                'width' => $wp_additional_image_sizes[$_size]['width'],
                'height' => $wp_additional_image_sizes[$_size]['height'],
                'crop' =>  $wp_additional_image_sizes[$_size]['crop']
                );
            }
            }
            return $sizes;

        }
        static public function get_brand_name($shema, $id) {
            $schema = $shema->get_schema();
            foreach($schema['properties']['category_attributes']['properties']['Brand Name']['properties']['value']['oneOf'] as $brand) {
                if($brand['const'] == $id) {
                    return $brand['title'];
                }

            }
            return '';
        }
        static public function aew_shortcode_product_name($data) {
            if(isset($_POST['ae_product'])) {
                $producto = $_POST['ae_product'];
                $value = $producto->get_name();
            }else{
                $value = '';
            }

            return $value;
        }

        static public function aew_shortcode_price($data) {
            if(isset($_POST['ae_product'])) {
                $producto = $_POST['ae_product'];
                $value = number_format($producto->get_price(),2);
            }else{
                $value = '';
            }

            return $value;
        }
        

        static function aew_exist_shipping_order($orderID) {
            global $wpdb;
            $rowcount = $wpdb->get_var("SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type = 'shipping' AND order_id = $orderID");

            return $rowcount;
        }
        function aew_update_order($orderID) {
            if(isset($_POST['aew_shipping_id']) and $_POST['aew_shipping_id'] != '' and $_POST['aew_shipping_id'] != '0') {
                $existe = AEW_MAIN::aew_exist_shipping_order($orderID);

                if(!$existe) {
                    $order_item_id = wc_add_order_item($orderID, array(
                        'order_item_name' => strtoupper(sanitize_text_field( $_POST['aew_shipping_id'] )),
                        'order_item_type' => 'shipping'
                    ));
    
                    wc_update_order_item_meta($order_item_id, 'method_id', sanitize_text_field($_POST['aew_shipping_id']));
                }else{
                    $order_item_id = wc_update_order_item($existe, array(
                        'order_item_name' => strtoupper(sanitize_text_field( $_POST['aew_shipping_id'] )),
                        'order_item_type' => 'shipping'
                    ));
    
                    wc_update_order_item_meta($existe, 'method_id', sanitize_text_field($_POST['aew_shipping_id']));
                }
            }
            
        }
        // function update_plugin( $options ) {
        //     if(str_replace(".php", '', basename(__FILE__)) == str_replace(".zip", "", $_FILES['pluginzip']['name']) ) {
        //         $options['clear_destination'] = true;
        //         $options['abort_if_destination_exists'] = false;
        //     }
        //     return $options;
        // }
        static function show_store($datos){
            echo '<div class="store_data">
                <span class="name">'.$datos->shop_name.' | <span class="country">'.$datos->country_code.'</span></span>
                <a href="'.$datos->shop_url.'" target="_blank">'.__('View Store', 'aliexpress').'</a>
                <a href="?page=aliexpress-general-options&tab=general&force_upload_store=AE"><span class="dashicons dashicons-image-rotate"></span></a>
            </div>';
        }
        function add_button_cat(){
            if(isset($_GET['product_cat']) and isset($_GET['post_type']) and sanitize_text_field($_GET['post_type']) == 'product' and !empty($_GET['product_cat'])) {
                if(AEW_TOKEN == '') { return; }
                ?>
                
                <script type="text/javascript">
                jQuery(document).ready( function($){
                    if($("#importCategoryAew").length == 0) {
                        jQuery(jQuery(".page-title-action")).last().after("<a  id='importCategoryAew' data-id='<?=sanitize_text_field($_GET['product_cat'])?>' class='page-title-action'><?=__('Upload products to AliExpress','aliexpress')?></a>");
                    }
                });
                </script>
                
                <?php
            }
        }

        public static function aew_get_documentation_link() {
            $locale = get_user_locale();

            switch($locale) {
                case 'es_ES':
                    $link = 'https://wecomm.es/documentacion/aliexpress-woocommerce-sync';
                break;
                case 'it_IT':
                    $link = 'https://wecomm.es/it/documentazione';
                break;
                default:
                    $link = 'https://wecomm.es/en/documentation';
                break;
            }
            return $link;
        }
        public static function get_lang_support(){
            $locale = get_user_locale();

            switch($locale) {
                case 'es_ES':
                    $lang = 'spanish';
                break;
                case 'it_IT':
                    $lang = 'italian';
                break;
                default:
                    $lang = 'english';
                break;
            }
            return $lang;
        }
        public function filter_posts( $where ) {
            if ( $this->is_filter_active() ) {
                global $wpdb;
                $where .= sprintf(
                    ' AND ' . $wpdb->posts . '.ID IN( SELECT post_id FROM ' . $wpdb->postmeta . ' WHERE meta_key = "%s" AND meta_value != "" ) ',
                    '_aew_product_id'
                );
            }
            return $where;
        }
        protected function get_post_total() {
            global $wpdb;
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    '
                    SELECT COUNT( 1 )
                    FROM ' . $wpdb->postmeta . '
                    WHERE post_id IN( SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type = %s ) &&
                    meta_value != "" AND meta_key = %s
                    ',
                    'product',
                    '_aew_product_id'
                )
            );
        }
        public function get_query_val() {
            return 'onlyaliexpress';
        }
        protected function get_filter_url() {
            $query_args = array(
                'aliexpressproducts' => $this->get_query_val(),
                'post_type'            => 'product',
                'orderby' => 'meta_value_num',
                'meta_key' => '_aew_product_sincro',
                'order' => 'asc'
            );
            return add_query_arg( $query_args, 'edit.php' );
        }

        public static function aew_get_product_by_idae($idAE) {
                global $wpdb;

                if( empty( $idAE ) ) {
                    return;
                }

                $exits = $wpdb->get_row( "SELECT * FROM ".$wpdb->postmeta." WHERE meta_key = '_aew_product_id' and meta_value= '$idAE'", ARRAY_A);
                if($exits) {
                    return $exits;
                }else{
                    return false;
                }
        }
        public static function aew_get_product_by_sku_ae($skuAE) {
            global $wpdb;

            if( empty( $skuAE ) ) {
                return false;
            }

            $exits = $wpdb->get_row( "SELECT pm.post_id, p.post_parent, p.post_title FROM {$wpdb->postmeta} as pm
            LEFT JOIN {$wpdb->posts} as p ON p.ID = pm.post_id 
            WHERE pm.meta_key = '_sku' and pm.meta_value= '$skuAE'", ARRAY_A);
            if($exits) {
                return $exits;
            }else{
                return false;
            }
        }
        public static function aew_get_category_byidae($idAE) {
            $args = array(
                'meta_query' => array(
                    array(
                       'key'       => 'aew_category_id',
                       'value'     => $idAE,
                       'compare'   => '='
                    )
                ),
                'taxonomy'  => 'product_cat',
                );
                $terms = get_terms( $args );
                if($terms) {
                    return $terms[0]->term_id;
                }else{
                    return null;
                }
        }
        public static function aew_get_product($idAE) {
            if(AEW_TOKEN == '') { return; }
            require_once(AEW_AE_PATH . 'AEFactory.php');
            $factory = new AEFactory(AEW_TOKEN);
            $p = json_decode($factory->wrapper->getProductFromAE($idAE),true);
            
            if(isset($p['code']) and $p['code'] == '7') {
                return ['error' => __('API Limited','aliexpress')];
            }
           
            if(!isset($p['result']['aeop_ae_product_s_k_us']['global_aeop_ae_product_sku']['sku_code'])) {
                return ['combinacion' => true];
            }
            return [
                'sku' => $p['result']['aeop_ae_product_s_k_us']['global_aeop_ae_product_sku']['sku_code'],
                'price' => $p['result']['aeop_ae_product_s_k_us']['global_aeop_ae_product_sku']['sku_price'],
                'quantity' => $p['result']['aeop_ae_product_s_k_us']['global_aeop_ae_product_sku']['ipm_sku_stock'],
                'category_id' => self::aew_get_category_byidae($p['result']['category_id']),
                'detail' => $p['result']['detail'],
                'images' => $p['result']['image_u_r_ls'],
                'package_height' => $p['result']['package_height'],
                'package_length' => $p['result']['package_length'],
                'package_width' => $p['result']['package_width'],
                'product_id' => $p['result']['product_id'],
                'product_price' => $p['result']['product_price'],
                'name' => $p['result']['subject'],
                'freight_template_id' => $p['result']['freight_template_id']
            ];
        }
        protected function is_filter_active() {
            return ( filter_input( INPUT_GET, 'aliexpressproducts' ) === $this->get_query_val() );
        }
        function add_filter_link($views){
            if(AEW_TOKEN == '') { return $views; }
            $views[ 'aliexpresso_' . $this->get_query_val() ] = sprintf(
                '<a href="%1$s"%2$s>%3$s</a> (%4$s)',
                esc_url( $this->get_filter_url() ),
                ( $this->is_filter_active() ) ? ' class="current" aria-current="page"' : '',
                __('AliExpress Products','aliexpress'),
                $this->get_post_total()
            );
            return $views;
        }
        
        function product_updated($product_id) {
            global $wpdb;
            if(AEW_TOKEN == '') { return; }


            if(is_int($product_id)) {
                if(!get_post_meta($product_id, '_aew_product_id', true)) { return; }
                update_post_meta($product_id, '_aew_need_upload', '1');
            }else{
                if(!get_post_meta($product_id->ID, '_aew_product_id', true)) { return; }
                update_post_meta($product_id->ID, '_aew_need_upload', '1');
            }

            if(isset($_POST['action']) && $_POST['action'] == 'woocommerce_save_variations') {
                foreach($_POST['variable_post_id'] as $idvariacion) {
                    update_post_meta($idvariacion, '_aew_need_upload', '1');
                }
            }
        }
        function remove_meta_data_order($arr){
            $arr[] = '_aew_line_order_id';
            return $arr;
        }
        function admin_notices(){
            $errores = get_option('aew_notices_jobs', '');
            if($errores != '') {
                
                echo '<div class="error notice info-job-aliexpress">
                    <h3>'.__('AliExpress Information', 'aliexpress').'</h3>
                    '.$errores.'<br><br>
                    <a href="#" class="removeInformationJobAew">'.__('Not show again', 'aliexpress').'</a>
                </div>';
            }
            if(get_option('aew_token_expiration')) {
                if(strtotime(get_option('aew_token_expiration')) < strtotime('+10 days')) {
                    echo '<div class="error notice info-job-aliexpress">
                        <p>'.__('Your AliExpress session will expire soon, renew the session.', 'aliexpress').'</p>
                        <a href="'.admin_url('admin.php?page=aliexpress-general-options&tab=login').'">'.__('Log-in again', 'aliexpress').'</a>
                    </div>';
                }
                if(strtotime(get_option('aew_token_expiration')) < strtotime('+1 minutes')) {
                    delete_option('aew_token_auth');
                    delete_option('aew_token_expiration');
                    update_option('aew_show_expiration', '1');
                }
            }elseif(get_option('aew_show_expiration') == '1') {
                echo '<div class="error notice info-job-aliexpress">
                        <p>'.__('Your AliExpress session has expired, renew the session.', 'aliexpress').'</p>
                        <a href="'.admin_url('admin.php?page=aliexpress-general-options&tab=login').'">'.__('Log-in again', 'aliexpress').'</a>
                    </div>';
            }
        }
        function aew_init() {
            
             //REGISTER LINK API
             $urlFinalAdminCore = str_replace(home_url()."/","",admin_url('admin-ajax.php?action=aew_$1'));
             add_rewrite_rule( 'aliexpress/([^/]*)/?', $urlFinalAdminCore, 'top');
 
             $urlFinalAdminCron = str_replace(home_url()."/","",admin_url('admin-ajax.php?action=aew_cron_$2&token=$1'));
             add_rewrite_rule( 'aliexpresscron/([^/]*)/([^/]*)/?', $urlFinalAdminCron, 'top');

            //Check Jobs
            if(is_admin() and !wp_doing_ajax()) {
                    if(AEW_TOKEN == '') { return; }
                    // echo '<div style="text-align:center"><h1>Comprobando JOBS</h1>';
                    $timeNow = strtotime(date('Y-m-d H:i:s'));
                    $optionLast = intval(get_option('aew_last_check_job')) ?: 0;
                    // echo '<h1>Tiempo pasado '.($timeNow - $optionLast).'</h1>';
                    if(($timeNow - $optionLast) >= 30) {
                        update_option('aew_last_check_job', strtotime(date('Y-m-d H:i:s')));
                        $trabajos = AEW_Job::get_pending_jobs();
                        // self::printDebug($trabajos);
                        if($trabajos) {
                            
                            foreach($trabajos as $trabajo) {
                               self::check_job_curl($trabajo->jobID);
                            // echo '<p>'.$trabajo->jobID . ' -> '.json_encode($data).'</p>';
                            }
                        }

                    }
                    // echo '</div>';
            }
        }


        function check_job_curl($idJob) {
			require_once(AEW_AE_PATH . 'AEFactory.php');
            $factory = new AEFactory(AEW_TOKEN); 
            $job_state = $factory->batch_query_job($idJob);
            $job_state['last_check'] = date_i18n('d-m-Y H:i');
            $job_state = AEW_Job::get_status_job($job_state);
            AEW_Job::update_job($job_state);
            $job_state['update_products'] = AEW_Product::update_products_ids($job_state);
        }
        function create_metabox_aliexpress_products() { 
            if(AEW_TOKEN == '') { return; } 
            add_meta_box( 'aew_data_product', __('AliExpress Data', 'aliexpress'), array($this, 'content_metabox_aliexpress_products'), 'product', 'normal', 'high' );  
            add_meta_box( 'aew_description_product', __('AliExpress Custom Description', 'aliexpress'), array($this, 'content_metabox_aliexpress_products_description'), 'product', 'normal', 'high' );  
        }
        function create_metabox_aliexpress_orders(){
            if(AEW_TOKEN == '') { return; }
            global $post;
            if(get_post_meta($post->ID, '_aew_order_id', true)){
                add_meta_box( 'aew_number_shipping_metabox', __('AliExpress Tracking Number','aliexpress'), array($this, 'content_metabox_aliexpress_order'), 'shop_order', 'normal', 'high' );  
                // add_meta_box( 'aew_number_shipping_metabox_carrier', __('AliExpress Carrier Connect','aliexpress'), array($this, 'content_metabox_aliexpress_order_carrier'), 'shop_order', 'side' );  
            }
        }
        // function content_metabox_aliexpress_order_carrier($post){
        //     require_once(AEW_VIEW_PATH . 'meta-box-order-shipping-carrier.php');     

        // }
        function content_metabox_aliexpress_order($post) {
            require_once(AEW_VIEW_PATH . 'meta-box-order-shipping.php');     
        }
        function content_metabox_aliexpress_products($post) {
            require_once(AEW_VIEW_PATH . 'meta-box-product.php');     
        }
        function content_metabox_aliexpress_products_description($post) {
            require_once(AEW_VIEW_PATH . 'meta-box-description-product.php');     
        }
        function load_textdomain() {
            load_plugin_textdomain( 'aliexpress', false, dirname( plugin_basename(__FILE__) ) . '/languages' );

            // if( class_exists( 'WooCommerce' ) ) {
            //     add_action( 'admin_notices', function(){
            //         $class = 'notice notice-error';
            //         $message = __( 'AliExpress for WooCommerce need Woocommerce Plugin, please, install or active Woocommerce.', 'aliexpress' );
                
            //         printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
            //     });
            // }
        }
        function load_style() {
            if(is_admin()) {

                //Lang JS
                if(file_exists(plugin_dir_path(__FILE__) . 'js/langs/'.get_user_locale().'.js')) {
                    wp_enqueue_script( 'langsaliexpress', plugins_url('js/langs/'.get_user_locale().'.js?v='.AEW_VERSION, __FILE__), array( 'wp-i18n' ) );
                }else{
                    wp_enqueue_script( 'langsaliexpress', plugins_url('js/langs/en_US.js?v='.AEW_VERSION, __FILE__), array( 'wp-i18n' ) );
                }

                wp_enqueue_style( 'cssaliexpress', plugins_url('style.css?v='.AEW_VERSION, __FILE__) );
                wp_enqueue_style( 'treeview-css-aliexpress', plugins_url('jstree.style.min.css?v='.AEW_VERSION, __FILE__) );
                
                wp_enqueue_script( 'sweetalerts', plugins_url('js/sweetalert.js?v='.AEW_VERSION, __FILE__) );
                wp_enqueue_script( 'eventsaliexpress', plugins_url('js/events.js?v='.AEW_VERSION, __FILE__), array( 'wp-i18n' ) );

                wp_localize_script( 'eventsaliexpress', 'AEW_Data', array(
                    'assetsPath' => plugins_url('/assets', __FILE__),
                    'adminAjaxURL' => admin_url('admin-ajax.php'),
                    'duplicateAttributes' => get_option('aew_duplicate_attributes', '0')
                ));

                wp_enqueue_script( 'treeviewaliexpress', plugins_url('js/jstree.min.js?v='.AEW_VERSION, __FILE__), array('jquery') );


                //Tooltip
                //tooltipster-sideTip-borderless.min.css
                wp_enqueue_style( 'tooltip-css-aliexpress', plugins_url('tooltipster.bundle.min.css?v='.AEW_VERSION, __FILE__) );
                wp_enqueue_style( 'tooltip-css-aliexpress-theme', plugins_url('plugins/tooltipster/sideTip/themes/tooltipster-sideTip-borderless.min.css?v='.AEW_VERSION, __FILE__) );
                wp_enqueue_script( 'tooltipaliexpress', plugins_url('js/tooltipster.bundle.min.js?v='.AEW_VERSION, __FILE__), array('jquery') );
                

                //wp_set_script_translations( 'eventsaliexpress', 'aliexpress', dirname( plugin_basename(__FILE__) ) . '/languages' );
            }
        }
        function create_menu() {
            global $iconUrl;
            add_menu_page(__('AliExpress Option Page','aliexpress'), 'AliExpress' , 'manage_woocommerce', 'aliexpress-general-options', array($this, 'view_page_options') , $iconUrl );
            if(AEW_TOKEN != '') {
                add_submenu_page('aliexpress-general-options', __('Settings','aliexpress'), __('Settings', 'aliexpress') , 'manage_woocommerce', 'aliexpress-general-options', array($this, 'view_page_options'));
                add_submenu_page('aliexpress-general-options', __('Manage Categories','aliexpress'), __('Manage Categories', 'aliexpress') , 'manage_woocommerce', 'aliexpress-general-options&tab=upload_cats', array($this, 'view_page_options'));
                add_submenu_page('aliexpress-general-options', __('Orders AliExpress','aliexpress'), __('Orders', 'aliexpress') , 'manage_woocommerce', 'aliexpress-general-options&tab=view_orders', array($this, 'view_page_options'));
                add_submenu_page('aliexpress-general-options', __('Jobs','aliexpress'), __('Jobs', 'aliexpress') , 'manage_woocommerce', 'aliexpress-general-options&tab=view_jobs', array($this, 'view_page_options'));
                add_submenu_page('aliexpress-general-options', __('Error Log','aliexpress'), __('Error Log', 'aliexpress') , 'manage_woocommerce', 'aliexpress-general-options&tab=error', array($this, 'view_page_options'));
            }
        }
        function register_settings_options() {
            //register our settings
            register_setting( 'aew_general_options', 'aew_token_api' );
        }
        function view_page_options($tab) {
            require_once(AEW_VIEW_PATH . 'general-options.php');
        }

        public static function printDebug($data){
            echo '<pre>'.print_r($data, true).'</pre>';
        }
        function save_carriers_mapping(){
            global $wpdb;
            require_once(AEW_AE_PATH . 'AEFactory.php');
            require_once(AEW_AE_PATH . 'AECarriers.php');
            $factory = new AEFactory(AEW_TOKEN); 
            $carriers = new AECarriers($factory);
            $AllCarriers = json_decode($carriers->get_all_carriers(),true);
            $zones = AEW_MAIN::get_zones_wocommerce();

            // $res = array();
            $dataCarriers = $AllCarriers['result_list']['aeop_logistics_service_result'];
            foreach($_POST['carrier'] as $key => $carrier) {
                if($carrier == '0') { continue; }
                $code = intval($_POST['carrier_index'][$key]);
                if($code == '') { continue;}
                $saveData = array(
                    'id_carrier' => $zones[$key]['instance_id'],
                    'expression' => $dataCarriers[$code]['tracking_no_regex'],
                    'name_carrier' =>$dataCarriers[$code]['display_name'],
                    'name_zone' => $zones[$key]['name'],
                    'key_carrier' => $carrier,
                );
                // $res[] = $saveData;
                if(isset($_POST['carrier_id_reg'][$zones[$key]['instance_id']])) {
                    $wpdb->update( $wpdb->prefix.'aew_mapping_carriers', $saveData, array('id_mapping_carriers' => sanitize_text_field($_POST['carrier_id_reg'][$zones[$key]['instance_id']])));
                }else{
                $wpdb->insert( $wpdb->prefix.'aew_mapping_carriers', $saveData);
                }
            }
            // return $res;
            
        }

        /**
         * Comprueba a nivel binario dos strings y 
         * devuelve true en caso de comparación exitosa 
         * (no case sensitive)
         * 
         * Se ha añadido una expresión regular para eliminar cualquier caracter que no sea número o letra
         *
         * @param string $var1
         * @param string $var2
         * @param boolean $isSelect Si se establece true devolverá un select="select", por defecto false
         * @return void
         */
        public static function check_strings($var1, $var2, $isSelect = false) {
            
            $var1 = preg_replace('/[^A-Za-z0-9]+/', '', $var1);
            $var2 = preg_replace('/[^A-Za-z0-9]+/', '', $var2);
            
            $comparacion = strcasecmp(trim($var1), trim($var2));
            if($comparacion === 0) {
                if($isSelect) {
                    return 'selected="selected"';
                }else{
                    return true;
                }
            }
            return false;
        }
        function AEW_log( $message ) {
            if( WP_DEBUG === true ){
            if( is_array( $message ) || is_object( $message ) ){
                error_log( print_r( $message, true ) );
            } else {
                error_log( $message );
            }
            }
        }

        function save_product($postID, $post, $update){
                if(AEW_TOKEN == '') { return; }
                if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                    return;
                }

                if ( wp_is_post_revision( $postID ) ) {
                    return;
                }
                /**Prevent Action WP All Import */
                if(!isset($_POST['_wp_http_referer']) or strpos($_POST['_wp_http_referer'],'action=edit') === false) {
                    return;
                }

                if(isset($_POST['aew_category_id'])) {
                    update_post_meta($postID, '_aew_default_category_id', sanitize_text_field($_POST['aew_category_id']));
                }
                if(isset($_POST['aew_explosion_variations'])) {
                    update_post_meta($postID, '_aew_explosion_variations', '1');
                }else{
                    update_post_meta($postID, '_aew_explosion_variations', '0');
                }

                if(isset($_POST['aew_ean'])) {
                    update_post_meta($postID, 'aew_ean', sanitize_text_field( $_POST['aew_ean'] ));
                }

                if(isset($_POST['aew_group_select'])) {
                    update_post_meta($postID, 'aew_group_product', sanitize_text_field($_POST['aew_group_select']));
                }
                if(isset($_POST['aew_custom_description'])) {
                    update_post_meta($postID, '_aew_custom_description', wp_kses_post($_POST['aew_custom_description']));
                }
                if(isset($_POST['aew_custom_title'])) {
                    update_post_meta($postID, '_aew_custom_title', sanitize_textarea_field($_POST['aew_custom_title']));
                }
                // if(get_post_meta($postID, '_aew_product_id',true) and get_post_meta($postID, '_aew_product_id',true) != '0' ) {
                //     require_once(AEW_AE_PATH . 'AEFactory.php');
                //     $product_id_list = get_post_meta($postID, '_aew_product_id');
                //     $factory = new AEFactory(AEW_TOKEN);
                //     if(isset($_POST['_aew_status_ae'])) {
                //         $factory->set_products_online($product_id_list);
                //         update_post_meta($postID, '_aew_status_ae', '1');
                //     }else{
                //         $factory->set_products_offline($product_id_list);
                //         update_post_meta($postID, '_aew_status_ae', '0');
                //     }
                // }

                if(isset($_POST['shippingTemplateSelected'])) {
                    update_post_meta($postID, '_aew_shipping_template_product', sanitize_text_field($_POST['shippingTemplateSelected']));
                }
                
        }

        static public function get_ean_product($id) {
            $optionSelected = get_option('aew_use_ean', '0');
            if($optionSelected == '0') {
                $result = get_post_meta($id, 'aew_ean', true);
            }elseif($optionSelected == '1'){
                    $aewvalue = get_option('aew_ean_meta', '');
                if($aewvalue != '') {
                $result = get_post_meta($id, $aewvalue, true);
                }else{
                $result = '';	
                }
            }else{
            $result = '';
            }
            
            if(!$result) {
            $result = '';
            }
            return AEW_Product::aew_validate_ean($result);
        }

        static public function sendTrackingOrder($orderID) {
            if(AEW_TOKEN == '') { return; }
            $AliExpress_Order_id = get_post_meta($orderID,'_aew_order_id', true);
            if($AliExpress_Order_id and $AliExpress_Order_id != '' and $AliExpress_Order_id != 0) {
                $trackerSavedNumber = get_post_meta($orderID, '_aew_tracking_number', true);

                if(!$trackerSavedNumber || $trackerSavedNumber == '') {
                    AEW_MAIN::register_error(__('Tracking number required, Not communicated to AliExpress','aliexpress'),$orderID,'order');
                    return false;
                }

                $carirerSelected = get_post_meta($orderID, '_aew_key_carrier', true);

                require_once(AEW_AE_PATH . 'AEFactory.php');
                require_once(AEW_AE_PATH . 'AECarriers.php');

                $factory = new AEFactory(AEW_TOKEN); 
                $carriers = new AECarriers($factory);

                $webSiteTracking = '';
                
                $result = $carriers->fulfill_order($carirerSelected ,$webSiteTracking ,$AliExpress_Order_id ,AECarrier_Send_type::ALL_SEND_TYPE ,null , $trackerSavedNumber);
                    if(!is_array($result) && $result === true) {
                        update_post_meta($orderID, '_ae_order_send_aliexpress_finish', '1');
                        $registerMensaje = sprintf(__('Order Nº %s Finished, Tracking Number %s', $orderID, $trackerSavedNumber), 'aliexpress');
                        AEW_MAIN::register_error($registerMensaje,$orderID,'order');
                        update_post_meta($orderID, '_ae_order_msg_aliexpress_finish', $registerMensaje);
                        update_post_meta($orderID, '_ae_order_date_aliexpress_finish', date('Y-m-d H:i:s'));
                        return true;
                    }else{
                        if(isset($result[-999])) {
                            $msg = __("The order was already completed", "aliexpress");
                            update_post_meta($orderID, '_ae_order_send_aliexpress_finish', '1');
                        }else{
                            $msg = $result[array_key_first($result)];
                            update_post_meta($orderID, '_ae_order_send_aliexpress_finish', '0');
                            update_post_meta($orderID, '_ae_order_msg_aliexpress_finish', 'Generic Error');
                        }
                        AEW_MAIN::register_error(__('Error AliExpress') . " " . $msg,$orderID,'order');
                        return false;
                    }
            }
        }

        function order_status_changed($orderID, $oldStatus, $newStatus){
            if(AEW_TOKEN == '') { return; }
            $AliExpress_Order_id = get_post_meta($orderID,'_aew_order_id', true);
            if($AliExpress_Order_id and $AliExpress_Order_id != '' and $AliExpress_Order_id != 0) {
                $notificadoAliExpress = get_post_meta($orderID, '_ae_order_send_aliexpress_finish', true);
                //Enviar número de seguimiento si el estado es enviado
                //Pedido de AliExpress
                if(isset($_POST['aew_tracking_number'])) {
                    $trackerSavedNumber = sanitize_text_field($_POST['aew_tracking_number']);
                }else{
                    $trackerSavedNumber = get_post_meta($orderID, '_aew_tracking_number', true);
                }
                
                //If is empty
                if(!$trackerSavedNumber || $trackerSavedNumber == '') {
                    AEW_MAIN::register_error(__('Tracking number required, Not communicated to AliExpress','aliexpress'),$orderID,'order');
                    return;
                }

                update_post_meta($orderID, '_aew_tracking_number', $trackerSavedNumber);
                
                //Selected by vendor
                if(isset($_POST['aew_carriers_sent'])) {
                    $carirerSelected = sanitize_text_field( $_POST['aew_carriers_sent'] );
                    update_post_meta($orderID, '_aew_key_carrier',$carirerSelected);
                    update_post_meta($orderID, '_aew_key_carrier_real',$carirerSelected);
                }else{
                    $carirerSelected = get_post_meta($orderID, '_aew_key_carrier', true);
                }
                if($newStatus == 'completed' and $notificadoAliExpress != '1') {

                    require_once(AEW_AE_PATH . 'AEFactory.php');
                    require_once(AEW_AE_PATH . 'AECarriers.php');


                    if(isset($_POST['aew_website_tracking'])) {
                        $webSiteTracking = sanitize_text_field($_POST['aew_website_tracking']);
                    }
                    if(substr($carirerSelected,0,5) == 'OTHER') {
                        if($webSiteTracking == '' or $webSiteTracking == null) {
                            AEW_MAIN::register_error(__('The order has not been communicated to AliExpress, the tracking URL is required.','aliexpress'),$orderID,'order');
                            return false;
                        }else{
                            $trakingWithoutNumber = str_replace($trackerSavedNumber, '', $webSiteTracking);
                            update_option('_aew_other_carrier_url', $trakingWithoutNumber);
                        }
                    }else{
                        $webSiteTracking = '';
                    }
                    $factory = new AEFactory(AEW_TOKEN); 
                    $carriers = new AECarriers($factory);

                    $result = $carriers->fulfill_order($carirerSelected ,$webSiteTracking ,$AliExpress_Order_id ,AECarrier_Send_type::ALL_SEND_TYPE ,null , $trackerSavedNumber);
                    if(!is_array($result) && $result === true) {
                        update_post_meta($orderID, '_ae_order_send_aliexpress_finish', '1');
                        AEW_MAIN::register_error(sprintf(__('Order Nº %s Finished, Tracking Number %s', $orderID, $trackerSavedNumber), 'aliexpress'),$orderID,'order');
                        return true;
                    }else{
                        if(isset($result[-999])) {
                            $msg = __("The order was already completed", "aliexpress");
                            update_post_meta($orderID, '_ae_order_send_aliexpress_finish', '1');
                        }else{
                            $msg = $result[array_key_first($result)];
                            update_post_meta($orderID, '_ae_order_send_aliexpress_finish', '0');
                        }
                        AEW_MAIN::register_error(__('Error AliExpress') . " " . $msg,$orderID,'order');
                        return false;
                    }
                    
                    
                }
            }
        }

        function create_tables() {
            global $wpdb;
            
            $dbVersion = get_option('aew_db_version');

            $sql = "";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $charset_collate = $wpdb->get_charset_collate();
            
            $table_name = $wpdb->prefix . 'aew_mapping_attributes';

            //Mapping Attributes
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id_mapping_attr int(11) NOT NULL AUTO_INCREMENT,
                category_id bigint(20) NOT NULL,
                key_real varchar(255) NOT NULL,
                key_ali varchar(255) NOT NULL,
                value_real varchar(255) NOT NULL,
                value_ali varchar(255) NOT NULL,
                id_ali bigint(20) NOT NULL,
                attr_type varchar(10) NOT NULL,
                value_alias varchar(50) NULL,
                use_alias int(2) DEFAULT 0,
                PRIMARY KEY  (id_mapping_attr)
            ) $charset_collate;";
            dbDelta( $sql );

            $table_name = $wpdb->prefix . 'aew_mapping_features';

            //Mapping Features
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id_mapping_features int(11) NOT NULL AUTO_INCREMENT,
                category_id bigint(20) NOT NULL,
                key_real varchar(255) NOT NULL,
                key_ali varchar(55) NOT NULL,
                value_real varchar(255) NOT NULL,
                value_ali varchar(55) NOT NULL,
                id_ali bigint(20) NOT NULL,
                attr_type varchar(10) NOT NULL,
                use_alias int(2) DEFAULT 0,
                PRIMARY KEY  (id_mapping_features)
            ) $charset_collate;";
            dbDelta( $sql );

            $table_name = $wpdb->prefix . 'aew_mapping_carriers';

            //Mapping carriers
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id_mapping_carriers int(11) NOT NULL AUTO_INCREMENT,
                id_carrier int(11) NOT NULL,
                expression varchar(100) NOT NULL,
                name_carrier varchar(50) NOT NULL,
                name_zone varchar(50) NOT NULL,
                key_carrier varchar(50) NOT NULL,
                PRIMARY KEY  (id_mapping_carriers)
            ) $charset_collate;";
            dbDelta( $sql );

            $table_name = $wpdb->prefix . 'aew_jobs';


            /** Change Script for SQL < 5.6.5 */


            //JOBS
            //$sql = "DROP TABLE IF EXISTS $table_name;";
            //$wpdb->query( $sql );

            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_check DATETIME,
                success_total int NOT NULL,
                total_item int NOT NULL,
                jobID varchar(30) NOT NULL,
                finished int DEFAULT 0,
                data_job BLOB,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            dbDelta( $sql );

            //Error Products
            $table_name = $wpdb->prefix . 'aew_products_error';

            //Mapping Features
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id_error int(11) NOT NULL AUTO_INCREMENT,
                msg varchar(200) NOT NULL,
                created_at DATETIME NOT NULL,
                element_id bigint(20) NOT NULL,
                type_error varchar(50) NOT NULL,
                PRIMARY KEY  (id_error)
            ) $charset_collate;";
            dbDelta( $sql );

            //Features Default Category
            $table_name = $wpdb->prefix . 'aew_data_default';

            //Products
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id_default int(11) NOT NULL AUTO_INCREMENT,
                data_id int(11) NOT NULL,
                key_data VARCHAR(100) NOT NULL,
                value_data TEXT NOT NULL,
                PRIMARY KEY  (id_default),
                customValue VARCHAR(200) NULL
            ) $charset_collate;";
            dbDelta( $sql );

            $this->aew_init();
            flush_rewrite_rules();
            
        }
        // function get_category_aliexpress($idLocalCategory) {
        //     global $wpdb;
        //     $categoryAliexpress = $wpdb->get_var( "SELECT ali_id FROM {$wpdb->prefix}aew_categories WHERE real_id=$idLocalCategory" );
        //     return $categoryAliexpress;
        // }
        static function has_uploaded_variations($id) {
            $args = array(
                'post_type'     => 'product_variation',
                'post_status'   => array( 'private', 'publish' ),
                'numberposts'   => -1,
                'orderby'       => 'menu_order',
                'fiels'         => 'ids',
                'order'         => 'asc',
                'post_parent'   => $id
            );
            $variations = get_posts( $args );
            $upload = false;
            foreach ( $variations as $variation ) {

                $variation_ID = $variation->ID;
                $upload = get_post_meta( $variation->ID , '_aew_product_id', true );
                if($upload == '1') {
                    return true;
                }
            }
            return $upload;
        }
        function colum_product( $column, $post_id ) {
            if(AEW_TOKEN == '') { return; }
            if ($column == 'aew_product_sync'){
                $sincronize = get_post_meta($post_id, '_aew_product_id', true);
                $price = get_post_meta($post_id, '_price', true);
                if($price == '') {
                    echo __('Not Compatible');
                    return;
                }
                $jobID = get_post_meta($post_id, '_aew_run_job', true);
                if($jobID and $jobID != "0") {
                    echo __('Uploading...','aliexpress');
                }else{
                    if(get_post_status($post_id) != 'publish') {
                        echo '';
                        return;
                    }
                    $hasVariationsUploaded = self::has_uploaded_variations($post_id);
                    if(($sincronize != '' and $sincronize != 0) || $hasVariationsUploaded) {
                        
                        $botonUpdate = '<button class="aew_publish_update" type="button" data-id="'.$post_id.'">'.__('Update','aliexpress').'</button>';
                        if($hasVariationsUploaded) {
                            echo $botonUpdate . '<br><span class="minidate">'.__('With Explosion Variations','aliexpress').'</span>';
                        }else{
                            $botonUpdate .= '<a class="aew_view" href="https://www.aliexpress.com/item/'.$sincronize.'.html" target="_blank">'.__('View','aliexpress').'</a><br>'; 
                            echo $botonUpdate . '<span class="minidate">'.__('Last Update:','aliexpress').' '.date('d-m-Y H:i:s', intval(get_post_meta($post_id, '_aew_product_sincro', true))).'</span>';
                        }
                        if(get_post_meta($post_id,'_aew_need_upload', true) == "1") {
                            echo '<span class="need_update">'.__('Need update', 'aliexpress').'</span>';
                        }
                    }else{
                        echo '<button class="aew_publish" type="button" data-id="'.$post_id.'">'.__('Upload','aliexpress').'</button>';
                    }
                }
            }
        }

        static function select_group_category($groups, $selected = false, $name = 'aew_group_select') {
            $html = '';

            if(AEW_TOKEN == '') { return $html; }
            if(isset($groups['result']['target_list']['aeop_ae_product_tree_group'])) {
                $html .= "<select name='{$name}'>";
                $html .= "<option value='0' >".__('No set', 'aliexpress')."</option>";
                $gs = $groups['result']['target_list']['aeop_ae_product_tree_group'];
                if(isset($gs['group_id'])){
                    $gs = array($gs);
                }
                if(is_array($gs)) {
                    foreach($gs as $group) {
                        $sel = '';
                        if($selected == $group['group_id']) {
                            $sel = "selected='selected'";
                        }
                        $html .= "<option {$sel} value='{$group['group_id']}' >{$group['group_name']}</option>";
                        if(isset($group['child_group_list']['aeop_ae_product_child_group'])){
                            $childs = $group['child_group_list']['aeop_ae_product_child_group'];
                            if(isset($childs['group_id'])) $childs = [$childs];
                            foreach ($childs as $child) {
                                $sel = '';
                                if($selected == $child['group_id']) {
                                    $sel = "selected='selected'";
                                }
                                $html .= "<option {$sel} value='{$child['group_id']}'>&nbsp;&nbsp;{$child['group_name']}</option>";
                            }
                        }
                    }
                }

                $html .= '</select>';
            }

            return $html;
        }

        static function aew_connect() {
            if(AEW_TOKEN == '') { return; }
            if(is_admin() and !wp_doing_ajax()) {
                $timeNow = strtotime(date('Y-m-d H:i:s'));
                $optionLast = intval(get_option('aew_last_check_connect')) ?: 0;
                //if(($timeNow - $optionLast) >= 43200) {
                    update_option('aew_last_check_connect', strtotime(date('Y-m-d H:i:s')));
                    $domain = get_option('siteurl');
                    $domain = str_replace(['http://','https://','www'], ['','',''], $domain);
                    $t = md5('u?r98 r73%489ry38ryf'.$domain);
                    $d = self::base64_encode_url(  $domain );

                    $check = wp_remote_get('https://aliexpress.wecomm.es/services/getdomaininfo/'.$t.'/'.$d);
                    $response = json_decode($check['body'],true);
                    if($response['error']) {
                        //No valid domain
                    }else{
                        // update_option('_aew_support', $response['license']);
                        // update_option('_aew_email', $response['user_login']);
                        // update_option('_aew_email', $response['user_login']);
                    }
                //}
        }
        }
        private static function base64_encode_url($string) {
            return str_replace(['+','/','='], ['-','_',''], base64_encode($string));
        }
        function add_colum_products( $columns ) {
            if(AEW_TOKEN == '') { return $columns; }
            return array_merge( $columns, 
                array( 'aew_product_sync' => __( 'AliExpress', 'aliexpress' ) ) );
        }

        /**Pedidos */
        function colum_orders( $column, $post_id ) {
            // if(AEW_TOKEN == '') { return; }
            if ($column == 'order_status'){
                $orderID = get_post_meta($post_id, '_aew_order_id', true);
                if($orderID != '' and $orderID != 0) {
                    $createdVia = get_post_meta($post_id, '_created_via', true);
                echo '<span class="created_via">'.sprintf(__('Created Vía %s','aliexpress'), $createdVia).'</span>';
                }
            }
        }


        static function country_prices(){
            if(AEW_TOKEN == '') { return; }
            require_once(AEW_AE_PATH . 'AEFactory.php');

            $factory = new AEFactory(AEW_TOKEN); 

            $countries = $factory->get_countries(200001968);
            $prices_saved = get_option('countryPrices', []);


            $print = '<form method="post">
            <input type="hidden" name="action" value="save_country_prices" />
            <table style="width:100%" class="aew_table" cellpadding="0" cellspacing="0">';
            $print .= '<tr>
                <td>'.__('Country ISO', 'aliexpress').'</td>
                <td>'.__('Percent', 'aliexpress').'</td>
            </tr>';
            if($countries) {
                foreach($countries as $country) {
                    $value = isset($prices_saved[$country['const']]) ? $prices_saved[$country['const']] : '';
                    $print .= '<tr>
                        <td>'.$country['title'].'</td>
                        <td><input min="70" type="number" step="1" name="countryPrices['.$country['const'].']" value="'.$value.'" /></td>
                    </tr>';
                }
            }else{
                $print .= '<tr><td colspan="2">Please reload page.</td></tr>';
            }

            $print .= '</table>';

            $print .= '<input type="submit" class="ae_button endForm" value="'.__('Save Percent Prices','aliexpress').'" />';

            echo $print;
            
        }
        // function register_order_status() {
        //     // if(AEW_TOKEN == '') { return; }
        //     foreach(AEW_MAIN::$posiblesEstados as $key => $label) {
        //         register_post_status( 'wc-' . $label['internal_name'], array(
        //             'label'=> __($label['name'], 'aliexpress'),
        //             'public' => true
        //         ));
        //     }
        // }
        // function add_order_status( $order_statuses ) {
        //         // if(AEW_TOKEN == '') { return $order_statuses; }
        //         global $post;
        //         if($post) {
        //             $orderID = get_post_meta($post->ID, '_aew_order_id', true);
        //         }else{ $orderID = null; }
        //         if($orderID) {
        //             $order_statuses = array();
        //             foreach(AEW_MAIN::$posiblesEstados as $key => $label) {
        //                 $order_statuses['wc-' . $label['internal_name']] =  __($label['name'], 'aliexpress');
        //             }
        //             return $order_statuses;
        //         }else{
        //             foreach(AEW_MAIN::$posiblesEstados as $key => $label) {
        //                 $order_statuses['wc-' . $label['internal_name']] =  __($label['name'], 'aliexpress');
        //             }

        //             return $order_statuses;
        //         }
                
            
        // }
        /**
         * Registra un error en la tabla de errores del plugin
         * 
         * Errores:
         * generic: error genérico del plugin
         * product: error sobre un producto obligatorio usar element_id
         * order: error sobre un pedido
         * 
         *
         * @param string $msg
         * @param integer $element_id
         * @param string $type Por defecto generic
         * @return void
         */
        static function register_error($msg, $element_id = 0, $type = 'generic') {
            global $wpdb;
            $wpdb->insert( 
                $wpdb->prefix.'aew_products_error', 
                array( 
                    'msg' => $msg, 
                    'element_id' => $element_id,
                    'type_error' => $type,
                    'created_at' => current_time( 'mysql' )
                ));
        }
        static function get_errors() {
            global $wpdb;
            $Errors = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."aew_products_error ORDER by created_at DESC LIMIT 200");
            update_option('aew_last_check_errors', date('Y-m-d H:i:s'));
            return $Errors;
        }
        function get_last_errors() {
            global $wpdb;
            $lastCheckError = get_option('aew_last_check_errors');
            $Errors = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."aew_products_error WHERE created_at >= '".$lastCheckError."'");
            update_option('aew_last_check_errors', date('Y-m-d H:i:s'));
            return $Errors;
        }

        /**
         * Create new fields for variations
         *
        */
        function aew_variable_fields( $loop, $variation_data, $variation ) {
        ?>
            <tr>
                <td>
                    <?php
                    // Text Field
                    woocommerce_wp_text_input( 
                        array( 
                            'id'          => 'aew_ean['.$loop.']', 
                            'label'       => __( 'EAN AliExpress', 'aliexpress' ), 
                            'placeholder' => '',
                            'desc_tip'    => 'true',
                            'description' => __( 'Enter the EAN variation', 'aliexpress' ),
                            'value'       => get_post_meta( $variation->ID, 'aew_ean', true )
                        )
                    );
                    ?>
                </td>
            </tr>
            <?php
            $idVariationAliExpress = get_post_meta($variation->ID, '_aew_product_id', true);
            if($idVariationAliExpress and $idVariationAliExpress != '') {
                echo '<tr><td>
                        <a class="button removeProductID" data-id="'.$variation->ID.'">'.__('Remove AliExpress Product ID','aliexpress').'</a>
                    </td>
                    <td>
                        <a class="aew_view" target="_blank" class href="https://www.aliexpress.com/item/'.$idVariationAliExpress.'.html">'.__('View on AliExpress', 'aliexpress').'</a>
                        <a class="boton_actions_products" style="background-color:red;" href="javascript:aew_delete_products(\''.$idVariationAliExpress.'\', true);">'.__('Delete Product','aliexpress').'</a>
                    </td></tr>';
            } ?>
        <?php
        }

        /**
         * Create new fields for new variations
         *
        */
        function aew_variable_fields_js() {
        ?>
            <tr>
                <td>
                    <?php
                    // Text Field
                    woocommerce_wp_text_input( 
                        array( 
                            'id'          => 'aew_ean[ + loop + ]', 
                            'label'       => __( 'EAN AliExpress', 'aliexpress' ), 
                            'placeholder' => '',
                            'desc_tip'    => 'true',
                            'description' => __( 'Enter the EAN variation', 'aliexpress' ),
                            'value'       => ''
                        )
                    );
                    ?>
                </td>
            </tr>
        <?php
        }

        /**
         * Save new fields for variations
         *
        */
        function aew_save_variable_fields( $post_id ) {
                if (isset( $_POST['variable_sku'] ) ) :

                    $variable_sku          = $_POST['variable_sku'];
                    $variable_post_id      = $_POST['variable_post_id'];
                    
                    // Text Field
                    $aew_ean = $_POST['aew_ean'];
                    for ( $i = 0; $i < sizeof( $variable_sku ); $i++ ) :
                        $variation_id = (int) $variable_post_id[$i];
                        if ( isset( $aew_ean[$i] ) ) {
                            update_post_meta( $variation_id, 'aew_ean', stripslashes( $aew_ean[$i] ) );
                        }
                    endfor;
                    
                endif;
            }

            /**
             * Get array of local products
             *
            */
            public static function get_local_products(){
                global $wpdb;

                $productos = $wpdb->get_results("SELECT p.ID AS id, p.post_title AS name, m.meta_value AS aew_id, ml.sku
                FROM {$wpdb->prefix}posts AS p 
                INNER JOIN {$wpdb->prefix}postmeta AS m ON p.ID = m.post_id 
                INNER JOIN {$wpdb->prefix}wc_product_meta_lookup AS ml ON p.ID = ml.product_id 
                WHERE m.meta_key = '_aew_product_id'
                ");
                
                foreach ($productos as $product) {
                    $ret[$product->aew_id] = array("id" => $product->id, "name" => $product->name, "sku" => $product->sku);
                }
                return $ret;
            }
        }

}


$AEMAIN = new AEW_MAIN();

?>

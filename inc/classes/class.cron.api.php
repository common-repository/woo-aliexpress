<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if(!class_exists('AEW_CRON')) {
    class AEW_CRON {
        function __construct(){
            $actionCron = array(
            'aew_cron_update_products',
            'aew_cron_get_orders',
            'aew_cron_send_all_products',
            'aew_cron_update_stock_price',
            'aew_cron_update_price',
            'aew_cron_update_stock'
            );
            foreach( $actionCron as $action ){
                add_action( 'wp_ajax_'.$action, array( $this, $action ) );
                add_action( 'wp_ajax_nopriv_'.$action, array( $this, $action ) );
            }
        }
        private static function check_token() {
            if(!isset($_GET['token'])) {
                wp_send_json(array('error' => 401));
                die('');
            }
            if(sanitize_text_field($_GET['token']) != get_option('AEW_TOKEN_CRON') and sanitize_text_field($_GET['token']) != AEW_TOKEN) {
                wp_send_json(array('error' => 401));
                die('');
            }
            return true;
        }

        /**
         * Actualiza el Stock de los productos
         */
        function aew_cron_update_stock() {
            self::check_token();
            require_once(AEW_AE_PATH . 'AEFactory.php');

            $factory = new AEFactory(AEW_TOKEN);
            $factory->price_by_country = get_option('countryPrices', []);
            $productClass = new AEW_Product();
            $TodosProductos = $productClass->get_products_to_upload_price($factory);
            if(isset($_GET['debug']) && $_GET['debug'] == 'aedev') {
                wp_send_json($TodosProductos);
                wp_die();
            }

            update_option('aew_last_cron_stock', time());

            if(!$TodosProductos) {
                wp_send_json(false); 
                wp_die();
                return;
            }
            
            $result = array();
            $chunk_array = array_chunk($TodosProductos, AEW_CHUNK_JOBS);
            foreach($chunk_array as $k => $chunk) {
                $result['stock'] = $factory->batch_update_products_inventory($chunk);
    
                //STOCKS
                $obj_job = array(
                    'success_item_count' =>0, 
                    'total_item_count' =>count($chunk),
                    'job_id' =>$result['stock']->aejob_id,
                    'result_list' => ''
                );
                AEW_Job::create_job($obj_job);
            }

            if(count($TodosProductos) > 0) {
                wp_send_json(true); 
            }else{
                wp_send_json(false); 
            }

            wp_die();
        }

        /**
         * Actualiza el precio de los productos
         */
        function aew_cron_update_price() {
            self::check_token();
            require_once(AEW_AE_PATH . 'AEFactory.php');

            $factory = new AEFactory(AEW_TOKEN);
            $factory->price_by_country = get_option('countryPrices', []);
            $productClass = new AEW_Product();
            $TodosProductos = $productClass->get_products_to_upload_price($factory);

            if(isset($_GET['debug']) && $_GET['debug'] == 'aedev') {
                wp_send_json($TodosProductos);
                wp_die();
            }

            update_option('aew_last_cron_price', time());

            if(!$TodosProductos) {
                wp_send_json(false); 
                wp_die();
                return;
            }

            $result = array();
            $factory->price_by_country = get_option('countryPrices', []);

            $chunk_array = array_chunk($TodosProductos, AEW_CHUNK_JOBS);
            foreach($chunk_array as $k => $chunk) {
                $result['price'] = $factory->batch_update_products_price($chunk);
    
                //PRECIOS
                if($result['price']) {
                    $obj_job = array(
                        'success_item_count' =>0, 
                        'total_item_count' =>count($chunk),
                        'job_id' =>$result['price']->aejob_id,
                        'result_list' => ''
                    );
                    AEW_Job::create_job($obj_job);
                }
            }

            if($TodosProductos && count($TodosProductos) > 0) {
                wp_send_json(true); 
            }else{
                wp_send_json(false); 
            }

            wp_die();
        }

        /**
         * Actualiza el Stock y el Precio de los productos
         */
        function aew_cron_update_stock_price() {
            self::check_token();
            require_once(AEW_AE_PATH . 'AEFactory.php');

            $factory = new AEFactory(AEW_TOKEN);
            $productClass = new AEW_Product();

            $prods = false;
            if(isset($_GET['full_update'])) {
                // Full update
                $meta_query = array(
                    array(
                        'key' => '_aew_product_id',
                        'compare' => '!=',
                        'value' => ''
                    )
                );
                $args = array(
                    'post_type' => array('product','product_variation'),
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'meta_query' => $meta_query
                );
                $prods = get_posts($args);
            }
            $TodosProductos = $productClass->get_products_to_upload_price($factory, false, $prods);

            //PRODUCTS OK
            if(isset($_GET['debug']) && $_GET['debug'] == 'aedev') {
                wp_send_json($TodosProductos);
                wp_die();
            }

            update_option('aew_last_cron_stock_price', time());

            if(!$TodosProductos) {
                wp_send_json(false); 
                wp_die();
                return;
            }

            $result = array();
            $factory->price_by_country = get_option('countryPrices', []);

            $chunk_array = array_chunk($TodosProductos, AEW_CHUNK_JOBS);
            foreach($chunk_array as $k => $chunk) {
                $result['price'] = $factory->batch_update_products_price($chunk);
                $result['stock'] = $factory->batch_update_products_inventory($chunk);
    
                
                //PRECIOS
                if($result['price']) {
                    $obj_job = array(
                        'success_item_count' =>0, 
                        'total_item_count' =>count($chunk),
                        'job_id' =>$result['price']->aejob_id,
                        'result_list' => ''
                    );
                    AEW_Job::create_job($obj_job);
                }
    
                //STOCKS
                if($result['stock']) {
                    $obj_job = array(
                        'success_item_count' =>0, 
                        'total_item_count' =>count($chunk),
                        'job_id' =>$result['stock']->aejob_id,
                        'result_list' => ''
                    );
                    AEW_Job::create_job($obj_job);
                }
    
                if(get_option("aew_checked_debug_mode") == "0") {
                    foreach($chunk as $proJob) {
                        update_post_meta(intval($proJob['item_content_id']),'_aew_run_job', $result['stock']->aejob_id);
                    }
                }
            }

            if($TodosProductos && count($TodosProductos) > 0) {
                wp_send_json(true);
            }else{
                wp_send_json(false); 
            }
            wp_die();
        }
        function aew_cron_update_products() {
            
            self::check_token();
            require_once(AEW_AE_PATH . 'AEFactory.php');
            require_once(AEW_AE_PATH . 'AEProduct.php');
            require_once(AEW_AE_PATH . 'AECategory.php');
            $argsCategorias = array(
                'taxonomy' => 'product_cat',
                'orderby' => 'meta_value_num',
                'order' => 'ASC',
                'fields' => 'ids',
                'hide_empty' => false,
                'hierarchical' => true,
                'meta_query' => [[
                'key' => 'aew_category_id',
                'type' => 'NUMERIC'
                ]],
            );
            $unidadPeso = strtolower( get_option( 'woocommerce_weight_unit' ) );
            $categorias = get_terms( $argsCategorias );
            
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => array(
                    array(
                        'taxonomy'      => 'product_cat',
                        'field'         => 'term_id',
                        'terms'         => $categorias,
                        'operator'      => 'IN'
                    )
                ),
                'meta_query' => array(
                    array(
                        'key'     => '_aew_need_upload',
                        'value'   => '1',
                        'compare' => '='
                    )
                )
            );

            update_option('aew_last_cron_update', time());

            $productos = get_posts($args);
            if(!$productos) { wp_send_json(false); die(); }
            $productsToSend = array();
            $categoriaSave = null;
            $factory = new AEFactory(AEW_TOKEN);
            $factory->price_by_country = get_option('countryPrices', []);


            if(get_option('aew_only_stock_price', '0') == '1') {
                $productAW = new AEW_Product();
                $TodosProductos = $productAW->get_products_to_upload_price($factory, false, $productos);

                $chunk_array = array_chunk($TodosProductos, AEW_CHUNK_JOBS);
                foreach($chunk_array as $k => $chunk) {
                    $result['price'] = $factory->batch_update_products_price($chunk);
                    $result['stock'] = $factory->batch_update_products_inventory($chunk);
    
            
                    //PRECIOS
                    if($result['price']) {
                        $obj_job = array(
                            'success_item_count' =>0, 
                            'total_item_count' =>count($chunk),
                            'job_id' =>$result['price']->aejob_id,
                            'result_list' => ''
                        );
                        AEW_Job::create_job($obj_job);
                    }
    
                    //STOCKS
                    if($result['stock']) {
                        $obj_job = array(
                            'success_item_count' =>0, 
                            'total_item_count' =>count($chunk),
                            'job_id' =>$result['stock']->aejob_id,
                            'result_list' => ''
                        );
                        AEW_Job::create_job($obj_job);
                    }
    
                    if(get_option("aew_checked_debug_mode") == "0") {
                        foreach($chunk as $proJob) {
                            update_post_meta(intval($proJob['item_content_id']),'_aew_run_job', $result['stock']->aejob_id);
                        }
                    }
                }
            }else{
                foreach($productos as $p) {
                    //Comprueba primero la categoría por defecto marcada en el producto.
                    $categoriaLocal = get_post_meta($p, '_aew_default_category_id', true);
    
                
                    //Si no hay categoría por defecto en el producto, extraigo la primera.
                    if(!$categoriaLocal) {
                        $categorias = get_the_terms( $p, 'product_cat' );
                        if($categorias) {
                            $totalCategorias = count($categorias);
                            $categoriaLocal = $categorias[intval($totalCategorias - 1)]->term_id;
                        }
                    }
                    /**
                     * Si la categoria anterior es diferente a la categoría que se va a procesar ahora
                     * extraigo los datos, si no, ya los tengo almacenados.
                     */
                    if($categoriaSave != $categoriaLocal) {
                        $fee_by_category = floatval(get_term_meta(intval($categoriaLocal),'aew_category_fee',true));
                        $fixed_amount = floatval(get_term_meta(intval($categoriaLocal),'aew_fixed_amount',true));
                        $categoryAliExpress = get_term_meta($categoriaLocal, 'aew_category_id', true);
                        $category = $factory->get_category(intval($categoryAliExpress));
                        $attrsSelect = AEW_Attributes::get_mapping_attr($categoriaLocal);
                        $featuresSelect = AEW_Features::get_mapping_features($categoriaLocal);
                        $typesFeatures = $factory->get_category_attributes_types(intval($categoryAliExpress));
                        $categoriaSave = $categoriaLocal;
                    }
                    
                    $AtributosObligatorios = $factory->get_category_required_attributes(intval($categoryAliExpress));
                
                    /**
                     * Si la categoría del producto no está mapeada, registra un error.
                     */
                    if(!$categoryAliExpress) {
                        $productId = $p->ID;
                        AEW_MAIN::register_error(__('Category Aliexpress is not declared', 'aliexpress'), $productId, 'product');
                        continue;
                    }
                    
                    $productAW = new AEW_Product();
                    $productoSend = $productAW->create($p, $category, $attrsSelect, $featuresSelect, $typesFeatures, $fee_by_category, $categoriaLocal, $AtributosObligatorios, $unidadPeso, $fixed_amount);
                    
                    if($productoSend) {
                        $productsToSend[] = $productoSend;
                    }
                    
                }
                if(isset($_GET['debug']) && $_GET['debug'] == 'aedev') {
                    wp_send_json([$category, $productsToSend]);
                    wp_die();
                }

                if(!$productsToSend) {
                    wp_send_json(false); 
                    wp_die();
                    return;
                }
                /**
                 * Se crear el trabajo y se envia a AE.
                 */

                $chunk_array = array_chunk($productsToSend, AEW_CHUNK_JOBS);
                foreach($chunk_array as $k => $chunk) {
                    $job = $productAW->sendUpdate($factory, $category, $chunk);
                    $obj_job = array(
                        'success_item_count' =>0, 
                        'total_item_count' =>count($chunk),
                        'job_id' =>$job,
                        'result_list' => ''
                    );
                    /**
                     * Se almacena el trabajo en el sistema
                     */
                    AEW_Job::create_job($obj_job);
                    /**
                     * awe_run_job Updated with JOB id AE
                     * @since 1.0.4
                     */
                    if(get_option("aew_checked_debug_mode") == "0") {
                        foreach($chunk as $proJob) {
                            update_post_meta(intval($proJob->id),'_aew_run_job', $job);
                        }
                    }
                }
            }
            
            wp_send_json(true);
            wp_die(); 
        }



        function aew_cron_send_all_products() {
            self::check_token();
            require_once(AEW_AE_PATH . 'AEFactory.php');
            require_once(AEW_AE_PATH . 'AEProduct.php');
            require_once(AEW_AE_PATH . 'AECategory.php');
            $unidadPeso = strtolower( get_option( 'woocommerce_weight_unit' ) );
            
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => array(
                    'relation' => 'OR',
                        array(
                        'key' => '_aew_product_id',
                        'compare' => 'NOT EXISTS' // doesn't work
                        ),
                        array(
                        'key' => '_aew_product_id',
                        'value' => ''
                        )
                )
            );
            $productos = get_posts($args);
            // echo json_encode($productos);
            // die();
            if(!$productos) { wp_send_json(false); die(); }
            $productsToSend = array();
            $categoriaSave = null;
            $factory = new AEFactory(AEW_TOKEN);
            $factory->price_by_country = get_option('countryPrices', []);
            foreach($productos as $p) {
                
                //Comprueba primero la categoría por defecto marcada en el producto.
                $categoriaLocal = get_post_meta($p, '_aew_default_category_id', true);

            
                //Si no hay categoría por defecto en el producto, extraigo la primera.
                if(!$categoriaLocal) {
                    $categorias = get_the_terms( $p, 'product_cat' );
                    if($categorias) {
                        $totalCategorias = count($categorias);
                        $categoriaLocal = $categorias[intval($totalCategorias - 1)]->term_id;
                    }
                }
                /**
                 * Si la categoria anterior es diferente a la categoría que se va a procesar ahora
                 * extraigo los datos, si no, ya los tengo almacenados.
                 */
                if($categoriaSave != $categoriaLocal) {
                    $fee_by_category = floatval(get_term_meta(intval($categoriaLocal),'aew_category_fee',true));
                    $fixed_amount = floatval(get_term_meta(intval($categoriaLocal),'aew_fixed_amount',true));
                    $categoryAliExpress = get_term_meta($categoriaLocal, 'aew_category_id', true);
                    $category = $factory->get_category(intval($categoryAliExpress));
                    $attrsSelect = AEW_Attributes::get_mapping_attr($categoriaLocal);
                    $featuresSelect = AEW_Features::get_mapping_features($categoriaLocal);
                    $typesFeatures = $factory->get_category_attributes_types(intval($categoryAliExpress));
                    $categoriaSave = $categoriaLocal;
                }

                $AtributosObligatorios = $factory->get_category_required_attributes(intval($categoryAliExpress));
            
                /**
                 * Si la categoría del producto no está mapeada, registra un error.
                 */
                if(!$categoryAliExpress) {
                    AEW_MAIN::register_error(__('Category Aliexpress is not declared', 'aliexpress'), $p, 'product');
                    continue;
                }
                
                $productAW = new AEW_Product();
                $productoSend = $productAW->create($p, $category, $attrsSelect, $featuresSelect, $typesFeatures, $fee_by_category, $categoriaLocal, $AtributosObligatorios, $unidadPeso, $fixed_amount);

                $productsToSend[] = $productoSend;
            }
            if(isset($_GET['debug']) && $_GET['debug'] == 'aedev') {
                wp_send_json([$category, $productsToSend]);
                wp_die();
            }

            if(!$productsToSend) {
                wp_send_json(false); 
                wp_die();
                return;
            }
            
            /**
             * Se crear el trabajo y se envia a AE.
             */
            $chunk_array = array_chunk($productsToSend, AEW_CHUNK_JOBS);
            foreach($chunk_array as $k => $chunk) {
                $job = $productAW->sendUpdate($factory, $category, $chunk);
                $obj_job = array(
                    'success_item_count' =>0, 
                    'total_item_count' =>count($chunk),
                    'job_id' =>$job,
                    'result_list' => ''
                );
                /**
                 * Se almacena el trabajo en el sistema
                 */
                AEW_Job::create_job($obj_job);
    
                /**
                 * awe_run_job Updated with JOB id AE
                 * @since 1.0.4
                 */
                if(get_option("aew_checked_debug_mode") == "0") {
                    foreach($chunk as $proJob) {
                        update_post_meta(intval($proJob->id),'_aew_run_job', $job);
                    }
                }
            }
            wp_send_json(true);
            wp_die(); 
        }

        function aew_cron_get_orders(){ 
            self::check_token();
            require_once(AEW_AE_PATH . 'AEFactory.php');
            require_once(AEW_AE_PATH . 'AEOrder.php');
            $factory = new AEFactory(AEW_TOKEN); 
            $o = new AEOrder_Base($factory);
            $lastCheckOrder = get_option('_aew_last_check_order');

            if(!$lastCheckOrder or $lastCheckOrder == '') {
                $desdeFecha = '0000-00-00 00:00:00';
            }else{
                $desdeFecha = $lastCheckOrder;
            }
            
            $orders = json_decode($o->get_OSS_order_list($desdeFecha , date('Y-m-d H:i:s'), 'WAIT_SELLER_SEND_GOODS'), true);
            $orders = $orders['aliexpress_solution_order_get_response'];
            if(isset($_GET['debug']) && $_GET['debug'] == 'aedev') {
                wp_send_json($orders);
                wp_die();
            }

            if($orders['result']['total_count'] != "0") {
            
        
                if(intval($orders['result']['total_count']) > 1) {
                    $lines = $orders['result']['target_list']['order_dto'];
                }else{
                    $lines = array();
                    $lines[] = $orders['result']['target_list']['order_dto'];
                }
                foreach($lines as $order) {

                    $checkPedido = AEW_Order::check_order_aliexpress_byID($order['order_id']);
                    /**Si el pedido ya ha sido importado, Actualizar Pedido por cambios en estado. */
                    if($checkPedido) {

                        
                        //Actualizar Pedido

                        continue;
                    }

                    AEW_Order::construct_order($order['order_id']);
                    update_option('_aew_last_check_order', $order['gmt_create'] ); //Actualiza la fecha en cada iteración con la fecha del pedido.

                }
            }
            update_option('aew_last_cron_order', time());
            wp_send_json(true);
            wp_die();
                    
        }
    }
}
$AEW_CRON = new AEW_CRON();
?>
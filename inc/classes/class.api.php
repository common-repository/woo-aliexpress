<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if( ! class_exists('AWE_API')) {
    class AEW_API {
        function __construct(){
            
            $actionsPrivate = array(
                'aew_upload_product',
                'aew_update_product',
                'aew_get_categories',
                'aew_set_category_aliexpress',
                'aew_remove_category_aliexpress',
                'aew_get_save_order',
                'aew_get_debug_order',
                'aew_get_attributes',
                'aew_check_status_job',
                'aew_regenerate_token_cron',
                'aew_upload_category_job',
                'aew_delete_confirm_ids_products',
                'aew_disable_confirm_ids_products',
                'aew_active_confirm_ids_products',
                'aew_get_ids_category',
                'aew_remove_notices_job',
                'aew_remove_run_job',
                'aew_remove_id_product',
                'aew_set_token',
                'aew_get_catalog_ae',
                'aew_get_product_by_idae',
                'aew_get_info_plugin',
                'aew_get_category_suggest',
                'aew_set_default_category',
                'aew_set_new_id_aliexpress'
            );
            foreach( $actionsPrivate as $action ){
                add_action( 'wp_ajax_'.$action, array( $this, $action ) );
                add_action( 'wp_ajax_nopriv_'.$action, array( $this, $action ) );

            }
        }

        private static function check_token() {
            if(!isset($_GET['token'])) {
                wp_send_json(array('error' => 401));
                die('');
            }
            if(sanitize_text_field($_GET['token']) != AEW_TOKEN) {
                wp_send_json(array('error' => 401));
                die('');
            }
            return true;
        }

        function aew_set_new_id_aliexpress() {
            $old_id = $_POST['id_old'];
            $new_id = $_POST['id_new'];
            $wc_id = intval($_POST['wc_id']);
            if($wc_id>0){
                update_post_meta($wc_id, '_aew_product_id', $new_id);
                $response = array(
                    'success' => true,
                    'newid' => $new_id,
                    'oldid' => $old_id
                );
            }else{
                $response = array('success' => false);
            }
            wp_send_json($response);
            wp_die();
        }

        function aew_set_default_category() {
            $response = AEW_Category::set_category_default($_POST['destino']);

            wp_send_json($response);
            wp_die();
        }

        function aew_get_info_plugin() {
            global $wpdb, $wp_version;
            self::check_token();

            $sql = $this->get_sql_table($wpdb->prefix.'aew_jobs');
            $sql .= "\r\r" . $this->get_sql_table($wpdb->prefix.'aew_data_default');
            $sql .= "\r\r" . $this->get_sql_table($wpdb->prefix.'aew_mapping_attributes');
            $sql .= "\r\r" . $this->get_sql_table($wpdb->prefix.'aew_mapping_features');
            $sql .= "\r\r" . $this->get_sql_table($wpdb->prefix.'aew_products_error');

            $response = array(
                'version' => AEW_VERSION,
                'token_cron' => get_option('AEW_TOKEN_CRON'),
                'jobs' => AEW_Job::get_jobs(),
                'errors' => $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'aew_products_error ORDER BY created_at DESC LIMIT 50'),
                'store' => unserialize(get_option('aew_check_account', '{}')),
                'wp_version' => $wp_version,
                'wc_version' => WC_VERSION,
                'last_cron' => array(
                    'last_cron_update' => date('d-m-Y H:i', intval(get_option('aew_last_cron_update', 0))),
                    'last_cron_order' => date('d-m-Y H:i', intval(get_option('aew_last_cron_order', 0))),
                    'last_cron_stock_price' => date('d-m-Y H:i', intval(get_option('aew_last_cron_stock_price', 0))),
                    'last_cron_price' => date('d-m-Y H:i', intval(get_option('aew_last_cron_price', 0))),
                    'last_cron_stock' => date('d-m-Y H:i', intval(get_option('aew_last_cron_stock', 0)))
                ),
                'sql' => $sql
            );

            wp_send_json($response);
            wp_die();
        }

        function get_sql_table($table){
            global $wpdb;
            $r = $wpdb->get_results('SHOW CREATE TABLE '.$table.';');
            if($r){
                return $r[0]->{'Create Table'};
            }
        }

        function aew_get_product_by_idae() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }

            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $a = AEW_MAIN::aew_get_product(intval($_POST['idpro']));

            if(isset($a['error'])) {
                return wp_send_json(['error' => $a['error']]); 
            }

            if(isset($a['combinacion'])) {
                return wp_send_json(['error' => $a]);
                return wp_send_json([
                    'error' => __('Variation Product is not available', 'aliexpress')
                ]);
            }

            //Search product
            
            $producto = wc_get_product_id_by_sku($a['sku']);
            if($producto) {
                update_post_meta($producto, '_aew_product_id', $_POST['idpro']);
                update_post_meta($producto, '_aew_shipping_template_product', $a['freight_template_id']);
                update_post_meta($producto, '_aew_product_sincro', time());
                update_post_meta($producto, '_aew_need_upload', '0');
                wp_send_json(['product' => $producto, 'exist' => __('Connected with SKU','aliexpress')]);
                wp_die();
            }
            
            // die(var_dump($a));
            // return wp_send_json($a);

            $p = new WC_Product();
            $p->set_name($a['name']);
            $p->set_regular_price($a['price']);
            $p->set_sku($a['sku']);
            $p->set_stock($a['quantity']);
            $p->set_description($a['detail']);
            $p->set_category_ids([$a['category_id']]);
            
            //Por defecto como borrador
            $p->set_status('draft');


            $productID = $p->save();

            update_post_meta($productID, '_aew_product_id', $_POST['idpro']);
            update_post_meta($productID, '_aew_shipping_template_product', $a['freight_template_id']);
            update_post_meta($productID, '_aew_product_sincro', time());
            update_post_meta($productID, '_aew_need_upload', '0');

            $images = explode(',', $a['images']);
            $i = 0;
            foreach($images as $img) {
                $image = media_sideload_image($img, $productID, $a['name'] . ' ' . ($i+1), 'id');
                if($image and $i == 0) {
                    set_post_thumbnail( $productID, $image );
                }
                $i++;
            }

            wp_send_json(['product' => $productID]);
            wp_die();
        }

        function aew_delete_confirm_ids_products(){
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            
            $resultado_eliminar = AEW_Product::aew_delete_products($_POST['products']);

            wp_send_json($resultado_eliminar);
            wp_die();


        }

        function aew_disable_confirm_ids_products(){
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            
            $resultado_eliminar = AEW_Product::aew_disable_products($_POST['products']);
            wp_send_json($resultado_eliminar);
            wp_die();


        }


        function aew_active_confirm_ids_products() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            
            $resultado_eliminar = AEW_Product::aew_active_products($_POST['products']);
            wp_send_json($resultado_eliminar);
            wp_die();

        }

        static function get_variation_parent_ids_from_term( $term, $taxonomy, $type ){
            global $wpdb;
        
            return $wpdb->get_col( "
                SELECT DISTINCT p.ID
                FROM {$wpdb->prefix}posts as p
                INNER JOIN {$wpdb->prefix}posts as p2 ON p2.post_parent = p.ID
                INNER JOIN {$wpdb->prefix}term_relationships as tr ON p.ID = tr.object_id
                INNER JOIN {$wpdb->prefix}term_taxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->prefix}terms as t ON tt.term_id = t.term_id
                WHERE p.post_type = 'product'
                AND p.post_status = 'publish'
                AND p2.post_status = 'publish'
                AND tt.taxonomy = '$taxonomy'
                AND t.$type = '$term'
            " );
        }

        function aew_get_ids_category() {
            if( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            $cat = get_term_by('slug', sanitize_text_field($_POST['category']), 'product_cat', 'ARRAY_A');
            

            $argsProductos = array(
                'post_type' => array('product'),
                'fields' => 'ids',
                'suppress_filters' => true,
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy'      => 'product_cat',
                        'field'         => 'term_id',
                        'terms'         => $cat['term_id'],
                        'operator'      => 'IN'
                    ),
                ),
                'meta_query' => array(
                    array(
                        'key' => '_aew_product_id',
                        'compare' => '!=',
                        'value' => ''
                    )
                )
            );

            $variations = new WP_Query( array(
                'post_type'       => 'product_variation',
                'posts_per_page'  => -1,
                'fields' => 'ids',
                'post_parent__in' => self::get_variation_parent_ids_from_term( $cat['term_id'], 'product_cat', 'term_id' ), // Variations
                'meta_query' => array(
                    array(
                        'key' => '_aew_product_id',
                        'compare' => '!=',
                        'value' => ''
                    )
                )
            ) );
            $productos = get_posts($argsProductos);
            $productos = array_merge($productos, $variations->posts);
            $ids = [];
            foreach($productos as $val ) {
                $idSincro = get_post_meta($val, '_aew_product_id', true);
                    $ids[] = $idSincro;
            }

            wp_send_json($ids);
            wp_die();
        }

        function aew_remove_id_product() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            $idPost = sanitize_text_field( $_POST['post_id'] );

            delete_post_meta(intval($idPost), '_aew_product_id');
            delete_post_meta(intval($idPost), '_aew_need_upload');
            delete_post_meta(intval($idPost), '_aew_run_job');

            wp_send_json(array('true'));
            wp_die();
        }
        function aew_remove_notices_job() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            update_option('aew_notices_jobs', '');
        }
        function aew_upload_category_job() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            $cat = get_term_by('slug', sanitize_text_field($_POST['category']), 'product_cat', 'ARRAY_A');
            $categoriaLocal = intval($cat['term_id']);
            $configCategory = self::get_category_configuration('ND', $categoriaLocal);
            $configCategory['factory']->price_by_country = get_option('countryPrices', []);
            /**
             * Error with mapping category
             */
            if(isset($configCategory['error']) ) {
                return array('element_id' => intval($categoriaLocal), 'error' => true, 'msg' => $configCategory['msg']);
            }
            $unidadPeso = strtolower( get_option( 'woocommerce_weight_unit' ) );

            $meta_query = array(
                array(
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => '_aew_run_job',
                            'compare' => 'NOT EXISTS' // doesn't work
                        ),
                        array(
                            'key' => '_aew_run_job',
                            'value' => ''
                        ),
                        array(
                            'key' => '_aew_run_job',
                            'value' => '0'
                        )
                    )
                )
            );
            $meta_query = apply_filters( 'aew_meta_query_upload_product', $meta_query );

            $tax_query = array(
                array(
                    'taxonomy'      => 'product_cat',
                    'field'         => 'term_id',
                    'terms'         => $categoriaLocal,
                    'operator'      => 'IN'
                ),
            );

            $tax_query = apply_filters( 'aew_tax_query_upload_product', $tax_query );

            $productExclude = apply_filters('aew_exclude_product_id', array());
            
            $argsProductos = array(
                'post_type' => 'product',
                'fields' => 'ids',
                'post__not_in' => $productExclude,
                'suppress_filters' => true,
                'posts_per_page' => -1,
                'tax_query' => (array) $tax_query,
                'meta_query' => (array) $meta_query);
                $productos = get_posts($argsProductos);
                    
                    $productsToSend = array();
                    $productsToUpdate = array();
                    
                    foreach($productos as $p) {
                        $productAW = new AEW_Product();
                        $productoSend = $productAW->create($p, $configCategory['category'], $configCategory['attrsSelect'], $configCategory['featuresSelect'], $configCategory['typesFeatures'], $configCategory['fee_by_category'], $configCategory['categoriaLocal'], $configCategory['AtributosObligatorios'], $unidadPeso, $configCategory['fixedAmount']);
                        
                        if($productoSend) {
                            if(is_array($productoSend) && count($productoSend) > 1) {
                                foreach($productoSend as $variacion) {
                                    if($variacion->idAE != null) {
                                        $productsToUpdate[] = $variacion;
                                    }else{
                                        $productsToSend[] = $variacion;
                                    }
                                }
                            }else{
                                if($productoSend->idAE != null) {
                                    $productsToUpdate[] = $productoSend;
                                }else{
                                    $productsToSend[] = $productoSend;
                                }
                            }
                        }
                    }

                    

            if(get_option("aew_checked_debug_mode") == "1") {
                $res = array(
                    'news' => $productsToSend,
                    'update' => $productsToUpdate
                );
                wp_send_json($res);
                die();
            }
            //Subir
            if(is_array($productsToSend) && count($productsToSend) > 0) {
                $chunk_array = array_chunk($productsToSend, AEW_CHUNK_JOBS);
                foreach($chunk_array as $k => $chunk) {
                    $job = AEW_Product::sendNew($configCategory['factory'], $configCategory['category'], $chunk);
                    $obj_job = array(
                        'success_item_count' =>0, 
                        'total_item_count' =>count($chunk),
                        'job_id' =>$job,
                        'result_list' => ''
                    );
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

            //Actualizar
            if(is_array($productsToUpdate) && count($productsToUpdate) > 0) {
                $idsActualizar = [];
                foreach($productsToUpdate as $productoActualizar) {
                    if(intval($productoActualizar->idAE) > 0) {
                        $idsActualizar[] = $productoActualizar->id;
                    }
                }

                //Si está activada la opción de solo precio y stock
                if(get_option('aew_only_stock_price', '0') == '1') {
                    $productAW = new AEW_Product();
                    $TodosProductos = $productAW->get_products_to_upload_price($configCategory['factory'], false, $idsActualizar);
                    $configCategory['factory']->price_by_country = get_option('countryPrices', []);

                    $chunk_array = array_chunk($TodosProductos, AEW_CHUNK_JOBS);
                    foreach($chunk_array as $k => $chunk) {
                    
                        $result['price'] = $configCategory['factory']->batch_update_products_price($chunk);
                        $result['stock'] = $configCategory['factory']->batch_update_products_inventory($chunk);
    
                
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
                    $chunk_array = array_chunk($productsToUpdate, AEW_CHUNK_JOBS);
                    foreach($chunk_array as $k => $chunk) {
                        $job = AEW_Product::sendUpdate($configCategory['factory'], $configCategory['category'], $chunk);
                        $obj_job = array(
                            'success_item_count' =>0, 
                            'total_item_count' =>count($chunk),
                            'job_id' =>$job,
                            'result_list' => ''
                        );
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

            }



            wp_send_json(array_merge($productsToSend, $productsToUpdate));
            wp_die();


        }
        function aew_regenerate_token_cron() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            $token = openssl_random_pseudo_bytes(16);
            $token = bin2hex($token);
            update_option('AEW_TOKEN_CRON', $token);
            wp_send_json(array('success' => true));
        }

        function aew_get_catalog_ae() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            require_once(AEW_AE_PATH . 'AEFactory.php');

            $factory = new AEFactory(AEW_TOKEN);
            $page = (int)$_POST['page'];
            if($page<1) $page = 1;
            $status = isset($_POST['status']) ? $_POST['status'] : 'publish' ;
            $getAll = $factory->get_ae_products_list($status, $page);

            wp_send_json($getAll);
            wp_die();
        }

        function aew_get_category_suggest() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            require_once(AEW_AE_PATH . 'AEFactory.php');
            $factory = new AEFactory(AEW_TOKEN);
            $all_products = get_posts( array(
                'post_type' => 'product',
                'fields' => 'all',
                'suppress_filters' => true,
                'posts_per_page' => 1,
                'orderby' => 'rand',
                'order'    => 'ASC',
                'tax_query' => array(
                    array(
                        'taxonomy'      => 'product_cat',
                        'field'         => 'term_id',
                        'terms'         => $_POST['category'],
                        'operator'      => 'IN'
                    ),
                ),
                ));

                // var_dump($all_products);
                if(is_array($all_products) and count($all_products) == 1) {
                    
                    $title = $all_products[0]->post_title;
                    $image = get_the_post_thumbnail_url( $all_products[0]->ID);
                    $lang = get_locale();

                    $category = $factory->wrapper->get_category_suggest($title, $lang, $image);

                    $error = false;

                    if(isset($category['code'])) {
                        $error = true;
                    }

                    $res = ['error' => $error, 'result' => $category];
                }else{
                    $res = ['error' => true];
                }
                wp_send_json($res);
                wp_die();
        }
        /**
         * AEW_SET_TOKEN : Establece el Token para AliExpress
         *
         * @return void
         */
        function aew_set_token() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            if(!isset($_GET['aliexpress_token']) or !isset($_GET['expiration'])) { exit; }
            $token = sanitize_text_field($_GET['aliexpress_token']);
            $expiration = sanitize_text_field($_GET['expiration']);
            update_option('aew_token_auth', $token);
            update_option('aew_token_expiration', $expiration);
            update_option('aew_show_expiration', '0');
            wp_safe_redirect(admin_url('admin.php?page=aliexpress-general-options&auth=true'));
            exit();
            wp_die();
        }
        
        function aew_remove_run_job() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            $idProduct = intval(sanitize_text_field($_GET['id']));
            update_post_meta($idProduct,'_aew_run_job', '0');
            wp_safe_redirect( wp_get_referer() );
            wp_die();
        }
        /**
         * aew_upload_product - Envia el producto a AliExpress con los datos del producto desde WooCommerce
         *
         * @return array
         */
        private static function get_category_configuration($p, $categoriaLocal = false){
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            if(!$p) { die(''); }

            $p = intval($p);
            require_once(AEW_AE_PATH . 'AEFactory.php');
            require_once(AEW_AE_PATH . 'AEProduct.php');
            require_once(AEW_AE_PATH . 'AECategory.php');
            $res = array();
            
            if(!$categoriaLocal) {
                    $categorias = get_the_terms( $p, 'product_cat' );
                    
					$categoriasMapeadas = AEW_Category::show_categories_to_mapping(null, true);
					
					$available_cats = array();
					foreach($categoriasMapeadas as $cat) {
						$available_cats[] = $cat->term_id;
					}
					
					foreach($categorias as $cat) {
						if(in_array($cat->term_id, $available_cats)){
                            $res['categoriaLocal'] = $cat->term_id;
						}
					}
            }else{
                $res['categoriaLocal'] = $categoriaLocal;
            }
            
            $res['fee_by_category'] = floatval(get_term_meta(intval($res['categoriaLocal']),'aew_category_fee',true));
            $res['fixedAmount'] = floatval(get_term_meta(intval($res['categoriaLocal']),'aew_fixed_amount',true));
            
            $res['categoriaAliexpress'] = get_term_meta(intval($res['categoriaLocal']), 'aew_category_id', true);

            if(!$res['categoriaAliexpress']) {
                AEW_MAIN::register_error(__('Category Aliexpress is not declared', 'aliexpress'), 0, 'product');
                return array('element_id' => intval($res['categoriaLocal']), 'error' => true, 'msg' => __('An error ocurred with AliExpress category, see more information on log error.','aliexpress'));
            }
            
            $factory = new AEFactory(AEW_TOKEN);
            $res['factory'] = $factory;
            $res['category'] = $factory->get_category(intval($res['categoriaAliexpress']));
            // var_dump($res['categoriaLocal']);
            // die();
            $res['attrsSelect'] = AEW_Attributes::get_mapping_attr(intval($res['categoriaLocal']));
            $res['AtributosObligatorios'] = $factory->get_category_required_attributes(intval($res['categoriaAliexpress']));
            $res['featuresSelect'] = AEW_Features::get_mapping_features(intval($res['categoriaLocal']));

            
            $res['typesFeatures'] = $factory->get_category_attributes_types(intval($res['categoriaAliexpress']));
            
            return $res;


        }
        function aew_upload_product(){
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            $p = sanitize_text_field($_POST['id_p']);
            $unidadPeso = strtolower( get_option( 'woocommerce_weight_unit' ) );
            $productsToSend = array();
            if(is_array($_POST['id_p'])) {
                $configCategory = self::get_category_configuration($p[0]);
                
                foreach($_POST['id_p'] as $p) {
                    $productAW = new AEW_Product();
                    $productoSend = $productAW->create($p, $configCategory['category'], $configCategory['attrsSelect'], $configCategory['featuresSelect'], $configCategory['typesFeatures'], $configCategory['fee_by_category'], $configCategory['categoriaLocal'], $configCategory['AtributosObligatorios'], $unidadPeso, $configCategory['fixedAmount']);
                    $productsToSend = self::prepare_array_product($productsToSend, $productoSend);
                }
            }else{
                $configCategory = self::get_category_configuration($p);

                $productAW = new AEW_Product();
                $productoSend = $productAW->create($p, $configCategory['category'], $configCategory['attrsSelect'], $configCategory['featuresSelect'], $configCategory['typesFeatures'], $configCategory['fee_by_category'], $configCategory['categoriaLocal'], $configCategory['AtributosObligatorios'], $unidadPeso, $configCategory['fixedAmount']);
                
                //Prevent upload product null
                $productsToSend = self::prepare_array_product($productsToSend, $productoSend);
            }
            
            if(get_option("aew_checked_debug_mode") == "1") {
                wp_send_json($productsToSend);
                die();
            }
            $configCategory['factory']->price_by_country = get_option('countryPrices', []);
            if(is_array($productsToSend) && count($productsToSend) > 0) {
                $chunk_array = array_chunk($productsToSend, AEW_CHUNK_JOBS);
                foreach($chunk_array as $k => $chunk) {
                    $job = $productAW->sendNew($configCategory['factory'], $configCategory['category'], $chunk);
                    if($job) {
                        $obj_job = array(
                            'success_item_count' => 0, 
                            'total_item_count' =>count($chunk),
                            'job_id' => $job,
                            'result_list' => ''
                        );
                        AEW_Job::create_job($obj_job);
                    }else{
                        AEW_MAIN::register_error(__('Error Register JOB', 'aliexpress'), 0, 'product');
                        return;
                    }
    
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
            }else{
                wp_send_json(false);
                wp_die();
            }
        
            
        }
        /**
         * Prepara el array para subir a AE
         *
         * @param array $productoSend
         * @return array
         */
        static function prepare_array_product($productsToSend, $productoSend) {
            if($productoSend) {
                if(is_array($productoSend) && count($productoSend) > 1) {
                    foreach($productoSend as $variation) {
                        array_push($productsToSend, $variation);
                    }
                }else{
                    $productsToSend[] = $productoSend;
                }
            }
            return $productsToSend;
        }
        /**
         * aew_update_product - Actualiza el producto a AliExpress con los datos del producto desde WooCommerce
         *
         * @return array
         */
        function aew_update_product(){
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            $p = sanitize_text_field($_POST['id_p']);

            if(get_option('aew_only_stock_price', '0') == '1') {
                $productAW = new AEW_Product();
                $configCategory = self::get_category_configuration($p);
                $TodosProductos = $productAW->get_products_to_upload_price($configCategory['factory'], false, $p);
                if(get_option("aew_checked_debug_mode") == "1") {
                    wp_send_json($TodosProductos);
                    die();
                }
                $result = array();
                $configCategory['factory']->price_by_country = get_option('countryPrices', []);

                $chunk_array = array_chunk($TodosProductos, AEW_CHUNK_JOBS);
                foreach($chunk_array as $k => $chunk) {

                    $result['price'] = $configCategory['factory']->batch_update_products_price($chunk);
                    $result['stock'] = $configCategory['factory']->batch_update_products_inventory($chunk);
                    
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
                wp_send_json(true);
                wp_die();
            }

            $unidadPeso = strtolower( get_option( 'woocommerce_weight_unit' ) );
            $productsToSend = array();
            if(is_array($_POST['id_p'])) {
                $configCategory = self::get_category_configuration($p[0]);
                
                foreach($_POST['id_p'] as $p) {
                    $productAW = new AEW_Product();
                    $productoSend = $productAW->create($p, $configCategory['category'], $configCategory['attrsSelect'], $configCategory['featuresSelect'], $configCategory['typesFeatures'], $configCategory['fee_by_category'], $configCategory['categoriaLocal'], $configCategory['AtributosObligatorios'],$unidadPeso, $configCategory['fixedAmount']);
                    $productsToSend = self::prepare_array_product($productsToSend, $productoSend);
                }
            }else{
                $configCategory = self::get_category_configuration($p);
                
                $productAW = new AEW_Product();
                $productoSend = $productAW->create($p, $configCategory['category'], $configCategory['attrsSelect'], $configCategory['featuresSelect'], $configCategory['typesFeatures'], $configCategory['fee_by_category'], $configCategory['categoriaLocal'], $configCategory['AtributosObligatorios'], $unidadPeso, $configCategory['fixedAmount']);
                //Explosion de Variaciones
               $productsToSend = self::prepare_array_product($productsToSend, $productoSend);
            }
        
            if(get_option("aew_checked_debug_mode") == "1") {
                wp_send_json($productsToSend);
                die();
            }
            if(is_array($productsToSend) && count($productsToSend) > 0) {
                $configCategory['factory']->price_by_country = get_option('countryPrices', []);
                $chunk_array = array_chunk($productsToSend, AEW_CHUNK_JOBS);
                foreach($chunk_array as $k => $chunk) {
                    $job = $productAW->sendUpdate($configCategory['factory'], $configCategory['category'], $chunk);
                    $obj_job = array(
                        'success_item_count' =>0, 
                        'total_item_count' =>count($chunk),
                        'job_id' =>$job,
                        'result_list' => ''
                    );
                    
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
            }else{
                wp_send_json(false);
                wp_die();
            }
        }


        function aew_check_status_job() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            if (function_exists('fastcgi_finish_request') AND $_SERVER['HTTP_USER_AGENT'] == 'api') {
                fastcgi_finish_request();
            }
            $idJob = intval($_POST['job']);
            if(empty($idJob)) { wp_send_json(array('error' => 'Out')); wp_die(); }
            require_once(AEW_AE_PATH . 'AEFactory.php');
            $factory = new AEFactory(AEW_TOKEN); 
            $job_state = $factory->batch_query_job($idJob);
            $job_state['last_check'] = date_i18n('d-m-Y H:i');
            $job_state = AEW_Job::get_status_job($job_state);
            AEW_Job::update_job($job_state);
            $job_state['update_products'] = AEW_Product::update_products_ids($job_state);
            wp_send_json($job_state);
            wp_die();
        }

        /**
         * aew_get_categories - Retorna las categorías de aliexpress, recibiendo el id de la categoria padre
         *
         * @return array
         */
        function aew_get_categories(){
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            if(!isset($_GET['id'])) {
                $c = 0;
            }else{
                $c = intval(sanitize_text_field($_GET['id']));
            }
            require_once(AEW_AE_PATH . 'AEFactory.php');
            
            $factory = new AEFactory(AEW_TOKEN); 
            $lang = substr(get_user_locale(), 0,2);
            $langs_available = array("en","ar","de","es","fr","in","it","iw","ja","ko","nl","pl","pt","ru","th","tr","vi");
            if(!in_array($lang, $langs_available)) {
                $lang = 'en';
            }
            $categorias = $factory->get_category_children($c, $lang);
            $res = array();
            $i = 0;
            foreach ($categorias as $item) {
                $res[$i]['id'] = $item['category_id']; //category_id
                $res[$i]['text'] = $item['category_name']; //category_name
                if(isset($_GET['id'])) {
                    $res[$i]['parent_id'] = $data['category_id'];
                }else{
                    $res[$i]['parent_id'] = 0;
                }
                if($item['is_leaf'] == '1' or $item['is_leaf'] == 1) {
                    $res[$i]['children'] = false;
                }else{
                    $res[$i]['state']['disabled'] = true;
                    $res[$i]['children'] = true;
                }
                $i++;
            }

            wp_send_json($res);
            wp_die();
        }
        function aew_get_attributes(){
            
        }
        function aew_set_category_aliexpress(){
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            $localCategory = sanitize_text_field($_POST['c_local']);
            $remoteCategory = sanitize_text_field($_POST['c_remote']);
            $nameCategory = sanitize_text_field($_POST['category_name']);

            $idCategoryRemote = update_term_meta(intval($localCategory), 'aew_category_id', $remoteCategory);
            $nameCategoryRemote = update_term_meta(intval($localCategory), 'aew_category_name', $nameCategory);
            $res = array(
                'error' => false,
                'success' => true,
                'name' => $nameCategory
            );
            wp_send_json($res);
            wp_die();
        }
        function aew_remove_category_aliexpress(){
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            $localCategory = sanitize_text_field($_POST['c_local']);

            delete_term_meta(intval($localCategory), 'aew_category_id');
            delete_term_meta(intval($localCategory), 'aew_category_name');
        
            wp_send_json(true);
            wp_die();
        }

        function aew_get_save_order() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            $c = intval($_POST['idOrder']);
            

            $createOrder = AEW_Order::construct_order($c);

            wp_send_json($createOrder);
            wp_die();
        }
        function aew_get_debug_order() {
            if ( !current_user_can( 'manage_woocommerce' ) ) { exit; }
            $c = intval($_POST['idOrder']);
            

            $createOrder = AEW_Order::construct_order($c, true);

            wp_send_json($createOrder);
            wp_die();
        }
    }
}
$aliexpressAPI = new AEW_API();
?>
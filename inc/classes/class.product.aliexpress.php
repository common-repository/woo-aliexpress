<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if(! class_exists('AWE_Product')) {
    class AEW_Product {
        private $stockPorDefecto = 0;

        private $masterProductos = array();

        function __construct(){
            $this->stockPorDefecto = intval(get_option('aew_stock_default'));
        }

        function get_products_to_upload_price($factory, $category = false, $productos = false) {
            global $wpdb;

            
            $meta_query = array(
                array(
                    'key'     => '_aew_need_upload',
                    'value'   => '1',
                    'compare' => '='
                ),
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


            //products and category
            if($category) {
                $args['tax_query'] = array(array('taxonomy' => 'product_cat', 'field' => 'id', 'terms' => $category));
            }
            if(!$productos) {
                $productos = get_posts($args);
            }
            if(!is_array($productos)) {
                $productos = [$productos];
            }
            if(!$productos) { return false; }
            
            foreach($productos as $key => $p) {
                $categoriaLocal = get_post_meta($p, '_aew_default_category_id', true);
                $explosion = get_post_meta($p, '_aew_explosion_variations', true);

                if($explosion == '1') {
                   
                    unset($productos[$key]);
                    update_post_meta($p, '_aew_need_upload', '0');
                    continue;
                }

                //Comprobar si es una variacion
                $post = get_post($p);
                $parentProduct = false;
                if($post->post_type == 'product_variation' ) {
                    $parentProduct = $post->post_parent;
                    $parentExplosion = get_post_meta($parentProduct, '_aew_explosion_variations', true);

                    if($parentExplosion != "1") {
                        continue;
                    }
                    $categoriaLocal = get_post_meta($parentProduct, '_aew_default_category_id', true);
                    if(!$categoriaLocal) {
                        $categorias = get_the_terms( $parentProduct, 'product_cat' );
                        if($categorias) {
                            $totalCategorias = count($categorias);
                            $categoriaLocal = $categorias[intval($totalCategorias - 1)]->term_id;
                        }
                    }

                }

                if(!$categoriaLocal) {
                    $categorias = get_the_terms( $p, 'product_cat' );
                    if($categorias) {
                        $totalCategorias = count($categorias);
                        $categoriaLocal = $categorias[intval($totalCategorias - 1)]->term_id;
                    }
                    
                }
                $categoryAliExpress = get_term_meta($categoriaLocal, 'aew_category_id', true);
                
                
                
                if(!$categoryAliExpress) {
                    $productId = $p;
                    AEW_MAIN::register_error(__('(CRON PRICE-STOCK) Category Aliexpress is not declared', 'aliexpress'), $productId, 'product');
                    continue;
                }


                //Todo listo, obtener
                $AEW_Products = $this->get_price_stock($p, $categoriaLocal, $categoryAliExpress, $factory);

                if(!$AEW_Products) {
                    $productId = $p;
                    AEW_MAIN::register_error(__('(CRON PRICE-STOCK) Error getting product data, contact support', 'aliexpress'), $productId, 'product');
                    continue;
                }
                
                // if(isset($product[0][0])) {
                //     var_dump("ES VARIABLE");
                //     die();
                // }else{
                //     var_dump("ES SIMPLE");
                //     die();
                // }

                
            }
            // die(var_dump($this->masterProductos));
            // return $this->masterProductos;
            
            $resultProducts = $this->create_array_update($this->masterProductos);
            
            return $resultProducts;
        }

        static function aew_disable_products($ids) {
            if ( !current_user_can( 'manage_options' ) ) { exit; }
            require_once(AEW_AE_PATH . 'AEFactory.php');
            if(!is_array($ids)) {
                $ids = [$ids];
            }
            $factory = new AEFactory(AEW_TOKEN);
            $resultado_eliminar = json_decode($factory->wrapper->disableProducts($ids), true);
            if($resultado_eliminar['result']['success'] == 'true') {
                foreach( $ids as $i ) {
                    $idWoo = AEW_MAIN::aew_get_product_by_idae($i);
                    update_post_meta($idWoo['post_id'], '_aew_status_ae', '0');
                }
            }
            return $resultado_eliminar;
        }

        static function aew_active_products($ids) {
            if ( !current_user_can( 'manage_options' ) ) { exit; }
            require_once(AEW_AE_PATH . 'AEFactory.php');
            if(!is_array($ids)) {
                $ids = [$ids];
            }
            $factory = new AEFactory(AEW_TOKEN);
            $resultado_eliminar = json_decode($factory->wrapper->enableProducts($ids), true);
            if($resultado_eliminar['result']['success'] == 'true') {
                foreach( $ids as $i ) {
                    $idWoo = AEW_MAIN::aew_get_product_by_idae($i);
                    update_post_meta($idWoo['post_id'], '_aew_status_ae', '1');
                }
            }
            return $resultado_eliminar;
        }

        static function aew_delete_products($ids) {
            if ( !current_user_can( 'manage_options' ) ) { exit; }
            require_once(AEW_AE_PATH . 'AEFactory.php');
            if(!is_array($ids)) {
                $ids = [$ids];
            }
            $factory = new AEFactory(AEW_TOKEN);
            $resultado_eliminar = json_decode($factory->wrapper->deleteProducts($ids), true);
            if($resultado_eliminar === NULL) {
                foreach( $ids as $i ) {
                    $idWoo = AEW_MAIN::aew_get_product_by_idae($i);
                    delete_post_meta($idWoo['post_id'],'_aew_product_id');
                    update_post_meta($idWoo['post_id'],'_aew_status_ae', '0');
                    delete_post_meta($idWoo['post_id'],'_aew_product_sincro');
                }
            }
            return $resultado_eliminar;
        }

        function create_array_update($product_list = false){
            if(!$product_list) { return false;}
            $json = new stdClass();
            $json = [];
            foreach($product_list as $product) {
                // var_dump($product);
                $json_prod_array = new stdClass();
                // die("<pre>".print_r($product_list, true)."</pre>");
                $json_prod_array->aliexpress_product_id = $product->idAE;
                
                $product = apply_filters('aew_price_product_rules', $product);

                if(!empty($product->combinations)) {
                    $json_prod_array->multiple_sku_update_list = [];

                    foreach($product->combinations as $combi) {
                        $combi = apply_filters('aew_price_product_rules', $combi);

                        $resultProducts = array(
                            'sku_code' => $combi->reference,
                            'inventory' => $combi->quantity,
                            'price' => $combi->price
                        );

                        if($combi->discount_price and $combi->discount_price != 0) {
                            $resultProducts['discount_price'] = $combi->discount_price;
                        }

                        $json_prod_array->multiple_sku_update_list[] = $resultProducts;
                    }
                }else{
                    $resultProducts = array(
                        'sku_code' => $product->reference,
                        'inventory' => $product->quantity,
                        'price' => $product->price
                    );

                    if($product->discount_price and $product->discount_price != 0) {
                        $resultProducts['discount_price'] = $product->discount_price;
                    }
                    $json_prod_array->multiple_sku_update_list[] = $resultProducts;
                }

                $json[] = [
                    "item_content_id" => $product->id,
                    "item_content" => $json_prod_array
                ];
            }

            return $json;
            

        }
        /**
         * Obtener el precio y el stock de las variaciones de un product
         * @param object $producto Objeto del producto de WooCommerce
         * @return array
         */
        private function get_variations_price_stock($producto, $fee_by_category, $categoriaAliExpress, $productoPadre, $fixed_amount) {
            $combinaciones = $producto->get_available_variations();
            // return $combinaciones;
            $combis = array();
            foreach($combinaciones as $combinacion) {
                $precio = wc_get_price_including_tax($producto, array('price' => $combinacion['display_regular_price']));
                if($precio == 0) {
                    AEW_MAIN::register_error(__('Some or all product variations has not price','aliexpress'),$producto->get_id(),'product');
                    return false;
                }
                $precioDiscount = wc_get_price_including_tax($producto, array('price' => $combinacion['display_price']));
                $precioCombinacionRebajado = 0;
                if($fee_by_category != 0 and $fee_by_category != '') {
                    $precioCombinacion = ($precio * $fee_by_category / 100) + $precio;
                    if($precioDiscount < $precio) {
                        $precioCombinacionRebajado = (floatval($precioDiscount) * $fee_by_category / 100) + floatval($precioDiscount);
                    }
                }else{
                    $precioCombinacion = $precio;
                    if($precioDiscount < $precio) {
                        $precioCombinacionRebajado = floatval($precioDiscount);
                    }
                }

                if($fixed_amount != 0 and $fixed_amount != '') {
                    $precioCombinacion = $precioCombinacion + floatval($fixed_amount);
                    if($precioDiscount < $precio) {
                        $precioCombinacionRebajado = $precioCombinacionRebajado + floatval($fixed_amount);
                    }
                }

                if($combinacion['max_qty'] != '' and $combinacion['is_in_stock'] === true) {
                    $stockCombinacion = floatval($combinacion['max_qty']);
                }elseif($combinacion['is_in_stock'] === false) {
                    $stockCombinacion = floatval(0);
                }elseif($combinacion['is_in_stock'] === true and $combinacion['max_qty'] == '') {
                    $stockCombinacion = floatval($this->stockPorDefecto);
                }else{
                    $stockCombinacion = floatval($this->stockPorDefecto);
                }

                $pro = new AECombination($productoPadre);
                $pro->quantity = $stockCombinacion;

                if(intval($pro->quantity) < 0 or $pro->quantity == NULL) {
                    $pro->quantity = 0;
                }
                $pro->price = $precioCombinacion;
                $pro->discount_price = $precioCombinacionRebajado;
                $pro->reference = $combinacion['sku'];
                $pro->idAE = $productoPadre->idAE;
                $combis[] = $pro;
            }

            return $combis;

        }
        private function get_price_stock($idPro, $categoriaLocal, $categoriaAliExpress, $factory) {
            $pro = new stdClass();
            $pro->quantity = 0;
            $pro->price = 0;
            $pro->price_by_country = [];
            $pro->id = $idPro;
            $pro->discount_price = 0;
            $pro->reference = false;
            $pro->idAE = false;
            $pro->category_id = $categoriaAliExpress;
            $producto = wc_get_product( $idPro );
            // Obtener Comisiones de Categoria 
            $fee_by_category = floatval(get_term_meta(intval($categoriaLocal),'aew_category_fee',true));
            $fixed_amount = floatval(get_term_meta(intval($categoriaLocal),'aew_fixed_amount', true));
            if($producto->get_type() == "variation") {
                $explosionVariations = get_post_meta($producto->get_parent_id(), '_aew_explosion_variations', true);
                if($explosionVariations == '1' or (get_option('aew_explosion_products', false))) {
                    $pro->idAE = get_post_meta($idPro, '_aew_product_id', true);
                }else{
                    $pro->idAE = get_post_meta($producto->get_parent_id(), '_aew_product_id', true);
                }
            }else{
                $pro->idAE = get_post_meta($idPro, '_aew_product_id', true);
            }

            if($producto->is_type('variable')) {
                $combinations = $this->get_variations_price_stock($producto, $fee_by_category, $categoriaAliExpress, $pro, $fixed_amount);
                $pro->combinations = $combinations;
            }else{
                $combinations = false;
                $productReference = $producto->get_sku();

                if($productReference == '') {
                    AEW_MAIN::register_error(__('(CRON PRICE-STOCK) The product does not have a SKU','aliexpress'),$idPro,'product');
                    return false;
                }

                $pro->reference = $productReference;

                $precio = wc_get_price_including_tax($producto, array('price' => $producto->get_regular_price()));
                if($precio == 0) {
                    AEW_MAIN::register_error(__('(CRON PRICE-STOCK) The product does not have a sale price','aliexpress'),$idPro,'product');
                    return false;
                }

                if($producto->get_manage_stock() === false) {
                    if($producto->get_stock_status() == "instock") {
                        $pro->quantity = intval($this->stockPorDefecto);
                    }else{
                        $pro->quantity = 0;
                    }
                }else{
                    $pro->quantity = $producto->get_stock_quantity();
                }

                if(intval($pro->quantity) < 0 or $pro->quantity == NULL) {
                    $pro->quantity = 0;
                }

                
                $precioDiscount = wc_get_price_including_tax($producto, array('price' => $producto->get_sale_price()));
                if($fee_by_category != 0 and $fee_by_category != '') {
                    $pro->price = ($precio * $fee_by_category / 100) + $precio;
                    if($producto->is_on_sale()) {
                        $pro->discount_price = (floatval($precioDiscount) * $fee_by_category / 100) + floatval($precioDiscount);
                    }
                }else{
                    $pro->price = $precio;
                    if($producto->is_on_sale()) {
                        $pro->discount_price = floatval($precioDiscount);
                    }
                }

                if($fixed_amount != 0 and $fixed_amount != '') {
                    $pro->price = $pro->price + $fixed_amount;
                    if($producto->is_on_sale()) {
                        $pro->discount_price = $pro->discount_price + $fixed_amount;
                    }
                }
            }
            $this->masterProductos[] = $pro;

            return $pro;
        }

        function get_related_product_ae($productID) {

            //COMPROBAR QUE LA OPCIÓN ESTÁ ACTIVADA
            if(get_option('aew_add_block_related','0') == '0') {
                return '';
            }
            $array = get_post_meta( $productID, '_crosssell_ids', true );

            if($array == '') {
                return '';
            }
            
            $array = array_slice($array, 0, 6);
            if(!$array or count($array) == 0) {
                return '';
            }
            $typeLinks = get_option('aew_related_links', 'aliexpress');

            //Construct HTML
            $string = '<div style="width:100%">';
            $string .= '<h2 style="border-bottom: 1px solid #5e5e5e;font-weight: bold;color: #5e5e5e;">'. __('Other related products', 'aliexpress') . '</h2>';
            foreach($array as $a) {
                if($typeLinks == 'aliexpress') {
                    $idAE = get_post_meta($a, '_aew_product_id', true);
                    if(!$idAE or $idAE == '') {
                        continue;
                    }
                }
                $post = get_post($a);
                if($post->post_type == 'product') {
                    $image = get_the_post_thumbnail_url($a);
                }else if($post->post_type == 'product_variation' and $typeLinks == 'woocommerce'){
                    $variation = new WC_Product_Variation( $a );
                    $imageID = $variation->get_image_id();
                    $image = wp_get_attachment_url($imageID);
                }else{
                    continue;
                }

                if($image == '') { continue; }

                $titulo = $post->post_title;
                $customTitle = get_post_meta($a, '_aew_custom_title', true);

                if($typeLinks == 'woocommerce') {
                    $link = get_permalink( $a );
                    $target = 'target="_blank"';
                }else{
                    $link = 'https://aliexpress.com/item/' . $idAE . '.html';
                    $target = 'target="_self"';
                }

                if($customTitle and $customTitle != '') {
                    $titulo = $customTitle;
                }
                $title = $titulo;
                $string .= '<div style="float:left;width:calc(33% - 28px);padding: 14px;border-radius: 10px;text-align: center;">
                        <a href="'.$link.'" '.$target.'><div style="background:url('.$image.');background-size:cover;width: 100%;height: 314px;border: 1px solid #CCCC;border-radius: 10px;"></div></a>
                        <a href="'.$link.'" '.$target.'></a><p style="font-size:16px;margin: 10px 0px;color: #ff4747;">'.$title.'</p></a>
                        <a href="'.$link.'" '.$target.' style="background: #3f8d4a;color: #FFF;padding: 10px;display: block;border-radius: 10px;">'.__('More Info', 'aliexpress').'</a>
             </div>';
            }

            $string .= '</div><div style="clear:both"></div></div>';
            return $string;
        }
        function create($idPro, $category, $attrsSelect, $featuresSelect, $typesFeatures = array(), $fee_by_category, $categoriaLocal, $AtributosObligatorios, $unidadPeso = 'kg', $fixed_amount) {
            
            $json = array();
            $producto = wc_get_product( $idPro );
            $idAli = get_post_meta($producto->get_id(), '_aew_product_id', true);

            $minPrice = intval(get_option('aew_min_price',0));
            $maxPrice = intval(get_option('aew_max_price',999999));

            if($producto->is_type('variable')) {
                $explosionVariations = get_post_meta($idPro, '_aew_explosion_variations', true);
                if($explosionVariations == '1' or (get_option('aew_explosion_products', false) and !$idAli)) {
                    $available_variations = $producto->get_available_variations();

                    //Update status general configuration
                    if($explosionVariations != '1') {
                        update_post_meta($idPro, '_aew_explosion_variations', '1');
                    }
                    foreach($available_variations as $variation) {
                        $productsExplosions[] = $this->create($variation['variation_id'], $category, $attrsSelect, $featuresSelect, $typesFeatures, $fee_by_category, $categoriaLocal, $AtributosObligatorios, $unidadPeso, $fixed_amount);
                    }
                    
                    //if only one
                    if(count($productsExplosions) == 1) {
                        return $productsExplosions[0];
                    }
                    return $productsExplosions;
                }
            }
            if($category == null) {
                AEW_MAIN::register_error(__('Error, Category Factory','aliexpress'),$producto->get_id(),'product');
                return;
            }
            $pro = $category->create_product();
            $_POST['ae_product'] = $producto;
            $CorrelativeSKUActive = get_option('aew_corralative_skus');
            $pro->id = $producto->get_id();
            if($idAli != '' and $idAli != 0) {
                $pro->idAE = $idAli;
            }
           
            $productName = $producto->get_name();
            if(strlen($productName) > 218) {
                $productName = substr($productName,0,215) . '...';
            }
            $stockPorDefecto = get_option('aew_stock_default');

            /**Custom Description
             * @since 1.0.5
             */
            $descriptionProduct = '';
            $customDescription = get_post_meta($producto->get_id(), '_aew_custom_description', true);
            $customTitle = get_post_meta($producto->get_id(), '_aew_custom_title', true);
            if($customDescription and $customDescription != '') {
                $descriptionProduct = apply_filters('the_content', $customDescription);
            }else{
                $descriptionProduct = $producto->get_description() ? apply_filters('the_content', $producto->get_description()) : apply_filters('the_content',  $producto->get_short_description());
            }
            $pro->ean = AEW_MAIN::get_ean_product($producto->get_id());
            $categoryDescription = get_term_meta($categoriaLocal,'aew_category_description', true);
            $diasPreparacion = get_term_meta($categoriaLocal, 'aew_category_preparation', true);
            $tagsCategoria = get_term_meta($categoriaLocal, 'aliexpress_tags', true);
            if($diasPreparacion) {
                $pro->preparation_time = intval($diasPreparacion);
            }
            if($categoryDescription != '') {
                $categoryDescriptionText = apply_filters('the_content', $categoryDescription);
                $optionCategoryDescription = get_option('aew_category_description_option', '0');

                if($optionCategoryDescription == '0') {
                    $descriptionProduct = $categoryDescriptionText;
                }elseif($optionCategoryDescription == '1') {
                    $descriptionProduct = $categoryDescriptionText . $descriptionProduct;
                }elseif($optionCategoryDescription == '2') {
                    $descriptionProduct = $descriptionProduct . $categoryDescriptionText;
                }
            }
            

            if(!$customTitle and $customTitle == '') {
                $customTitle = $productName;
            }



            
            

            $related_products = self::get_related_product_ae($producto->get_id());
            // echo $related_products;
            // die();
            $pro->description =  $descriptionProduct . $related_products;
            // $pro->description = $producto->get_description();
            if($producto->get_manage_stock() === false) {
                if($producto->get_stock_status() == "instock") {
                    $pro->quantity = intval($stockPorDefecto);
                }else{
                    $pro->quantity = 0;
                }
            }else{
                $pro->quantity = $producto->get_stock_quantity();
            }

            if(intval($pro->quantity) < 0 or $pro->quantity == NULL) {
                $pro->quantity = 0;
            }
            $medidasDefault = AEW_Category::get_medidas_categoria($categoriaLocal);
            $features = get_post_meta($pro->id, '_product_attributes', true);
            $featuresArray = [];
            if($features) {
                foreach($features as $key => $value) {
                    if($value['is_variation'] == "1") { continue; }
                    // AEW_MAIN::printDebug($featuresSelect[$key]['terms']);
                    // die();
                    $termCustom = null;
                    if($value['is_taxonomy'] == "1") {
                        $terminos = $producto->get_attribute( $key );
                        $terminos = explode(",", $terminos);
            
                            $term = array();
                            
                                if($typesFeatures[$featuresSelect[$key]['value_ali']] == "array") {
                                    foreach($terminos as $t) {
                                            $valueTerm = $featuresSelect[$key]['terms'][trim($t)]['key_ali'];
                                            if($valueTerm != null) {
                                                $term[] = $valueTerm;
                                            }
                                    }
                                }else{
                                    //String
                                    if($featuresSelect[$key]['terms'][trim($terminos[0])]['use_alias'] == 1) {
                                        $termCustom = $featuresSelect[$key]['terms'][trim($terminos[0])]['key_real'];
                                        $term = $featuresSelect[$key]['terms'][trim($terminos[0])]['key_ali'];
                                    }else{
                                        $term = $featuresSelect[$key]['terms'][trim($terminos[0])]['key_ali'];
                                    }
                                }
                        }else{
                            if($featuresSelect[$key]['terms'][trim($value['value'])]['use_alias'] == 1) {
                                $termCustom = $featuresSelect[$key]['terms'][trim($value['value'])]['key_real'];
                                $term = $featuresSelect[$key]['terms'][trim($value['value'])]['key_ali'];
                            }else{
                                $term = $featuresSelect[$key]['terms'][trim($value['value'])]['key_ali'];
                            }
                            
                        }
                    if($term === null) {
                        //El termino es nulo, obtener valor por defecto
                        $term = AEW_Features::get_features_default($categoriaLocal, $featuresSelect[$key]['value_ali']);
        
                        if($term['customValue']) {
                            $termCustom = $term['customValue'];
                        }
                        if($featuresSelect[$key]['terms'][trim($value['value'])]['use_alias'] == 1) {
                            $termCustom = $term['key_real'];
                        }
                        $term = $term['value'];
                        if(!$term) {
                            $term = 'No encontrado';
                        }
                    }
                    if($featuresSelect[$key]['value_ali'] != "") {
                        if($term == '4' and $featuresSelect[$key]['terms'][trim($value['value'])]['use_alias'] == 1) {
                            $pro->add_feature($featuresSelect[$key]['value_ali'], $term, ["CustomValue" => $termCustom]);
        
                        }else{
                            $pro->add_feature($featuresSelect[$key]['value_ali'], $term);
                            
                        }
                        $featuresArray[$featuresSelect[$key]['value_ali']] = $term;
                    }
                
                }
            }

            
            
            //Comprobar caracteriscas obligatorias
            foreach($AtributosObligatorios as $requiredFeature) {
                if(is_array($pro->features) and !array_key_exists($requiredFeature, $pro->features)) {
                    //Agregar valor por defecto
                    $term = AEW_Features::get_features_default($categoriaLocal, $requiredFeature);
                    if($term) {
                        if($typesFeatures[$requiredFeature] == "array") {
                                $dataFeature = array();
                                $dataFeature[] = $term['value'];
                        }else{
                            //String
                            $dataFeature = $term['value'];
                        }
                        if($dataFeature == '4' and $term['customValue'] != NULL) {

                            $pro->add_feature($requiredFeature, $dataFeature, ["CustomValue" => $term['customValue']]);
        
                        }else{
                            $pro->add_feature($requiredFeature, $dataFeature);
                            
                        }
                        $featuresArray[$requiredFeature] = $dataFeature;
                    }
                }
            }

            //brand y tags
            $anteTitulo = '';
            // die(var_dump($featuresArray));
            if(get_term_meta($categoriaLocal, 'aliexpress_add_brand', true) == '1' and isset($featuresArray['Brand Name'])) {
                $anteTitulo = AEW_MAIN::get_brand_name($pro, $featuresArray['Brand Name']);
                if($anteTitulo != '') {
                    $anteTitulo = $anteTitulo . ' ';
                }
            }
            $pro->name = substr($anteTitulo . $customTitle . ' ' . $tagsCategoria,0, 218);



            $precio = wc_get_price_including_tax($producto, array('price' => $producto->get_regular_price()));
            if($precio == 0) {

                if($producto->is_type('variable')) {
                    AEW_MAIN::register_error(__('The product does not have a sale price or does not have active variations','aliexpress'),$producto->get_id(),'product');
                }else{
                    AEW_MAIN::register_error(__('The product does not have a sale price','aliexpress'),$producto->get_id(),'product');
                }
                return false;
            }

            if($precio > $maxPrice or $precio < $minPrice) {
                AEW_MAIN::register_error(__('The price is not in the allowed range, please set another range to allow this price.','aliexpress'),$producto->get_id(),'product');
                return false;
            }
            $precioDiscount = wc_get_price_including_tax($producto, array('price' => $producto->get_sale_price()));
            if($fee_by_category != 0 and $fee_by_category != '') {
                $pro->price = ($precio * $fee_by_category / 100) + $precio;
                if($producto->is_on_sale()) {
                    $pro->discount_price = (floatval($precioDiscount) * $fee_by_category / 100) + floatval($precioDiscount);
                }
            }else{
                $pro->price = $precio;
                if($producto->is_on_sale()) {
                    $pro->discount_price = floatval($precioDiscount);
                }
            }


            if($fixed_amount != 0 and $fixed_amount != '') {
                $pro->price = $pro->price + $fixed_amount;
                if($producto->is_on_sale()) {
                    $pro->discount_price = $pro->discount_price + $fixed_amount;
                }
            }
            $productReference = $producto->get_sku();
            if($productReference == '' && get_option('aew_generate_sku_id','0') == '1') {
                //Generar SKU by ID
                $productReference = $producto->get_id();	
                $producto->set_sku($productReference);
                $producto->save();
            }

            if($productReference == '') {
                AEW_MAIN::register_error(__('The product does not have a SKU','aliexpress'),$producto->get_id(),'product');
                return false;
            }

            $pro->reference = $productReference;

            /**
             * Convertir todos los pesos a KG
             * @since 1.2.11
             * 
             */

            $pesoProducto = (floatval($producto->get_weight()) != 0 ? floatval($producto->get_weight()) : floatval($medidasDefault->weight));

            if($unidadPeso != 'kg') {
                $pesoProducto = wc_get_weight($pesoProducto, 'kg', $unidadPeso);
            }

            $group = get_post_meta($producto->get_id(), 'aew_group_product', true);

            if(!$group) {
                $group = get_term_meta($categoriaLocal, 'aew_group_category', true);
            }

            if(!$group || $group == '0') {
                $group = null;
            }

            $pro->product_group_id = $group;
            $pro->package_length = (floatval($producto->get_length()) != 0 ? floatval($producto->get_length()) : floatval($medidasDefault->length));
            $pro->package_height = (floatval($producto->get_height()) != 0 ? floatval($producto->get_height()) : floatval($medidasDefault->height));
            $pro->package_width = (floatval($producto->get_width()) != 0 ? floatval($producto->get_width()) : floatval($medidasDefault->width));
            $pro->package_weight = $pesoProducto;
            $galeria = array();

            $imgs_producto = $producto->get_gallery_image_ids();
            $images_all = array();
            $imagenPrincipal = $producto->get_image_id();
            $optionSizeImage = get_option('aew_size_images', 'medium');
            if($imagenPrincipal) {
                array_push($galeria, $imagenPrincipal);
            }
            $imgs_producto = array_merge($galeria, $imgs_producto);
            $respuesta_bank_image = false;
            $countImages = 0;
            foreach( $imgs_producto as $imagenes_id )  {
                if($countImages > 6) {
                    continue;
                }
                $image = wp_get_attachment_image_src( $imagenes_id, $optionSizeImage )[0];
                if(!in_array($image, $images_all)) {
                    //Subir a AliExpress si no existe
                    $url_bank_image = get_post_meta($imagenes_id, 'aew_bank_image_url', true);
                    if(!$url_bank_image or $url_bank_image == '') {
                        //No existe subir
                        $imagePath = wp_get_original_image_path($imagenes_id);
                        $respuesta_bank_image = $category->get_factory()->wrapper->putImage("@".$imagePath, get_post_meta($imagenes_id, '_wp_attached_file', true));
                        if($respuesta_bank_image and isset($respuesta_bank_image['result'])) {
                            $url_bank_image = $respuesta_bank_image['result']['photobank_url'];
                            update_post_meta($imagenes_id, 'aew_bank_image_url', $url_bank_image);
                        }

                        if($url_bank_image == '') {
                            AEW_MAIN::register_error(__('Error upload image','aliexpress'),$producto->get_id(),'product');
                            return false;
                        }
                    }
                    $image = $url_bank_image;
                    array_push($images_all, $image );
                }
                $countImages++;
            }	
            
            //UPDATE STATS BANK IMAGES
            if($respuesta_bank_image and isset($respuesta_bank_image['result'])) {
                update_option('aew_bank_total_space', $respuesta_bank_image['result']['photobank_total_size']);
                update_option('aew_bank_used_space', $respuesta_bank_image['result']['photobank_used_size']);
            }

            // $image_system = get_option('aew_image_system', 'direct');
            // if(is_array($images_all) and count($images_all) > 0 and $image_system == 'alternative') {
            //     foreach($images_all as $k => $img) {
            //         $images_all[$k] = str_replace('https://', 'http://', $img);
            //     }
            // }
            if(count($images_all) == 0) {
                AEW_MAIN::register_error(__('The product has no images, at least one image is required to upload to AliExpress','aliexpress'),$producto->get_id(),'product');
                return false;
            }
            $pro->language_product = get_locale();
            $pro->url_images = $images_all;
            //Shipping Template

            $shippingTemplateProduct = get_post_meta($idPro, '_aew_shipping_template_product', true);
            $shippingTemplateCategory = get_term_meta($categoriaLocal, 'aew_shipping_template_category', true);

            $shippingTemplate = get_option('_aew_shipping_template');
            
            if($shippingTemplateCategory and $shippingTemplateCategory != '0' and $shippingTemplateCategory != ''){
                $shippingTemplate = $shippingTemplateCategory;
            }

            if($shippingTemplateProduct and $shippingTemplateProduct != '0' and $shippingTemplateProduct != ''){
                $shippingTemplate = $shippingTemplateProduct;
            }            
           
            $pro->shipping_template = $shippingTemplate;

            $allowUseEmptyAttr = get_option('aew_check_empty_attrs');
            //Combinaciones
            if($producto->is_type('variable')) {
                $combinaciones = $producto->get_available_variations();

                if($combinaciones) {


                    //Si la configuración puede enviar productos con atributos dinámicos
                    if($allowUseEmptyAttr == "1") {
        
                        //Comprobación de Atributos
                        foreach($combinaciones as $keyCombination => $combinacion) {
                            foreach($combinacion['attributes'] as $key => $value) {
                                $keyReplace = str_replace('attribute_','',$key);
                                $skuCombinacion = $combinacion['sku'];
                                if($value == "") {
                                    $subfijo = 0;
                                    //La combinación tiene atributos con varias opciones.
                                    $terminos = $producto->get_attribute( $keyReplace );
                                    
                                    $json[$keyReplace] = $terminos;
                                    $terminosVacios = explode(",", $terminos);
                                    foreach($terminosVacios as $termino) {
        
                                        $combinacion['attributes'][$key] = trim($termino);
                                        $combinacion['sku'] = $skuCombinacion . '-' . $subfijo;
                                    
                    
                                        unset($combinaciones[$keyCombination]);
                                        $combinaciones[] = $combinacion;
                                        $subfijo++;
                                    }
                                
                                }
                            }
                        }
                    }

                    $combinaciones = array_values($combinaciones);
                    $attrsUse = array();
                    $incrementalSKU = 0;
                    foreach($combinaciones as $combinacion) {
                        $combi = new AECombination($pro);
                        $saltarCombinacion = false;
                        if($combinacion['max_qty'] != '' and $combinacion['is_in_stock'] === true) {
                            $combi->quantity = floatval($combinacion['max_qty']);
                        }elseif($combinacion['is_in_stock'] === false) {
                            $combi->quantity = floatval(0);
                        }elseif($combinacion['is_in_stock'] === true and $combinacion['max_qty'] == '') {
                            $combi->quantity = floatval($stockPorDefecto);
                        }else{
                            $combi->quantity = floatval($stockPorDefecto);
                        }
                        if(intval($combi->quantity) < 0 or $combi->quantity == NULL) {
                            $combi->quantity = 0;
                        }
                        
                        //Tamaño y peso
                        $combi->ean = AEW_MAIN::get_ean_product($combinacion['variation_id']);
                        $combi->package_length = (floatval($combinacion['dimensions']['length']) != 0 ? floatval($combinacion['dimensions']['length']) : floatval($medidasDefault->length));
                        $combi->package_height = (floatval($combinacion['dimensions']['height']) != 0 ? floatval($combinacion['dimensions']['height']) : floatval($medidasDefault->height));
                        $combi->package_width = (floatval($combinacion['dimensions']['width']) != 0 ? floatval($combinacion['dimensions']['width']) : floatval($medidasDefault->width));
                        $combi->package_weight = (floatval($combinacion['weight']) != 0 ? floatval($combinacion['weight']) : floatval($medidasDefault->weight));
                        $precio = wc_get_price_including_tax($producto, array('price' => $combinacion['display_regular_price']));
                        if($precio == 0) {
                            AEW_MAIN::register_error(__('Some or all product variations has not price','aliexpress'),$producto->get_id(),'product');
                            return false;
                        }
                        $precioDiscount = wc_get_price_including_tax($producto, array('price' => $combinacion['display_price']));
                        if($fee_by_category != 0 and $fee_by_category != '') {
                            $combi->price = ($precio * $fee_by_category / 100) + $precio;
                            if($precioDiscount < $precio) {
                                $combi->discount_price = (floatval($precioDiscount) * $fee_by_category / 100) + floatval($precioDiscount);
                            }
                        }else{
                            $combi->price = $precio;
                            if($precioDiscount < $precio) {
                                $combi->discount_price = floatval($precioDiscount);
                            }
                        }

                        if($fixed_amount != 0 and $fixed_amount != '') {
                            $combi->price = $combi->price + $fixed_amount;
                            if($producto->is_on_sale()) {
                                $combi->discount_price = $combi->discount_price + $fixed_amount;
                            }
                        }

                        
                        if($combinacion['sku'] == $productReference and $CorrelativeSKUActive == '0') {
                            AEW_MAIN::register_error(__('The product has variations, but some or all variations have no reference (SKU)','aliexpress'),$producto->get_id(),'product');
                            return false;
                        }elseif( ($combinacion['sku'] == $productReference or $combinacion['sku'] == '') and $CorrelativeSKUActive == '1') {
                            $localCombi = new WC_Product_Variation($combinacion['variation_id']);
                            $sku_dinamic = $productReference .'-'.$incrementalSKU;
                            $incrementalSKU++;
                            try{
                                $localCombi->set_sku($sku_dinamic);
                                $localCombi->save();
                            }catch (Exception $e){
                                AEW_MAIN::register_error($e->getMessage(),$producto->get_id(),'product');
                                return false;
                            }
                            $combi->reference = $sku_dinamic;
                        }else{
                            $combi->reference = $combinacion['sku'];
                        }
						
						//Si todo lo anterior falla
						if($combi->reference == '') {
							AEW_MAIN::register_error(__('Variations do not have SKU, it is not possible to upload the product','aliexpress'),$producto->get_id(),'product');
                            return false;
						}
                        
                        foreach($combinacion['attributes'] as $key => $value) {
                            if($value == "" and $allowUseEmptyAttr == "0") {
                                AEW_MAIN::register_error(sprintf(__('The product has empty attributes, the upload is not allowed, for more information <a href="%s">click here</a>','aliexpress'),''),$producto->get_id(),'product');
                                return false;
                            }
                            $keyReplace = str_replace('attribute_','',$key);
                            if(substr($key, 0,12) == "attribute_pa") {
                                $term = get_term_by('slug',$value,$keyReplace);
                            }else{
                                $term = new stdClass();
                                $term->name = $value;
								$keyReplace = wc_attribute_label( $keyReplace, $producto );
                            }						
                        
                            $objAlias = array();
                            if(isset($combinacion['image']['full_src'])) {
                                $objAlias['sku_image_url'] = $combinacion['image']['full_src'];
                            }
                            
        
                            if($attrsSelect[$keyReplace]['terms'][$term->name]['use_alias'] == 1) {
                                $objAlias['alias'] = $attrsSelect[$keyReplace]['terms'][$term->name]['value_alias'];
                            }
                            $termAgregado = $combi->set_attribute($attrsSelect[$keyReplace]['value_ali'], $attrsSelect[$keyReplace]['terms'][$term->name]['value_real'], $objAlias);
							
                            if(!$termAgregado) {
                                $saltarCombinacion = true;
                            }
                        }
                        
                        if($saltarCombinacion) {
                            continue;    
                        }
                        
                        $combi->save();
                        //$combi->ean = '';

                    }
                    
                }
            }
            $categoryDefault = get_post_meta($idPro, '_aew_default_category_id', true);
            if(!$categoryDefault or $categoryDefault == '' or $categoryDefault == '0') {
                update_post_meta($idPro, '_aew_default_category_id', $categoriaLocal);
            }

            if(get_option('ean_precio_bajo_aew')) {
                $pro = apply_filters( 'aew_ean_bajo', $pro );
            }

            $pro = apply_filters('aew_price_product_rules', $pro);
            
            return $pro;
        }

        function checkAttrCompare($atributos) {

        }

        static function aew_validate_ean($ean) {
            if($ean == '') { return ''; }
            $ean = strrev($ean);
            // Split number into checksum and number
            $checksum = substr($ean, 0, 1);
            $number = substr($ean, 1);
            $total = 0;
            for ($i = 0, $max = strlen($number); $i < $max; $i++) {
                if (($i % 2) == 0) {
                    $total += ($number[$i] * 3);
                } else {
                    $total += $number[$i];
                }
            }
            $mod = ($total % 10);
            $calculated_checksum = (10 - $mod) % 10;
            if ($calculated_checksum == $checksum) {
                return strrev($ean);
            } else {
                return '';
            }
        }

        static function sendNew($factory, $category, $productos) {
            $factory->price_by_country = get_option('countryPrices', []);
            $job = $factory->batch_create_products($category->category_id, $productos);
            $job->query($factory);

            //$job_state = $factory->batch_query($job->aejob_id);
            return $job->aejob_id;
        }
        static function sendUpdate($factory, $category, $productos) {
            $factory->price_by_country = get_option('countryPrices', []);
            // die(var_dump($factory->price_by_country));
            $job = $factory->batch_update_products($category->category_id, $productos);
            $job->query($factory);

            //$job_state = $factory->batch_query($job->aejob_id);
            return $job->aejob_id;
        }
        static function update_products_ids($job) {
            if(!isset($job['result_list'])) { return; }
            $trabajo = $job['result_list']['single_item_response_dto'];
            update_option('aew_notices_jobs', '');
            $string = '';
            if(intval($job['total_item_count']) == 1) { //Only one
                
                $aliExpressProductID = json_decode($trabajo['item_execution_result'], true);
                if(!$aliExpressProductID) { return; }
                if($aliExpressProductID['success'] === false) {
                    update_post_meta(intval($trabajo['item_content_id']),'_aew_run_job', '0');
                    AEW_MAIN::register_error($aliExpressProductID['errorMessage'], intval($trabajo['item_content_id']), 'product');
                    $string = sprintf(__('Job AliExpress products with errors, for more information check <a href="%s">the log here</a>.','aliexpress'), home_url() . '/wp-admin/admin.php?page=aliexpress-general-options&tab=error');
                    update_option('aew_notices_jobs', $string);
                    return;
                };
                /**
                 * Actualiza los productos según el id del trabajo
                 * @since 1.0.4
                 */
                self::update_products_by_job($job);
                update_post_meta(intval($trabajo['item_content_id']), '_aew_status_ae', '1');
                update_post_meta(intval($trabajo['item_content_id']),'_aew_product_id', intval($aliExpressProductID['productId']));
                update_post_meta(intval($trabajo['item_content_id']),'_aew_product_sincro', time());

            }else{

                /**
                 * Actualiza los productos según el id del trabajo
                 * @since 1.0.4
                 */
                self::update_products_by_job($job);

                foreach($trabajo as $product) {
                
                    if(!isset($product['item_content_id'])) { continue; }
                    
                    $aliExpressProductID = json_decode($product['item_execution_result'], true);
                    if(!$aliExpressProductID) { continue; }
                    
                    if($aliExpressProductID['success'] == false) {
                        update_post_meta(intval($product['item_content_id']),'_aew_run_job', '0');
                        AEW_MAIN::register_error($aliExpressProductID['errorMessage'], intval($product['item_content_id']), 'product');
                        $string = sprintf(__('Job AliExpress products with errors, for more information check <a href="%s">the log here</a>.','aliexpress'), home_url() . '/wp-admin/admin.php?page=aliexpress-general-options&tab=error');
                        continue;
                    }
                    update_post_meta(intval($product['item_content_id']), '_aew_status_ae', '1');
                    update_post_meta(intval($product['item_content_id']),'_aew_product_id', intval($aliExpressProductID['productId']));
                    update_post_meta(intval($product['item_content_id']),'_aew_product_sincro', time());
                }
            }
            if($string != '') {
                update_option('aew_notices_jobs', $string);
            }
            
        }
        /**
         * update_products_by_job - Actualiza el estado del trabajo y el necesita actualizar de todos los productos del trabajo
         *
         * @param array $job
         * @return void
         * @since 1.0.4
         */
        private static function update_products_by_job($job) {
            global $wpdb;
            $trabajos = $wpdb->get_results('SELECT post_id FROM '.$wpdb->prefix.'postmeta WHERE meta_value = '.$job['job_id']);
            foreach($trabajos as $trabajo) {
                update_post_meta(intval($trabajo->post_id),'_aew_run_job', '0');
                update_post_meta(intval($trabajo->post_id), '_aew_need_upload', '0');
            }
        }

        public static function get_orphan_products_cats() {

            global $wpdb;


            $meta_query = array(
                array(
                    'key'     => '_aew_product_id',
                    'value'   => '0',
                    'compare' => '>'
                )
            );

            $args = array(
                'post_type' => array('product','product_variation'),
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => $meta_query
            );

            $productos = get_posts($args);

            if(!is_array($productos)) {
                $productos = [$productos];
            }
            if(!$productos) { return false; }

            $orphan_cats=[];

            foreach($productos as $key => $p) {
                $categoriaLocal = get_post_meta($p, '_aew_default_category_id', true);
                $explosion = get_post_meta($p, '_aew_explosion_variations', true);

                if($explosion == '1') {
                    continue;
                }

                //Comprobar si es una variacion
                $post = get_post($p);
                $parentProduct = false;
                if($post->post_type == 'product_variation' ) {
                    $parentProduct = $post->post_parent;
                    $parentExplosion = get_post_meta($parentProduct, '_aew_explosion_variations', true);

                    if($parentExplosion != "1") {
                        continue;
                    }
                    $categoriaLocal = get_post_meta($parentProduct, '_aew_default_category_id', true);
                    if(!$categoriaLocal) {
                        $categorias = get_the_terms( $parentProduct, 'product_cat' );
                        if($categorias) {
                            $totalCategorias = count($categorias);
                            $categoriaLocal = $categorias[intval($totalCategorias - 1)]->term_id;
                        }
                    }

                }

                if(!$categoriaLocal) {
                    $categorias = get_the_terms( $p, 'product_cat' );
                    if($categorias) {
                        $totalCategorias = count($categorias);
                        $categoriaLocal = $categorias[intval($totalCategorias - 1)]->term_id;
                    }

                }
                $categoryAliExpress = get_term_meta($categoriaLocal, 'aew_category_id', true);



                if(!$categoryAliExpress) {
                    $orphan_cats[$categoriaLocal] = '1';
                }
            }

            return $orphan_cats;
        }
    }
}
?>

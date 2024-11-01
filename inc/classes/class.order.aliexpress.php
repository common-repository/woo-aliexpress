<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if(!class_exists('AEW_Order')) {
    class AEW_Order {

        /**
         * create_order - Crea un pedido en WooCommerce con los datos recibidos
         *
         * @param array $products
         * @param string $status
         * @param array $customer
         * @param integer $aliexpress_order_id
         * @param string $customer_note
         * @param string $created_via
         * @return void
         */
        static function create_order($products = array(), $status = 'PLACE_ORDER_SUCCESS', $customer = 0, $aliexpress_order_id = 0, $carrier_aliexpress = 0, $address, $shippingAmount = false, $orderAliExpress = [], $customer_note = '', $created_via = 'AliExpress'){
            if(!$customer or $aliexpress_order_id == 0) { return; }
            if(count($products) == 0) { return;}
            $finalNameOrderStatus = get_option('aew_order_default_status', 'wc-processing');
            $order = wc_create_order();
            $order->set_customer_id(intval($customer));
            $order->set_created_via( sanitize_text_field( $created_via ) );
            
            $order->set_address( $address, 'billing' );
            $order->set_address( $address, 'shipping' );
            
            foreach($products as $product) {
                    if(!isset($product['id'])) {
                        $idProducto = wc_get_product_id_by_sku($product['sku_code']);
                    }else{
                        $idProducto = $product['id'];
                    }
                    if($idProducto > 0) {
                        $_producto = wc_get_product($idProducto);
                    }else { $_producto = false; }

                    $totalWitOutTax = floatval($product['price_without_tax']*$product['qty']);
                    $total = floatval($product['price']*$product['qty']);
                    $dataLine = [
                        'name' => $product['order_item_name'],
                        'subtotal' => floatval($product['price_without_tax']),
                        'total' => $totalWitOutTax
                    ];
                    $item_id = $order->add_product($_producto, $product['qty'], $dataLine);
                    
                    if($item_id) {
                        if($idProducto == 0) { wc_add_order_item_meta($item_id, 'SKU', $product['sku_code']); }
                        wc_add_order_item_meta($item_id, '_aew_line_order_id', $product['child_id']);
                        if(isset($product['ae_info'])) {
                            wc_add_order_item_meta($item_id, 'AliExpress Info', $product['ae_info']);
                        }
                    }
            }

            /**
             * @since 1.2.11
             */
            $item = new WC_Order_Item_Shipping();
            $typeCalculo = get_option('woocommerce_shipping_tax_class', 'inherit');
            $ivaEnvio = 0;
            if($typeCalculo == 'inherit') {
                //Basado en productos
                $ivaEnvio = $products[0]['tax'];
            }else{
                $tax = WC_Tax::get_rates_for_tax_class($typeCalculo);
                //por clase
                $ivaEnvio = floatval(current($tax)->tax_rate);
            }
            if($ivaEnvio != 0) {
                $shippingAmountFinal = ($shippingAmount['amount'] / (1 + ($ivaEnvio / 100)));
            }else{
                $shippingAmountFinal = $shippingAmount['amount'];
            }
            $item->set_method_title( __('Shipping Cost', 'aliexpress') );
            $item->set_method_id( get_option('_aew_method_shipping_id', '') );
            $item->set_total( $shippingAmountFinal );
            $order->add_item( $item );
           
            //Discount
            if(get_option('aew_discount_support_order','0') == "1" and isset($orderAliExpress['order_discount_info'])) {
                $descuentoSinIva = (floatval($orderAliExpress['order_discount_info']['amount']) / (1 + ($orderAliExpress['tax_max'] / 100)));
                $dataDiscount = [
                        'name' => __('Discount', 'aliexpress'),
                        'subtotal' => -$descuentoSinIva,
                        'total' => -$descuentoSinIva,
                    ];
                    $order->add_product(false, 1, $dataDiscount);
            }
            $order->calculate_totals();
            // $order->set_total($orderAliExpress['init_oder_amount']['amount']);
            $order->update_status($finalNameOrderStatus);
            $order->save();
            update_post_meta($order->ID,  '_aew_order_id', $aliexpress_order_id); //Guarda el id del pedido de AliExpress en el pedido local
            update_post_meta($order->ID,  '_aew_key_carrier', $carrier_aliexpress); //Guarda el transportista de AliExpress en el pedido local
            
            //For Holded Plugin
            $fechaAhora = date('Y-m-d H:i:s');
            $timeAhora = time();

            update_post_meta($order->ID,  '_payment_method', 'AliExpress');
            update_post_meta($order->ID,  '_payment_method_title', 'AliExpress');
            update_post_meta($order->ID,  '_billing_address_2', $address['address_2']);
            update_post_meta($order->ID,  '_billing_company', '');
            update_post_meta($order->ID,  '_shipping_address_2', $address['address_2']);
            update_post_meta($order->ID,  '_shipping_company', '');
            update_post_meta($order->ID,  '_shipping_phone', '');
            update_post_meta($order->ID,  '_cart_hash', md5($fechaAhora));
            update_post_meta($order->ID,  '_completed_date', $fechaAhora);
            update_post_meta($order->ID,  '_paid_date', $fechaAhora);
            update_post_meta($order->ID,  '_date_completed', $timeAhora);
            update_post_meta($order->ID,  '_date_paid', $timeAhora);
            update_post_meta($order->ID,  'is_vat_exempt', 'no');


            //Actualiza el stock de productos cuando el pedido se descarga desde AE
            wc_reduce_stock_levels($order->ID);
            try {
                WC()->mailer()->get_emails()['WC_Email_New_Order']->trigger( $order->ID, $order );
            }catch(Exception $e) {}
            return $order->ID;

        }

        public static function aew_get_line_product($order, $product) {
            if($product['type'] == 'variable') {
                $productVariable = new WC_Product_Variable(intval($product['id']));
                $variations_products = $productVariable->get_available_variations();
                $variationData = array();
            }elseif($product['type'] == 'product_variation'){
                $productVariable = new WC_Product_Variation(intval($product['id']));
                $variations_products = false;
            }else{
                $productVariable = new WC_Product(intval($product['id']));
                $variations_products = false;
            }
        

            
            if($product['variation_id']) {
                foreach ($variations_products as $variation) {
                    if ($variation['sku'] == $product['variation_sku']) {
                        // $variationID = $variation['variation_id'];
                        $variationData = $variation['attributes'];
                        break;
                    }
                }
                $item_id = $order->add_product($productVariable, $product['qty'], array(
                    'subtotal' => $product['price'],
                    'variation' => $variationData
                ));
            }else{
                $item_id = $order->add_product($productVariable, $product['qty'], array(
                    'subtotal' => $product['price']
                ));
            }
            return $item_id;
        }

        public static function check_order_aliexpress_byID($idOrder) {
            global $wpdb;
            $orderid_aliexpress = $wpdb->get_var( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key='_aew_order_id' and meta_value='$idOrder' ORDER by meta_id DESC" );
            return $orderid_aliexpress;
        }

        public static function get_expression_shipping($keyCarrier) {
            global $wpdb;
            $expression = $wpdb->get_var( "SELECT expression FROM {$wpdb->prefix}aew_mapping_carriers WHERE key_carrier='$keyCarrier'" );
            return $expression;
        }
        public static function get_zones_wocommerce(){
            global $wpdb;
            $res = array();
            $query = "SELECT z.*, cm.key_carrier, cm.id_mapping_carriers, m.instance_id FROM ".$wpdb->prefix."woocommerce_shipping_zone_methods AS m 
            INNER JOIN ".$wpdb->prefix."woocommerce_shipping_zones AS z ON z.zone_id = m.zone_id
            LEFT JOIN ".$wpdb->prefix."aew_mapping_carriers AS cm ON cm.id_carrier = m.instance_id
            ";
            $zones = $wpdb->get_results($query);
            foreach($zones as $zone) {
                $data = get_option('woocommerce_flat_rate_'.$zone->instance_id.'_settings');
                if(!$data) {
                    continue;
                }
                $res[] = array(
                    'id_zone' => $zone->zone_id,
                    'instance_id' => $zone->instance_id,
                    'name' => $zone->zone_name,
                    'data' => $data,
                    'selected' => $zone->key_carrier,
                    'id_reg_select' => $zone->id_mapping_carriers
                );
            }
            // AEW_MAIN::print($res);
            return $res;
        }
        public static function pagination_orders($orders){
            // AEW_MAIN::printDebug($orders);
            
            $totalPages = $orders['result']['total_page'];
            $totalOrder = $orders['result']['total_count'];
            $currentPage = $orders['result']['current_page'];
            $pageSize = $orders['result']['page_size'];
            $string = '';
            if($totalPages == "1") {
                return $string;
            }
            $stringLink = '';
            if(isset($_GET['from_date'])) {
                $stringLink .= '&from_date='.sanitize_text_field($_GET['from_date']).'&to_date='.sanitize_text_field($_GET['to_date']);
            }
            if(isset($_GET['status_order'])) {
                $stringLink .= '&status_order='.sanitize_text_field($_GET['status_order']);
            }
            $string = '<div class="pagination_aliexpress">';
            //Hay más páginas
            for($i = 0; $i < $totalPages; $i++) {
                $b = $i+1;
                if($b == $currentPage) {
                    $active = 'active';
                }else{
                    $active = '';
                }
                $string .= '<a class="'.$active.'" href="?page=aliexpress-general-options&tab=view_orders'.$stringLink.'&pnumber='.$b.'">'.$b.'</a>';
            }

            $string .= '</dib>';
            return $string;
        }
        public static function construct_order($c, $debug = false) {
            global $wpdb;
            
            require_once(AEW_AE_PATH . 'AEFactory.php');
            require_once(AEW_AE_PATH . 'AEOrder.php');

            if(defined('AEW_ROOT_PATH') and file_exists(AEW_ROOT_PATH . '/data/order.json')) {
                $order = json_decode(file_get_contents(AEW_ROOT_PATH . '/data/order.json'), true);
            }else{
                $factory = new AEFactory(AEW_TOKEN); 
                $o = new AEOrder_Base($factory);
                
                $order = json_decode($o->get_order_by_id($c), true);
            }
            // die(wp_send_json($order));
            $res = array();
            if($debug) {
                return $order;
            }

            $order = $order['result']['data'];
            
            $addrs = $order['receipt_address'];
            
            $address_2 = $addrs['address2'];
            if(is_array($addrs['address2'])) {
                $address_2 = json_encode($addrs['address2']);
            }
            
            $address = array(
                'address_1'=> $addrs['detail_address'],
                'city'=>$addrs['city'],
                'first_name'=> self::calculate_name_customer($addrs['contact_person']),
                'last_name'=> self::calculate_name_customer($addrs['contact_person'], 'last_name'),
                'country'=>$addrs['country'],
                'address_2'=>$address_2,
                'phone'=>$addrs['mobile_no'],
                'state'=>$addrs['province'],
                'postcode'=>$addrs['zip']
            );
            $addressTax = [
                $address['country'],
                $address['state'],
                $address['postcode'],
                $address['city'],
            ];
            $tax = WC_Tax::get_rates_from_location('', $addressTax);
            
            if(count($tax) == 0) {
                $tax[0] = array('rate' => 0);
            }

            $tax = $tax[array_keys($tax)[0]];
            $productos = $order['child_order_list']['global_aeop_tp_child_order_dto'];
            
            $test = array();
            $carrierOrder = $productos[0]['logistics_type'];
            if(isset($productos['id'])) {
                $productos = [$productos];
            }
            $tax_max = 0;
            $prods = array();
            foreach($productos as $producto) {
                $productID = $producto['product_id'];
                $product_id =  $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_aew_product_id' and meta_value='$productID'" );
                $type =  $wpdb->get_var( "SELECT post_type FROM $wpdb->posts WHERE ID='$product_id'" );
                
                if($product_id == NULL) {
                    $sku_text = '';
                    $ae_info = '';
                    $skus = json_decode($producto['product_attributes'], true);
                    foreach($skus['sku'] as $p) {
                        $sku_text .= ' - '. $p['pName'] . ' : ' . $p['pValue'];
                        $ae_info .= $p['pName'] . ' : ' . $p['pValue'] . ' | ';
                    }
                    if($tax_max < $tax['rate']) {
                        $tax_max = $tax['rate'];
                    }
                    $productObj = array(
                        'order_item_name' => $producto['product_name'] . $sku_text,
                        'qty' => $producto['product_count'],
                        'price' => $producto['product_price']['amount'],
                        'tax' => $tax['rate'],
                        'price_without_tax' => ($producto['product_price']['amount'] / (1 + ($tax['rate'] / 100))),
                        'child_id' => $producto['child_order_id'],
                        'sku_code' => $producto['sku_code'],
                        'ae_info'  => $ae_info,
                        'type' => $type
                    );
                }else{
                    $product = wc_get_product($product_id);
                    update_post_meta($product_id, '_aew_need_upload', '1');
                    $ae_info = '';
                    $skus = json_decode($producto['product_attributes'], true);
                    foreach($skus['sku'] as $p) {
                        $ae_info .= $p['pName'] . ' : ' . $p['pValue'] . ' | ';
                    }
                    $productObj = array(
                        'id' => $product_id,
                        'price' => $producto['product_price']['amount'],
                        'qty' => $producto['product_count'],
                        'tax' => $tax['rate'],
                        'price_without_tax' => ($producto['product_price']['amount'] / (1 + ($tax['rate'] / 100))),
                        'child_id' => $producto['child_order_id'],
                        'ae_info'  => $ae_info,
                        'type' => $type
                    );
                    if($product->is_type('variable')) {
                        $skuCode = $producto['sku_code'];
                        $variationID =  $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' and meta_value='$skuCode'" );
                        $productObj['variation_id'] = $variationID;
                        $productObj['variation_sku'] = $skuCode;
                        $productObj['type'] = 'variable';
                    }
                    
                }
                array_push($prods, $productObj);
            }

            //Cliente
            $client_exist = get_user_by('email',$order['buyerloginid'].'@aliexpress.local');

            if(!$client_exist) {
                //Crear Cliente
                $random_password = wp_generate_password( 12, true, true );
                $customer = wp_create_user($order['buyerloginid'],$random_password,$order['buyerloginid'].'@aliexpress.local');

            }else{
                $customer = $client_exist->ID;
            }

            // return [$prods, $customer, $address, $addressTax];
            // die();
            $order['tax_max'] = $tax_max;
            
            $createOrder = self::create_order($prods, $order['order_status'], $customer, $order['id'], $carrierOrder, $address, $order['logistics_amount'], $order);
            return array('order' => $createOrder);
        }

        private static function calculate_name_customer($realName, $return = 'first_name') {
            $name = explode(' ',$realName);
            $totalPalabras = count($name);
            // return $totalPalabras;
            if($totalPalabras == 2 and $return == 'first_name') {
                return $name[0];
            }
            if($totalPalabras == 3 and $return == 'first_name') {
                return $name[0];
            }
            if($totalPalabras == 4 and $return == 'first_name') {
                return $name[0] . ' ' . $name[1];
            }
            if($totalPalabras == 2 and $return == 'last_name') {
                return $name[1];
            }
            if($totalPalabras == 3 and $return == 'last_name') {
                return $name[1] . ' ' . $name[2];
            }
            
            if($totalPalabras == 4 and $return == 'last_name') {
                return $name[2] . ' ' . $name[3];
            }

            return $realName;
        }

    }
}
?>

<?php
die();
if ( ! defined( 'ABSPATH' ) ) { exit; }
class AEW_GESTOR {
    function __construct(){

    }


    function save($product) {
        global $wpdb;

        $data = array(
            'title' => $product['title'],
            'description_content' => $product['description'],
            'attributes' => serialize($product['attributes']),
            'features' => serialize($product['features']),
            'stock_attributes' => $product['stock_attributes'],
            'stock' => $product['stock'],
            'categoria' => $product['categoria'],
            'ae_id' => $product['ae_id'],
            'sku' => $product['sku'],
            'images' => serialize($product['images']),
            'medidas' => serialize($product['title']),
            'id_local' => $product['id']
        );
    }

    function create($product) {

        $pro = new stdClass();
        $pro->id = $product->get_id();
        $pro->name = $producto->get_name();
        $pro->description = $producto->get_description();

        //Features
        $features = get_post_meta($pro->id, '_product_attributes', true);
        foreach($features as $key => $value) {
            if($value['is_variation'] == "1") { continue; }
            
            if($value['is_taxonomy'] == "1") {
                $terminos = $producto->get_attribute( $key );
                $terminos = explode(",", $terminos);

                $term = array();
                if(count($terminos) > 1) {
                    
                    if($typesFeatures[$key] == "array") {
                        foreach($terminos as $t) {
                            $valueTerm = $featuresSelect[$key]['terms'][trim($t)]['key_ali'];
                            if($valueTerm != null) {
                                $term[] = $valueTerm;
                            }
                        }
                    }else{
                        //String
                        $term[] = $featuresSelect[$key]['terms'][trim($terminos)]['key_ali'];
                    }
                }else{
                    $term[] = $featuresSelect[$key]['terms'][trim($terminos[0])]['key_ali'];
                }
            }else{
                $term = $featuresSelect[$key]['terms'][$value['value']]['key_ali'];
            }
            
            $pro->features[] = array($featuresSelect[$key]['value_ali'] => $term);
            $pro->ean = '';
           
        }
        
    }
}
?>
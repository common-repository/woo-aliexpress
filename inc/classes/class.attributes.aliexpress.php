<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if(!class_exists('AEW_Attributes')) {
    class AEW_Attributes {

        private static $masterAttr = array();

        public static function get_attributes_mapping($categories){
        
            $argsProductos = array(
                'post_type' => 'product',
                'fields' => 'ids',
                'suppress_filters' => true,
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy'      => 'product_cat',
                        'field' => 'term_id',
                        'terms'         => $categories,
                        'operator'      => 'IN'
                    ),
                )
            );

            $productos = get_posts($argsProductos);
        
            //$attrsSelf = array();
            foreach ( $productos as $pro ){
                $product = wc_get_product($pro);
                $attributes = $product->get_attributes();
                
                foreach ( $attributes as $attribute ){
                    $attribute_data = $attribute->get_data();
                    
                    if($attribute_data['is_variation'] == 0) { continue; }
                    $attrKey = $attribute_data['name'];
                    
                    self::$masterAttr[$attrKey]['data'] = array(
                        'key_real' => $attrKey,
                        'category_id' => $categories
                    );
                    if(!isset(self::$masterAttr[$attrKey]['data']['value_real'])) {
                        if($attribute_data['is_taxonomy'] == 1) {
                            $nameAttr = wc_attribute_label($attrKey);
                            self::$masterAttr[$attrKey]['data']['personalizado'] = __('General','aliexpress');
                            self::$masterAttr[$attrKey]['data']['value_real'] = $nameAttr;
                        }else{
                            self::$masterAttr[$attrKey]['data']['value_real'] = $attrKey;
                            self::$masterAttr[$attrKey]['data']['personalizado'] = __('Custom','aliexpress');
                        }
                    }
                    
                
                    foreach($attribute_data['options'] as $keyData => $data) {
                        if(is_int($data)) {
                            $term = get_term_by('id',$data,$attrKey, ARRAY_A);
                            if(isset(self::$masterAttr[$attrKey]['values'])) {
                                if(in_array($term['name'], self::$masterAttr[$attrKey]['values'])) {
                                    continue;
                                }
                            }
                            
                            self::$masterAttr[$attrKey]['values'][] = $term['name'];
                        }else{
							if(isset(self::$masterAttr[$attrKey]['values'])) {
                                if(in_array($data, self::$masterAttr[$attrKey]['values'])) {
                                    continue;
                                }
                            }
                            
                            self::$masterAttr[$attrKey]['values'][] = $data;
						}
                    }
                }
            }
            $aliExpress_category_id = get_term_meta(intval($categories), 'aew_category_id', true);
            self::print_attributes_mapping($aliExpress_category_id, $categories);

            
        }

        public static function print_attributes_mapping($cat_aliexpress, $wooCategory){

            //Get Attr
            require_once(AEW_AE_PATH . 'AEFactory.php');
            $factory = new AEFactory(AEW_TOKEN);
            $options = $factory->get_sku_attributes_schema_values(intval($cat_aliexpress));
            // echo '<pre>' . print_r($options, true) . '</pre>';            
            $paramsSelect = self::get_mapping_attr($wooCategory);
            $attributos_posibles = array_keys($options);
            
            echo '<script>AEW_Attributes = '.json_encode($options).'</script>';

            echo '<form method="post">';
            echo '<input type="hidden" name="action" value="save_attrs" />';
            echo '<input type="hidden" name="category_id" value="'.$wooCategory.'" />';
            echo '<table style="width:100%" class="table_attributes" cellpadding="0" cellspacing="0">';
            $i = 0;
            foreach(self::$masterAttr as $attr => $terms) {

                $select_attributes = '';
                $valorSeleccionado = false;
                // AEW_MAIN::printDebug($terms);
                // die();
                $keyReal =  $terms['data']['key_real'];
                $valueReal = $terms['data']['value_real'];
                $datosGuardados = null;
                
                if(array_key_exists($keyReal,$paramsSelect)) {
                    $datosGuardados = $paramsSelect[$keyReal];
                    if(isset($datosGuardados['value_ali']) and isset($datosGuardados['id_reg'])) {
                        $valueReal = $datosGuardados['value_ali'];
                        echo '<input type="hidden" name="'.$wooCategory.'['.$i.'][id_reg]" value="'.$datosGuardados['id_reg'].'" />';
                    }
                }
                foreach($attributos_posibles as $attributo) {
                    
                    $select_attributes .= '<option value="'.$attributo.'" '.AEW_MAIN::check_strings($attributo, $valueReal, true).'>'.$attributo.'</option>';

                    if(AEW_MAIN::check_strings($attributo, $valueReal)) {
                        $valorSeleccionado = $attributo;
                    }
                }
                echo '<input type="hidden" name="'.$wooCategory.'['.$i.'][value_real]" value="'.$valueReal.'">';
                echo '<input type="hidden" name="'.$wooCategory.'['.$i.'][key_real]" value="'.$keyReal.'">';
                echo '<tr class="attr_name">
                        <td>'.$terms['data']['value_real'].' <span class="attr_type">'. $terms['data']['personalizado'] .'</span></td>
                        <td></td>
                        <td>
                            <select name="'.$wooCategory.'['.$i.'][value_ali]" class="change_aew_attr" data-key="'.$keyReal.'">
                                <option value="0">'.__('Choose Attribute', 'aliexpress').'</option>
                                '.$select_attributes.'
                            </select>
                        </td>
                        <td style="width:100px;text-align:center;">
                        <span class="tooltip tooltip_ae" title="'.__('Replace AliExpress value with a custom value','aliexpress').'">?</span> 
                        '.__('Use Alias','aliexpress').'</td>
                        <td style="width:200px;">
                        <span class="tooltip tooltip_ae" title="'.__('The Alias by which this attribute will be replaced in AliExpress, (Use Alias) must be marked','aliexpress').'">?</span> 
                        '.__('Alias','aliexpress').'</td>
                    </tr>'; 
                    if($terms['values']) {
                        $b=0;
                        // AEW_MAIN::printDebug($terms);
                        foreach($terms['values'] as $term) {

                            
                            if($datosGuardados != null and isset($datosGuardados['terms'][$term]) and array_key_exists($term,$datosGuardados['terms'])) {
                                $termSearch = $datosGuardados['terms'][$term]['key_ali'];
                                $select_terms = '';
                                if($valorSeleccionado != null) {
                                    $valueKeySelected = '';
                                    foreach($options[$valorSeleccionado] as $key => $attributo) {
                                        if(AEW_MAIN::check_strings($key, $termSearch)) {
                                            $valueKeySelected = $attributo;   
                                        }
                                        $select_terms .= '<option value="'.$key.'" '.AEW_MAIN::check_strings($key, $termSearch, true).'>'.$attributo.'</option>';
                                    }
                                }
                                $inputReg = '<input type="hidden" name="'.$wooCategory.'['.$i.'][terms]['.$term.'][id_reg]" value="'.$datosGuardados['terms'][$term]['id_reg'].'" />';
                                $inputValueAli = '<input type="hidden" name="'.$wooCategory.'['.$i.'][terms]['.$term.'][value_ali]" value="'.($valueKeySelected != '' ? $valueKeySelected : $datosGuardados['terms'][$term]['value_real']).'" />';
                            }else{
                                $select_terms = '';
                                $valueKeySelected = '';
                                if($valorSeleccionado != null) {
                                    
                                    foreach($options[$valorSeleccionado] as $key => $attributo) {
                                        if(AEW_MAIN::check_strings($attributo, $term)) {
                                            $valueKeySelected = $attributo;   
                                        }
                                        $select_terms .= '<option value="'.$key.'" '.AEW_MAIN::check_strings($attributo, $term, true).'>'.$attributo.'</option>';
                                    }
                                }
                                $inputReg = '';
                                if($valueKeySelected !== null or $valueKeySelected != '') {
                                    $valueTermSelected = $valueKeySelected;
                                }else{
                                    $valueTermSelected = '';
                                }
                                $inputValueAli = '<input type="hidden" name="'.$wooCategory.'['.$i.'][terms]['.$term.'][value_ali]" value="'.$valueTermSelected.'" />';
                            }
                            
                            $marcarCheck = '';
                            $displayAlias = '';
                            // AEW_MAIN::print($datosGuardados);
                            if($datosGuardados != null and isset($datosGuardados['terms'][$term])) {
                                if($datosGuardados['terms'][$term]['use_alias'] == "1") {
                                    $marcarCheck = 'checked="checked"';
                                    $displayAlias = 'style="display:block"';
                                }
                            }
                            if(isset($datosGuardados['terms'][$term]['value_alias'])) {
                                $stringAlias = $datosGuardados['terms'][$term]['value_alias'];
                            }else{
                                $stringAlias = $term;
                            }
                            
                            echo '<tr>
                            <td width="150px"></td>
                            <td>' . $term . ' ('.self::aew_get_count_product_by_attr($keyReal, $term, $wooCategory).')</td>
                            <td class="selects_terms_attr" data-key="'.$keyReal.'">
                                '.$inputReg.'
                                '.$inputValueAli.'
                                <select name="'.$wooCategory.'['.$i.'][terms]['.$term.'][value]" class="select_terms_'.$keyReal.' changeTextValue_Ali aew_check_this_values" data-real-name="'.$term.'" data-search-input="'.$wooCategory.'['.$i.'][terms]['.$term.']">
                                    <option value="0">'.__('Choose Term', 'aliexpress').'</option>
                                    '.$select_terms.'
                                </select>
                            </td>
                            <td style="text-align:center">
                                <input type="checkbox" class="useAlias" term="'.$term.'" name="'.$wooCategory.'['.$i.'][terms]['.$term.'][use_alias]" '.$marcarCheck.' value="1" />
                            </td>
                            <td>
                                <input type="text" class="text_alias_term" term="'.$term.'" name="'.$wooCategory.'['.$i.'][terms]['.$term.'][value_alias]" '.$displayAlias.' value="'.$stringAlias.'" />
                            </td>
                            </tr>';
                            $b++;
                        };
                    } //END IF TERMS
                    $i++;
            }

            
            echo '</table>';
            echo '<input type="submit" name="submit" class="ae_button endForm" value="'.__('Save Attributes Mapping','aliexpress').'">';
            echo '</form>';
            echo '<script>jQuery(document).ready(function($) { awe_check_terms(\'pa_color\') });</script>';
        }

        public static function print_term_attribute($terminos, $key){
            if($terminos == null) { return;}
            foreach($terminos as $term) {
                
                if(!in_array(trim($term), self::$masterAttr[$key]['values'])) {
                array_push(self::$masterAttr[$key]['values'], trim($term));
                }

            }
        }
        
        public static function get_terms_attributes($attr_principal) {
            $terms = get_terms(array(
                'taxonomy' => $attr_principal,
                'hide_empty' => false,
            ));
            //TAXONOMY ATTRIBUTES
            if( $terms ) :
                foreach( $terms as $term ) :
                    if(!in_array(trim($term->name), self::$masterAttr[$attr_principal]['values'])) {
                        array_push(self::$masterAttr[$attr_principal]['values'], trim($term->name));
                    }

                endforeach;
            endif;
        }

        public static function save_attrs_mapping($data) {
            global $wpdb;
            //AEW_MAIN::printDebug($data);
            //Guardar Atributo
            $ci = $data['category_id'];
            if(isset($data[$ci])) {
                foreach($data[$ci] as $key => $attr) {
                    if($attr['value_ali'] == "0") { 
                        if(isset($attr['id_reg'])) {
                            $wpdb->delete( $wpdb->prefix.'aew_mapping_attributes', array('id_mapping_attr' => intval($attr['id_reg'])));
                            $wpdb->delete( $wpdb->prefix.'aew_mapping_attributes', array('id_ali' => intval($attr['id_reg'])));
                        }
                        continue;
                    }
                    $saveData = array(
                            'category_id' => $ci,
                            'key_real' => $attr['key_real'],
                            'value_real' => $attr['value_real'],
                            'value_ali' => $attr['value_ali'],
                            'key_ali' => '',
                            'attr_type' => 'attr',
                        );
                        if(isset($attr['id_reg'])) {
                            $idAttr = $attr['id_reg'];
                            $wpdb->update( $wpdb->prefix.'aew_mapping_attributes', $saveData, array('id_mapping_attr' => $attr['id_reg']));
                        }else{
                            $idAttr = $wpdb->insert( $wpdb->prefix.'aew_mapping_attributes', $saveData);
                        }
    
                        //Guardar Terminos
                        foreach($attr['terms'] as $keyTerm => $valueTerm) {
                            if($valueTerm['value'] == "0") { 
                            if(isset($attr['id_reg'])) {
                                $wpdb->delete( $wpdb->prefix.'aew_mapping_attributes', array('id_mapping_attr' => intval($valueTerm['id_reg'])));
                            }
                            continue; }
                            $saveData = array(
                                'category_id' => $ci,
                                'key_real' => $keyTerm,
                                'key_ali' => $valueTerm['value'],
                                'value_ali' => $attr['key_real'],
                                'value_real' => $valueTerm['value_ali'],
                                'attr_type' => 'term',
                                'id_ali' => $idAttr
                            );
                            if(isset($valueTerm['use_alias']) and $valueTerm['use_alias'] == "1") {
                                $saveData['value_alias'] = $valueTerm['value_alias'];
                                $saveData['use_alias'] = $valueTerm['use_alias'];
                            }else{
                                $saveData['value_alias'] = '';
                                $saveData['use_alias'] = 0;
                            }
                            if(isset($valueTerm['id_reg'])) {
                                $wpdb->update( $wpdb->prefix.'aew_mapping_attributes', $saveData, array('id_mapping_attr' => $valueTerm['id_reg']));
                            }else{
                                $wpdb->insert( $wpdb->prefix.'aew_mapping_attributes', $saveData);
                            }
                        }
                    }
            }

        }

        public static function get_mapping_attr($category_id) {
            global $wpdb;
            $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}aew_mapping_attributes WHERE category_id = $category_id", ARRAY_A  );
            $res = array();
            foreach($results as $r) {
                if($r['attr_type'] == 'attr') {
                    $res[$r['key_real']] = array();
                    $res[$r['key_real']]['value_ali'] = $r['value_ali'];
                    $res[$r['key_real']]['value_real'] = $r['value_real'];
                    $res[$r['key_real']]['id_reg'] = $r['id_mapping_attr'];
                }else{
                    if($r['value_ali'] == '') {
                        continue;
                    }
                    $res[$r['value_ali']]['terms'][$r['key_real']] = array(
                        'key_ali' => $r['key_ali'],
                        'id_reg' => $r['id_mapping_attr'],
                        'value_real' => $r['value_real'],
                        'use_alias' => $r['use_alias'],
                        'value_alias' => $r['value_alias']
                    );
                }
            }
    //         echo '<pre>'.json_encode($res, JSON_PRETTY_PRINT).'</pre>';
            return $res;
        }

        public static function get_map_attributes($category) {
            global $wpdb;
            $res = array();
            $query = "SELECT * FROM ".$wpdb->prefix."aew_mapping_attributes WHERE category_id = '$category'";
            $zones = $wpdb->get_results($query);
            foreach($zones as $zone) {
                
                
            }
        }

        public static function get_meta_values( $key = '', $ids ) {
            global $wpdb;
        
            if( empty( $key ) or empty($ids) )
                return;

                $sql = "
                SELECT pm.meta_value FROM {$wpdb->postmeta} pm
                LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_key = %s 
                AND p.post_type = %s
                AND pm.post_id IN (".implode(', ', array_fill(0, count($ids), '%d')).")
                ";

                $genericos = array($key, 'product');
                $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $genericos, $ids));

                $r = $wpdb->get_col($query);
                //return $query;
            return $r;
        }

        public static function aew_get_count_product_by_attr($attr, $term, $category = false) {
            $termSlug = get_term_by('name', $term, $attr, 'ARRAY_A');
            $args = [];
            if($termSlug) {
                $args = array( 
                    'post_type'             => 'product',
                    'post_status'           => 'publish',
                    'ignore_sticky_posts'   => 1,
                    'tax_query'             => array(
                        'relation'          => 'AND',
                        array(
                            'taxonomy'      => 'product_cat',
                            'field'         => 'term_id',
                            'terms'         => $category,
                            'operator'      => 'IN'
                        ),
                        array(
                            'taxonomy'      => $attr,
                            'terms'         => $termSlug['slug'],
                            'field'         => 'slug',
                        )                   
                    )
                );
            }
            return count (wc_get_products($args));
        }
    }
}
?>
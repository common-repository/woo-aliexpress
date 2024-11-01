<?php

if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('AWE_Features')) {

    class AEW_Features {

        private static $masterAttr = array();
        public static function get_features_mapping($categories)
        {

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
            foreach ($productos as $pro) {
                $product = wc_get_product($pro);
                $attributes = $product->get_attributes();

                foreach ($attributes as $attribute) {
                    $attribute_data = $attribute->get_data();
                    //$attrsSelf[$attribute_data['name']] = $attribute_data;

                    if ($attribute_data['is_variation'] == 1) {
                        continue;
                    }
                    $attrKey = $attribute_data['name'];
                    // if(array_key_exists($attrKey, self::$masterAttr)) {
                    //     continue;
                    // }
                    self::$masterAttr[$attrKey]['data'] = array(
                        'key_real' => $attrKey,
                        'category_id' => $categories
                    );
                    if (!isset(self::$masterAttr[$attrKey]['data']['value_real'])) {
                        if ($attribute_data['is_taxonomy'] == 1) {
                            $nameAttr = wc_attribute_label($attrKey);
                            self::$masterAttr[$attrKey]['data']['personalizado'] = __('General', 'aliexpress');
                            self::$masterAttr[$attrKey]['data']['value_real'] = $nameAttr;
                        } else {
                            self::$masterAttr[$attrKey]['data']['value_real'] = $attrKey;
                            self::$masterAttr[$attrKey]['data']['personalizado'] = __('Custom', 'aliexpress');
                        }
                    }


                    foreach ($attribute_data['options'] as $keyData => $data) {
                        if (is_int($data)) {
                            $term = get_term_by('id', $data, $attrKey, ARRAY_A);
                            if (isset(self::$masterAttr[$attrKey]['values'])) {
                                if (in_array($term['name'], self::$masterAttr[$attrKey]['values'])) {
                                    continue;
                                }
                            }

                            self::$masterAttr[$attrKey]['values'][] = $term['name'];
                        }else{
                            if (isset(self::$masterAttr[$attrKey]['values'])) {
                                if (in_array($data, self::$masterAttr[$attrKey]['values'])) {
                                    continue;
                                }
                            }

                            self::$masterAttr[$attrKey]['values'][] = $data;
                        }
                    }
                }
            }

            $aliExpress_category_id = get_term_meta(intval($categories), 'aew_category_id', true);

            self::print_features_mapping($aliExpress_category_id, $categories);
        }

        public static function print_term_attribute($terminos, $key)
        {
            if ($terminos == null) {
                return;
            }
            foreach ($terminos as $term) {

                if (!in_array(trim($term), self::$masterAttr[$key]['values'])) {

                    array_push(self::$masterAttr[$key]['values'], trim($term));
                }
            }
        }

        public static function print_features_mapping($cat_aliexpress, $wooCategory)
        {

            //Get Attr
            require_once(AEW_AE_PATH . 'AEFactory.php');
            // require_once(AEW_AE_PATH . 'AECategory.php');
            $factory = new AEFactory(AEW_TOKEN);
            // $category = new AECategory($cat_aliexpress, $factory);

            $options = $factory->get_category_attributes_values(intval($cat_aliexpress));

            $AtributosObligatorios = $factory->get_category_required_attributes(intval($cat_aliexpress));

            $free = $factory->get_category_free_attribute_value(intval($cat_aliexpress));

            // AEW_MAIN::print($AtributosObligatorios);

            $paramsSelect = self::get_mapping_features($wooCategory);

            $attributos_posibles = array_keys($options);
            echo '<script>AEW_Attributes = ' . json_encode($options) . '</script>';
            echo '<form method="post">';
            echo '<div class="params_required">';
            // AEW_MAIN::printDebug($free['Type']);
            if ($AtributosObligatorios) {
                echo '<h4>' . __('This category have required features', 'aliexpress') . '</h4>';
                foreach ($AtributosObligatorios as $feature) {
                    $selectedDefault = '<option value="0">' . __('No Default', 'aliexpress') . '</option>';

                    $selectOption = false;

                    $term = self::get_features_default($wooCategory, $feature);

                    foreach ($options[$feature] as $key => $id) {

                        $selected = '';

                        if (!$selectOption) {



                            if ($term && $term['value'] == $id) {

                                $selectOption = true;

                                $selected = 'selected="selected"';
                            }
                        }

                        $selectedDefault .= '<option value="' . $id . '" ' . $selected . '>' . $key . '</option>';
                    }

                    // AEW_MAIN::printDebug($free);

                    if (isset($free[$feature]) and isset($free[$feature]['title']) == 'value') {



                        $selectDefaultOrInput = '';

                        $inputFreeValue = '<input type="text" name="defaultInputFeatures[' . $feature . ']" value="' . $term['value'] . '" />';
                    } else {

                        $selectDefaultOrInput = '<select data-feature="' . $feature . '" class="featureDefaultSelect" name="defaultFeatures[' . $feature . ']">' . $selectedDefault . '</select>';

                        if ($term && $term['value'] == '4') {

                            $inputFreeValue = '<input type="text" name="defaultInputFeaturesCustom[' . $feature . ']" value="' . $term['customValue'] . '" />';
                        } else {

                            $inputFreeValue = '';
                        }
                    }

                    echo '<div class="feature" data-feature="' . $feature . '">' . $feature . '<br>' . $selectDefaultOrInput . ' ' . $inputFreeValue . '</div>';
                }
            } else {

                echo '<p>' . __('No features required', 'aliexpress') . '</p>';
            }

            echo '</div>';

            echo '<input type="hidden" name="action" value="save_features" />';

            echo '<input type="hidden" name="category_id" value="' . $wooCategory . '" />';

            echo '<table style="width:100%" class="table_attributes" cellpadding="0" cellspacing="0">';

            $i = 0;

            foreach (self::$masterAttr as $attr => $terms) {



                $select_attributes = '';

                $valorSeleccionado = false;





                $keyReal =  $terms['data']['key_real'];

                $valueReal = $terms['data']['value_real'];



                $datosGuardados = null;



                if (array_key_exists($keyReal, $paramsSelect)) {

                    $datosGuardados = $paramsSelect[$keyReal];

                    // AEW_MAIN::print($datosGuardados);

                    $valueReal = $datosGuardados['value_ali'];

                    echo '<input type="hidden" name="' . $wooCategory . '[' . $i . '][id_reg]" value="' . $datosGuardados['id_reg'] . '" />';
                }

                echo '<input type="hidden" name="' . $wooCategory . '[' . $i . '][value_real]" value="' . $valueReal . '">';

                echo '<input type="hidden" name="' . $wooCategory . '[' . $i . '][key_real]" value="' . $keyReal . '">';

                foreach ($attributos_posibles as $attributo) {

                    $select_attributes .= '<option value="' . $attributo . '" ' . AEW_MAIN::check_strings($attributo, $valueReal, true) . '>' . $attributo . '</option>';

                    if (AEW_MAIN::check_strings($attributo, $valueReal)) {

                        $valorSeleccionado = $attributo;
                    }
                }



                echo '<tr class="attr_name">

                        <td>' . $terms['data']['value_real'] . '</td>

                        <td></td>

                        <td>

                            <select name="' . $wooCategory . '[' . $i . '][value_ali]" class="change_aew_feature" data-key="' . $terms['data']['key_real'] . '">

                                <option value="0">' . __('Choose Feature', 'aliexpress') . '</option>

                                ' . $select_attributes . '

                            </select>

                        </td>

                    </tr>';


                if (isset($terms['values'])) {

                    //$valorSeleccionado = null;

                    foreach ($terms['values'] as $term) {

                        $termSelect = null;



                        // AEW_MAIN::printDebug($datosGuardados);



                        if (isset($datosGuardados['terms']) and array_key_exists($term, $datosGuardados['terms'])) {

                            $termSearch = $datosGuardados['terms'][$term]['key_ali'];

                            $select_terms = '';

                            if ($valorSeleccionado != null) {

                                foreach ($options[$valorSeleccionado] as $key => $attributo) {

                                    if ($key == 'title') {

                                        //El valor debe ser un alias

                                        $select_terms = 'ND';
                                        break;
                                    }

                                    if (AEW_MAIN::check_strings($attributo, $termSearch)) {

                                        $termSelect = $attributo;
                                    }

                                    $select_terms .= '<option value="' . $attributo . '" ' . AEW_MAIN::check_strings($attributo, $termSearch, true) . '>' . $key . '</option>';
                                }
                            }

                            $inputReg = '<input type="hidden" name="' . $wooCategory . '[' . $i . '][terms][' . $term . '][id_reg]" value="' . $datosGuardados['terms'][$term]['id_reg'] . '" />';
                        } else {

                            $select_terms = '';

                            if ($valorSeleccionado != null) {

                                foreach ($options[$valorSeleccionado] as $key => $attributo) {

                                    if ($key == 'title') {

                                        //El valor debe ser un alias

                                        $select_terms = 'ND';
                                        break;
                                    }

                                    if (AEW_MAIN::check_strings($key, $term)) {

                                        $termSelect = $attributo;
                                    }

                                    $select_terms .= '<option value="' . $attributo . '" ' . AEW_MAIN::check_strings($key, $term, true) . '>' . $key . '</option>';
                                }
                            }

                            $inputReg = '';
                        }





                        echo '<tr>

                            <td width="150px"></td>

                            <td>' . $term . '</td>

                            <td class="selects_terms_attr" data-key="' . $terms['data']['key_real'] . '">

                                ' . $inputReg;

                        echo '<select name="' . $wooCategory . '[' . $i . '][terms][' . $term . '][value]" class="select_term_feature select_terms_' . $terms['data']['key_real'] . ' aew_check_this_values" data-real-name="' . $term . '">';

                        if ($select_terms != 'ND') {

                            echo '<option value="0">' . __('Choose Option', 'aliexpress') . '</option>';

                            echo $select_terms;
                        } else {

                            echo '<option value="AliExpressAlias">' . __('Use Alias', 'aliexpress') . '</option>';
                        }

                        echo '</select>';

                        if ($termSelect != null and $termSelect == '4') {

                            echo '<span class="explicationAliExpressUseValue">Use Value</span>';
                        }



                        echo '</td></tr>';
                    };
                } //END IF TERMS

                $i++;
            }







            echo '</table>';

            echo '<input type="submit" name="submit" class="ae_button endForm" value="' . __('Save Features Mapping', 'aliexpress') . '">';

            echo '</form>';
        }



        public static function save_features_mapping($data)
        {

            global $wpdb;



            //Guardar Atributo

            self::save_default_features($data);

            $ci = $data['category_id'];

            if (isset($data[$ci])) {

                // AEW_MAIN::printDebug($data[$ci]);

                foreach ($data[$ci] as $key => $attr) {

                    if ($attr['value_ali'] == "0") {

                        if (isset($attr['id_reg'])) {

                            $wpdb->delete($wpdb->prefix . 'aew_mapping_features', array('id_mapping_features' => intval($attr['id_reg'])));

                            $wpdb->delete($wpdb->prefix . 'aew_mapping_features', array('id_ali' => intval($attr['id_reg'])));
                        }

                        continue;
                    }

                    $saveData = array(

                        'category_id' => $ci,

                        'key_real' => $attr['key_real'],

                        'value_real' => $attr['value_real'],

                        'value_ali' => $attr['value_ali'],

                        'key_ali' => '',

                        'attr_type' => 'attr'

                    );

                    if (isset($attr['id_reg'])) {

                        $wpdb->update($wpdb->prefix . 'aew_mapping_features', $saveData, array('id_mapping_features' => $attr['id_reg']));

                        $idAttr = $attr['id_reg'];
                    } else {

                        $wpdb->insert($wpdb->prefix . 'aew_mapping_features', $saveData);

                        $idAttr = $wpdb->insert_id;
                    }



                    //Guardar Terminos

                    foreach ($attr['terms'] as $keyTerm => $valueTerm) {

                        if (isset($valueTerm['value']) and $valueTerm['value'] == '0') {

                            if (isset($attr['id_reg'])) {

                                $wpdb->delete($wpdb->prefix . 'aew_mapping_features', array('id_mapping_features' => intval($valueTerm['id_reg'])));
                            }

                            continue;
                        }

                        $saveData = array(

                            'category_id' => $ci,

                            'key_real' => $keyTerm,

                            'key_ali' => $valueTerm['value'],

                            'value_ali' => $attr['key_real'],

                            'attr_type' => 'term',

                        );

                        if ($valueTerm['value'] == '4') {

                            $saveData['use_alias'] = '1';
                        }

                        if (isset($valueTerm['id_reg'])) {

                            $wpdb->update($wpdb->prefix . 'aew_mapping_features', $saveData, array('id_mapping_features' => $valueTerm['id_reg']));
                        } else {

                            $saveData['id_ali'] = $idAttr;

                            $wpdb->insert($wpdb->prefix . 'aew_mapping_features', $saveData);
                        }
                    }
                }
            }





            // echo '<pre>' . print_r($saveData, true) . '</pre>';

        }



        public static function get_terms_attributes($attr_principal)
        {

            $terms = get_terms(array(

                'taxonomy' => $attr_principal,

                'hide_empty' => false,

            ));

            //TAXONOMY ATTRIBUTES

            if ($terms) :

                foreach ($terms as $term) :

                    if (!in_array(trim($term->name), self::$masterAttr[$attr_principal]['values'])) {

                        array_push(self::$masterAttr[$attr_principal]['values'], trim($term->name));
                    }



                endforeach;

            endif;
        }



        public static function get_mapping_features($category_id)
        {

            global $wpdb;

            $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aew_mapping_features WHERE category_id = $category_id", ARRAY_A);

            $res = array();

            foreach ($results as $r) {

                if ($r['attr_type'] == 'attr') {

                    $res[$r['key_real']] = array();

                    $res[$r['key_real']]['value_ali'] = $r['value_ali'];

                    $res[$r['key_real']]['value_real'] = $r['value_real'];

                    $res[$r['key_real']]['id_reg'] = $r['id_mapping_features'];
                } else {

                    $res[$r['value_ali']]['terms'][$r['key_real']] = array(

                        'key_ali' => $r['key_ali'],

                        'id_reg' => $r['id_mapping_features'],

                        'key_real' => $r['key_real'],

                        'use_alias' => intval($r['use_alias'])

                    );
                }
            }

            // echo '<pre>'.json_encode($res, JSON_PRETTY_PRINT).'</pre>';

            return $res;
        }

        public static function get_meta_values($key = '', $ids)
        {

            global $wpdb;



            if (empty($key) or empty($ids))

                return;



            $sql = "

                SELECT pm.meta_value FROM {$wpdb->postmeta} pm

                LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id

                WHERE pm.meta_key = %s 

                AND p.post_type = %s

                AND pm.post_id IN (" . implode(', ', array_fill(0, count($ids), '%d')) . ")

                ";



            $genericos = array($key, 'product');

            $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $genericos, $ids));



            $r = $wpdb->get_col($query);

            //return $query;

            return $r;
        }





        public static function save_default_features($data)
        {

            global $wpdb;

            if (isset($data['defaultInputFeatures'])) {

                $datosDefault = array_merge($data['defaultFeatures'], $data['defaultInputFeatures']);
            } elseif (isset($data['defaultFeatures'])) {

                $datosDefault = $data['defaultFeatures'];
            } else {

                $datosDefault = false;
            }

            if ($datosDefault) {

                foreach ($datosDefault as $key => $value) {

                    if ($value == '0') {

                        $wpdb->delete($wpdb->prefix . 'aew_data_default', array(

                            'key_data' => 'feature-default-' . $key,

                            'data_id' => intval($data['category_id'])

                        ));

                        continue;
                    }

                    $id_default = $wpdb->get_var(

                        $wpdb->prepare(

                            "SELECT id_default FROM " . $wpdb->prefix . "aew_data_default

                            WHERE data_id = %d AND key_data = %s LIMIT 1",

                            intval($data['category_id']),
                            'feature-default-' . $key

                        )

                    );

                    $saveData = array(

                        'data_id' => intval($data['category_id']),

                        'key_data' => 'feature-default-' . $key,

                        'value_data' => $value,

                        'customValue' => isset($data['defaultInputFeaturesCustom'][$key]) ? $data['defaultInputFeaturesCustom'][$key] : NULL

                    );



                    if ($id_default > 0) {

                        $wpdb->update($wpdb->prefix . 'aew_data_default', $saveData, array('id_default' => $id_default));
                    } else {

                        $wpdb->insert($wpdb->prefix . 'aew_data_default', $saveData);
                    }
                }
            }
        }



        public static function get_features_default($category_id, $key) {

            global $wpdb;

            $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aew_data_default WHERE data_id = $category_id and key_data LIKE 'feature-default-$key'", ARRAY_A);

            if (!$results) {
                return false;
            }

            foreach ($results as $r) {

                $res = array(

                    'key' => str_replace('feature-default-', '', $r['key_data']),

                    'value' => $r['value_data'],

                    'customValue' => $r['customValue']

                );
            }

            return $res;
        }
    }
}
?>
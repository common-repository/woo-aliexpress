<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class AEW_Category  {
    private static function bc_get_wp_editor( $content = '', $editor_id, $options = array() ) {
        ob_start();
     
        wp_editor( $content, $editor_id, $options );
     
        $temp = ob_get_clean();
        $temp .= \_WP_Editors::enqueue_scripts();
        //$temp .= print_footer_scripts();
        $temp .= \_WP_Editors::editor_js();
     
        return $temp;
    }
    public static function get_categories_description($parentID = 0) {
        $page = isset($_REQUEST['paged']) ? sanitize_text_field($_REQUEST['paged']) : 1;
        $per_page = 5;
        $offset = ( $page-1 ) * $per_page;
        $args = array(
            'taxonomy'   => "product_cat",
            'hide_empty' => false,
            'parent' => $parentID,
            'number' => $per_page,
            'offset' => $offset
        );
        $next = get_terms($args);
        
        $paginacion = '';
      if( $next ) :
      if($parentID==0) {
        $total_terms = wp_count_terms( 'product_cat', array('parent' => $parentID) );
        $pages = ceil($total_terms/$per_page);

        // if there's more than one page
        
        if( $pages > 1 ):
            $paginacion .= '<ul class="pagination">';

            for ($pagecount=1; $pagecount <= $pages; $pagecount++) {
                $active = $page == $pagecount ? 'class="active"' : '';
                $paginacion .=  '<li><a '.$active.' href="admin.php?page=aliexpress-general-options&tab=desription_categories&paged='.$pagecount.'">'.$pagecount.'</a></li>';
            }

            $paginacion .= '</ul>';

            echo $paginacion;
        endif;
          echo '<form method="post">';
          echo '<input type="hidden" name="action" value="save_description_category" />';
          echo '<table style="width:100%" class="aew_table" cellpadding="0" cellspacing="0">';
            echo '<tr>
              <td>'.__('WooCommerce Category', 'aliexpress').'</td>
              <td>'.__('Description', 'aliexpress').'</td>
            </tr>';
            $classChild = '';
        }else{
          $classChild = "category_child";
        }
        
        foreach( $next as $cat ) :
            $categoryDescription = get_term_meta($cat->term_id,'aew_category_description', true);
            echo '<tr class="line_category '.$classChild.'" data-cat="'.$cat->term_id.'">
            <td><strong>' . $cat->name . '</strong></td>
            <td class="aliexpress_editor_insert">'.self::bc_get_wp_editor($categoryDescription, 'aliexpress_description'.$cat->term_id, array('textarea_name' => 'aliexpress_description['.$cat->term_id.']')).'</td></tr>';
            self::get_categories_description( $cat->term_id );
            endforeach;
            if($parentID==0) {
            echo '</table>
                <input type="submit" class="ae_button endForm" value="'.__('Save Categories Description','aliexpress').'" />
            </form>';  
            }
        endif;
        echo $paginacion;
    }

    public static function get_select_shipping_templates_category($st, $optSelected = 0, $nameInput = 'shippingTemplateSelected') {
        $shippingTemplateSelect = '';
        if($st) {
            $options_shipping_template = '';
            foreach ($st as $key => $item) {
                if($optSelected == $key ) {
                    $selected = 'selected="selected"';
                }else {
                    $selected = '';
                }
                $options_shipping_template .= '<option value="'.$key.'" '.$selected.'>'.$item['template_name'].'</option>';
            }
            $shippingTemplateSelect = '<select name="'.$nameInput.'">
                <option value="0">'.__('Default', 'aliexpress').'</option>
                '.$options_shipping_template.'
            </select>';
        }

        return $shippingTemplateSelect;
    }

    public static function get_category_mapped($ids = 0) {
        $args = array(
            'taxonomy' => 'product_cat',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'hide_empty' => false,
            'hierarchical' => true,
            'meta_query' => [
            [
              'key' => 'aew_category_id',
              'compare' => '>',
              'value' => '0'
            ],[
                'key' => 'aew_category_id',
                'compare' => '!=',
                'value' => ''
            ]],
        );
        $next = get_terms($args);

        return $next;
    }

    public static function get_category_parents( $category_id, $link = false, $separator = '/', $nicename = false) {     
        $format = $nicename ? 'slug' : 'name';
     
        $args = array(
            'separator' => $separator,
            'link'      => $link,
            'format'    => $format,
        );
     
        return get_term_parents_list( $category_id, 'product_cat', $args );
    }

    public static function set_category_default($destino) {
        global $wpdb;
        
        $args = array(
            'post_type' => array('product'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy'      => 'product_cat',
                    'field'         => 'term_id',
                    'terms'         => $destino,
                    'operator'      => 'IN'
                ),
            ),
            // 'meta_query' => array(
            //     array(
            //         'key' => '_aew_product_id',
            //         'compare' => '!=',
            //         'value' => ''
            //     )
            // )
        );

        $success = [];
        $productos = get_posts($args);

        foreach($productos as $pro) {
            $result = update_post_meta($pro, '_aew_default_category_id', $destino);
            $success[$pro] = $result;

        }

        return array(
            'products' => $productos,
            'success' => $success
        );

    }

    public static function get_categories_tree( $next ) {
        if(AEW_TOKEN == '') {
            echo __('Your token is no set, please contact with support service.','aliexpress');
            return;
        }
      
        echo '<form method="post">';
        echo '<input type="hidden" name="action" value="save_categories" />';
        echo '<table style="width:100%" class="aew_table categories_mapping_table" cellpadding="0" cellspacing="0">';
            echo '<tr>
            <td>'.__('WooCommerce Category', 'aliexpress').'</td>
            <td>'.__('AliExpress Category', 'aliexpress').'</td>
            <td width="30px">
            <span class="tooltip tooltip_ae" title="'.__('Add a percentage to the price when uploading products to AliExpress','aliexpress').'">?</span> 
            '.__('Add Percentage to Price', 'aliexpress').'</td>
            <td width="30px">
            <span class="tooltip tooltip_ae" title="'.__('Add a fixed amount the price when uploading products to AliExpress','aliexpress').'">?</span> 
            '.__('Add fixed amount', 'aliexpress').'</td>
            <td>'.__('Size Product default', 'aliexpress').'
                <span class="min-text">'.__('Width (cm), Length (cm), Height (cm), Weight (kg)','aliexpress').'</span>
            </td>
            <td style="width:40px">'.__('Preparation Days', 'aliexpress').'</td>
            <td>'.__('Default Group', 'aliexpress').'</td>
            <td>'.__('Shipping template', 'aliexpress').'</td>
            <td>
            <span class="tooltip tooltip_ae" title="'.__('Keywords for Title Product','aliexpress').'">?</span> 
            '.__('Keywords', 'aliexpress').'</td>
            <td width="30px">
            <span class="tooltip tooltip_ae" title="'.__('Prepend Brand name to title product','aliexpress').'">?</span> 
            '.__('Add Brand', 'aliexpress').'</td>
            <td>'.__('AE Category', 'aliexpress').'</td>
            <td>'.__('Unlink', 'aliexpress').'</td>
            </tr>';
        if( $next ) :
        
            $factory = new AEFactory(AEW_TOKEN);
            $st = $factory->get_shipping_templates();
            $groups = $factory->wrapper->get_product_groups();

            foreach( $next as $cat ) :
                $tagsCategory = '';
                $checkedBrandAE = '';
                $remoteCategory = get_term_meta($cat->term_id,'aew_category_name', true);
                $category_fee = get_term_meta($cat->term_id,'aew_category_fee', true);
                $fixed_amount = get_term_meta($cat->term_id,'aew_fixed_amount', true);
                if($remoteCategory and $remoteCategory != '') {
                    $nameOfAliExpress = $remoteCategory;
                }else{
                    $nameOfAliExpress = '<a href="javascript:void(0)" class="aew_get_category">'.__('Suggest Category', 'aliexpress').'</a>';
                }
                $namesSeparator = self::get_category_parents($cat->term_id, false, ' / ');
                echo '<tr class="line_categor" data-cat="'.$cat->term_id.'">
                <td><strong>' . substr($namesSeparator,0, -3) . '</strong></td>
                <td class="aliexpress-name">'.$nameOfAliExpress.'</td>
                <td class="category_fee">';
            
                    if($nameOfAliExpress != ''){
                        echo '<input type="text" size="5" name="feeCategory['.$cat->term_id.']" value="'.($category_fee ?: '').'" />';

                        
                    }
                echo '</td><td class="fixed_amount">';
            
                if($nameOfAliExpress != ''){
                    echo '<input type="text" size="5" name="fixedAmount['.$cat->term_id.']" value="'.($fixed_amount ?: '').'" />';

                    
                }
            echo '</td><td class="medidas">';
                    $medidasCategoria = self::get_medidas_categoria($cat->term_id);
                    if($nameOfAliExpress != ''){
                        echo '<input type="text" size="3" name="medidas['.$cat->term_id.'][width]" value="'.$medidasCategoria->width.'" placeholder="'.__('Width','aliexpress').'" />';
                        echo '<input type="text" size="3" name="medidas['.$cat->term_id.'][length]" value="'.$medidasCategoria->length.'" placeholder="'.__('Length','aliexpress').'" />';
                        echo '<input type="text" size="3" name="medidas['.$cat->term_id.'][height]" value="'.$medidasCategoria->height.'" placeholder="'.__('Height','aliexpress').'" />';
                        echo '<input type="text" size="3" name="medidas['.$cat->term_id.'][weight]" value="'.$medidasCategoria->weight.'" placeholder="'.__('Weight','aliexpress').'" />';
                    }

                    $preparationDays = get_term_meta($cat->term_id, 'aew_category_preparation', true);
                echo '</td>';
                echo '<td>';
                if($nameOfAliExpress != ''){
                    echo '<input type="text" name="preparation['.$cat->term_id.']" value="'.$preparationDays.'" size="3"/>';
                }
                echo '</td><td>';
                if($nameOfAliExpress != ''){
                    $checkedBrandAE = get_term_meta($cat->term_id, 'aliexpress_add_brand', true) == '1' ? 'checked="checked"' : '';
                    $tagsCategory = get_term_meta($cat->term_id,'aliexpress_tags', true);
                    $groupSelected = get_term_meta($cat->term_id,'aew_group_category', true);
                    echo AEW_MAIN::select_group_category($groups,$groupSelected, "aew_group_select[".$cat->term_id."]");
                }
                echo '</td><td>';
                $shippingTemplateCategory = get_term_meta($cat->term_id,'aew_shipping_template_category', true);
                if($nameOfAliExpress != ''){
                    echo self::get_select_shipping_templates_category($st, $shippingTemplateCategory, "shippingTemplateSelected[".$cat->term_id."]");
                }
                echo '</td>
                <td>';
                if($nameOfAliExpress != ''){

                echo '<input type="text" name="aliexpress_tags['.$cat->term_id.']" value="'.$tagsCategory.'" />';
                }
            
                echo ' </td><td>';
                if($nameOfAliExpress != ''){

                echo '<input type="checkbox" name="aliexpress_add_brand['.$cat->term_id.']" value="1" '.$checkedBrandAE.' />';
                }
                echo '</td>
                
                <td class="aliexpress_category">
                <input type="hidden" name="aliexpress-category'.$cat->term_id.'" value="" />
                <button type="button" class="ae_button button_category_aliexpress" data-id="'.$cat->term_id.'">'.__('Select', 'aliexpress').'</button>
            </td><td class="unlink-button">';
                    if($nameOfAliExpress != '') {
                        echo '<a class="removeCategoryAliExpress" data-id="'.$cat->term_id.'">'.__('Unlink','aliexpress').'</a>';
                    }
            echo '</td></tr>';
            //   self::get_categories_tree( $cat->term_id );
            endforeach;
        endif;
        echo '</table>
            <input type="submit" class="ae_button endForm" value="'.__('Save Categories','aliexpress').'" />
        </form>';  
      
       
      }
      public static function save_default_sizes($data, $categoryID){
        global $wpdb;
            $res = array(
                'width' => $data['width'],
                'length' => $data['length'],
                'height' => $data['height'],
                'weight' => $data['weight'],
            );
            if($res['width'] == 0 or $res['width'] == '') { 
                $wpdb->delete( $wpdb->prefix.'aew_data_default', array(
                    'key_data' => 'size-default',
                    'data_id' => $categoryID
                ));
                return;
            }
            
            $id_default = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id_default FROM " . $wpdb->prefix . "aew_data_default
                    WHERE data_id = %d AND key_data = %s LIMIT 1",
                    intval($categoryID), 'size-default'
                    )
            );
                
            $saveData = array(
                'data_id' => intval($categoryID),
                'key_data' => 'size-default',
                'value_data' => json_encode($res)
            );
            if($id_default > 0) {
                $wpdb->update( $wpdb->prefix.'aew_data_default', $saveData, array('id_default' => $id_default));
            }else{
                $wpdb->insert( $wpdb->prefix.'aew_data_default', $saveData);
            }

    }

      public static function get_medidas_categoria($categoryID) {
        global $wpdb;
        $results = $wpdb->get_var( 
            $wpdb->prepare("SELECT value_data FROM {$wpdb->prefix}aew_data_default WHERE data_id = %d and key_data = %s",
            $categoryID, 'size-default'
        ));
        if(!$results) { 
            $res = new stdClass();
               $res->width = '';
               $res->length = '';
               $res->height = '';
               $res->weight = '';
               return $res;
            }
        
        return json_decode($results);

      }
      public static function show_categories_to_mapping($tab, $return = false){
        $argsCategorias = array(
            'taxonomy' => 'product_cat',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'hide_empty' => false,
            'hierarchical' => true,
            'meta_query' => [
            [
              'key' => 'aew_category_id',
              'compare' => '>',
              'value' => '0'
            ],[
                'key' => 'aew_category_id',
                'compare' => '!=',
                'value' => ''
            ]],
        );

        //IDS de las categorias que están mapeadas
        $categorias = get_terms( $argsCategorias );
        if($return) {
            return $categorias;
            //TODO: Forma de calcular esto
            // foreach ($categorias as $key => &$cat) {
            //     $args = array(
            //         'post_type' => 'product',
            //         'post_status' => 'publish',
            //         'posts_per_page' => -1,
            //         'fields' => 'ids',
            //         'tax_query' => array(
            //             array(
            //                 'taxonomy'      => 'product_cat',
            //                 'field'         => 'term_id',
            //                 'terms'         => $cat->term_id,
            //                 'operator'      => 'IN'
            //             )
            //         ),
            //         'meta_query' => array(
            //             'relation' => 'OR',
            //             array(
            //                 'key'     => '_aew_default_category_id',
            //                 'value'   => $cat->term_id,
            //                 'compare' => '='
            //             ),
            //             array(
            //                 'key'     => '_aew_default_category_id',
            //                 'value'   => 0,
            //                 'compare' => '='
            //             ),
            //             array(
            //                 'key'     => '_aew_default_category_id',
            //                 'compare' => 'NOT EXISTS'
            //             ),
            //         )
            //     );
            //     $productos = new WP_Query( $args );
            //     $categorias[$key]->products_total = $productos->found_posts;
            //     $args = array(
            //         'post_type' => 'product',
            //         'post_status' => 'publish',
            //         'posts_per_page' => -1,
            //         'fields' => 'ids',
            //         'tax_query' => array(
            //             array(
            //                 'taxonomy'      => 'product_cat',
            //                 'field'         => 'term_id',
            //                 'terms'         => $cat->term_id,
            //                 'operator'      => 'IN'
            //             )
            //         ),
            //         'meta_query' => array(
            //             'relation' => 'AND',
            //             array(
            //                 'key'     => '_aew_product_id',
            //                 'value'   => '',
            //                 'compare' => '!='
            //             ),
            //             array(
            //                 'key'     => '_aew_default_category_id',
            //                 'value'   => $cat->term_id,
            //                 'compare' => '='
            //             )
            //         )
            //     );
            //     $productos = new WP_Query( $args );
            //     $categorias[$key]->products_mapped = $productos->found_posts;
                //$cat['slug'] = 'adsfs';
            // }
            //echo '<pre>' . print_r($categorias, true). '</pre>';
            //die();

            return $categorias;
        }
        $redirect = add_query_arg( array(
        	'page'   => 'aliexpress-general-options&tab='.$tab,
        ), admin_url( 'admin.php' ) );
        foreach($categorias as $cat) {
            echo '<a class="link_cat_mapping" href="'.$redirect.'&category='.$cat->term_id.'&name='.$cat->name.'"><div class="categorory">'.$cat->name.'</div></a>';
        }
    }
    public static function save_fee_category($data) {
        foreach($data['feeCategory'] as $key => $value) {
            update_term_meta(intval($key),'aew_category_fee', floatval($value));
        }
        
        foreach($data['fixedAmount'] as $key => $value) {
            update_term_meta(intval($key),'aew_fixed_amount', floatval($value));
        }

        foreach($data['medidas'] as $key => $value) {
            self::save_default_sizes($data['medidas'][$key], $key);
        }

        foreach($data['preparation'] as $id => $value) {
            if($value == '') { $value = '3'; }
            update_term_meta(intval($id), 'aew_category_preparation', intval($value));
        }

        foreach($data['aliexpress_tags'] as $id => $value) {
            update_term_meta(intval($id), 'aliexpress_tags', $value);
        }

        if(isset($data['aliexpress_add_brand'])) {
            foreach($data['aliexpress_add_brand'] as $id => $value) {
                update_term_meta(intval($id), 'aliexpress_add_brand', intval($value));
            }
        }

        if(isset($data['aew_group_select'])) {
            foreach($data['aew_group_select'] as $id => $value) {
                update_term_meta(intval($id), 'aew_group_category', intval($value));
            }
        }
        if(isset($data['shippingTemplateSelected'])) {
            foreach($data['shippingTemplateSelected'] as $id => $value) {
                if($value != '0') {
                    update_term_meta(intval($id), 'aew_shipping_template_category', intval($value));
                }
            }
        }
        //Guardar medidas por defecto
        

    }
    public static function save_description_category($data){
        foreach($data['aliexpress_description'] as $key => $value) {
            update_term_meta(intval($key),'aew_category_description', wp_kses_post($value));
        }
    }
}
?>
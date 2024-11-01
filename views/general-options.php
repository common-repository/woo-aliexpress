<?php
if ( ! defined( 'ABSPATH' ) ) { die('Error'); }
?>
<div class="wrap aliexpress">
<h1><?=__('AliExpress Options','aliexpress')?></h1>
<p><?= __('AliExpress Official Plugin for WooCommerce, synchronization of products and orders with AliExpress.','aliexpress')?></p>
<p><?=__('<strong>Need help?</strong> If you have any questions please send us a message','aliexpress')?> <a href="https://soporte.wecomm.es/?language=<?=AEW_MAIN::get_lang_support()?>" target="blank"><?=__('Send Message','aliexpress')?></a></p>
<a target="_blank" href="<?=AEW_MAIN::aew_get_documentation_link()?>" class="docs_button"><?=__('View the documentation','aliexpress')?></a>
<p>Version: <?=AEW_VERSION?></p>
<?php
if(isset($_GET['tab']) and $_GET['tab'] == 'clearlogin') {
    update_option('aew_token_auth', false);
    update_option('aew_check_account', false);
}
if(!get_option('aew_token_auth') or get_option('aew_token_auth') == '') {

    require(AEW_VIEW_PATH . 'login.php');

    exit();
}
/**
 * Check AE Account
 * @since 1.2.0
 */

if( get_option('aew_token_expiration', false) and strtotime(get_option('aew_token_expiration')) < strtotime('+1 minutes')) {
    delete_option('aew_token_auth');
    delete_option('aew_token_expiration');
    update_option('aew_show_expiration', '1');
}
require_once(AEW_AE_PATH . 'AEFactory.php');
$datosAccount = get_option('aew_check_account');
if(!$datosAccount) {
    $factory = new AEFactory(AEW_TOKEN);
    $datos = json_decode($factory->get_vendor_profile_info());
    //AEW_MAIN::printDebug($datos);
    if(isset($datos->code) and $datos->code == 15) {
        echo '<div class="notice notice-error">
            <p>'.__('Error: This is not a seller account, ','aliexpress').'</p>
            <p>'.sprintf(__('<strong>IMPORTANT NOTE:</strong> to set up AliExpress for WooCommerce it is essential that you have a merchant account on AliExpress. <a href="%s" target="_blank">Don\'t have a merchant account? Click here.</a>','aliexpress'), 'https://login.aliexpress.com/join/seller/unifiedJoin.htm?_regbizsource=ES_Woo_Wecomm').'</p>
            <a href="?page=aliexpress-general-options&tab=clearlogin">'.__('Login with other account', 'aliexpress').'</a></div>';
            exit();
    }
    update_option('aew_check_account', serialize($datos));
}else{
    if(isset($_GET['force_upload_store']) and sanitize_text_field($_GET['force_upload_store']) == 'AE') {
        $factory = new AEFactory(AEW_TOKEN);
        $datosAccount = serialize(json_decode($factory->get_vendor_profile_info()));
        update_option('aew_check_account', $datosAccount);
    }
    AEW_MAIN::show_store(unserialize($datosAccount));
}
?>

<?php if(isset($_GET['auth'])  and sanitize_text_field($_GET['auth']) == 'true') { ?>
<div class="notice notice-success">
    <p><?=__('The session started correctly, now you can configure AliExpress.','aliexpress');?></p>
</div>
<?php } ?>

<?php
    $save_active = false;
    if( isset( $_GET[ 'tab' ] ) ) {
        $active_tab = sanitize_text_field($_GET[ 'tab' ]);
    }else{ $active_tab = 'general'; }
?>
<h2 class="nav-tab-wrapper">
    <a href="?page=aliexpress-general-options&tab=general" class="nav-tab <?=$active_tab == 'general' ? 'nav-tab-active' : ''; ?> "><?=__('General Options', 'aliexpress')?></a>
    <a href="?page=aliexpress-general-options&tab=categories" class="nav-tab <?=$active_tab == 'categories' ? 'nav-tab-active' : ''; ?>"><?=__('Categories', 'aliexpress')?></a>
    <a href="?page=aliexpress-general-options&tab=attributes" class="nav-tab <?=$active_tab == 'attributes' ? 'nav-tab-active' : ''; ?>"><?=__('Attributes', 'aliexpress')?></a>
    <a href="?page=aliexpress-general-options&tab=features" class="nav-tab <?=$active_tab == 'features' ? 'nav-tab-active' : ''; ?>"><?=__('Features', 'aliexpress')?></a>
    <a href="?page=aliexpress-general-options&tab=country_prices" class="nav-tab <?=$active_tab == 'country_prices' ? 'nav-tab-active' : '';?>"><?=__('Country Prices', 'aliexpress')?></a>
    <a href="?page=aliexpress-general-options&tab=upload_cats" class="nav-tab <?=$active_tab == 'upload_cats' ? 'nav-tab-active' : '';?>"><?=__('Manage Categories', 'aliexpress')?></a>
    <a href="?page=aliexpress-general-options&tab=view_orders&pnumber=1" class="nav-tab <?=$active_tab == 'view_orders' ? 'nav-tab-active' : '';?>"><?=__('Orders', 'aliexpress')?></a>
    <a href="?page=aliexpress-general-options&tab=view_jobs" class="nav-tab <?=$active_tab == 'view_jobs' ? 'nav-tab-active' : ''; ?>"><?=__('Jobs', 'aliexpress')?></a>
    <a href="?page=aliexpress-general-options&tab=error" class="nav-tab <?=$active_tab == 'error' ? 'nav-tab-active' : ''; ?>"><?=__('Error Log', 'aliexpress')?></a>
    <a href="?page=aliexpress-general-options&tab=status" class="nav-tab <?=$active_tab == 'status' ? 'nav-tab-active' : '';?>"><?=__('System Status', 'aliexpress')?></a>
    <a href="?page=aliexpress-general-options&tab=map_products" class="nav-tab <?=$active_tab == 'map_products' ? 'nav-tab-active' : ''; ?>"><?=__('Maping Products', 'aliexpress')?></a>
    <!-- <a href="?page=aliexpress-general-options&tab=license" class="nav-tab //$active_tab == 'license' ? 'nav-tab-active' : '';">//__('License', 'aliexpress')</a> -->
</h2>



    <?php
    if(isset($_POST['action'])) {
        if($_POST['action'] == 'save_attrs') {
            AEW_Attributes::save_attrs_mapping($_POST);
            // echo '<pre>'. print_r($_POST, true).'</pre>';
        }elseif($_POST['action'] == 'save_features') {
            AEW_Features::save_features_mapping($_POST);
            // echo '<pre>'. print_r($_POST, true).'</pre>';
        }elseif($_POST['action'] == 'save_categories') {
            AEW_Category::save_fee_category($_POST);
            // echo '<pre>'. print_r($_POST, true).'</pre>';
        }elseif($_POST['action'] == 'save_description_category') {
            AEW_Category::save_description_category($_POST);
            // echo '<pre>'. print_r($_POST, true).'</pre>';
        }elseif($_POST['action'] == 'save_country_prices') {
            if(isset($_POST['countryPrices'])) {
                foreach($_POST['countryPrices'] as $iso => $percent) {
                    if($percent == '') {
                        unset($_POST['countryPrices'][$iso]);
                    }else{
                        $_POST['countryPrices'][$iso] = floatval($percent);
                    }
                }
                $prices_saved = update_option('countryPrices', $_POST['countryPrices']);
            }

        }elseif($_POST['action'] == 'save_carriers') {
            
            // if(isset($_POST['_aew_carriers_mapping']) and is_array($_POST['_aew_carriers_mapping'])) {
            //     $carriersAE = array_map('sanitize_text_field', $_POST['_aew_carriers_mapping']);
            //     update_option('_aew_carriers', $carriersAE );
            // }

            // $res = AEW_MAIN::save_carriers_mapping();
            // AEW_MAIN::printDebug($res);


        }elseif($_POST['action'] == "save_config") {
            update_option('aew_stock_default', sanitize_text_field($_POST['aew_stock_default']));
            if(isset($_POST['aew_shipping_template'])) {
                update_option('_aew_shipping_template', sanitize_text_field($_POST['aew_shipping_template']));
            }
            if(isset($_POST['aew_check_empty_attrs'])) {
                update_option('aew_check_empty_attrs', "1");
            }else{
                update_option('aew_check_empty_attrs', "0");
            }
            if(isset($_POST['aew_checked_debug_mode'])) {
                update_option('aew_checked_debug_mode', "1");
            }else{
                update_option('aew_checked_debug_mode', "0");
            }
            if(isset($_POST['aew_corralative_skus'])) {
                update_option('aew_corralative_skus', "1");
            }else{
                update_option('aew_corralative_skus', "0");
            }

            if(isset($_POST['aew_generate_sku_id'])) {
                update_option('aew_generate_sku_id', "1");
            }else{
                update_option('aew_generate_sku_id', "0");
            }

            //Explosion products
            if(isset($_POST['aew_explosion_products'])) {
                update_option('aew_explosion_products',"1");
            }else{
                update_option('aew_explosion_products',"0");
            }

            if(isset($_POST['aew_only_stock_price'])) {
                update_option('aew_only_stock_price',"1");
            }else{
                update_option('aew_only_stock_price',"0");
            }

            //Shipping method
            if(isset($_POST['method_shipping_id'])) {
                update_option('_aew_method_shipping_id', sanitize_text_field($_POST['method_shipping_id']));
            }

            // if(isset($_POST['aew_image_system'])) {
            //     update_option('aew_image_system', sanitize_text_field($_POST['aew_image_system']));
            // }

            if(isset($_POST['aew_duplicate_attributes'])) {
                update_option('aew_duplicate_attributes', "1");
            }else{
                update_option('aew_duplicate_attributes', "0");
            }

            if(isset($_POST['aew_add_block_related'])) {
                update_option('aew_add_block_related', "1");
            }else{
                update_option('aew_add_block_related', "0");
            }

            if(isset($_POST['aew_discount_support_order'])) {
                update_option('aew_discount_support_order', "1");

            }else{
                update_option('aew_discount_support_order', "0");
            }

            update_option('aew_min_price', sanitize_text_field( $_POST['aew_min_price'] ));
            update_option('aew_chunk_jobs', sanitize_text_field( $_POST['aew_chunk_jobs'] ));
            update_option('aew_max_price', sanitize_text_field( $_POST['aew_max_price'] ));
            
            if(isset($_POST['aew_category_description_option'])) {
                update_option('aew_category_description_option', sanitize_text_field( $_POST['aew_category_description_option'] ));
            }

            if(isset($_POST['aew_size_images'])) {
                update_option('aew_size_images', sanitize_text_field($_POST['aew_size_images']));
            }

            if(isset($_POST['aew_use_ean'])) {
                update_option('aew_use_ean', sanitize_text_field($_POST['aew_use_ean']));
            }

            if(isset($_POST['aew_ean_meta'])) {
                update_option('aew_ean_meta', sanitize_text_field($_POST['aew_ean_meta']));
            }

            update_option('aew_order_default_status', sanitize_text_field( $_POST['aew_order_default_status'] ));
            update_option('aew_related_links', sanitize_text_field($_POST['aew_related_links']));
        }
    }

    //General Section
    if($active_tab == 'general') {
    // settings_fields( 'aew_general_options' );
    // do_settings_sections( 'aew_general_options' );
    $save_active = true;
    ?>

    <form method="post">
        <input type="hidden" name="action" value="save_config" />
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?=__('Stock for products without manage stock', 'aliexpress')?></th>
                <td>
                <span class="tooltip tooltip_ae" title="<?= __('AliExpress need stock for products and variations, when your product not have manage stock, ¿What stock send to AliExpress?, default 100','aliexpress'); ?>">?</span>
                    <input type="number" name="aew_stock_default" value="<?=get_option('aew_stock_default') ?: 100?>" min="1" max="99999999" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?=__('Shipping Template', 'aliexpress')?></th>
                <td>
                <span class="tooltip tooltip_ae" title="<?=__('Shipping templates are configured in your AliExpress seller account, you can access here to configure these templates','aliexpress'); ?>">?</span>
                <?php
                $factory = new AEFactory(AEW_TOKEN);
                $st = $factory->get_shipping_templates();
				if($st) {
					$optionTemplateSelected = get_option('_aew_shipping_template');
					$options_shipping_template = '';
					foreach ($st as $key => $item) {
						if(!$optionTemplateSelected) {
							if($item['is_default'] === true) {
								$selected = 'selected="selected"';
							}else{
								$selected = '';
							}
						}else{
							if(selected( $optionTemplateSelected, $key, false)) {
								$selected = 'selected="selected"';
							}else{
								$selected = '';
							}
						}
						$options_shipping_template .= '<option value="'.$key.'" '.$selected.'>'.$item['template_name'].'</option>';
					}
					echo '<select name="aew_shipping_template">
                        <option value="0">'.__('Choose Option', 'aliexpress').'</option>
                        '.$options_shipping_template.'
                    </select>';
				}else{
					echo sprintf(__('No shipping templates availables, <a href="%s" target="_blank">set your shipping template here</a>', 'aliexpress'), 'https://freighttemplate.aliexpress.com/wsproduct/freight/freightTemplateList.htm');
				}
                
                ?>
                <p class="description"><a target="_blank" href="https://gsp.aliexpress.com/apps/shipping/list"><?= __('Click here to configure Shipping Templates on AliExpress','aliexpress'); ?></a></p>    
                </td>
            </tr>
            <tr>
                <th scope="row"><?=__('Default order status', 'aliexpress')?></th>
                <td>
                <?php
                $statuses = wc_get_order_statuses();
                $statusDefaultSelected = get_option('aew_order_default_status', 'wc-processing')
                ?>

                <select name="aew_order_default_status">
                    <?php
                    foreach($statuses as $key => $status) {
                        if($key == 'wc-completed' || $key == 'wc-refunded' || $key == 'wc-failed' || $key == 'wc-cancelled') { continue; }
                        $selected = selected($key, $statusDefaultSelected, false);
                        echo '<option value="'.$key.'" '.$selected.'>'.$status.'</option>';
                    }
                    ?>
                </select>

                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?=__('Use Product ID if the SKU is empty', 'aliexpress')?></th>
                <td>
                    <span class="tooltip tooltip_ae" title="<?=__('ATENTION: With this option, when a SKU product is empty this copy product id as SKU and save the product.','aliexpress'); ?>">?</span>
                    <input type="checkbox" name="aew_generate_sku_id" value="1" <?= checked("1", get_option('aew_generate_sku_id', '0'))?> />
                    <label><?=__('Activate SKU by ID','aliexpress')?></label>
                    <p class="description"><?=__('Advanced use, contact support for more information, use it if you know exactly what you are doing','aliexpress')?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?=__('Use EAN', 'aliexpress')?></th>
                <td>
                    <?php
                        $optionUseEAN = get_option('aew_use_ean', '0');
                    ?>
                    <select name="aew_use_ean">
                        <option value="0" <?php selected('0', $optionUseEAN) ?>><?=__('Use AliExpress field', 'aliexpress')?></option>
                        <option value="1" <?php selected('1', $optionUseEAN) ?>><?=__('Custom', 'aliexpress')?></option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?=__('Custom Field EAN', 'aliexpress')?></th>
                <td>
                    <?php
                        $valueCustomEAN = get_option('aew_ean_meta', '');
                    ?>
                    <input type="text" value="<?=$valueCustomEAN?>" name="aew_ean_meta">
                    <p class="description"><?=__('If you want use SKU like EAN Code, use _sku (Meta key value), or other meta key used by another plugin','aliexpress')?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?=__('Products Cron', 'aliexpress')?></th>
                <td>
                <span class="tooltip tooltip_ae" title="<?= __('You can configure a CRON with this URL, this will make regular product updates on AliExpress, this CRON completely overwrites the product','aliexpress'); ?>">?</span>    
                <?=home_url()?>/wp-admin/admin-ajax.php?action=aew_cron_update_products&token=<?=get_option('AEW_TOKEN_CRON')?></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?=__('Orders Cron', 'aliexpress')?></th>
                <td>
                <span class="tooltip tooltip_ae" title="<?=__('This CRON downloads AliExpress orders to your store','aliexpress')?>">?</span>    
                <?=home_url()?>/wp-admin/admin-ajax.php?action=aew_cron_get_orders&token=<?=get_option('AEW_TOKEN_CRON')?></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?=__('Price and Stock Cron', 'aliexpress')?></th>
                <td>
                <span class="tooltip tooltip_ae" title="<?=__('If your products do not change often or you do not want to overwrite custom descriptions that you have made in AliExpress, you can only update the Stock and Price of the products','aliexpress')?>">?</span>    
                <?=home_url()?>/wp-admin/admin-ajax.php?action=aew_cron_update_stock_price&token=<?=get_option('AEW_TOKEN_CRON')?></td>
            </tr>

            <tr valign="top">
                <th scope="row"><?=__('Price Cron', 'aliexpress')?></th>
                <td>
                <span class="tooltip tooltip_ae" title="<?=__('If your products do not change often or you do not want to overwrite custom descriptions that you have made in AliExpress, you can only update the Price of the products','aliexpress')?>">?</span>    
                <?=home_url()?>/wp-admin/admin-ajax.php?action=aew_cron_update_price&token=<?=get_option('AEW_TOKEN_CRON')?></td>
            </tr>

            <tr valign="top">
                <th scope="row"><?=__('Stock Cron', 'aliexpress')?></th>
                <td>
                <span class="tooltip tooltip_ae" title="<?=__('If your products do not change often or you do not want to overwrite custom descriptions that you have made in AliExpress, you can only update the Stock of the products','aliexpress')?>">?</span>    
                <?=home_url()?>/wp-admin/admin-ajax.php?action=aew_cron_update_stock&token=<?=get_option('AEW_TOKEN_CRON')?></td>
            </tr>
            <tr>
                <td></td>
                <td><?=__('You can use the free service','aliexpress')?> <a href="https://cron-job.org/en/" target="_blank">CRON JOBS</a></td>
            </tr>
            
        </table>

            <details style="text-align: left;width: 100%;">
                <summary style="padding: 20px;padding-left: 0;font-weight: bold;"><?=__('Avanced Options','aliexpress')?></summary>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?=__('Default Shipping Method', 'aliexpress')?></th>
                        <td>
                            <span class="tooltip tooltip_ae" title="<?=__('Normally used for shipping companies, establish a shipping method so that the shipping plugins can read orders from AliExpress','aliexpress'); ?>">?</span>
                            <input type="text" name="method_shipping_id" value="<?=get_option('_aew_method_shipping_id', '')?>" />
                            <p class="description"><?=__('Advanced use, contact support for more information, use it if you know exactly what you are doing','aliexpress')?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Exclude by min and max price', 'aliexpress')?></th>
                        <td>
                            <input type="text" name="aew_min_price" value="<?=get_option('aew_min_price', '0')?>" />
                            <label><?=__('and','aliexpress')?></label>
                            <input type="text" name="aew_max_price" value="<?=get_option('aew_max_price', '999999')?>" />
                            <p class="description"><?=__('Products between this prices are exclude to upload products.','aliexpress')?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Variations Explosion', 'aliexpress')?></th>
                        <td>
                            <span class="tooltip tooltip_ae" title="<?=__('Only apply to upload new products','aliexpress'); ?>">?</span>
                            <input type="checkbox" name="aew_explosion_products" value="1" <?= checked("1", get_option('aew_explosion_products', '0'))?> />
                            <label><?=__('Use explosion variations','aliexpress')?></label>
                            <p class="description"><?=__('Advanced use, contact support for more information, use it if you know exactly what you are doing','aliexpress')?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Only update stock and price', 'aliexpress')?></th>
                        <td>
                            <span class="tooltip tooltip_ae" title="<?=__('If this option is enabled, only update price and stock.','aliexpress'); ?>">?</span>
                            <input type="checkbox" name="aew_only_stock_price" value="1" <?= checked("1", get_option('aew_only_stock_price', '0'))?> />
                            <label><?=__('Use Only Stock and Price','aliexpress')?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Categories Description Options', 'aliexpress')?></th>
                        <td>
                            <?php
                                $optionCategoryDescription = get_option('aew_category_description_option', '0')
                            ?>
                            <select name="aew_category_description_option">
                                <option value="0" <?php selected('0', $optionCategoryDescription) ?>><?=__('Replace', 'aliexpress')?></option>
                                <option value="1" <?php selected('1', $optionCategoryDescription) ?>><?=__('Before', 'aliexpress')?></option>
                                <option value="2" <?php selected('2', $optionCategoryDescription) ?>><?=__('After', 'aliexpress')?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Size of Images', 'aliexpress')?></th>
                        <td>
                            <?php
                                $optionsSizeImages = AEW_MAIN::aew_get_possible_images_size();
                                $optionSizeImage = get_option('aew_size_images', 'medium')
                            ?>
                            <select name="aew_size_images">
                                <?php
                                    if($optionsSizeImages) {
                                        foreach($optionsSizeImages as $size => $value){
                                            echo '<option value="'.$size.'" '.selected($size, $optionSizeImage).'>'.$size.'</option>';
                                        }
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Create Correlative SKUs', 'aliexpress')?></th>
                        <td>
                            <span class="tooltip tooltip_ae" title="<?=__('When a product combination some of the attributes are selected as Any... AliExpress will not send AliExpress this product by default.','aliexpress'); ?>">?</span>
                            <input type="checkbox" name="aew_corralative_skus" value="1" <?= checked("1", get_option('aew_corralative_skus'))?> />
                            <label><?=__('Activate correlative SKU creation','aliexpress')?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Upload Products with empty attributes', 'aliexpress')?></th>
                        <td>
                            <span class="tooltip tooltip_ae" title="<?=__('When a product combination some of the attributes are selected as Any... AliExpress will not send AliExpress this product by default.','aliexpress'); ?>">?</span>

                            <input type="checkbox" name="aew_check_empty_attrs" value="1" <?= checked("1", get_option('aew_check_empty_attrs'))?> />
                            <label><?=__('Enable synchronization of empty variations','aliexpress')?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Allow duplicate attributes', 'aliexpress')?>
                    </th>
                        <td>
                            <span class="tooltip tooltip_ae" title="<?= __('When this option is active, duplicate attributes will be allowed. NOTE: They are allowed as long as they are not present in the same product, if a product is uploaded with duplicate attributes it will be rejected','aliexpress'); ?>">?</span>
                            <input type="checkbox" name="aew_duplicate_attributes" value="1" <?= checked("1", get_option('aew_duplicate_attributes'))?> />
                            <label><?=__('Enable duplicate attributes','aliexpress')?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Token Cron', 'aliexpress')?></th>
                        <td>
                        <span class="tooltip tooltip_ae" title="<?= __('This is the key to configure the CRON in your hosting, your hosting provider can help you configure this service.','aliexpress'); ?>">?</span>

                        <?=get_option('AEW_TOKEN_CRON')?> <button class="ae_button regen_token_cron" type="button"><?=__('Regenerate Token','aliexpress')?></button></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Add Related Products block to AliExpress', 'aliexpress')?></th>
                        <td>
                            <span class="tooltip tooltip_ae" title="<?=__('Add a related product block on AliExpress','aliexpress')?>">?</span>    
                            <input type="checkbox" name="aew_add_block_related" value="1" <?= checked("1", get_option('aew_add_block_related','0'))?> />
                            <label><?=__('Enable','aliexpress')?></label>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?=__('Discount Support', 'aliexpress')?></th>
                        <td>
                            <span class="tooltip tooltip_ae" title="<?=__('Add to order discount from AliExpress order','aliexpress')?>">?</span>    
                            <input type="checkbox" name="aew_discount_support_order" value="1" <?= checked("1", get_option('aew_discount_support_order','0'))?> />
                            <label><?=__('Enable','aliexpress')?></label>
                        </td>
                    </tr>
                    
                    <tr valign="top" class="ae_show_only_related_block" style="display:<?= get_option('aew_add_block_related') == '1' ? 'display' : 'none' ?>" >
                        <th scope="row"><?=__('Related Product Block Links', 'aliexpress')?></th>
                        <td>
                        <span class="tooltip tooltip_ae" title="<?=__('Where do you have to take the links of the related product block?','aliexpress')?>">?</span>
                        
                        <?php
                        $selectedLinks = get_option('aew_related_links', 'aliexpress');
                        ?>
                            <select name="aew_related_links">
                                <option value="aliexpress" <?= selected('aliexpress', $selectedLinks) ?> ><?=__('AliExpress Products','aliexpress')?></option>
                                <option value="woocommerce" <?= selected('woocommerce', $selectedLinks) ?> ><?=__('WooCommerce Products','aliexpress')?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Chunk Jobs', 'aliexpress')?></th>
                        <td>
                            <input type="number" name="aew_chunk_jobs" value="<?=get_option('aew_chunk_jobs', 200)?>" />
                            <label><?=__('products for job','aliexpress')?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?=__('Debug Mode', 'aliexpress')?></th>
                        <td>
                            <span class="tooltip tooltip_ae" title="<?=__('This option disables the upload of products to AliExpress, only for debugging jobs','aliexpress')?>">?</span>    
                            <input type="checkbox" name="aew_checked_debug_mode" value="1" <?= checked("1", get_option('aew_checked_debug_mode'))?> />
                            <label><?=__('Enable debug mode','aliexpress')?></label>
                        </td>
                    </tr>
                </table>
            </details>
            <input type="submit" name="submit" class="ae_button endForm" value="<?=__('Save Configuration','aliexpress')?>">
    </form>
    <?php
    //Categories Section
    }elseif($active_tab == 'categories') {
        echo '<h2>'.__('Categories Mapping','aliexpress').'</h2>';
        echo '<p><a href="?page=aliexpress-general-options&tab=desription_categories">'.__('Change the default descriptions of the products by categories (Only for AliExpress), click here','aliexpress').'</a></p>';
        $save_active = false;
        $ids = AEW_Category::get_category_mapped(0);
        $keyID = 'term_id';
        $idsMapping = array_map(function($e) use($keyID) {
            return is_object($e) ? $e->$keyID : $e[$keyID];
        }, $ids);
        echo '<div class="block_mapping_category" style="margin:10px">'.__('Add category to mapping', 'aliexpress').' ' .wp_dropdown_categories(array('taxonomy' => 'product_cat', 'echo' => '0', 'exclude' => $idsMapping)).'
        <button class="button addCategoryToMapping" type="button">'.__('Add', 'aliexpress').'</button></div>';
        echo '<div class="categories_list_sync">';
        AEW_Category::get_categories_tree($ids);
        echo '</div>';

    }elseif($active_tab == 'country_prices') {
        echo '<h2>'.__('Country Prices','aliexpress').'</h2>';
        echo '<p>'.__('If you do not want to change the price in the country, leave the field empty.','aliexpress').'</p>';

        echo '<p>'.__('The minimum possible value is 70, if you want to add 5% to the base price you must set the percentage to 105, if for example you want to decrease the price by 2% you must set the percentage to 98', 'aliexpress').'</p>';
        echo '<div class="categories_list_sync">';
        AEW_MAIN::country_prices();
        echo '</div>';

    }elseif($active_tab == 'desription_categories') {
        echo '<h2>'.__('Categories Description','aliexpress').'</h2>';
        echo '<p>'.__('Overwrite the description of the product when it is uploaded to AliExpress, if it is different from empty it will be used when uploading a product', 'aliexpress').'</p>';
        echo '<p>'.__('You can use [AE_PRODUCT_NAME] for product name or [AE_PRODUCT_PRICE] for product price.', 'aliexpress').'</p>';
        echo '<div class="categories_list_sync">';
        AEW_Category::get_categories_description(0);
        echo '</div>';

    }elseif($active_tab == 'support') { ?>
    <h2><?=__('¿You need support?','aliexpress')?></h2>

        


    <?php  
    }elseif($active_tab == 'login') {
        require(AEW_VIEW_PATH . 'renewLogin.php');
    }elseif($active_tab == 'attributes') {

        if(!isset($_GET['category'])) {
            echo '<h2>'.__('Select an Category','aliexpress').'</h2>';
            echo '<p>'.__('Select an category to attributes mapping','aliexpress').'</p>';
            AEW_Category::show_categories_to_mapping('attributes');
        }else{
            echo '<h2>'.sprintf(__('Attributes Mapping from Category %s','aliexpress'), sanitize_text_field($_GET['name'])).'</h2>';
            $save_active = false;
            echo '<div class="categories_list_sync">';
            AEW_Attributes::get_attributes_mapping(intval(sanitize_text_field($_GET['category'])));
            echo '</div>';
        }

    }elseif($active_tab == 'features') { 

        if(!isset($_GET['category'])) {
            echo '<h2>'.__('Select an Category','aliexpress').'</h2>';
            echo '<p>'.__('Select an category to features mapping','aliexpress').'</p>';
            AEW_Category::show_categories_to_mapping('features');
        }else{
            echo '<h2>'.sprintf(__('Features Mapping from Category %s','aliexpress'), sanitize_text_field($_GET['name'])).'</h2>';
            $save_active = false;
            echo '<div class="categories_list_sync">';
            AEW_Features::get_features_mapping(intval(sanitize_text_field($_GET['category'])));
            echo '</div>';
        }

    }elseif($active_tab == 'view_jobs') {
        if(isset($_GET['from_date']) and isset($_GET['to_date'])) {
            $dateStart = sanitize_text_field($_GET['from_date']);
            $dateEnd = sanitize_text_field($_GET['to_date']);
        }else{
            $dateStart = date('Y-m-d', strtotime('-1 days'));
            $dateEnd = date('Y-m-d');
        }
        $ResultJobs = AEW_Job::get_jobs();
        echo '<h2>'.__('Jobs','aliexpress').'</h2>';
        echo '<div class="filter_order">
            <form action="'.admin_url( 'admin.php' ).'" method="get">
            <input type="hidden" name="page" value="aliexpress-general-options" />
            <input type="hidden" name="tab" value="view_jobs" />
            <label>'.__('Date', 'aliexpress').'
            <input type="date" name="from_date" value="'.$dateStart.'"/>
            <input type="date" name="to_date" value="'.$dateEnd.'"/>
            </label>
            <input type="submit" class="ae_button" value="'.__('Filter','aliexpress').'">
            </form>
        </div>';
        echo '<p>' . __('When send products to AliExpress, is created a job that contains all products that need update, you can click on job to update job status','aliexpress') . '</p>';
        echo '<p><button type="button" class="ae_button refreshAllJobs">'.__('Refresh all status jobs','aliexpress').'</button></p>';
        echo '<table width="100%" class="aew_order_table" cellspacing="0" cellpadding="0">';
        // <td>'.__('Total', 'aliexpress').'</td>
        echo '<tr>
            <td>'.__('Date', 'aliexpress').'</td>
            <td>'.__('Last Check', 'aliexpress').'</td>
            <td>'.__('Total Products', 'aliexpress').'</td>
            <td>'.__('Products Success', 'aliexpress').'</td>
            <td>'.__('Finalized', 'aliexpress').'</td>
        </tr>';

        foreach($ResultJobs as $job) {
            $status = AEW_Job::get_status_byCode($job->finished);
            echo '<tr data-job="'.$job->jobID.'" class="checkJob '.AEW_Job::get_class_line_job($job->finished).'">
                <td>'.date('d-m-Y H:i', strtotime($job->create_at)).'</td>
                <td class="lastcheck">'.date('d-m-Y H:i', strtotime($job->last_check)).'</td>
                <td class="totalproducts">'.$job->total_item.'</td>
                <td class="success">'.$job->success_total.'</td>
                <td class="finished">'.$status.'</td>
                </tr>';
            }
        echo '</table>';


    }elseif($active_tab == 'view_errors_plain') {


        global $wpdb;
    $results = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'aew_jobs ORDER BY create_at DESC');
        
        echo '<h2>'.__('Plain Error','aliexpress').'</h2>';
        // <td>'.__('Total', 'aliexpress').'</td>
        echo '<table width="100%" class="aew_order_table">';
        echo '<tr>
            <td>'.__('JOB ID', 'aliexpress').'</td>
            <td>'.__('DATA', 'aliexpress').'</td>
        </tr>';

        foreach($results as $job) {
            echo '<tr>
                <td width="100px">'.$job->jobID.'</td>
                <td>'.$job->data_job.'</td>
                </tr>';
            }
        echo '</table>';

    }elseif($active_tab == 'carriers_old') {
        // echo '<h2>'.__('Carriers Mapping','aliexpress').'</h2>';
        
        // //No es necesario al guardar ya que existe
        // if(!isset($AllCarriers)) {
        //     require_once(AEW_AE_PATH . 'AEFactory.php');
        //     require_once(AEW_AE_PATH . 'AECarriers.php');
        //     $factory = new AEFactory(); 
        //     $carriers = new AECarriers($factory);
        //     $AllCarriers = json_decode($carriers->get_all_carriers(),true);
        //     $zones = AEW_Order::get_zones_wocommerce();
        // }
        // $optionsCarriers = '<option value="0">'.__('Choose Option', 'aliexpress').'</option>';
        // // echo $AllCarriers->result_list->aeop_logistics_service_result->0->display_name;
        
        // // echo '<pre>'.print_r($AllCarriers, true).'</pre>';
        // echo '<form method="post">';
        // echo '<input type="hidden" name="action" value="save_carriers" />';
        // echo '<table width="100%" class="aew_order_table" cellspacing="0" cellpadding="0">';
        // echo '<tr>
        //     <td>'.__('Zone Name', 'aliexpress').'</td>
        //     <td>'.__('Shipping Method', 'aliexpress').'</td>
        //     <td>'.__('Price', 'aliexpress').'</td>
        //     <td></td>
        //     <td></td>
        // </tr>';
        // $i = 0;
        // foreach($zones as $zone) {
        //     $b = 0;
        //     foreach($AllCarriers['result_list']['aeop_logistics_service_result'] as $carrier) {
        //         if(isset($zone['selected']) and $carrier['logistics_company'] == $zone['selected']) {
        //             $selectedZone = 'selected="selected"';
        //             echo '<input type="hidden" name="carrier_id_reg['.$zone['instance_id'].']" value="'.$zone['id_reg_select'].'" />';
        //         }else{ $selectedZone = ''; }
        //         $optionsCarriers .= '<option value="'.$carrier['logistics_company'].'" data-i="'.$b.'" '.$selectedZone.'>'.$carrier['display_name'].'</option>';
        //         $b++;
        //     }
        //     echo '<tr>
        //         <td>'.$zone['name'].'</td>
        //         <td>'.$zone['data']['title'].'</td>
        //         <td>'.number_format($zone['data']['cost'],2).'</td>
        //         <td>-></td>
        //         <td>
        //             <select name="carrier[]" class="carrier_select" data-i="'.$i.'">
        //                 '.$optionsCarriers.'
        //             </select>
        //             <input type="hidden" name="carrier_index[]" data-i="'.$i.'" />
        //         </td>
        //     </tr>';
        //     $i++;
        // }
        // echo '</table>';
        // echo '<input type="submit" name="submit" class="endForm" value="'.__('Save Carriers Mapping','aliexpress').'">';
        // echo '<script>
        //     jQuery("select.carrier_select").on("change", function(){
        //         var iOption = jQuery("option:selected", this).attr("data-i");
        //         var iSelect = jQuery(this).attr("data-i");
        //         jQuery("input[data-i="+iSelect+"]").val(iOption);

        //     });
        // </script>';
        // echo '</form>';
    }elseif($active_tab == 'error') {
        echo '<h2>'.__('Error Log','aliexpress').'</h2>';
        echo '<a href="?page=aliexpress-general-options&tab=view_errors_plain" class="nav-tab link-content">'.__('Plain Error', 'aliexpress').'</a>';
        $LnError = AEW_MAIN::get_errors();
        if($LnError) {
            $errorTypes = array(
                'generic' => array(
                   'value' =>  __('Generic', 'aliexpress'),
                   'link' => '',
                   'valueLink' => ''
                ),
                'product' =>array(
                    'value' =>  __('Product', 'aliexpress'),
                    'link' => admin_url( 'post.php?post=%s&action=edit' ),
                    'valueLink' => __('View Product', 'aliexpress')
                 ),
                'order' => array(
                    'value' =>  __('Order', 'aliexpress'),
                    'link' => admin_url( 'post.php?post=%s&action=edit' ),
                    'valueLink' => __('View Order', 'aliexpress')
                 ),
                 'category' => array(
                    'value' =>  __('Category', 'aliexpress'),
                    'link' => admin_url( 'admin.php?page=aliexpress-general-options&tab=categories' ),
                    'valueLink' => __('Mapping Category', 'aliexpress')
                 )
            );
            echo '<table width="100%" class="aew_order_table" cellspacing="0" cellpadding="0">';
            echo '<tr>
                <td>'.__('Date', 'aliexpress').'</td>
                <td>'.__('Message', 'aliexpress').'</td>
                <td>'.__('Type', 'aliexpress').'</td>
                <td>'.__('Options', 'aliexpress').'</td>
            </tr>';
            foreach($LnError as $error) {
                if($error->element_id == 0) {
                    $link_option = '';
                }else{
                    $link_option = '<a target="_blank" href="'.sprintf($errorTypes[$error->type_error]['link'],$error->element_id).'">'.$errorTypes[$error->type_error]['valueLink'].'</a>';
                }
                
                echo '<tr>
                    <td>'.date('d-m-Y H:i', strtotime($error->created_at)).'</td>
                    <td>'.$error->msg.'</td>
                    <td>'.$errorTypes[$error->type_error]['value'].'</td>
                    <td>'.$link_option.'</td>
                </tr>';
            }
            echo '</table>';
        }

    }elseif($active_tab == 'view_orders') {
        echo '<h2>'.__('Orders','aliexpress').'</h2>';
        $debugActiveAE = get_option('aew_checked_debug_mode', '0');
        if(isset($_GET['from_date']) and isset($_GET['to_date'])) {
            $dateStart = sanitize_text_field($_GET['from_date']);
            $dateEnd = sanitize_text_field($_GET['to_date']);
        }else{
            $dateStart = date('Y-m-d', strtotime('-10 week'));
            $dateEnd = date('Y-m-d', strtotime('+1 days'));
        }

       
        if(isset($_GET['status_order'])) {
            $orderStatus = sanitize_text_field($_GET['status_order']);
        }else{
            $orderStatus = null;
        }

        $optionsStatus = '';
        foreach(AEW_MAIN::$posiblesEstados as $key => $status) {
            $optionsStatus .= '<option value="'.$key.'" '.selected($key, $orderStatus, false) .'>'.__($status['name'], 'aliexpress').'</option>';
        }
        echo '<div class="filter_order">
            <form action="'.admin_url( 'admin.php' ).'" method="get">
            <input type="hidden" name="page" value="aliexpress-general-options" />
            <input type="hidden" name="tab" value="view_orders" />
            <input type="hidden" name="pnumber" value="1" />
            <label>'.__('Date', 'aliexpress').'
            <input type="date" name="from_date" value="'.$dateStart.'"/>
            <input type="date" name="to_date" value="'.$dateEnd.'"/>
            </label>
            <label>
            '.__('Order Status', 'aliexpress').'
                <select name="status_order">
                    <option value="all">'.__('All Status','aliexpress').'</option>
                    '.$optionsStatus.'
                </select>
            </label>
            <input type="submit" class="ae_button" value="'.__('Filter','aliexpress').'">
            </form>
        </div>';



        require_once(AEW_AE_PATH . 'AEOrder.php');
        $factory = new AEFactory(AEW_TOKEN); 
        $o = new AEOrder_Base($factory);
        if(isset($_GET['pnumber'])) {
           $page = intval(sanitize_text_field($_GET['pnumber']));
        }else{
            $page = 1;
        }

        $orders = json_decode($o->get_OSS_order_list($dateStart . ' 00:00:00', $dateEnd . ' 00:00:00', $orderStatus, 20, $page), true);
        $orders = $orders['aliexpress_solution_order_get_response'];
        if(!$orders or isset($orders['error']) ) {
            echo '';
            return;
        }
        if($orders['result']['total_count'] == "0") {
            _e('No orders','aliexpress');
        }else{
            //Listar Pedidos
            echo '<table width="100%" class="aew_order_table" cellspacing="0" cellpadding="0">';
            echo '<tr>
                <td>'.__('# Order', 'aliexpress').'</td>
                <td>'.__('Date', 'aliexpress').'</td>
                <td>'.__('Customer', 'aliexpress').'</td>
                <td>'.__('Order Status', 'aliexpress').'</td>
                <td>'.__('Options', 'aliexpress').'</td>
            </tr>';
            if(intval($orders['result']['total_count']) > 1) {
                $lines = $orders['result']['target_list']['order_dto'];
             }else{
				 $lines = array();
                 $lines[] = $orders['result']['target_list']['order_dto'];
             }
            foreach($lines as $order) {
                $checkPedido = AEW_Order::check_order_aliexpress_byID($order['order_id']);
                
                if($checkPedido) {
                    $button = '<a class="viewOrder" target="_blank" href="/wp-admin/post.php?post='.$checkPedido.'&action=edit">'.__('View Order','aliexpress').'</a>';
                }else{
                    if($order['order_status'] == 'WAIT_SELLER_SEND_GOODS') {
                        $button = '<button type="button" class="ae_button ae_button getOrderAliExpress" data-id="'.$order['order_id'].'">'.__('Get Order','aliexpress').'</button>';
                    }else{
                        // $button = '<button type="button" class="getOrderAliExpress" data-id="'.$order['order_id'].'">'.__('Get Order','aliexpress').'</button>';
                        $button = '';
                    }

                    
                }

                if($debugActiveAE == '1') {
                    $button = '<button type="button" class="ae_button getOrderDebug" data-id="'.$order['order_id'].'">'.__('Get DEBUG','aliexpress').'</button>';
                }

                echo '<tr>
                    <td><a target="_blank" href="https://gsp.aliexpress.com/apps/order/detail?orderId='.$order['order_id'].'" title="View on AliExpress">'.$order['order_id'].'</a></td>
                    <td>'.date('d-m-Y H:i', strtotime($order['gmt_create'])).'</td>
                    <td>'.$order['buyer_signer_fullname'].'</td>
                    <td>'.__(AEW_MAIN::$posiblesEstados[$order['order_status']]['name'],'aliexpress').'</td>
                    <td>'.$button.'</td>
                    </tr>';
                }
            echo '</table>';
    
            echo AEW_Order::pagination_orders($orders);
        }
        
    }elseif($active_tab == 'license') {
        echo '<h2>' . __('License AliExpress WooCommerce Sync', 'aliexpress') . '</h2>';
        ?>
        <div class="licence_expiration"><?=sprintf(__('License Date Expiration: %s', 'aliexpress'), 'TEST')?></div>
        <div class="licence_expiration"><?=sprintf(__('License Code: %s', 'aliexpress'), 'TEST')?></div>


        <?php

    }elseif($active_tab == 'carriers') {
       
        require_once(AEW_AE_PATH . 'AECarriers.php');
        $factory = new AEFactory(AEW_TOKEN); 
        $carriers = new AECarriers($factory);
        $AllCarriers = json_decode($carriers->get_all_carriers(),true); 
        // AEW_MAIN::printDebug($AllCarriers);
        $carriersAE = get_option('_aew_carriers', array());
        ?>
        <form method="post">
        <input type="hidden" name="action" value="save_carriers" />
        <h2>Carriers Mapping</h2>
        <table class="form-table table_attributes">
            <tr class="attr_name">
                <td><?=__('AliExpress Carrier','aliexpress')?></td>
                <td><?=__('Shipping ID','aliexpress')?></td>
            </tr>
        <?php
        foreach($AllCarriers['result_list']['aeop_logistics_service_result'] as $carrier) {
            
            echo '<tr><td>'.$carrier['display_name'].'</td><td><input type="text" name="_aew_carriers_mapping['.$carrier['logistics_company'].']" value="'.$carriersAE[$carrier['logistics_company']].'" /></td></tr>';
        }
        ?>
            </table>
            <input type="submit" name="submit" class="ae_button endForm" value="<?=__('Save Carriers','aliexpress')?>">

            </form>
        <?php
    }elseif($active_tab == 'upload_cats') {
        
        $categorias = AEW_Category::show_categories_to_mapping(false, true);

        echo '<p style="padding: 10px;background: #ff00009c;color: #FFF;width: fit-content;">'.__('Make sure that the category that you are going to send to AliExpress is correctly mapped and all its attributes correctly configured, otherwise the upload may fail.','aliexpress').'</p>';
        echo '<table width="100%" class="aew_order_table" cellspacing="0" cellpadding="0">';
        foreach($categorias as $cat) {
            echo '<tr><td>'.$cat->name.'</td><td>
            <button id="importCategoryAew" data-id="'.$cat->slug.'" class="aew_button">'.__('Upload','aliexpress').'</button>
            </td>
            <td>
            <button id="deleteAEProducts" data-id="'.$cat->slug.'" class="aew_button" style="background:red">'.sprintf(__('Delete AliExpress Products','aliexpress'),$cat->name).'</button>
            <button id="activeAEProducts" data-id="'.$cat->slug.'" class="aew_button" style="background:green">'.sprintf(__('Enable AliExpress Products','aliexpress'),$cat->name).'</button>
            <button id="disableAEProducts" data-id="'.$cat->slug.'" class="aew_button" style="background:orange">'.sprintf(__('Disable AliExpress Products','aliexpress'),$cat->name).'</button>
            </td></tr>';
        }
        echo '</table>';


    }elseif($active_tab == 'status') {

        global $wpdb;
        if (isset($_POST['maintenance_ok']) and wp_verify_nonce(  $_POST['maintenance_ok'], 'aew_maintenance' ) ) {
            if(!current_user_can('manage_options')) {
                echo '<div class="error notice">
                    <p>'.__('You do not have permissions to do that.', 'aliexpress').'</p>
                </div>';
            }else{
                if(isset($_POST['aew_remove_jobs']) and current_user_can('manage_options')) {
                    //elimina los jobs
                    $trabajosEliminados = $wpdb->query("UPDATE {$wpdb->prefix}postmeta SET meta_value='0' WHERE meta_key='_aew_run_job'");
                    echo '<div class="notice-success notice">
                        <p>'.sprintf(__('%s products have been unlocked', 'aliexpress'), $trabajosEliminados).'</p>
                    </div>';
                }

                if(isset($_POST['aew_clean_data']) and current_user_can('manage_options')) {
                    //Limpieza de tablas
                    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}aew_jobs;");
                    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}aew_data_default;");
                    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}aew_mapping_attributes;");
                    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}aew_mapping_features;");
                    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}aew_products_error;");

                    
                    //Limpieza de Metadatos productos
                    $truncateMetas = $wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_aew_%' or meta_key LIKE 'aew_%'");

                    //Limpieza de options
                    $truncateOptions = $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_aew_%' or option_name LIKE 'aew_%'");

                    //Limpieza de categorias
                    $truncateCategorias = $wpdb->query("DELETE FROM {$wpdb->prefix}termmeta WHERE meta_key LIKE 'aew_%'");
                    
                    wp_safe_redirect( '?page=aliexpress-general-options&tab=general', 302 );
                    wp_die();
                    
                }

                if(isset($_POST['aew_clean_jobs']) and current_user_can('manage_options')) {
                    $deleteJobsFinishedError = $wpdb->query("DELETE FROM {$wpdb->prefix}aew_jobs WHERE finished='1' or finished='2'");
                }

                if(isset($_POST['aew_clean_errors']) and current_user_can('manage_options')) {
                    $deleteJobsFinishedError = $wpdb->query("DELETE FROM {$wpdb->prefix}aew_products_error");
                }

                if(isset($_POST['aew_delete_conection_product']) and current_user_can('manage_options')) {
                    $truncateCategorias = $wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = '_aew_product_id'");
                }

                if(isset($_POST['aew_change_category']) and current_user_can('manage_options')) {
                    $response = AEW_Category::set_category_default($_POST['destino']);
                        if($response and is_array($response['success']) and count(array_filter($response['success'])) > 0) {
                            echo '<div class="notice notice-success is-dismissible">
                                <p>'.sprintf(__('Success change %s products default category', 'aliexpress'), count(array_filter($response['success']))).'</p>
                            </div>';
                        }else{
                            echo '<div class="notice notice-info is-dismissible">
                                <p>'.__('Error change category default or no exist products', 'aliexpress').'</p>
                            </div>';
                        }
                }


            }
            
           
        }
        $jobs = $wpdb->get_var("SELECT COUNT(1) FROM information_schema.tables WHERE table_schema='".DB_NAME."' AND table_name='{$wpdb->prefix}aew_jobs'");
        $default = $wpdb->get_var("SELECT COUNT(1) FROM information_schema.tables WHERE table_schema='".DB_NAME."' AND table_name='{$wpdb->prefix}aew_data_default'");
        $attributes = $wpdb->get_var("SELECT COUNT(1) FROM information_schema.tables WHERE table_schema='".DB_NAME."' AND table_name='{$wpdb->prefix}aew_mapping_attributes'");
        $features = $wpdb->get_var("SELECT COUNT(1) FROM information_schema.tables WHERE table_schema='".DB_NAME."' AND table_name='{$wpdb->prefix}aew_mapping_features'");
        $products = $wpdb->get_var("SELECT COUNT(1) FROM information_schema.tables WHERE table_schema='".DB_NAME."' AND table_name='{$wpdb->prefix}aew_products_error'");

        $count_jobs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aew_jobs");
        $count_default = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aew_data_default");
        $count_attributes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aew_mapping_attributes");
        $count_features = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aew_mapping_features");
        $count_products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aew_products_error");

        echo '<h2>'.__('Check AliExpress Tables','aliexpress').'</h2>';
        echo '<table width="100%" class="aew_order_table" cellspacing="0" cellpadding="0">';
        echo '<tr>
                <td>'.__('Jobs Table', 'aliexpress').'</td>
                <td>'.__('Default Table', 'aliexpress').'</td>
                <td>'.__('Attributes Table', 'aliexpress').'</td>
                <td>'.__('Features Table', 'aliexpress').'</td>
                <td>'.__('Products Error Table', 'aliexpress').'</td>
            </tr><tr>
            <td '.($jobs == 1 ? 'style="background:green;color:#FFF"' : 'style="background:red;color:#FFF"').'>'.($jobs == 1 ? __('Yes', 'aliexpress') : __('No', 'aliexpress')).' (' . $count_jobs . ' '.__('records','aliexpress').')</td>
            <td '.($default == 1 ? 'style="background:green;color:#FFF"' : 'style="background:red;color:#FFF"').'>'.($default == 1 ? __('Yes', 'aliexpress') : __('No', 'aliexpress')).' (' . $count_default . ' '.__('records','aliexpress').')</td>
            <td '.($attributes == 1 ? 'style="background:green;color:#FFF"' : 'style="background:red;color:#FFF"').'>'.($attributes == 1 ? __('Yes', 'aliexpress') : __('No', 'aliexpress')).' (' . $count_attributes . ' '.__('records','aliexpress').')</td>
            <td '.($features == 1 ? 'style="background:green;color:#FFF"' : 'style="background:red;color:#FFF"').'>'.($features == 1 ? __('Yes', 'aliexpress') : __('No', 'aliexpress')).' (' . $count_features . ' '.__('records','aliexpress').')</td>
            <td '.($products == 1 ? 'style="background:green;color:#FFF"' : 'style="background:red;color:#FFF"').'>'.($products == 1 ? __('Yes', 'aliexpress') : __('No', 'aliexpress')).' (' . $count_products . ' '.__('records','aliexpress').')</td>
            </tr></table>';

        echo '<h2>'.__('Actions Maintenance','aliexpress').'</h2>';
        echo '<form method="post">
        '.wp_nonce_field( 'aew_maintenance', 'maintenance_ok' ).'
        <table width="100%" class="aew_order_table" cellspacing="0" cellpadding="0">';


        $ids = AEW_Category::get_category_mapped(0);
        $keyID = 'term_id';
        $idsMapping = array_map(function($e) use($keyID) {
            return is_object($e) ? $e->$keyID : $e[$keyID];
        }, $ids);

        echo '<tr>
                <td>'.__('Delete Jobs on Products', 'aliexpress').'</td>
                <td><input onClick="return confirm(\''.__('You are sure delete jobs on Products?','aliexpress').'\')" type="submit" name="aew_remove_jobs" value="'.__('Process', 'aliexpress').'" /></td>            
            </tr>
            <tr>
                <td>'.__('Clean Finished or With Error Jobs', 'aliexpress').'</td>
                <td><input onClick="return confirm(\''.__('You are sure, Clean finished or with error jobs?','aliexpress').'\')" type="submit" name="aew_clean_jobs" value="'.__('Process', 'aliexpress').'" /></td>            
            </tr>
            <tr>
                <td>'.__('Clean Errors', 'aliexpress').'</td>
                <td><input type="submit" onClick="return confirm(\''.__('You are sure clean error?','aliexpress').'\')" name="aew_clean_errors" value="'.__('Process', 'aliexpress').'" /></td>            
            </tr>
            <tr>
                <td>'.__('Disconect AliExpress conection products', 'aliexpress').'</td>
                <td><input type="submit" onClick="return confirm(\''.__('This action delete conection products with AliExpress','aliexpress').'\')" name="aew_delete_conection_product" value="'.__('Process', 'aliexpress').'" /></td>            
            </tr>
            <tr>
                <td>'.__('Change AliExpress category default', 'aliexpress').'
                <p class="description" style="color:red">'.__('Atention: This set category by default on product','aliexpress').'</p>
                </td>
                <td>'.wp_dropdown_categories(array('taxonomy' => 'product_cat', 'echo' => '0', 'include' => $idsMapping, 'name' => 'destino')).' <input  type="submit" name="aew_change_category" value="'.__('Process', 'aliexpress').'"</td>            
            </tr>
            <tr>
                <td style="background-color:orange">'.__('Clear ALL Data', 'aliexpress').'</td>
                <td style="background-color:orange"><input onClick="return confirm(\''.__('¡ATENTION!: You are sure CLEAR ALL DATA on AliExpress Plugin?','aliexpress').'\')" type="submit" name="aew_clean_data" value="'.__('Process', 'aliexpress').'" /></td>            
            </tr>';
        $orphan_cats = AEW_Product::get_orphan_products_cats();
        if($orphan_cats && count($orphan_cats)){
            echo '<tr>
                    <td>'.__('Orphan category Ids (have been manually disconnected)', 'aliexpress').'</td>
                    <td>'. implode(', ',array_keys($orphan_cats)) . '</td>
                  </tr>';
        }

        echo '<tr>
            <td>'.__('Token Expiration', 'aliexpress').'</td>
            <td>'.date('d-m-Y', strtotime(get_option( 'aew_token_expiration', '0000-00-00' ))).'</td>            
        </tr>';
        echo '</table></form>';


    }elseif($active_tab = 'map_products') {
            $prods = AEW_MAIN::get_local_products();
            if(empty($prods)) {
                $prods = [];
            }
            echo '<script>const ae_local_products = ' . json_encode($prods) . '</script>';
            echo '<div class="ae_pagination" style="text-align:right;margin-top:5px;">';
            echo '<input type="text" class="" style="margin-right:10px;" id="ae_filter_box" placeholder="' . __('search','aliexpress') . '...">';
            echo '<button class="ae_button" id="ae_pg_prev"> &lt; </button> <select id="ae_pg_select" style="width:50px;"><option value="1"> 1 </option></select> ';
            echo '<button class="ae_button" id="ae_pg_next"> &gt; </button></div>';
            echo '<table width="100%" id="aew_products_table" class="aew_order_table" cellspacing="0" cellpadding="0">';
            echo '<tr><td>&nbsp;</td>
                <td>'.__('Product ID', 'aliexpress').'</td>
                <td>'.__('Online', 'aliexpress').'</td>
                <td>'.__('AliExpress Name', 'aliexpress').'</td>
                <td>'.__('Status', 'aliexpress').'</td>
                <td>'.__('Options', 'aliexpress').'</td>
            </tr>';
            $arrayData = [];
        echo '</table>';
        echo '<br><br><div><button class="ae_button" id="bt_ae_get_catalog"> Get all products from AliExpress </button></div>
            <div id="ae_catalog_container"></div>
            <div class="clear"></div>
        </div>';
    }
?>
</div>
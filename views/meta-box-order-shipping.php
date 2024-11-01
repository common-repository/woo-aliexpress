<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$orderID = get_post_meta($post->ID, '_aew_order_id',true);
$keyCarrier = get_post_meta($post->ID, '_aew_key_carrier',true);
$keyCarrierReal = get_post_meta($post->ID, '_aew_key_carrier_real',true); //Filter
$currentTrackingNumber = get_post_meta($post->ID, '_aew_tracking_number', true);
echo __('Required when you mark the order as finish', 'aliexpress');
?>
<br>
<label>
    <span><?=__('Select carrier', 'aliexpress'); ?></span>
    <select name="aew_carriers_sent">
        <?php
            require_once(AEW_AE_PATH . 'AEFactory.php');
            require_once(AEW_AE_PATH . 'AECarriers.php');
            $factory = new AEFactory(AEW_TOKEN); 
            $carriers = new AECarriers($factory);
            $AllCarriers = json_decode($carriers->get_all_carriers(),true);
            foreach($AllCarriers['result_list']['aeop_logistics_service_result'] as $carrier) {
                if($carrier['logistics_company'] == $keyCarrier) {
                    $originalCarrier = ' (Original Order)';
                }else{
                    $originalCarrier = '';
                }
                if($keyCarrierReal) {
                    if($keyCarrierReal == $carrier['logistics_company']) {
                        $selected = "selected='selected'";
                        $selectedExpression = $carrier['tracking_no_regex'];
                    }else{ 
                        $selected = '';
                        $selectedExpression = '';
                    }
                }else{
                    if($keyCarrier == $carrier['logistics_company']) {
                        $selected = "selected='selected'";
                        $selectedExpression = $carrier['tracking_no_regex'];
                    }else{ 
                        $selected = '';
                        $selectedExpression = '';
                    }
                }
                echo '<option value="'.$carrier['logistics_company'].'" data-expression="'.$carrier['tracking_no_regex'].'" '.$selected.'>'.$carrier['display_name']. $originalCarrier.'</option>';
            }
            ?>
    </select>
</label>
<?php
if(substr($keyCarrierReal, 0, 5) == 'OTHER') {
    $trakingURLShow = '';
    $requiredInput = 'required';
}else{
    $trakingURLShow = 'display:none';
    $requiredInput = '';
}
?>
<label class="trackingURL" style="<?=$trakingURLShow?>">
    <span><?=__('Tracking Website (Required)', 'aliexpress'); ?></span>
    <input type="text" name="aew_website_tracking" value="<?=get_option('_aew_other_carrier_url')?>" placeholder="https://...." <?=$requiredInput?> />
</label>
<label>
    <span><?=__('Tracking Number','aliexpress') ?></span>
    <input type="text" id="aew_tracking_number" name="aew_tracking_number" data-pattern="<?=$selectedExpression?>" placeholder="<?=__('Set Tracking Number','aliexpress')?>" value="<?=$currentTrackingNumber?>" />
</label>
<?php
$orderFinishAliExpress = get_post_meta($post->ID, '_ae_order_send_aliexpress_finish', true);
if($orderFinishAliExpress == "1") {
    $msgAliExpressOrder = get_post_meta($post->ID, '_ae_order_msg_aliexpress_finish', true);
    $dateAliExpressOrder = get_post_meta($post->ID, '_ae_order_date_aliexpress_finish', true);
?>
<label>
    <p><strong><?=__('Order Finish AliExpress','aliexpress')?></strong></p>
    <p><?=$msgAliExpressOrder?></p>
    <p><strong><?=__('Confirm and send tracking number to AliExpress', 'aliexpress')?></strong> <?=date_i18n(get_option('date_format'), strtotime($dateAliExpressOrder)) .' '. date_i18n(get_option('time_format'), strtotime($dateAliExpressOrder))?></p>
</label>
<?php } ?>
<p class="explication_partial_shipping" style="display:none;"><?=__('Select the products that are going to be sent','aliexpress')?></p>
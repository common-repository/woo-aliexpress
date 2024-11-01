<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$carriers = get_option('_aew_carriers', array());
$orderItemMetaID = AEW_MAIN::aew_exist_shipping_order($post->ID);
$shippingID = wc_get_order_item_meta($post->ID, 'method_id',true);
$keyCarrier = get_post_meta($post->ID, '_aew_key_carrier',true);
?>
<label>
    <span><?=__('Local Shipping ID','aliexpress') ?></span>
    <?php
    if($carriers) {
    echo '<select name="aew_shipping_id">';
        echo '<option value="0">'.__('Choose Option','aliexpress').'</option>';
        foreach($carriers as $key => $carrier) {
            if($carrier == '') { continue; }

            if($key == $keyCarrier) {
                $selected = 'selected="selected"';
            }else{
                if($carrier == $shippingID) {
                    $selected = 'selected="selected"';
                }else{
                    $selected = '';
                }
            }
            echo '<option value="'.$carrier.'" '.$selected.'>'.$carrier.'</option>';
        }
    echo '</select>';
    }
    ?>
</label>
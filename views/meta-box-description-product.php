<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$titleCustom = get_post_meta($post->ID,'_aew_custom_title',true);
echo '<p>'.__('Use a custom title if you don\'t want to send the WooCommerce title to AliExpress','aliexpress') .'</p>';
echo '<input type="text" name="aew_custom_title" size="100" value="'.$titleCustom.'">';

$content = get_post_meta($post->ID,'_aew_custom_description',true);
echo '<p>'.__('Use a custom description if you don\'t want to send the WooCommerce description to AliExpress','aliexpress') .'</p>';
wp_editor( $content, 'aew_custom_description' );
?>
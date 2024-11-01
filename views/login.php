<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<div class="block_need_aliexpress_account">
    <div class="row">
        <div class="col"><img src="<?=plugins_url('../assets/aliexpress-woocommerce-128x128.png', __FILE__)?>" /></div>
        <div class="col">
            <?=sprintf(__('<strong>IMPORTANT NOTE:</strong> to set up AliExpress for WooCommerce it is essential that you have a merchant account on AliExpress. <a href="%s" target="_blank">Don\'t have a merchant account? Click here.</a>','aliexpress'), 'https://login.aliexpress.com/join/seller/unifiedJoin.htm?_regbizsource=ES_Woo_Wecomm')?>
        </div>
    </div>
</div>

<p style="font-size:16px;width:40%;"><?=sprintf(__('Currently it has 60 days free use, then the use of the plugin will be subject to <a href="%s" target="_blank">our price rates</a>','aliexpress'),'https://wecomm.es/tarifa-de-precios-aliexpress-woocommerce-plugin')?></p>

<div style="text-align:center;display:block;width:100%;background:#FFF;padding:25px 0px;">
<h2 style="text-align:center"><?=__('Link AliExpress Account', 'aliexpress')?></h2>
    
    <input type="hidden" name="url_callback_aliexpress" value="<?=home_url()?>/wp-admin/admin-ajax.php?action=aew_set_token&version2=1" />
    <input type="email" class="email_ae" name="email_aliexpress" placeholder="<?=__('Your AliExpress Email','aliexpress')?>" required/>
    <p class="description"><?=__('Enter the account to login on AliExpress','aliexpress')?></p>
    <input type="hidden" name="aew_lang_wp" value="<?=get_locale()?>" required/>
    
    <form action="" method="get" id="formLoginAew" /><button class="ae_button authorize"><?=__('Log in / Authorize','aliexpress')?></button></form>
</div>
<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/5d923b0cdb28311764d69264/default';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->

<?php

/**
 * 商品的SKU信息
 * @author auto create
 */
class GlobalAeopAeProductSku
{
	
	/** 
	 * sku分国家的日常促销价
	 **/
	public $aeop_s_k_u_national_discount_price_list;
	
	/** 
	 * Sku属性对象list，允许1-3个sku属性对象，按sku属性顺序排放。sku属性从类目属性接口getAttributesResultByCateId获取。该项值输入sku属性，不能输入普通类目属性。注意，sku属性是有顺序的，必须按照顺序存放。
	 **/
	public $aeop_s_k_u_property_list;
	
	/** 
	 * 条码，所有的仓发商品（尖货，自营，假发）会返回这个参数
	 **/
	public $barcode;
	
	/** 
	 * 产品的货币单位。美元: USD, 卢布: RUB
	 **/
	public $currency_code;
	
	/** 
	 * SKU ID，格式：“sku_property_id:property_value_id”
	 **/
	public $id;
	
	/** 
	 * SKU实际可售库存属性ipmSkuStock，该属性值的合理取值范围为0~999999，如该商品有SKU时，请确保至少有一个SKU是有货状态，也就是ipmSkuStock取值是1~999999，在整个商品纬度库存值的取值范围是1~999999。 如果同时设置了skuStock属性，那么系统以ipmSkuStock属性为优先；如果没有设置ipmSkuStock属性，那么系统会根据skuStock属性进行设置库存，true表示999，false表示0。
	 **/
	public $ipm_sku_stock;
	
	/** 
	 * Sku商家编码。 格式:半角英数字,长度20,不包含空格大于号和小于号。如果用户只填写零售价（productprice）和商品编码，需要完整生成一条SKU记录提交，否则商品编码无法保存。系统会认为只提交了零售价，而没有SKU，导致商品编辑未保存。
	 **/
	public $sku_code;
	
	/** 
	 * sku日常促销价
	 **/
	public $sku_discount_price;
	
	/** 
	 * Sku价格。取值范围:0.01-100000;单位:美元。 如:200.07，表示:200美元7分。需要在正确的价格区间内。
	 **/
	public $sku_price;
	
	/** 
	 * Sku库存,数据格式有货true，无货false；至少有一条sku记录是有货的。
	 **/
	public $sku_stock;	
}
?>
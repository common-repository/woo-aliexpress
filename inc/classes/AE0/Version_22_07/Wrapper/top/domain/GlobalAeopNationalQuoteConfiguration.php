<?php

/**
 * 商品分国家报价的配置
 * @author auto create
 */
class GlobalAeopNationalQuoteConfiguration
{
	
	/** 
	 * jsonArray格式的分国家定价规则数据。 1)基于基准价格按比例配置的数据格式；2)基于基准价格按涨或者跌多少；3）为每个SKU直接设置价格绝对值。其中shiptoCountry：ISO两位的国家编码（目前支持国家：RU US CA ES FR UK NL IL BR CL AU UA BY JP TH SG KR ID MY PH VN IT DE SA AE PL TR）， percentage：相对于基准价的调价比例（百分比整数，支持负数，当前限制>=-30 && <=100）；configuration_type为absolute：14:193为sku属性ID：SKU属性值， 多个属性用，号拼接。
	 **/
	public $configuration_data;
	
	/** 
	 * 分国家定价规则类型[absolute: 为每个SKU直接设置价格绝对值；percentage：基于基准价格按比例配置; relative:相对原价涨或跌多少;
	 **/
	public $configuration_type;	
}
?>
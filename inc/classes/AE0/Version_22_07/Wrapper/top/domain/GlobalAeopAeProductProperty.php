<?php

/**
 * 商品的类目属性
 * @author auto create
 */
class GlobalAeopAeProductProperty
{
	
	/** 
	 * 自定义属性名属性名。 自定义属性名时,该项必填.
	 **/
	public $attr_name;
	
	/** 
	 * 属性名ID。从类目属性接口getAttributesResultByCateId获取普通类目属性，不可填入sku属性。 自定义属性名时,该项不填.
	 **/
	public $attr_name_id;
	
	/** 
	 * 自定义属性值。自定义属性名时,该项必填。 当自定义属性值内容为区间情况时，建议格式2 - 5 kg。(注意，数字'-'单位三者间是要加空格的！)
	 **/
	public $attr_value;
	
	/** 
	 * 自定义属性值的结束端
	 **/
	public $attr_value_end;
	
	/** 
	 * 属性值ID
	 **/
	public $attr_value_id;
	
	/** 
	 * 自定义属性值的开始端
	 **/
	public $attr_value_start;
	
	/** 
	 * 自定义属性值单位
	 **/
	public $attr_value_unit;	
}
?>
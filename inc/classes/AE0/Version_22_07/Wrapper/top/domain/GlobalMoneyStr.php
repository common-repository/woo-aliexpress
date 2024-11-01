<?php

/**
 * 手续费（已废弃）
 * @author auto create
 */
class GlobalMoneyStr
{
	
	/** 
	 * 金额（mount=cent/cant_factor）
	 **/
	public $amount;
	
	/** 
	 * 最小货币单位（例如人民币：分）
	 **/
	public $cent;
	
	/** 
	 * 到最小货币单元的乘积因子（例如人民币：100）
	 **/
	public $cent_factor;
	
	/** 
	 * 货币描述
	 **/
	public $currency;
	
	/** 
	 * 币种
	 **/
	public $currency_code;	
}
?>
<?php

/**
 * 入参
 * @author auto create
 */
class OrderListRequest
{
	
	/** 
	 * 订单创建时间结束值，格式: yyyy-MM-dd hh:MM:ss,如2018-12-01 23:59:29，此时间为美国太平洋时间。(输入01/12/2018格式的时间、或时间维度未精确到秒，该筛选条件不生效）
	 **/
	public $create_date_end;
	
	/** 
	 * 订单创建时间起始值，格式: yyyy-MM-dd hh:MM:ss,如2018-12-01 00:00:00，此时间为美国太平洋时间。(输入01/12/2018格式的时间、或时间维度未精确到秒，该筛选条件不生效）
	 **/
	public $create_date_start;
	
	/** 
	 * 订单修改时间结束值，格式: MM/dd/yyyy HH:mm:ss,如10/08/2013 00:00:00
	 **/
	public $modified_date_end;
	
	/** 
	 * 订单修改时间起始值，格式: MM/dd/yyyy HH:mm:ss,如10/08/2013 00:00:00
	 **/
	public $modified_date_start;
	
	/** 
	 * 订单状态： PLACE_ORDER_SUCCESS:等待买家付款; IN_CANCEL:买家申请取消; WAIT_SELLER_SEND_GOODS:等待您发货; SELLER_PART_SEND_GOODS:部分发货; WAIT_BUYER_ACCEPT_GOODS:等待买家收货; FUND_PROCESSING:买卖家达成一致，资金处理中； IN_ISSUE:含纠纷中的订单; IN_FROZEN:冻结中的订单; WAIT_SELLER_EXAMINE_MONEY:等待您确认金额; RISK_CONTROL:订单处于风控24小时中，从买家在线支付完成后开始，持续24小时。 以上状态查询可分别做单独查询，不传订单状态查询订单信息不包含（FINISH，已结束订单状态） FINISH:已结束的订单，需单独查询。
	 **/
	public $order_status;
	
	/** 
	 * 查询多个订单状态下的订单信息，具体订单状态见order_status描述
	 **/
	public $order_status_list;
	
	/** 
	 * 当前页码
	 **/
	public $page;
	
	/** 
	 * 每页个数，最大50
	 **/
	public $page_size;	
}
?>
<?php

/**
 * 出参
 * @author auto create
 */
class TpOpenOrderDetailDto
{
	
	/** 
	 * 买家信息
	 **/
	public $buyer_info;
	
	/** 
	 * 买家全名
	 **/
	public $buyer_signer_fullname;
	
	/** 
	 * 买家账号ID
	 **/
	public $buyerloginid;
	
	/** 
	 * 商品信息
	 **/
	public $child_order_ext_info_list;
	
	/** 
	 * 子订单列表
	 **/
	public $child_order_list;
	
	/** 
	 * 手续费（已废弃）
	 **/
	public $escrow_fee;
	
	/** 
	 * 冻结状态
	 **/
	public $frozen_status;
	
	/** 
	 * 资金状态(NOT_PAY,未付款； PAY_SUCCESS,付款成功； WAIT_SELLER_CHECK，卖家验款)
	 **/
	public $fund_status;
	
	/** 
	 * 订单创建时间
	 **/
	public $gmt_create;
	
	/** 
	 * 订单修改时间
	 **/
	public $gmt_modified;
	
	/** 
	 * 支付成功时间（与订单列表中gmtPayTime字段意义相同）
	 **/
	public $gmt_pay_success;
	
	/** 
	 * 订单结束时间
	 **/
	public $gmt_trade_end;
	
	/** 
	 * 订单ID
	 **/
	public $id;
	
	/** 
	 * 产品总金额
	 **/
	public $init_oder_amount;
	
	/** 
	 * 是否手机订单
	 **/
	public $is_phone;
	
	/** 
	 * 纠纷信息
	 **/
	public $issue_info;
	
	/** 
	 * 纠纷状态
	 **/
	public $issue_status;
	
	/** 
	 * 放款信息
	 **/
	public $loan_info;
	
	/** 
	 * 放款状态("loan_none":无放款;"wait_loan":等待放款;"loan_ok":放款成功)
	 **/
	public $loan_status;
	
	/** 
	 * 物流信息
	 **/
	public $logistic_info_list;
	
	/** 
	 * 物流费用
	 **/
	public $logistics_amount;
	
	/** 
	 * 物流状态：等待卖家发货 WAIT_SELLER_SEND_GOODS,卖家部分发货 SELLER_SEND_PART_GOODS,卖家已发货  SELLER_SEND_GOODS,买家已确认收货  BUYER_ACCEPT_GOODS,NO_LOGISTICS
	 **/
	public $logistics_status;
	
	/** 
	 * 买家备注
	 **/
	public $memo;
	
	/** 
	 * 操作日志列表
	 **/
	public $opr_log_dto_list;
	
	/** 
	 * 订单金额
	 **/
	public $order_amount;
	
	/** 
	 * 留言信息
	 **/
	public $order_msg_list;
	
	/** 
	 * 订单状态
	 **/
	public $order_status;
	
	/** 
	 * 付款方式 （migs信用卡支付走人民币渠道； migs102MasterCard支付并且走人民币渠道； migs101Visa支付并且走人民币渠道； pp101 PayPal； mb MoneyBooker渠道； tt101 Bank Transfer支付； wu101 West Union支付； wp101 Visa走美金渠道的支付； wp102 Mastercard 走美金渠道的支付； qw101 QIWI支付； cybs101 Visa走CYBS渠道的支付； cybs102 Mastercard 走CYBS渠道的支付； wm101 WebMoney支付； ebanx101 巴西Beloto支付 ；）
	 **/
	public $payment_type;
	
	/** 
	 * 收货地址信息
	 **/
	public $receipt_address;
	
	/** 
	 * 退款信息
	 **/
	public $refund_info;
	
	/** 
	 * 负责人账号ID
	 **/
	public $seller_operator_aliidloginid;
	
	/** 
	 * 负责人账号ID
	 **/
	public $seller_operator_login_id;
	
	/** 
	 * 卖家名称
	 **/
	public $seller_signer_fullname;	
}
?>
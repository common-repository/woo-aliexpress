<?php

/**
 * 留言信息
 * @author auto create
 */
class GlobalAeopTpOrderMsgDto
{
	
	/** 
	 * 订单号
	 **/
	public $business_order_id;
	
	/** 
	 * 留言内容
	 **/
	public $content;
	
	/** 
	 * 订单创建时间(此时间为美国太平洋时间)
	 **/
	public $gmt_create;
	
	/** 
	 * 订单修改时间(此时间为美国太平洋时间)
	 **/
	public $gmt_modified;
	
	/** 
	 * 订单ID
	 **/
	public $id;
	
	/** 
	 * 信息发送方( buyer; seller)
	 **/
	public $poster;
	
	/** 
	 * 接收者主帐号序号
	 **/
	public $receiver_admin_seq;
	
	/** 
	 * 接收者FirstName
	 **/
	public $receiver_first_name;
	
	/** 
	 * 接收者LastName
	 **/
	public $receiver_last_name;
	
	/** 
	 * 接收者帐号ID
	 **/
	public $receiver_login_id;
	
	/** 
	 * 接收者帐号序号
	 **/
	public $receiver_seq;
	
	/** 
	 * 发送者主帐号序号
	 **/
	public $sender_admin_seq;
	
	/** 
	 * 发送者FirstName
	 **/
	public $sender_first_name;
	
	/** 
	 * 发送者LastName
	 **/
	public $sender_last_name;
	
	/** 
	 * 发送者帐号ID
	 **/
	public $sender_login_id;
	
	/** 
	 * 发送者帐号序号
	 **/
	public $sender_seq;
	
	/** 
	 * 留言状态
	 **/
	public $status;	
}
?>
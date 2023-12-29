<?php

// user.status
define('USER_STATUS_NORMAL',			'n');		// 正常
define('USER_STATUS_FROZEN',			'f');		// 冻结中


// warehouse.status
define('WARE_HOUSE_STATUS_NORMAL',		'n');		// 仓库状态正常
define('WARE_HOUSE_STATUS_FROZEN',		'f');		// 仓库状态禁用

// warehouse.status
define('PRODUCT_STATUS_ONLINE',			'n');		// 商品状态上线
define('PRODUCT_STATUS_OFFLINE',		'f');		// 商品状态下线

// cart.status
define('CART_STATUS_NORMAL',			'n');		// 正常
define('CART_STATUS_DELETED',			'd');		// 删除状态
define('CART_STATUS_ORDER',				'o');		// 已生成订单


// user_order.status
define('USER_ORDER_STATUS_PAYMENT',		'f');		// 待付款
define('USER_ORDER_STATUS_PAID',		'p');		// 待发货
define('USER_ORDER_STATUS_SHIPPED',		's');		// 已发货
define('USER_ORDER_STATUS_CANCEL',		'c');		// 取消订单


//user_order.consignee_status 
define('ORDER_CONSIGNEE_STATUS_NOTHING',		0);		// 订单下包裹无退款
define('ORDER_CONSIGNEE_STATUS_PART',		1);		// 订单下包裹部分退款
define('ORDER_CONSIGNEE_STATUS_FULL',		2);		// 订单下包裹全部退款


// user_order_sync.sync_status
define('USER_ORDER_SYNC_STATUS_PENDING',		'u');		// 未同步
define('USER_ORDER_SYNC_STATUS_SUCCESS',		's');		// 同步成功
define('USER_ORDER_SYNC_STATUS_FAILED',			'f');		// 同步失败

// user_order_sync.sync_query_status
define('USER_ORDER_SYNC_QUERY_STATUS_PENDING',		'u');		// 未同步
define('USER_ORDER_SYNC_QUERY_STATUS_SUCCESS',		's');		// 同步成功
define('USER_ORDER_SYNC_QUERY_STATUS_FAILED',	 	'f');		// 同步失败

// package.status
define('PACKAGE_STATUS_PAYMENT',				'f');		// 待付款
define('PACKAGE_STATUS_PENDING',				'p');		// 待发货
define('PACKAGE_STATUS_SHIPPED',				's');		// 已发货
define('PACKAGE_STATUS_CANCELED',				'c');		// 已取消

// user_balance.type
define('USER_BALANCE_TYPE_CHARGE',		'c');			// 充值
define('USER_BALANCE_TYPE_PAY',			'p');			// 支出
define('USER_BALANCE_TYPE_REFUND',		'r');			// 取消订单退回金额

// user_order.source
define('USER_ORDER_SOURCE_TAOBAO',	'taobao');			// 淘宝
define('USER_ORDER_SOURCE_TMALL',	'tmall');			// 天猫
define('USER_ORDER_SOURCE_PDD',		'pdd');				// 拼多多
define('USER_ORDER_SOURCE_JD',		'jd');				// 京东
define('USER_ORDER_SOURCE_OTHER',	'other');			// 其他


// site.type
define('SITE_TYPE_WEB',					'w');				// web站点
define('SITE_TYPE_API',					'a');				// API接口

// site.status
define('SITE_STATUS_NORMAL',			'n');				// 已激活
define('SITE_STATUS_FROZEN',			'f');				// 未激活

// recharge
define('RECHARGE_PAY_TYPE_ALIPAY',		'a');				// 支付宝
define('RECHARGE_PAY_TYPE_WECHAT',		'w');				// 微信支付

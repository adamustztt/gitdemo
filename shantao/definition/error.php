<?php


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// define.php 已定义
define('ERROR_SUCCESS',					0);		// 操作成功
define('ERROR_INVALID_REQUEST',			3);		// 请求非法（非json格式）
define('ERROR_ADMIN_NOT_LOGGED_IN',		996);	// 管理员没有登录

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 外部错误码，数值请控制在 2000 以内

// -----BEGIN errors.md-----
// basic
//define('ERROR_INTERNAL',							2);		// 内部错误
define('ERROR_INVALID_ID',							10);	// id不合法
//define('ERROR_INVALID_ID_CARD',						11);	// 身份证号不合法
//define('ERROR_INVALID_DESCRIPTION',					13);	// 描述不合法
//define('ERROR_INVALID_NAME',						14);	// 名字不合法
define('ERROR_INVALID_DATETIME',					22);	// 时间不合法
define('ERROR_INVALID_AMOUNT',						26);	// 金额不合法
//define('ERROR_ALREADY_EXISTS',						25);	// 已经存在了
define('ERROR_INVALID_MOBILE',						28);	// 手机号不合法
//define('ERROR_INVALID_LIST',						30);	// 列表不合法
//define('ERROR_INVALID_DAY',							34);	// 天数不合法
//define('ERROR_INVALID_ADDITIONAL',					37);	// 附加信息不合法
//define('ERROR_INVALID_DATE',						38);	// 日期不合法
//define('ERROR_INVALID_TRADE_ID',					42);	// 流水号不合法
define('ERROR_INVALID_CHANNEL',						44);	// 渠道不合法
//define('ERROR_INVALID_QQ',							60);	// QQ 号不合法
//define('ERROR_INVALID_EMAIL',						61);	// Email 不合法
//define('ERROR_INVALID_ADDRESS',						62);	// 地址不合法
//define('ERROR_INVALID_TELEPHONE',					63);	// 固定电话不合法
//define('ERROR_INVALID_RELATIONSHIP',				64);	// 关系不合法
define('ERROR_INVALID_TOKEN',						72);	// token不合法
//define('ERROR_INVALID_URL',							73);	// URL不合法
define('ERROR_INVALID_ORDER_SN',					74);	// 订单号不合法
// basic
define('ERROR_INVALID_RANGE',						17);	// Range不合法
define('ERROR_INVALID_STATUS',						18);	// 状态不合法
//define('ERROR_INVALID_LICENSE',						23);	// 执照号不合法
//define('ERROR_ALREADY_VERIFIED',					35);	// 已经认证过了
define('ERROR_INVALID_DATA',						43);	// 数据不合法
//define('ERROR_INVALID_INFO',						45);	// 信息不合法
//define('ERROR_INVALID_ID_RANGE',					46);	// ID Range不合法
//define('ERROR_INVALID_COMPANY_NAME',				48);	// 企业名不合法
define('ERROR_NOT_ENOUGH_MONEY',					50);	// 金额不足
//define('ERROR_INVALID_OPERATION',					51);	// 操作不合法
//define('ERROR_INVALID_SIGNATURE',					71);	// 签名不合法
//define('ERROR_INVALID_PASSWORD',					75);	// 密码不对
//define('ERROR_USER_NOT_EXISTS',						76);	// 用户不存在
//define('ERROR_USER_STATUS_FROZEN',					77);	// 用户被冻结
//define('ERROR_INVALID_VERIFY_CODE',					78);	// 验证码错误
//define('ERROR_INVALID_USER_ID',						79);	// 用户id号错误

// user
//define('ERROR_USER_BALANCE_NOT_ENOUGH',				90);	// 用户余额不足
// site
//define('ERROR_INVALID_SITE_ID',						100);	// 分站id号错误

// product
define('ERROR_INVALID_PRODUCT_ID',					120);	// 商品ID号错误
define('ERROR_INVALID_PRODUCT_NUMBER',				121);	// 商品数量错误


//define('ERROR_INVALID_APP_ID',						122);	// AppID不合法
//define('ERROR_INVALID_APP_SECRET',					123);	// AppSecret不合法
//define('ERROR_INVALID_AUTHORIZATION',				124);	// 认证信息出错

// order
//define('ERROR_INVALID_CONSIGNEE',					130);	// 收货人信息有误
//define('ERROR_CART_IS_EMPTY',						131);	// 购物车为空
//define('ERROR_ORDER_STATUS',						132);	// 订单状态有误
//define('ERROR_INVALID_ORDER',						133);	// 订单状态有误
//define('ERROR_ORDER_CANCEL_FAIL',					134);	// 订单取消失败

define('ERROR_INVALID_WAREHOUSE_ID',				135);	// 无效的仓库ID
//define('ERROR_INVALID_PROVINCE',					137);	//省份不合法
//define('ERROR_INVALID_CITY',						138);	//城市不合法
//define('ERROR_INVALID_DISTRICT',					139);	//地区不合法


// external
//define('ERROR_EXT_AUTH_FAILED',						200);	// 认证失败
//define('ERROR_EXT_SITE_FROZEN',						201);	// 站点冻结中，请联系管理员
//define('ERROR_EXT_INVALID_PRODUCT_ID',				202);	// 商品ID不合法
//define('ERROR_EXT_INVALID_WAREHOUSE_ID',			203);	// 仓库ID不合法
//efine('ERROR_EXT_INVALID_PRODUCT_NUMBER',			204);	// 商品数量不合法
//define('ERROR_EXT_INVALID_CONSIGNEE',				205);	// 收货人信息不合法
define('ERROR_EXT_INVALID_SOURCE',					206);	// 订单来源不合法
//define('ERROR_EXT_INVALID_ORDER_ID',				207);	// 订单号不合法
//define('ERROR_EXT_CANCEL_ORDER_FAILED',				208);	// 取消订单失败，请联系客服
//define('ERROR_EXT_BALANCE_NOT_ENOUGH',				209);	// 账户余额不足
//define('ERROR_EXT_UNKNOWN',							210);	// 未知错误，请联系客服
//define('ERROR_EXT_INVALID_REMARK',					211);	// 备注错误
//define('ERROR_EXT_INVALID_PACKAGE_ID',				212);	// 包裹ID错误
//define('ERROR_EXT_PACKAGE_HAS_DELIVERED',			213);	// 包裹已发货，无法取消


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 内部错误码，2000 以内不要和外部错误码冲突，2000 以上可随意定义

// basic
//define('ERROR_INVALID_PHONE',						10012);	// 手机号不合法
define('ERROR_INVALID_FILTER',						10015);	// Filter不合法
define('ERROR_INVALID_SORT',						10016);	// Sort不合法
//define('ERROR_INVALID_PASSWORD',					10019);	// 密码不对
//define('ERROR_LOGIN_FAILED_TOO_MANY',				10021);	// 登录失败次数太多
define('ERROR_INVALID_TYPE',						10027);	// 类型不合法
//define('ERROR_INVALID_NEW_PASSWORD',				10029);	// 新密码不合法
//define('ERROR_MULITIPLE_ROLE',						10031);	// 管理员角色重复
//define('ERROR_INVALID_PLAN',						10032);	// 计划不合法
//define('ERROR_VERIFY_FAILED',						10036);	// 认证失败
//define('ERROR_INVALID_CMD',							10039);	// 命令不合法
//define('ERROR_INVALID_REASON',						10040);	// 原因不合法
//define('ERROR_INVALID_FRONT_DATA',					10041);	// 前端数据不合法
//define('ERROR_INVALID_NUMBER',						10047);	// 数字不合法
//define('ERROR_DUPLICATE_LICENSE',					10049);	// 营业执照号冲突
//define('ERROR_INVALID_REQUEST_ID',					10052);	// req_id不合法
//define('ERROR_INVALID_RATE',						10053);	// 利率不合法
//define('ERROR_XIUPINJIE_BALANCE_NOT_ENOUGH',		10054);	// 秀品街余额不足
define('ERROR_INVALID_RECHARGE_TRADE_SN',			10055);	// 充值订单号不合法
//define('ERROR_CANCEL_PACKAGE_FAILED',				10056);	// 取消订单失败


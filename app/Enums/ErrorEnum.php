<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/16
 * Time: 16:20
 */

namespace App\Enums;


use App\Exceptions\OuterApiException;

class ErrorEnum
{
	const SUCCESS = [0, '操作成功'];
	const INTERNAL_ERROR = [2, '内部错误'];
	const ERROR_INVALID_TOKEN = [72, 'token已过期'];
	const ERROR_INVALID_DOMAIN = [73, '请求域名错误'];
	const ERROR_CRON = [423, '定时任务更新失败'];
	
	
	const SYSTEM_ERROR = [100, '系统繁忙，请稍后重试'];
	const PARAMS_ERROR = [110, '缺少必要参数'];
	const DATA_NOT_EXIST = [120, '订单号错误或订单不存在'];
	const DATA_EXIST_PAY = [121, '订单号已完成'];
	const ILLEGAL_OPERATION = [130, '非法操作'];
	const INVALID_OPERATION = [131, '无效操作'];
	const NOT_JSON_OPERATION = [132, '请求格式必须为JSON'];
	const CAPTCHA_ERROR = [140, '验证码错误'];
	const REQUEST_LIMIT_ERROR = [160, '您的请求太过频繁，请稍后再试'];
	const REQUEST_DAY_LIMIT_ERROR = [161, '今天请求次数已达到上限，请明天再试'];
	const REQUEST_TIMEOUT = [162, '请求超时'];
	const DATABASE_HANDLE_ERROR = [163, '数据处理失败'];
	const SITE_MONEY_ERROR = [164, '分站扣款失败'];
	const SITE_MONEY_LOG_ERROR = [165, '分站流水记录创建失败'];
	const SITE_MONEY_NEGATIVE_ERROR = [166, '分站余额不足无法扣款'];
	const OUTER_API_ERROR = [170, '接口出错'];


	const UNAUTHORIZED = [401, '登录过期，请重新登录'];
	const EXCEPTION_ERROR = [400, '系统异常，请稍后重试'];
	const NOT_FOUND = [404, '请求的网页不存在'];
	const METHOD_NOT_FOUND = [405, '请求的方法不存在'];
	const VALIDATE_ERROR = [422, '请求参数校验失败'];
	const VALIDATE_SHOP_ID_ERROR = [422, '店铺ID必填'];
	const VALIDATE_PRODUCT_ERROR = [422, '该商品不支持密文'];
	const VALIDATE_PROVINCE_ERROR = [422, '省错误'];
	const VALIDATE_CITY_ERROR = [422, '市错误'];
	const PARAM_ERROR = [427,'参数错误'];
	const ERROR_COST_PRODUCT = [428,'成本价层数超过限制'];
	const ERROR_ENABLE_API = [429,'该接口已废弃'];
	const ERROR_POPUP= [430, '暂无数据'];


	// 外部接口
	const ERROR_EXT_AUTH_FAILED = [200, '认证失败'];
	const ERROR_EXT_SITE_FROZEN = [201, '站点冻结中，请联系管理员'];
	const ERROR_EXT_INVALID_PRODUCT_ID = [202, '商品ID不合法'];
	const ERROR_EXT_INVALID_WAREHOUSE_ID = [203, '仓库ID不合法'];
	const ERROR_EXT_INVALID_PRODUCT_NUMBER = [204, '商品数量不合法'];
	const ERROR_EXT_INVALID_CONSIGNEE = [205, '收货人信息不合法'];
	const ERROR_EXT_INVALID_SOURCE = [206, '订单来源不合法'];
	const ERROR_EXT_INVALID_ORDER_ID = [207, '订单号不合法'];
	const ERROR_EXT_CANCEL_ORDER_FAILED = [208, '取消订单失败，请联系客服'];
	const ERROR_EXT_BALANCE_NOT_ENOUGH = [209, '账户余额不足,请先充值'];
	const ERROR_EXT_UNKNOWN = [210, '未知错误，请联系客服'];
	const ERROR_EXT_INVALID_REMARK = [211, '备注错误'];
	const ERROR_EXT_INVALID_PACKAGE_ID = [212, '包裹ID错误'];
	const ERROR_EXT_PACKAGE_HAS_DELIVERED = [213, '包裹已发货，无法取消'];
	const ERROR_SHOPPING_CART_NULL = [214, '购物车为空'];
	const ERROR_ORDER_PAYMENT = [215, '订单支付失败'];
	const ERROR_ORDER_STATUS = [216, '订单不是待付款状态'];
	const ERROR_CREATE_ORDER_FAIL = [217, '创建订单失败'];
	const ERROR_PACKAGE_STATUS = [218, '当前状态不可取消发货'];
	const ERROR_PAY_TIMEOUT = [219, '支付超时!'];
	const ERROR_EXT_CANCEL_PACKAGE_FAILED = [220, '取消包裹失败，请联系客服'];
	const ERROR_PACKAGE_LOCK = [221, "请稍等,当前包裹正在发货中..."];//包裹发货枷锁失败提示
	const ERROR_UP_PARAMS = [222, '苍源回调参数错误'];
	const ERROR_HANDLE_UP_PARAMS = [223, '处理苍源参数错误'];
	const ERROR_TOOL_LINK = [224, '商品链接不存在'];
	const ERROR_TOOL_ACCOUNT = [225, '旺旺账号不存在'];
	const ERROR_PRODUCT_STATUS = [226, '商品已下架'];
	const ERROR_WAREHOUSE_STATUS = [227, '仓库已下架'];
	const ERROR_WAREHOUSE_SOURCE = [228, '当前商品暂不支持该发货平台'];
	const ERROR_EXPRESS_SEND = [229, '当前商品未设置发货地,请联系客服'];
	const ERROR_BASE_EXPRESS_SEND = [230, '当前商品仓库未设置发货地'];
	const ERROR_SITE_USER = [231, '用户信息异常,请联系客服'];
	const ERROR_PRODUCT = [232, '商品信息异常,请联系客服'];
	const ERROR_PRODUCT_EXPRESS = [233, '商品发货地异常,请联系客服'];
	const ERROR_PRODUCT_SITE_ID = [234, 'site_order_id已存在'];
	const ERROR_BAN_CITY = [235, '商品发货地已禁发'];
	const ERROR_PRODUCT_EXPRESS_ID = [236, '商品发货地错误'];
	const ERROR_WAREHOUSE_PRICE = [237, '仓库价格错误'];
	const ERROR_SHENFENG_WAREHOUSE_ORDER = [238, '顺丰仓库下单失败'];
	const ERROR_EXT_INVALID_PACKAGE_STATUS = [239, '该包裹不支持取消'];
	const ERROR_SITE_INFO = [240, '站长信息错误,请联系客服'];
	const ERROR_YUNDA_WAREHOUSE = [241, '韵达服务器请求失败'];
	const ERROR_YUNDA_WAREHOUSE_RESULT = [242, '韵达面单不足'];
	// 业务用户相关
	const AGE_WRONG = [1001, '年龄不符'];
	const MOBILE_ILLEGAL = [1002, '手机号不合法'];
	const USER_NOT_EXISTS = [1003, '用户不存在'];
	const USER_STATUS_OlREADY_FROZEN = [1004, '用户被冻结'];
	const PASSWORD_ERROR = [1005, '密码不正确'];
	const MOBILE_EXISTS = [1006, '手机号已存在'];
	const LOGIN_FAILURE = [1007, '登录失败'];
	const ERROR_INVALID_STATUS = [1008, '包裹状态不正确'];
	const ERROR_INVALID_ORDER = [1009, '第三方单号不存在'];
	const ERROR_CANCEL_CAOSUDAIFA_ORDER = [1010, '取消参数快递单号不能为空'];
	const ERROR_FABI_NOT_CANCEL = [1011, '无取消接口 请先到苍源取消该订单'];
	const ERROR_TOOL_PRICE = [1012, '站长工具未设置，请联系管理员'];
	const ERROR_SUBMIT_PAY_NO= [1013, '该订单已完成'];
	const ERROR_SITE_TOOL_COST_PRICE = [1014, '工具价格设置有误，请联系客服'];
	const ERROR_ORDER_DECRYPT = [1015, '包裹待解密'];

	//支付回调相关
    const ERROR_UPDATE_ORDER=[1200,"订单更新失败"];
    const ERROR_UPDATE_USER=[1201,"用户余额更新失败"];
    const ERROR_ADD_RECHARGE=[1202,"支付记录添加失败"];
    const ERROR_ADD_BALANCE_LOG=[1203,"用户金额记录添加失败"];
    const ERROR_PAYED=[1204,"用户金额记录添加失败"];
    
    // api用户返回错误码相关
	const ERP_USER_AUTH =[2000,"未开通该权限"]; // 店铺未授权
	const FORBIDDEN_ZONE =[2001,"该地区已禁发"]; // 禁发区
	const ERR_PRODUCT_SOURCE =[2003,"该商品暂不支持该发货平台"]; // 禁发区
	const PACKAGE_TRUE =[2000,""]; // 包裹正确
	const ERP_ERP_ERROR =[2010,"未知错误 请联系客服"]; // 包裹正确
	const ERP_USER_SHOP =[2011,"店铺未授权"]; // 店铺未授权
	const ERP_USER_SHOP_BUY =[2012,"店铺未订购该软件"]; // 店铺未授权
	const ERP_PRODUCT_INFO =[2013,"该商品信息不存在，请请求ERP接口获取"];
	const ERP_USER_SHOP_ORDER =[2004,"该订单不存在"]; // 店铺未授权
	const ERROR_COUNT = [2012, '数量最小为1'];
	const KS_ERP_ERROR = [2013, '未知错误,请联系客服'];
	const ERROR_ORDER_INFO = [2014, '参数oaid无效'];

	const ERROR_CAPTCHA_VAL = [2015, '图形验证码错误'];
}

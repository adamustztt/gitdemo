<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/



//生成swagger文档
use App\Http\Utils\CustomExpress\YundaGuangzhouQingtianUtil;
use App\Models\OrderConsignee;

$router->get("swagger","BaseController@swagger");
//加载swagger文档
$router->get("loadswagger","BaseController@loadSwagger");

$router->get("register_captcha","BaseController@registerCaptcha");

$router->group(['middleware' => ['requestLog']], function () use ($router) {
	$router->post('importBlackPhone', 'BaseController@importBlackPhone');
	$router->get('/test', function () {

		$w = new \App\Services\Warehouses\KuaidiyunNewWarehouse();
		$w->saveWarehouse();
		dd(1);
		$package_info = OrderConsignee::getOrderConsigneeById(775211);
		$w->saveOrderByQuery($package_info);
		dd(1);
		$w->createOrder($package_info);
		$aa = $w->saveWarehouse();
//		$aa = $w->saveProduct();
		$w->saveProduct();
		\App\Http\Logic\Cron\CronOrderDeliverControllerLogic::orderDeliverDy(431226,1);
	});
	$router->group(['middleware' => ['requestLog']], function () use ($router) {
		$router->get('fixUserBalanceLogType', 'TempController@fixUserBalanceLogType');
		$router->get('fixApiUserOrderPlatformIncome', 'TempController@fixApiUserOrderPlatformIncome');


		$router->get('fixUserBalanceLogTypeRefund', 'TempController@fixUserBalanceLogTypeRefund');
		$router->get('fixApiPackageRefundPlatformIncome', 'TempController@fixApiPackageRefundPlatformIncome');
		$router->get('packagePolicyLst', 'Cron\OrderPolicyController@packagePolicyLst'); // 礼速通包裹预警
		$router->get('cronGetPlatformOrder', 'Cron\CronPlatformOrderController@cronGetPlatformOrder'); // 同步平台订单
	});
	
	// 预警
	$router->get('checkPackageStatus', 'Cron\OrderPolicyController@checkPackageStatus');
	$router->get('checkPackageExpressNo', 'Cron\OrderPolicyController@checkPackageExpressNo');
	



	$router->get('example/test', 'ExampleController@test');
	$router->get('example/getList', 'ExampleController@getList');
	$router->get('syncDamaijiaWarehouseUserSource', 'ProductController@syncDamaijiaWarehouseUserSource');
	$router->get('update_site_money', 'SiteController@siteBalance'); //修复取消订单站长利润  此接口只用一次
	// 定时任务
	$router->get('exportOrderConsigneeExcel', 'ExcelController@exportOrderConsigneeExcel'); // 订单导出
	$router->post('syncDownOrderStatus',			'Console\OrderConsigneeController@syncDownOrderStatus');	//同步api用户订单状态
	$router->post('syncDownOrderStatusV1',			'Console\OrderConsigneeController@syncDownOrderStatusV1');	//同步api用户包裹信息推送
	$router->post('pushVtoolOrderInfo',			'Console\OrderConsigneeController@pushVtoolOrderInfo');	//同步vtool4-6次用户包裹信息推送
	$router->post('addPackagePush',			'Cron\PackagePushController@addPackagePush');	//包裹定时推送 出单推送有漏的 补救漏掉的包裹
	
	$router->get('cronSyncProduct',			'Cron\CronProductController@cronSyncProduct');	//同步商品
	$router->get('cronSyncSiteProductPrice',			'Cron\CronProductController@cronSyncSiteProductPrice');	//同步分站商品价格
	$router->get('cronSyncWarehouse',			'Cron\CronWarehouseController@cronSyncWarehouse');	//同步仓库
	$router->get('cronOrderDeliverByPackageId',			'Cron\CronOrderDeliverController@cronOrderDeliverByPackageId');	//单个发货
	$router->get('cronOrderDeliver',			'Cron\CronOrderDeliverController@cronOrderDeliver');	//批量发货
	$router->get('cronRequestPddShopOrder',			'Cron\CronOrderDeliverController@cronRequestPddShopOrder');	//拉去拼多多店铺订单

	$router->get('/exportToolOrder', 'ToolController@exportToolOrder'); //下载
	$router->post('/callbackShopInfo', 'External\ErpController@callbackShopInfo'); //erp回调
	$router->post('/getBalanceLogType', 'UserBalanceController@getBalanceLogType'); //erp回调
	$router->post('/ksErpShopCallback', 'External\ErpController@ksErpShopCallback'); //快手erp回调
	$router->post('/dyErpShopCallback', 'External\ErpController@dyErpShopCallback'); //快手erp回调
	$router->post('/jdCallbackShopInfo', 'DamaijiaUserDeliverShopController@jdCallbackShopInfo'); //jderp回调


	// 禁发推送定时轮训发送推送
	$router->post('/cronBanCity', 'Cron\CronBanCityController@cronBanCity');
	// 禁发发送推送接口
	$router->post('/cronBanCityByPushId', 'Cron\CronBanCityController@cronBanCityByPushId');
	// 订单定时解密任务
	$router->post('/taskPackageDecrypt', 'Console\OrderConsigneeDecryptController@taskPackageDecrypt');

});

$router->group(['middleware' => ['requestLog','checkToken',"checkSiteDomain"]], function () use ($router) {
	
	// 用户
	$router->post('/user_get_info', 'UserController@getInfo');
	$router->post('/set_notify_url', 'UserController@set_notify_url');

	// 购物车
	$router->post('/cart_add', 'CartController@add');
	$router->post('/cart_delete', 'CartController@delete');
	$router->post('/cart_clear', 'CartController@clear');
	$router->post('/cart_get_list', 'CartController@getList');
	$router->post('/cart_compute_amount', 'CartController@computeAmount');//旧版计算订单价格
	$router->post('/v1/cart_compute_amount', 'CartController@computeAmountV1');//新版计算订单价格
	$router->post('/verifyConsignee', 'CartController@verifyConsignee'); // 验证包裹信息
	$router->post('/verifyConsigneeV1', 'CartController@verifyConsigneeV1');// 验证包裹信息
	
	// 订单
	$router->post('/order_get_list', 'UserOrderController@getList');
	$router->post('/order_create', 'UserOrderController@create');//订单支付接口
	$router->post('/order_upload_parse_address', 'UserOrderController@uploadAndParseAddress'); //下单验证
	$router->post('/uploadAndParseAddressV1', 'UserOrderController@uploadAndParseAddressV1');// 下单发货验证
	$router->post('/cancelUserOrder', 'UserOrderController@cancelUserOrder'); // 取消订单
	
	// 包裹
	$router->post('/package_get_list', 'PackageController@getListV2');
	$router->post('/package_cancel_ship', 'PackageController@cancelShip');
	$router->post('/submitExpressSheet', 'ExpressSheetController@submitExpressSheet');
	
	// 账单
	$router->post('/balance_get_list', 'UserBalanceController@getList');
	//账单v1
	$router->post('v1/balance_get_list', 'UserBalanceController@getListV1');

	// 充值
	$router->post('/recharge_query', 'RechargeRecordController@query');
	$router->post('/recharge_apply_payment_qr', 'RechargeRecordController@applyPaymentCode');//唤起支付接口
	$router->get('/recharge_query_payment_alipay_status', 'RechargeRecordController@queryRechargeStatus');
	$router->get('/getPayType', 'RechargeRecordController@getPayType'); // 获取支持的充值方式
	//工具
	
	$router->post('/getToolPrice', 'ToolController@getToolPrice'); //获取工具价格
	$router->post('/createOrderGuessLike', 'ToolController@createOrderGuessLike'); //猜你喜欢  //有好货
	$router->post('/createOrderFirstScreen', 'ToolController@createOrderFirstScreen'); //洋淘秀卡首屏
	$router->post('/createOrderKeywordFirstScreen', 'ToolController@createOrderKeywordFirstScreen'); //关键词卡首屏
	$router->post('/createOrderSimilar', 'ToolController@createOrderSimilar'); //找相似入口
	$router->post('/createOrderSearchplus', 'ToolController@createOrderSearchplus'); //找相似入口
	$router->post('/addToolReport', 'ToolReportController@addToolReport'); //打黑
	$router->post('/getBlackNumber', 'ToolController@getBlackNumber'); //黑号库
	$router->post('/listToolLog', 'ToolController@listToolLog'); //工具使用记录
	$router->post('/getToolDetail', 'ToolController@getToolDetail'); //工具使用详情
	
	

	$router->post('/fileUpload', 'UtilController@fileUpload'); //上传图片
	
	// 邀请相关
	$router->get('/recordUserInviteCount', 'InviteController@recordUserInviteCount'); //复制记录
	$router->post('/listInviteLog', 'InviteController@listInviteLog'); //邀请列表
	$router->post('/listUserLevel', 'UserLevelController@listUserLevel'); //等级列表
	$router->post('/listUserLevelExpress', 'UserLevelController@listUserLevelExpress'); //等级运费列表
	$router->post('/userLevelChangePopup', 'UserLevelLogController@userLevelChangePopup'); //等级运费列表


	$router->get('/tbOrderLink', 'DamaijiaUserDeliverShopController@tbOrderLink'); //获取订购授权链接


});


$router->group(["middleware"=>["requestLog"]],function ($router){
    $router->get('/requestUpOrderV1', 'Console\OrderConsigneeController@requestUpOrderV1');
    $router->get('/cronRequestUpOrderByPackageId', 'Console\OrderConsigneeController@cronRequestUpOrderByPackageId');
});

$router->group(["middleware"=>["requestLog","checkSiteDomain"]],function ($router){
    // 用户
    $router->post('/user_login', 'UserController@login');
    $router->post('/user_send_code', 'UserController@sendCode');
    $router->post('/user_register', 'UserController@register');
    $router->post('/user_forget_password', 'UserController@updateUserPassword');


    // 商品
    $router->post('/product_get_list', 'ProductController@getList');//旧版获取商品列表
    $router->post('/v1/product_get_list', 'ProductController@getListV1');//新版获取商品列表
    $router->post('/product_get_info', 'ProductController@getInfo');
    $router->post('/v1/product_get_info', 'ProductController@getInfoV1');//新版获取商品详情
    $router->post('/product_get_warehouse_list', 'ProductController@getWarehouseList');

    // 系统设置
    $router->post('/setting_get_list', 'SettingController@getList');

    // 仓库
    $router->post('/warehouse_get_list', 'WarehouseController@getList');//旧版获取仓库
    $router->post('/v1/warehouse_get_list', 'CustomWarehouseController@getListV1');//新版获取仓库
	$router->post('/yunlipinOrderCallback', 'WarehouseCallbackController@yunlipinOrderCallback');
	$router->post('/getExpressPriceList', 'CustomWarehouseController@getExpressPriceList');//新版获取仓库价格
    
    // 定时任务
	$router->get('/requestUpOrder', 'Console\OrderConsigneeController@requestUpOrder');
	$router->get('/queryUpOrder', 'Console\OrderConsigneeController@queryUpOrder');
	$router->get('/updateOrderOvertime', 'Console\OrderConsigneeController@updateOrderOvertime');
    $router->get('/getOrderPayResult', 'Cron\CronPayController@getOrderPayResult');

	$router->get('/getOrderPayResult', 'Cron\CronPayController@getOrderPayResult');

	
	
	// 分站
	$router->post('/getSiteInfo', 'SiteController@getSiteInfo');



	// 店铺相关
	$router->get('/getShopOrderLink', 'DamaijiaUserDeliverShopController@getShopOrderLink'); //店铺订购链接
	$router->post('/listUserShop', 'DamaijiaUserDeliverShopController@listUserShop'); //店铺列表
	$router->post('/listShopOrder', 'DamaijiaUserDeliverShopController@listShopOrder'); //店铺商品列表
	$router->post('/getUserShop', 'DamaijiaUserDeliverShopController@getUserShop'); //店铺详情
	$router->post('/setUserShop', 'DamaijiaUserDeliverShopController@setUserShop'); //店铺设置
	$router->post('/refreshGerUserShop', 'DamaijiaUserDeliverShopController@refreshGerUserShop'); //刷新获取关联店铺
	$router->post('/authorizationShop', 'DamaijiaUserDeliverShopController@authorizationShop'); //重新授权
	$router->post('/getUserShopByProductId', 'DamaijiaUserDeliverShopController@getUserShopByProductId'); //获取商品店铺信息
	$router->post('/deleteShop', 'DamaijiaUserDeliverShopController@deleteShop'); //店铺删除
	$router->post('/ksShopCallback', 'DamaijiaUserDeliverShopController@ksShopCallback'); //快手店铺回掉
	$router->post('/webCallbackShopInfo', 'DamaijiaUserDeliverShopController@webCallbackShopInfo'); //淘宝店铺回调
	$router->post('/webCallbackShopInfo3', 'DamaijiaUserDeliverShopController@webCallbackShopInfo3'); //打单软件3淘宝店铺回调
	$router->post('/webCallbackShopInfo4', 'DamaijiaUserDeliverShopController@webCallbackShopInfo4'); //打单软件4淘宝店铺回调
	$router->get('/tb_auth_link4', 'DamaijiaUserDeliverShopController@tb_auth_link4'); //获取授权跳转
	
	$router->post('/getPlatformOrder', 'PlatformOrderController@getPlatformOrder'); //拉取店铺订单
	$router->post('/getPlatformOrderV1', 'PlatformOrderController@getPlatformOrderV1'); //拉取店铺订单
	$router->post('/getPlatformOrderV3', 'PlatformOrderController@getPlatformOrderV3'); //获取店铺订单
	$router->post('/entryCreateOrder', 'PlatformOrderController@entryCreateOrder');// 密文页面下单
	
	$router->get('/getJdAuthLink', 'DamaijiaUserDeliverShopController@getJdAuthLink');// 获取京东授权链接

});
// API用户相关
$router->group(['middleware' => ['requestLog',"checkExtToken"], 'namespace'=> '\App\Http\Controllers\External',
	'prefix'=>'external'], function ($router) {
//	$router->group(['middleware' => ['requestLog'], 'namespace'=> '\App\Http\Controllers\External',
//		'prefix'=>'external'], function ($router) {	
	$router->post('warehouse_get_list',		'WareHouseController@getList');//旧版获取仓库列表
	$router->get('v1/warehouse_get_list',		'WareHouseController@getListV1');//新版获取仓库列表
	$router->post('product_get_list',		'ProductController@getList');//旧版获取商品列表
	$router->get('v1/product_get_list',		'ProductController@getListV1');//新版获取商品列表
	$router->post('order_create',			'OrderController@create');//旧版创建订单
	$router->post('v1/order_create',			'OrderController@createV1');//新版创建订单
	$router->post('v1/order_create_encryption',			'OrderController@orderCreateEncryption');//密文创建订单
	$router->post('createEntryOrder',			'PlatformOrderController@createEntryOrder');//密文创建订单包裹 一对多 // vtool页面调用
	$router->post('order_cancel',			'OrderController@cancel');
	$router->post('submit_payment',			'OrderController@submitPayment');
	$router->post('package_get_list',		'PackageController@getList');
	$router->post('getPackageById',		'PackageController@getPackageById');
//	$router->post('package_cancel',			'PackageController@cancel');
	$router->post('package_cancel',			'PackageController@cancelV1');
	
	$router->post('order_get_list',			'OrderController@orderList');
	$router->post('get_user_balance',			'UserController@getBalance');
	$router->post('get_the_last_product',			'UpdateController@getTheLastProduct');
	$router->post('get_the_last_warehouse',			'UpdateController@getTheLastWarehouse');
	$router->post('get_product_count',			'UpdateController@getProductCount');
	$router->post('get_warehouse_count',			'UpdateController@getWarehouseCount');
	$router->post('get_packages_status',			'OrderController@listOrderConsignee');
	$router->post('getScanCode', 'PayManageController@getScanCode'); // 获取支付二维码v2接口
	$router->post('listMerchant', 'PayManageController@listMerchant'); // 获取商户列表接口
	$router->post('getOrderDetail', 'PayManageController@getOrderDetail'); // 获取订单明细接口
	//tberp
	$router->post('/tb-oaidhigh', 'TbErpController@tboaidhigh'); //oaid解密升级
	$router->post('/get-decrypt-by-id', 'TbErpController@getdecryptbyid'); //oaid解密升级第二步

	$router->get('/1688-item-info', 'TbErpController@iteminfo1688'); //1688商品详情
	$router->get('/item-info-low-price', 'TbErpController@iteminfolowprice'); //淘宝商品详情

	$router->group(['middleware' => ["checkApiUserErpShopMiddleware"]], function ($router) {
		$router->post('/getShopInfo', 'TbErpController@requestTbErp');
		$router->post('/getSubscribe', 'ErpController@getSubscribe');//订购时间查询
		$router->post('/listTraderates', 'ErpController@listTraderates'); //评价列表查询
		$router->post('/getCompanies', 'ErpController@getCompanies'); //快递公司查询
		$router->post('/getShopAuthorize', 'ErpController@getShopAuthorize');//淘宝店铺授权
		$router->post('/listSold', 'ErpController@listSold');//订单列表查询
		$router->post('/getFullInfo', 'ErpController@getFullInfo');//订单明细
		$router->post('/setMemo', 'ErpController@setMemo');//订单备注
		$router->post('/listIncrement', 'ErpController@listIncrement'); //增量订单查询
		$router->post('/getCompaniesDetail', 'ErpController@getCompaniesDetail');//查询物详情
		$router->post('/send', 'ErpController@send');//线下物流发货
		$router->post('/setResend', 'ErpController@setResend'); //修应该快递单号
		$router->post('/decode', 'ErpController@decode'); //解密
		$router->post('/waybill', 'ErpController@waybill'); //云打印

		$router->post('/v1/requestTbErp/{code}', 'TbErpController@requestTbErp'); // 获取店铺信息

	});
	// pdderp
	$router->group(['middleware' => ["checkApiUserPddErpShopMiddleware"]], function ($router) {
		$router->post('/pgetShopInfo', 'ErpController@pgetShopInfo'); // /拼多多订购时间查询（拼）
		$router->post('/pagingOrders', 'ErpController@pagingOrders');//订单列表查询
		$router->post('/mpagingOrders', 'ErpController@mpagingOrders'); //通过订单号获取订单（拼）
		$router->post('/getwaybillfreewn', 'ErpController@getwaybillfreewn'); //非官方订单出单
		$router->post('/platformTheSingle', 'ErpController@platformTheSingle');////平台出单（拼）
		$router->post('/pddShipFree', 'ErpController@pddShipFree');//批量发货（拼）
		$router->post('/pddShopInfo', 'ErpController@pddShopInfo');//获取店铺信息(拼)
		$router->post('/pddGetRefundAddress', 'ErpController@pddGetRefundAddress');////拼获取退货地址(拼)
		$router->post('/pddGetNetworkInformation', 'ErpController@pddGetNetworkInformation'); ////获取网点信息(拼)
		$router->post('/pddWaybillRecovery', 'ErpController@pddWaybillRecovery');//单号回收(拼)（拼）
		$router->post('/pddSynchronizationOrder', 'ErpController@pddSynchronizationOrder');//用订单同步接口(拼)
		
		// pdd接pdderp版
	});
	// pdderp
	$router->group(['middleware' => ["checkApiUserPddErpShopMiddleware"]], function ($router) {
		$router->post('/v1/requestPddErp/{code}', 'PddErpController@requestPddErp');
	});
	// kserp
	$router->group(['middleware' => ["checkApiUserKsErpShopMiddleware"]], function ($router) {
		$router->post('/requestKsErp/{code}', 'KsErpController@requestKsErp');
	});
	// dyerp
	$router->group(['middleware' => ["checkApiUserDyErpShopMiddleware"]], function ($router) {
		$router->post('/requestDyErp/{code}', 'DyErpController@requestDyErp');
	});
	// 物流发货
	$router->post('/taoStoreBackstageDelivery', 'SendGoodsController@taoStoreBackstageDelivery'); //(淘)店铺后台发货
	$router->post('/taoNoteFlagBatch', 'SendGoodsController@taoNoteFlagBatch'); //(淘)备注标旗(批量)
	$router->post('/taoToObtainOrderInformation', 'SendGoodsController@taoToObtainOrderInformation'); //(淘)获取订单信息
	$router->post('/taoGetTheOrderForShipment', 'SendGoodsController@taoGetTheOrderForShipment');//(淘)获取订单(待发货)
	$router->post('/taoForSurfaceSingleZn', 'SendGoodsController@taoForSurfaceSingleZn'); //(淘)获取面单(智能)
	$router->post('/taoForSurfaceSingleFzn', 'SendGoodsController@taoForSurfaceSingleZn'); //(淘)获取面单(非智能)
	$router->post('/suborderDelivery', 'SendGoodsController@suborderDelivery'); //(淘)子订单发货
	$router->post('/pinToOrder', 'SendGoodsController@pinToOrder'); //(拼)待发订单
	$router->post('/pinForSurfaceSingleFzn', 'SendGoodsController@pinForSurfaceSingleFzn'); //(拼)获取面单(非智能)
	$router->post('/pinForSurfaceSingleZn', 'SendGoodsController@pinForSurfaceSingleZn'); //(拼)获取面单(智能)
	$router->post('/pinSynchronization', 'SendGoodsController@pinSynchronization'); //(拼)根据订单号同步
	$router->post('/pinAutomaticDelivery', 'SendGoodsController@pinAutomaticDelivery'); //(拼)自动发货
	$router->post('/taoAccessToTheMerchantOrderTime', 'SendGoodsController@taoAccessToTheMerchantOrderTime'); //(淘)获取商家订购时长
	$router->post('/pinAccessToTheMerchantOrderTime', 'SendGoodsController@pinAccessToTheMerchantOrderTime'); //(拼)获取订购时长
});

$router->group(["middleware"=>"requestLog",'namespace'=> '\App\Http\Controllers\External', 'prefix'=>'external'], function ($router) {

	$router->post('user_login',		'UserController@login');
	$router->post('payment_notify', 'PayController@notify'); // 支付宝充值回调
	
});

// 给管理后台调用的数据初始化接口
$router->group(["middleware"=>["requestLog"]],function ($router){
    $router->post('warehouse_sync',			'ChannelSyncController@syncWarehouse');
    $router->post('product_sync',			'ChannelSyncController@syncProduct');
    $router->post('package_sync',			'ChannelSyncController@syncPackage');//发货接口
    $router->post('package_cancel_sync',	'ChannelSyncController@syncCancelPackage');
    $router->post('package_sync_express',	'ChannelSyncController@syncExpress');//手动获取包裹物流信息接口

});

// 定时任务
$router->group(["prefix"=>"cron"],function ($router){
	$router->get('getPddUserShop',			'Cron\PddErpController@getPddUserShop');
});

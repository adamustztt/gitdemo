<?php


namespace App\Enums;


class TbEnum
{
	const TB_CODE=[
		"taobao-shop-seller-get"=>"taobao.shop.seller.get",// ( 卖家店铺基础信息查询 )
		"taobao-vas-subscribe-get"=>"taobao.vas.subscribe.get", // ( 订购关系查询 ) 
		"taobao-traderates-get"=>"taobao.traderates.get", // ( 搜索评价信息 ) 
		"taobao-logistics-companies-get"=>"taobao.logistics.companies.get", //快递公司查询
		"taobao-user-seller-get"=>"taobao.user.seller.get", // 查询卖家用户信息 )
		"taobao-trades-sold-get"=>"taobao.trades.sold.get", //查询卖家已卖出的交易数据（根据创建时间）
		"taobao-trade-fullinfo-get"=>"taobao.trade.fullinfo.get", //获取单笔交易的详细信息
		"taobao-trade-memo-update"=>"taobao.trade.memo.update", //( 修改交易备注 )
		"taobao-trades-sold-increment-get"=>"taobao.trades.sold.increment.get", //( 查询卖家已卖出的增量交易数据（根据修改时间） )
		"taobao-logistics-orders-detail-get"=>"taobao.logistics.orders.detail.get", //( 批量查询物流订单,返回详细信息 )
		"taobao-logistics-offline-send"=>"taobao.logistics.offline.send", //( 自己联系物流（线下物流）发货 )
		"taobao-logistics-consign-resend"=>"taobao.logistics.consign.resend", //( 修改物流公司和运单号 )
		"taobao-top-oaid-decrypt"=>"taobao.top.oaid.decrypt", //( OAID解密 )
		"waybill"=>"cainiao.waybill.ii.get", //( 电子面单云打印接口 )
	];
}

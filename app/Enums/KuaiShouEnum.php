<?php


namespace App\Enums;


class KuaiShouEnum
{

	const METHOD = [
		"open.service.market.buyer.service.info"=>"get",
		"open.order.cursor.list"=>"get",
		"open.order.detail"=>"get",
		"open.seller.order.cps.list"=>"get",
		"open.seller.order.cps.detail"=>"get",
		"open.seller.order.sku.update"=>"post",
		"open.seller.order.goods.deliver"=>"post",
		"open.seller.order.logistics.update"=>"post",
		"open.seller.order.note.add"=>"post",
		"open.seller.order.close"=>"post",
		"open.seller.order.refund.pcursor.list"=>"get",
		"open.seller.order.refund.detail"=>"get",
		"open.seller.order.refund.returngoods.approve"=>"post",
		"open.seller.order.refund.approve"=>"post",
		"open.refund.reject"=>"post",
	];
	const CODE = [
		"ksSeller"=>"open.service.market.buyer.service.info",
		"ksPcursorList"=>"open.order.cursor.list",
		"ksDetail"=>"open.order.detail",
		"ksCpsList"=>"open.seller.order.cps.list",
		"ksCpsDetail"=>"open.seller.order.cps.detail",
		"ksSkuUpdate"=>"open.seller.order.sku.update",
		"ksGoodsDeliver"=>"open.seller.order.goods.deliver",
		"ksLogisticsUpdate"=>"open.seller.order.logistics.update",
		"ksNoteAdd"=>"open.seller.order.note.add",
		"ksOrderClose"=>"open.seller.order.close",
		"ksRefundPcursor"=>"open.seller.order.refund.pcursor.list",
		"ksRefundDetail"=>"open.seller.order.refund.detail",
		"ksReturngoodsApprove"=>"open.seller.order.refund.returngoods.approve",
		"ksRefundApprove"=>"open.seller.order.refund.approve",
		"ksRefundReject"=>"open.refund.reject",
	];
}

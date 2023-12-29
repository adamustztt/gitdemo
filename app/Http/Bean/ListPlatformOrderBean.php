<?php


namespace App\Http\Bean;

/**
 * @SWG\Definition(
 * )
 */
class ListPlatformOrderBean
{
	/**
	 * @SWG\Property(
	 *     description="总数量"
	 * )
	 * @var int
	 */
	public $total = "";

	/**
	 * @SWG\Property(
	 *     description="当前页数"
	 * )
	 * @var string
	 */
	public $index = "";

	/**
	 * @SWG\Property(
	 *     description="数据",
	 *     @SWG\Items(
	 *          @SWG\Property(
	 *              property="ext_platform_order_sn",
	 *              type="string",
	 *              description="第三方订单平台号"
	 *          ),
	 *          @SWG\Property(
	 *              property="consignee",
	 *              type="string",
	 *              description="收件人姓名"
	 *          ),
	 *          @SWG\Property(
	 *              property="consignee_mask",
	 *              type="string",
	 *              description="收件人姓名脱敏 平台是拼多多显示脱敏数据"
	 *          ),
	 *          @SWG\Property(
	 *              property="mobile",
	 *              type="string",
	 *              description="手机号"
	 *          ),
	 *          @SWG\Property(
	 *              property="mobile_mask",
	 *              type="string",
	 *              description="手机号脱敏 平台是拼多多显示脱敏数据"
	 *          ),
	 *          @SWG\Property(
	 *              property="address",
	 *              type="string",
	 *              description="收货地址"
	 *          ),
	 *          @SWG\Property(
	 *              property="address_mask",
	 *              type="string",
	 *              description="收货地址脱敏 平台是拼多多显示脱敏数据"
	 *          ),
	 *          @SWG\Property(
	 *              property="province",
	 *              type="string",
	 *              description="省"
	 *          ),
	 *          @SWG\Property(
	 *              property="city",
	 *              type="string",
	 *              description="市"
	 *          ),
	 *          @SWG\Property(
	 *              property="district",
	 *              type="string",
	 *              description="区"
	 *          ),
	 *          @SWG\Property(
	 *              property="tag",
	 *              type="string",
	 *              description="旗帜标记颜色 red yellow green purple blue grey"
	 *          ),
	 *          @SWG\Property(
	 *              property="seller_memo",
	 *              type="string",
	 *              description="卖家备注"
	 *          ),
	 *          @SWG\Property(
	 *              property="oaid",
	 *              type="string",
	 *              description="oaid 淘宝下单参数"
	 *          )
	 *     )
	 * )
	 * @var array
	 */
	public $list = "";
}

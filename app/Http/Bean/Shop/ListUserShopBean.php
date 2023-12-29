<?php


namespace App\Http\Bean\Shop;

/**
 * @SWG\Definition(
 * )
 */
class ListUserShopBean
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
	 *              property="id",
	 *              type="string",
	 *              description="ID"
	 *          ),
	 *          @SWG\Property(
	 *              property="user_id",
	 *              type="string",
	 *              description="用户ID"
	 *          ),
	 *          @SWG\Property(
	 *              property="shop_id",
	 *              type="string",
	 *              description="店铺ID"
	 *          ),
	 *          @SWG\Property(
	 *              property="shop_type",
	 *              type="string",
	 *              description="店铺类型"
	 *          ),
	 *          @SWG\Property(
	 *              property="shop_name",
	 *              type="string",
	 *              description="店铺名称"
	 *          ),
	 *          @SWG\Property(
	 *              property="authorization_time",
	 *              type="string",
	 *              description="授权时间"
	 *          ),
	 *          @SWG\Property(
	 *              property="expiration_time",
	 *              type="string",
	 *              description="过期时间"
	 *          ),
	 *          @SWG\Property(
	 *              property="is_expiration",
	 *              type="string",
	 *              description="是否过期 true是 false否"
	 *          ),
	 *          @SWG\Property(
	 *              property="create_time",
	 *              type="string",
	 *              description="创建时间"
	 *          ),
	 *          @SWG\Property(
	 *              property="shop_status",
	 *              type="string",
	 *              description="店铺状态 0关闭 1开启"
	 *          ),
	 *          @SWG\Property(
	 *              property="is_tag",
	 *              type="string",
	 *              description="是否标记 1以标记  2未标记"
	 *          ),
	 *          @SWG\Property(
	 *              property="tag_color",
	 *              type="string",
	 *              description="标记颜色 'red','yellow','green','purple','blue'"
	 *          ),
	 *          @SWG\Property(
	 *              property="tag_remark",
	 *              type="string",
	 *              description="标记备注"
	 *          )
	 *     )
	 * )
	 * @var array
	 */
	public $list = "";
}

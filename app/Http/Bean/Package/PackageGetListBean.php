<?php


namespace App\Http\Bean\Package;

/**
 * @SWG\Definition(
 * )
 */
class PackageGetListBean
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
	 *              property="express_sheet.no",
	 *              type="string",
	 *              description="底单边号"
	 *          ),
	 *          @SWG\Property(
	 *              property="express_sheet.create_time",
	 *              type="string",
	 *              description="申请时间"
	 *          ),
	 *          @SWG\Property(
	 *              property="express_sheet.reason",
	 *              type="string",
	 *              description="申请原因"
	 *          ),
	 *          @SWG\Property(
	 *              property="express_sheet.status",
	 *              type="string",
	 *              description="状态 1 待审核 2审核通过 3驳回"
	 *          ),
	 *          @SWG\Property(
	 *              property="express_sheet.remark",
	 *              type="string",
	 *              description="客服备注"
	 *          ),
	 *          @SWG\Property(
	 *              property="express_no",
	 *              type="string",
	 *              description="快递单号"
	 *          ),
	 *          @SWG\Property(
	 *              property="status",
	 *              type="string",
	 *              description="'包裹状态 f 待付款 p未发货 s已发货 c已取消',"
	 *          ),
	 *          @SWG\Property(
	 *              property="express_sheet.email",
	 *              type="string",
	 *              description="接受邮箱"
	 *          )
	 *     )
	 * )
	 * @var array
	 */
	public $list = "";
}

<?php


namespace App\Http\Bean\Product;


/**
 * @SWG\Definition(
 * )
 */
class ProductListV1ResultBean
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
     *              property="productId",
     *              type="string",
     *              description="商品ID"
     *          ),
     *          @SWG\Property(
     *              property="warehouseId",
     *              type="string",
     *              description="关联仓库ID"
     *          ),
     *          @SWG\Property(
     *              property="name",
     *              type="string",
     *              description="商品名称"
     *          ),
     *          @SWG\Property(
     *              property="othumb",
     *              type="string",
     *              description="商品图片"
     *          ),
     *          @SWG\Property(
     *              property="price",
     *              type="string",
     *              description="商品价格"
     *          ),
     *          @SWG\Property(
     *              property="weight",
     *              type="string",
     *              description="重量"
     *          ),
     *          @SWG\Property(
     *              property="stock",
     *              type="string",
     *              description="商品库存"
     *          ),
     *          @SWG\Property(
     *              property="sales",
     *              type="string",
     *              description="销售数量"
     *          ),
     *          @SWG\Property(
     *              property="createTime",
     *              type="string",
     *              description="创建时间"
     *          ),
     *          @SWG\Property(
     *              property="warehouseAliasName",
     *              type="string",
     *              description="发货仓库"
     *          ),
     *          @SWG\Property(
     *              property="expressName",
     *              type="string",
     *              description="快递"
     *          ),
     *          @SWG\Property(
     *              property="expressPrice",
     *              type="string",
     *              description="运费"
     *          ),
	 *          @SWG\Property(
	 *              property="sourceInfo",
	 *              type="array",
	 *              description="发货平台",
	 * 				@SWG\Items(
	 *          		@SWG\Property(
	 *              		property="user_source",
	 *              		type="string",
	 *              		description="发货平台名称"
 *          			),
	 *     				@SWG\Property(
	 *              		property="remark",
	 *              		type="string",
	 *              		description="说明"
 *          			)
	 * 				)
	 *          )
     *     )
     * )
     * @var array
     */
    public $list = "";
}

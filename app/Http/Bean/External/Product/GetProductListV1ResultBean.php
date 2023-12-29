<?php


namespace App\Http\Bean\External\Product;

/**
 * @SWG\Definition(
 * )
 */
class GetProductListV1ResultBean
{
    /**
     * @SWG\Property(
     *     description="总数量"
     * )
     * @var string
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
     *              description="商品ID"
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
     *              property="cost_price",
     *              type="string",
     *              description="成本价"
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
     *              property="create_time",
     *              type="string",
     *              description="创建时间"
     *          ),
     *          @SWG\Property(
     *              property="user_source",
     *              type="string",
     *              description="收件人来源"
     *          )
     *     )
     * )
     * @var array
     */
    public $list = "";
}
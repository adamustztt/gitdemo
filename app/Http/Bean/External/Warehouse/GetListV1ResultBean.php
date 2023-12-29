<?php


namespace App\Http\Bean\External\Warehouse;

/**
 * @SWG\Definition(
 * )
 */
class GetListV1ResultBean
{
    /**
     * @SWG\Property(
     *     description="快递ID"
     * )
     * @var string
     */
    public $id = "";

    /**
     * @SWG\Property(
     *     description="快递名称"
     * )
     * @var string
     */
    public $expressName = "";

    /**
     * @SWG\Property(
     *     description="快递图片url"
     * )
     * @var string
     */
    public $expressImgUrl = "";

    /**
     * @SWG\Property(
     *     description="仓库信息",
     *     @SWG\Items(
     *          @SWG\Property(
     *              property="id",
     *              type="string",
     *              description="仓库ID"
     *          ),
     *          @SWG\Property(
     *              property="warehouseName",
     *              type="string",
     *              description="仓库名称"
     *          ),
     *          @SWG\Property(
     *              property="warehousePrice",
     *              type="string",
     *              description="仓库快递价格"
     *          )
     *     )
     * )
     * @var array
     */
    public $warehouseSend = "";
}
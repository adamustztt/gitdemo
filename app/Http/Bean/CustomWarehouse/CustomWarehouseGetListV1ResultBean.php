<?php


namespace App\Http\Bean\CustomWarehouse;

/**
 * @SWG\Definition(
 * )
 */
class CustomWarehouseGetListV1ResultBean
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
    public $warehouseName = "";

    /**
     * @SWG\Property(
     *     description="快递图片url"
     * )
     * @var string
     */
    public $warehouseImgUrl = "";

    /**
     * @SWG\Property(
     *     description="快递",
     *     @SWG\Items(
     *          @SWG\Property(
     *              property="id",
     *              type="string",
     *              description="ID"
     *          ),
     *          @SWG\Property(
     *              property="expressSendName",
     *              type="string",
     *              description="发货地名称"
     *          )
     *     )
     * )
     * @var array
     */
    public $expressSend = "";
}
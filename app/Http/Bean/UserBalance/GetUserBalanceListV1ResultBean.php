<?php


namespace App\Http\Bean\UserBalance;


/**
 * @SWG\Definition(
 * )
 */
class GetUserBalanceListV1ResultBean
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
     *     description="数据",
     *     @SWG\Items(
     *          @SWG\Property(
     *              property="trade_number",
     *              type="string",
     *              description="变更编号"
     *          ),
     *          @SWG\Property(
     *              property="type",
     *              type="string",
     *              description="交易类型"
     *          ),
     *          @SWG\Property(
     *              property="amount",
     *              type="string",
     *              description="交易点券"
     *          ),
     *          @SWG\Property(
     *              property="balance",
     *              type="string",
     *              description="交易后点券"
     *          ),
     *          @SWG\Property(
     *              property="additional",
     *              type="string",
     *              description="交易备注"
     *          )
     *     )
     * )
     * @var array
     */
    public $list = "";
}
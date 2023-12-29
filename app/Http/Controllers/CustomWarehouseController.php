<?php


namespace App\Http\Controllers;


use App\Http\Logic\CustomWarehouseLogic;
use App\Models\Site;

class CustomWarehouseController extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/v1/warehouse_get_list",
     *     tags={"快递管理"},
     *     summary="获取快递列表接口",
     *     description="获取快递列表接口",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response="200",
     *         description="success",
     *          @SWG\Schema(ref="#/definitions/CustomWarehouseGetListV1ResultBean")
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="fail",
     *         @SWG\Schema(ref="#/definitions/ErrorBean")
     *     )
     * )
     */
    public function getListV1()
    {
        $data = CustomWarehouseLogic::getCustomeWarehouseLogic();
        return $this->responseJson($data);
    }
	public function getExpressPriceList()
	{
		if($this->_site_id!=1){
			$user_id = Site::query()->where("id",$this->_site_id)->value("user_id");
		} else {
			$user_id = 0;
		}
		$data = CustomWarehouseLogic::getExpressPriceLogic($user_id);
		return $this->responseJson($data);
	}
}

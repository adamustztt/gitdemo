<?php
namespace App\Http\Controllers\External;

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use App\Http\Logic\External\CustomWarehouseLogic;
use App\Models\CustomWarehouseModel;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller;
use Base;
use WareHouse;
use Filter;

class WareHouseController extends BaseController
{
	public function getList()
	{
		CommonUtil::throwException(ErrorEnum::ERROR_ENABLE_API);
		$filter = [
			Filter::makeDBFilter('warehouse.status', WARE_HOUSE_STATUS_NORMAL, Filter::TYPE_EQUAL)
		];
		$warehouse_list = WareHouse::getList($filter);

		foreach($warehouse_list as $k=>$v) {
			$warehouse_list[$k]['name'] = $v['alias_name'];
			$warehouse_list[$k]['cost_price'] = $v['price'];
			$warehouse_list[$k]['typename'] = $v['alias_typename'];
		}
		return $this->responseJson($warehouse_list);
//		Base::dieWithResponse($warehouse_list);
	}

    /**
     * @SWG\Get(
     *     path="/external/v1/warehouse_get_list",
     *     tags={"API接口管理"},
     *     summary="获取快递列表接口",
     *     description="获取快递列表接口",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response="200",
     *         description="success",
     *          @SWG\Schema(ref="#/definitions/GetListV1ResultBean")
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
//        $datas = CustomWarehouseLogic::getCustomeWarehouseLogic();
		$datas = CustomWarehouseLogic::getCustomerWarehouseLogicV1();
		
        return $this->responseJson($datas);
	}
}

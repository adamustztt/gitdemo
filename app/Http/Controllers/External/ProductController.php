<?php
namespace App\Http\Controllers\External;

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Helper\WhereUtil;
use App\Http\Controllers\BaseController;
use App\Http\Logic\External\ProductLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller;
use Base;
use Product;
use Filter;
use App\Models\Product as Pro;

class ProductController extends BaseController
{
	/*public function getList(Request $request)
	{
//		$filter = [
//			Filter::makeDBFilter('product.status', PRODUCT_STATUS_ONLINE, Filter::TYPE_EQUAL),
//			Filter::makeDBFilter('site_product.site_id', $request->site_id, Filter::TYPE_EQUAL)
//		];
        $this->validate($request, [
            'filter.id' => 'int' ,
            'filter.status' => 'string' ,
            'range' => 'array',
        ]);
        if(!empty($request['filter'])){
			$where = [];
			$whereUtil = new WhereUtil($request['filter'],$where);
			$whereUtil->applyFilter('id','product.id');
			$whereUtil->applyFilter('status','product.status');
		}
        $field = ['product.id','product.warehouse_id','product.name','product.othumb','product.thumb',
			'site_product.price as cost_price','product.weight','product.stock','product.sales','product.status',
            'product.isort','product.create_time','product.update_time'];
        $where[] = ['site_id','=',$request->site_id];
		$products = Pro::listProduct($request,$where,$field);
        return $this->responseJson([
            'index' => $request['range'][0],
            'list' => $products,
            'total' => $products->total()
        ]);
	}*/
	public function getList(Request $request)
	{
		CommonUtil::throwException(ErrorEnum::ERROR_ENABLE_API);
		$params = $this->validate($request, [
			'filter.id' => 'int' ,
			'filter.status' => 'string' ,
			'range' => 'array',
		]);
		$params['filter']["status"] = PRODUCT_STATUS_ONLINE;
			$where = [];
			$whereUtil = new WhereUtil($params['filter'],$where);
			$whereUtil->applyFilter('id','product.id');
			$whereUtil->applyFilter('status','product.status');
		$field = ['product.id','product.warehouse_id',DB::raw("product.alias_name as name"),'product.othumb','product.thumb',
			'site_product.price as cost_price','product.weight','product.stock','product.sales','product.status',
			'product.isort','product.create_time','product.update_time','product.user_source'];
		$where[] = ['site_id','=',$request->site_id];
		$products = Pro::listProduct($request,$where,$field);
		return $this->responseJson([
			'index' => $request['range'][0],
			'list' => $products,
			'total' => $products->total()
		]);
	}

    /**
     * @SWG\Get(
     *     path="/external/v1/product_get_list",
     *     tags={"API接口管理"},
     *     summary="获取商品列表接口",
     *     description="获取商品列表接口",
     *     produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          in="query",
     *          description="仓库id",
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="page",
     *          in="query",
     *          description="第几页",
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="pageSize",
     *          in="query",
     *          description="每页数量",
     *          type="string"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="success",
     *          @SWG\Schema(ref="#/definitions/GetProductListV1ResultBean")
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
        $data = ProductLogic::getListV1Logic();
        return $this->responseJson($data);
	}
}

<?php
namespace App\Http\Controllers;

use App\Models\ExpressModel;
use App\Models\ExpressProductModel;
use App\Models\Product;
use App\Models\UserShopModel;
use App\Services\Warehouses\WarehouseService;
use Base;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Param;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tool\ShanTaoTool\ExcelTool;
use UserOrder;
use OrderConsignee;
use Filter;
use Util;
use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\UserOrder as UserOrderModel;
use App\Models\OrderConsignee as OrderConsigneeModel;

class UserOrderController extends BaseController
{
	public function create(Request $request)
	{
		$req = Base::getRequestJson();
		$this->validate($request, [
			'order_id' => 'required|int',
		]);
		UserOrder::create($req['order_id'],$this->_user_info);
		Base::dieWithResponse();
	}
	public function getList()
	{
		$params = app("request")->all();
		$date = date("Y-m-d H:i:s",time()-3600*24*30*6);
		if(empty($params["filter"]["create_time"])) {
			$start_time = $date;
			$end_time = date("Y-m-d H:i:s");
		} else {
			if($params["filter"]["create_time"][0] < $date) {
				$start_time = $date;
			} else {
				$start_time = $params["filter"]["create_time"][0];
			}
			$end_time = $params["filter"]["create_time"][1];
		}
		$range = $params["range"];
		$product_express =  ExpressProductModel::query()->pluck("damaijia_express_id","product_id");
		$express_name = ExpressModel::query()->pluck("express_name","id");
		$express_name = $express_name?$express_name->toArray():[];
		$condition["user_id"] = $this->_user_info["id"];
		if(!empty($params["filter"]["source"])) {
			$condition["source"] = $params["filter"]["source"];
		}
		if(!empty($params["filter"]["order_sn"])) {
			$condition["order_sn"] = $params["filter"]["order_sn"];
		}
		$count = UserOrderModel::query()
			->where($condition)
			->whereBetween("create_time",[$start_time,$end_time])->count();
		$list = UserOrderModel::query()
			->where($condition)
			->whereBetween("create_time",[$start_time,$end_time])
			->offset($range[0])->limit($range[1])->orderBy("id","desc")->get();
		foreach ($list as $k=>$v) {
			//如果订单未支付且超2小时  状态为取消
			if(($v['status'] == USER_ORDER_STATUS_PAYMENT) && (time()-strtotime($v['create_time']))>7200) {
				$list[$k]['status'] = USER_ORDER_STATUS_CANCEL;
			}
			if($express_name[$product_express[$v["product_id"]]]) {
				$list[$k]["alias_name"] = $express_name[$product_express[$v["product_id"]]];
			}
			$list[$k]["package_count"] = OrderConsigneeModel::query()->where("order_id",$v["id"])->count();
			$list[$k]["product_name"] = Product::query()->where("id",$v["product_id"])->value("alias_name");
		}
		return $this->responseJson(["total"=>$count,"index"=>$params['range'][0],"list"=>$list]);
	}
	// 废弃
	public function getList111() {
		$req = Base::getRequestJson();
//		dd($req);
		//大麦家订单记录以及包裹记录数据在用户端只保存6个月，超过6个月的记录将不再给用户展示
		$date = date("Y-m-d H:i:s",time()-3600*24*30*6);
		
		if(empty($req["filter"]["create_time"])) {
			$req["filter"]["create_time"][0] = $date;
			$req["filter"]["create_time"][1] = date("Y-m-d H:i:s");
		} else {
			$req["filter"]["create_time"][0] = $date;
		}
		Base::checkAndDie([
			'filter' => [ Param::OPTIONAL . Param::POST_ACTION_REPLACE_TO_DB_FILTERS . ERROR_INVALID_FILTER,
				'create_time' => Param::OPTIONAL . Param::extra('user_order.create_time')
					. Param::IS_RANGE_DATETIME . ERROR_INVALID_DATETIME,
				'status' => Param::OPTIONAL . Param::extra('user_order.status') . Param::isOP('USER_ORDER_STATUS_')
					. ERROR_INVALID_STATUS,
				'order_sn' => Param::OPTIONAL . Param::extra('user_order.order_sn') . Param::IS_STRING
					. ERROR_INVALID_ORDER_SN,
				'warehouse_id' => Param::OPTIONAL . Param::extra('user_order.warehouse_id') . Param::IS_INT_ID .
					ERROR_INVALID_WAREHOUSE_ID,
				'channel_id' => Param::OPTIONAL . Param::IS_INT_ID . Param::extra('user_order.channel_id') .
					ERROR_INVALID_CHANNEL,
				'mobile' =>  Param::OPTIONAL . Param::IS_INT_MOBILE
					. Param::extra('order_consignee.mobile') . ERROR_INVALID_MOBILE,
				'source' => Param::OPTIONAL . Param::isOP('USER_ORDER_SOURCE_') . Param::extra('user_order.source')
					. ERROR_EXT_INVALID_SOURCE,
			],
			'sort' => Param::OPTIONAL . Param::POST_ACTION_REPLACE_TO_DB_SORTS . ERROR_INVALID_SORT,
			'range' => Param::IS_RANGE_INT . ERROR_INVALID_RANGE,
		], $req);
		$filter = array_merge($req['filter'], [
			Filter::makeDBFilter('user_order.user_id', $this->_user_info['id'], Filter::TYPE_EQUAL)
		]);
		$product_express =  ExpressProductModel::query()->pluck("damaijia_express_id","product_id");
		$express_name = ExpressModel::query()->pluck("express_name","id");
		$express_name = $express_name?$express_name->toArray():[];
		$list = UserOrder::getList($filter, $req['range'], $req['sort'] ?? null);
		foreach ($list as $k=>$v) {
			//如果订单未支付且超2小时  状态为取消
			if(($v['status'] == USER_ORDER_STATUS_PAYMENT) && (time()-strtotime($v['create_time']))>7200) {
				$list[$k]['status'] = USER_ORDER_STATUS_CANCEL;
			}
			if($express_name[$product_express[$v["product_id"]]]) {
				$list[$k]["alias_name"] = $express_name[$product_express[$v["product_id"]]];
			}
		}
		$total = UserOrder::getCount($filter);
		// 过滤以下输出的字段
		foreach ($list as &$item) {
			unset($item['channel_name']);
			unset($item['ext_order_sn']);
			unset($item['channel_id']);
		}

		foreach ($list as $k=>$v) {
			//如果订单为待付款且超过两个小时 状态为取消状态
			if(($v['status'] ==USER_ORDER_STATUS_PAYMENT) && (time()-strtotime($v['create_time'])>7200)) {
				$list[$k]['status'] = USER_ORDER_STATUS_CANCEL;
			}
		}
		Base::dieWithResponse([
			'index' => $req['range'][0],
			'list' => $list,
			'total' => $total
		]);
	}
	
	/*public function uploadAndParseAddress(Request $request)
	{
		
		$allowed_exts = [ 'xls', 'xlsx',"csv" ];
		$file = $request->file('file');
		$file_ext = $file->getClientOriginalExtension();
		if (!in_array($file_ext, $allowed_exts)) {
//			Base::dieWithError(ERROR_INVALID_TYPE);
			CommonUtil::throwException([10027,"只支持xls，xlsx格式文件"]);
		}
		$file_store_name = date('YmdHms') . '.' . $file_ext;
		$file->move(base_path() . '/storage/uploads/', $file_store_name);
		$spreadsheet = IOFactory::load(base_path() . '/storage/uploads/' . $file_store_name);
		$sheet = $spreadsheet->getSheet(0);
		
		$row_total = $sheet->getHighestRow();			// 总行数
		$col_last_index = $sheet->getHighestColumn();	// 最后一列 如BJ
		$col_total = Coordinate::columnIndexFromString($col_last_index);	// 总列数
		
		// 找出哪些列需要存
		$pick_cols = [ '订单编号', '收货人姓名', '收货地址', '联系电话', '联系手机', '修改后的收货地址'];
		// k => 列 v => 中文名称
		$pick_cols_kv = [];
		$picked_cols = [];
		// 收货人姓名列
		$order_col = [];
		$shrxm_col = [];
		$shdz_col = [];
		$lxdh_col = [];
		$lxsj_col = [];
		$xgshdz_col = [];
		for ($i = 1; $i <= $col_total; $i++) {
			$cell_value  = trim($sheet->getCellByColumnAndRow($i, 1)->getValue());
			if (in_array($cell_value, $pick_cols)) {
				$pick_cols_kv[$i] = $cell_value;
			}
		}
		for ($i = 1; $i <= $col_total; $i++) {
			for ($j = 2; $j <= $row_total; $j++) {
				if (in_array($i, array_keys($pick_cols_kv))) {
					$cell_value  = trim($sheet->getCellByColumnAndRow($i, $j)->getValue());
					switch ($pick_cols_kv[$i]) {
						case '收货人姓名':
							$shrxm_col[] = $cell_value;
						break;
						case '收货地址':
							$shdz_col[] = str_replace('null', '', $cell_value);
						break;
						case '联系电话':
							$lxdh_col[] = $cell_value;
						break;
						case '联系手机':
							$lxsj_col[] = $cell_value;
						break;
						case '修改后的收货地址':
							$xgshdz_col[] = str_replace('null', '', $cell_value);
						break;
						case '订单编号':
							$order_col[] = $cell_value;
							break;
					}
				}
			}
			
		}
		$rows = [];
		// col wrap row
		foreach ($shrxm_col as $k => $v) {
			$address = $xgshdz_col[$k];
			if (empty($address)) {
				$address = $shdz_col[$k];
			}
			$province = explode(' ', $address)[0];
			$city = explode(' ', $address)[1];
			$district = explode(' ', $address)[2];
			$rows[] = [
				'consignee' => $v,
				'mobile' => empty($lxsj_col[$k]) ? $lxdh_col[$k]: $lxsj_col[$k],
				'province' => $province,
				'city' => $city,
				'district' => $district,
				'address' => trim(str_replace($district, '', str_replace($city, '', str_replace($province, '', $address)))),
				'platform_order_sn' => $order_col[$k]
			];
		}
		Base::dieWithResponse($rows);
	}*/

	/**
	 * @author ztt
	 * 取消订单
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 */
	public function cancelUserOrder(Request $request) {
		$params = $this->validate($request, [
			'id' => 'required|int'
		]);
		$user_order = UserOrderModel::getUserOrder(["id"=>$params["id"],"user_id"=>$this->_user_info["id"]]);
		if(!$user_order) {
			CommonUtil::throwException(ErrorEnum::DATA_NOT_EXIST);
		}
		if($user_order->status != USER_ORDER_STATUS_PAYMENT) {
			CommonUtil::throwException(ErrorEnum::ERROR_ORDER_STATUS);
		}
		DB::beginTransaction();
		$user_order->status = USER_ORDER_STATUS_CANCEL;
		$result = $user_order->save();
		$results = OrderConsigneeModel::updateOrderConsignee(["order_id"=>$user_order->id],["status"=>PACKAGE_STATUS_CANCELED]);
		if($result&&$results) {
			DB::commit();
			return $this->responseJson();
		} else {
			DB::rollBack();
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
	}
	/**
	 * @SWG\Post(
	 *     path="/order_upload_parse_address",
	 *     tags={"订单管理"},
	 *     summary="批量发货",
	 *     description="批量发货接口",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *     		name="file",
	 *     		in="query",
	 *     		description="上传的文件",
	 *     		required=true,
	 *     		type="string"
	 *   	),
	 *    @SWG\Parameter(
	 *     		name="source",
	 *     		in="query",
	 *     		description="发货平台 淘宝：taobao 天猫：tmall 京东：jd 品多多：pdd 其他：other",
	 *     		required=true,
	 *     		type="string"
	 *   ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *          @SWG\Schema(ref="#/definitions/SuccessBean")
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function uploadAndParseAddress(Request $request)
	{
		$params = $request->all();
		$source = $params["source"];
		$allowed_exts = [ 'xls', 'xlsx',"csv" ];
		$file = $request->file('file');
		$file_ext = $file->getClientOriginalExtension();
		if (!in_array($file_ext, $allowed_exts)) {
			CommonUtil::throwException([10027,"只支持xls，xlsx，csv格式文件"]);
		}
		$file_store_name = date('YmdHms') . '.' . $file_ext;
		$file->move(base_path() . '/storage/uploads/', $file_store_name);
//		$datas = ExcelTool::importCsv(base_path() . '/storage/uploads/' . $file_store_name);
		$content = file_get_contents(base_path() . '/storage/uploads/' . $file_store_name);
		$encode = $this->get_encoding(file_get_contents(base_path() . '/storage/uploads/' . $file_store_name));
		if($encode == "GBK"){
			$content = mb_convert_encoding($content,"UTF-8",$encode);
			file_put_contents(base_path() . '/storage/uploads/'.$file_store_name,$content);
		}
//		foreach ($datas as $data){
//			foreach ($data as $datum) {
//				dump(iconv("GBK","UTF-8",$datum));
//			}
//		}

		$spreadsheet = IOFactory::load(base_path() . '/storage/uploads/' . $file_store_name);
		$sheet = $spreadsheet->getSheet(0);
		$row_total = $sheet->getHighestRow();			// 总行数
		$col_last_index = $sheet->getHighestColumn();	// 最后一列 如BJ
		$col_total = Coordinate::columnIndexFromString($col_last_index);	// 总列数
		switch ($source) {
			case "taobao":
				$rows = $this->uploadAndParseAddressTb($col_total,$sheet,$row_total);
				break;
			case "tmall":
				$rows = $this->uploadAndParseAddressTm($col_total,$sheet,$row_total);
				break;
			case "jd":
				$rows = $this->uploadAndParseAddressJd($col_total,$sheet,$row_total);
				break;
			case "pdd":
				$rows = $this->uploadAndParseAddressPdd($col_total,$sheet,$row_total);
				break;
			case "other":
				$rows = $this->uploadAndParseAddressOther($col_total,$sheet,$row_total);
				break;
		}
		if(empty($rows)) {
			CommonUtil::throwException([10028,"模板错误或内容不能为空"]);
		}
		
		foreach ($rows as &$v) {
			$v["mobile"] = WarehouseService::strFilter($v["mobile"]);
		}
		Base::dieWithResponse($rows);
	}
	function convert_scientific_number_to_normal($number)

	{

		if(stripos($number, 'e') === false) {

			//判断是否为科学计数法

			return $number;

		}

		if(!preg_match(

			"/^([\\d.]+)[eE]([\\d\\-\\+]+)$/",

			str_replace(array(" ", ","), "", trim($number)), $matches)

		) {

			//提取科学计数法中有效的数据，无法处理则直接返回

			return $number;

		}

		//对数字前后的0和点进行处理，防止数据干扰，实际上正确的科学计数法没有这个问题

		$data = preg_replace(array("/^[0]+/"), "", rtrim($matches[1], "0."));

		$length = (int)$matches[2];

		if($data[0] == ".") {

			//由于最前面的0可能被替换掉了，这里是小数要将0补齐

			$data = "0{$data}";

		}

		//这里有一种特殊可能，无需处理

		if($length == 0) {

			return $data;

		}

		//记住当前小数点的位置，用于判断左右移动

		$dot_position = strpos($data, ".");

		if($dot_position === false) {

			$dot_position = strlen($data);

		}

		//正式数据处理中，是不需要点号的，最后输出时会添加上去

		$data = str_replace(".", "", $data);

		if($length > 0) {

			//如果科学计数长度大于0

			//获取要添加0的个数，并在数据后面补充

			$repeat_length = $length - (strlen($data) - $dot_position);

			if($repeat_length > 0) {

				$data .= str_repeat('0', $repeat_length);

			}

			//小数点向后移n位

			$dot_position += $length;

			$data = ltrim(substr($data, 0, $dot_position), "0").".".substr($data, $dot_position);

		} elseif($length < 0) {

			//当前是一个负数

			//获取要重复的0的个数

			$repeat_length = abs($length) - $dot_position;

			if($repeat_length > 0) {

				//这里的值可能是小于0的数，由于小数点过长

				$data = str_repeat('0', $repeat_length).$data;

			}

			$dot_position += $length;//此处length为负数，直接操作

			if($dot_position < 1) {

				//补充数据处理，如果当前位置小于0则表示无需处理，直接补小数点即可

				$data = ".{$data}";

			} else {

				$data = substr($data, 0, $dot_position).".".substr($data, $dot_position);

			}

		}

		if($data[0] == ".") {

			//数据补0

			$data = "0{$data}";

		}

		return trim($data, ".");

	}
	/**
	 * 获取内容的编码
	 * @param string $str
	 */
	function get_encoding($str = "") {
		$encodings = array (
			'ASCII',
			'UTF-8',
			'GBK'
		);
		foreach ( $encodings as $encoding ) {
			if ($str === mb_convert_encoding ( mb_convert_encoding ( $str, "UTF-32", $encoding ), $encoding, "UTF-32" )) {
				return $encoding;
			}
		}
		return false;
	}
	public function uploadAndParseAddressV1(Request $request)
	{
		$params = $request->all();
		$user_shop_id = $params["user_shop_id"];
		$user_shop = UserShopModel::getById($user_shop_id);
		$source = $user_shop["shop_type"];
		$allowed_exts = [ 'xls', 'xlsx',"csv" ];
		$file = $request->file('file');
		$file_ext = $file->getClientOriginalExtension();
		if (!in_array($file_ext, $allowed_exts)) {
			CommonUtil::throwException([10027,"只支持xls，xlsx，csv格式文件"]);
		}
		$file_store_name = date('YmdHms') . '.' . $file_ext;
		$file->move(base_path() . '/storage/uploads/', $file_store_name);
		$datas = ExcelTool::importCsv(base_path() . '/storage/uploads/' . $file_store_name);
		$content = file_get_contents(base_path() . '/storage/uploads/' . $file_store_name);
		$encode = $this->get_encoding(file_get_contents(base_path() . '/storage/uploads/' . $file_store_name));
		if($encode == "GBK"){
			$content = mb_convert_encoding($content,"UTF-8",$encode);
			file_put_contents(base_path() . '/storage/uploads/'.$file_store_name,$content);
		}
//		foreach ($datas as $data){
//			foreach ($data as $datum) {
//				dump(iconv("GBK","UTF-8",$datum));
//			}
//		}

		$spreadsheet = IOFactory::load(base_path() . '/storage/uploads/' . $file_store_name);
		$sheet = $spreadsheet->getSheet(0);
		$row_total = $sheet->getHighestRow();			// 总行数
		$col_last_index = $sheet->getHighestColumn();	// 最后一列 如BJ
		$col_total = Coordinate::columnIndexFromString($col_last_index);	// 总列数
		switch ($source) {
			case "tb":
				$rows = $this->uploadAndParseAddressTb($col_total,$sheet,$row_total);
				break;
			case "tmall":
				$rows = $this->uploadAndParseAddressTm($col_total,$sheet,$row_total);
				break;
			case "jd":
				$rows = $this->uploadAndParseAddressJd($col_total,$sheet,$row_total);
				break;
			case "pdd":
				$rows = $this->uploadAndParseAddressPdd($col_total,$sheet,$row_total);
				break;
			case "ks":
			case "other":
			case "dy":
				$rows = $this->uploadAndParseAddressOther($col_total,$sheet,$row_total);
				break;
		}
		if(empty($rows)) {
			CommonUtil::throwException([10028,"模板错误或内容不能为空"]);
		}
		foreach ($rows as &$v) {
			$v["mobile"] = WarehouseService::strFilter($v["mobile"]);
		}
		Base::dieWithResponse($rows);
	}
	public function uploadAndParseAddressTm($col_total,$sheet,$row_total) {
		return $this->uploadAndParseAddressTb($col_total,$sheet,$row_total);
	}
	/*
	 * 淘宝模板导入解析
	 */
	public function uploadAndParseAddressTb($col_total,$sheet,$row_total) {
		// 找出哪些列需要存
//		$pick_cols = [ '订单编号', '收货人姓名', '收货地址', '联系手机'];
		$pick_cols = [ '订单编号', '收货人姓名', '收货地址', '联系电话', '联系手机', '修改后的收货地址'];
		// k => 列 v => 中文名称
		$pick_cols_kv = [];
		// 收货人姓名列
		$order_col = [];
		$shrxm_col = [];
		$shdz_col = [];
		$lxsj_col = [];
		$xgshdz_col = [];
		$lxdh_col = [];
		for ($i = 1; $i <= $col_total; $i++) {
			$cell_value  = trim($sheet->getCellByColumnAndRow($i, 1)->getValue());
			if (in_array($cell_value, $pick_cols)) {
				$pick_cols_kv[$i] = $cell_value;
			}
		}
		for ($i = 1; $i <= $col_total; $i++) {
			for ($j = 2; $j <= $row_total; $j++) {
				if (in_array($i, array_keys($pick_cols_kv))) {
					$cell_value  = trim($sheet->getCellByColumnAndRow($i, $j)->getValue());
					switch ($pick_cols_kv[$i]) {
						case '收货人姓名':
							$shrxm_col[] = $cell_value;
							break;
						case '收货地址':
							$shdz_col[] = str_replace('null', '', $cell_value);
							break;
						case '联系电话':
							$lxdh_col[] = $cell_value;
							break;
						case '联系手机':
							$lxsj_col[] = $cell_value;
							break;
						case '订单编号':
							$order_col[] = $this->convert_scientific_number_to_normal($cell_value);
							break;
						case '修改后的收货地址':
							$xgshdz_col[] = str_replace('null', '', $cell_value);
							break;
					}
				}
			}

		}
		$rows = [];
		// col wrap row
		foreach ($shrxm_col as $k => $v) {
			$address = $xgshdz_col[$k];
			if (empty($address)) {
				$address = $shdz_col[$k];
			}
//			$province = explode(' ', $address)[0];
//			$city = explode(' ', $address)[1];
//			$district = explode(' ', $address)[2];
			$rows[] = [
				'consignee' => $v,
				'mobile' => empty($lxsj_col[$k]) ? $lxdh_col[$k]: $lxsj_col[$k],
//				'province' => $province,
//				'city' => $city,
//				'district' => $district,
//				'address' => trim(str_replace($district, '', str_replace($city, '', str_replace($province, '', $address)))),
				'address'=>$address,
				'platform_order_sn' => $order_col[$k]
			];
		}
		return $rows;
	}
	/*
	 * 拼多多模板导入解析
	 */
	public function uploadAndParseAddressPdd($col_total,$sheet,$row_total) {
// 找出哪些列需要存
		$pick_cols = ['订单号', '收货人', '手机','省','市','区','详细地址'];
		// k => 列 v => 中文名称
		$pick_cols_kv = [];
		// 收货人姓名列
		$order_col = [];
		$province_col = [];
		$city_col = [];
		$address_col = [];
		$district_col = [];
		$lxsj_col = [];
		for ($i = 1; $i <= $col_total; $i++) {
			$cell_value  = trim($sheet->getCellByColumnAndRow($i, 1)->getValue());
			if (in_array($cell_value, $pick_cols)) {
				$pick_cols_kv[$i] = $cell_value;
			}
		}
		for ($i = 1; $i <= $col_total; $i++) {
			for ($j = 2; $j <= $row_total; $j++) {
				if (in_array($i, array_keys($pick_cols_kv))) {
					$cell_value  = trim($sheet->getCellByColumnAndRow($i, $j)->getValue());
					switch ($pick_cols_kv[$i]) {
						case '收货人':
							$shrxm_col[] = $cell_value;
							break;
						case '省':
							$province_col[] = $cell_value;
							break;
						case '区':
							$district_col[] = $cell_value;
							break;
						case '市':
							$city_col[] = $cell_value;
							break;
						case '详细地址':
							$address_col[] = $cell_value;
							break;
						case '手机':
							$lxsj_col[] = $cell_value;
							break;
						case '订单号':
							$order_col[] = $cell_value;
							break;
					}
				}
			}

		}
		$rows = [];
		// col wrap row
		foreach ($shrxm_col as $k => $v) {
			$rows[] = [
				'consignee' => $v,
				'mobile' =>$lxsj_col[$k],
//				'province' => $province_col[$k],
//				'city' => $city_col[$k],
//				'district' => $district_col[$k],
//				'address' =>$address_col[$k],
				'address'=>$province_col[$k].$city_col[$k].$district_col[$k].$address_col[$k],
				'platform_order_sn' => $order_col[$k]
			];
		}
		return $rows;
	}
	/*
	 * 京东模板导入解析
	 */
	public function uploadAndParseAddressJd($col_total,$sheet,$row_total) {
		// 找出哪些列需要存
		$pick_cols = [ '订单号', '客户姓名', '客户地址', '联系电话'];
		// k => 列 v => 中文名称
		$pick_cols_kv = [];
		// 收货人姓名列
		$order_col = [];
		$shrxm_col = [];
		$shdz_col = [];
		$lxsj_col = [];
		for ($i = 1; $i <= $col_total; $i++) {
			$cell_value  = trim($sheet->getCellByColumnAndRow($i, 1)->getValue());
			if (in_array($cell_value, $pick_cols)) {
				$pick_cols_kv[$i] = $cell_value;
			}
		}
		for ($i = 1; $i <= $col_total; $i++) {
			for ($j = 2; $j <= $row_total; $j++) {
				if (in_array($i, array_keys($pick_cols_kv))) {
					$cell_value  = trim($sheet->getCellByColumnAndRow($i, $j)->getValue());
					switch ($pick_cols_kv[$i]) {
						case '客户姓名':
							$shrxm_col[] = $cell_value;
							break;
						case '客户地址':
							$shdz_col[] = str_replace('null', '', $cell_value);
							break;
						case '联系电话':
							$lxsj_col[] = $cell_value;
							break;
						case '订单号':
							$order_col[] = $cell_value;
							break;
					}
				}
			}

		}
		$rows = [];
		// col wrap row
		foreach ($shrxm_col as $k => $v) {
			$address = $shdz_col[$k];
			$province = explode(' ', $address)[0];
			$city = explode(' ', $address)[1];
			$district = explode(' ', $address)[2];
			$rows[] = [
				'consignee' => $v,
				'mobile' =>$lxsj_col[$k],
//				'province' => $province,
//				'city' => $city,
//				'district' => $district,
//				'address' => trim(str_replace($district, '', str_replace($city, '', str_replace($province, '', $address)))),
				'address' =>$address,
				'platform_order_sn' => $order_col[$k]
			];
		}
		return $rows;
	}
	/*
	 * 通用模板导入解析
	 */
	public function uploadAndParseAddressOther($col_total,$sheet,$row_total) {
// 找出哪些列需要存
		$pick_cols = [ '订单编号', '收货人姓名', '收货地址', '联系手机'];
		// k => 列 v => 中文名称
		$pick_cols_kv = [];
		// 收货人姓名列
		$order_col = [];
		$shrxm_col = [];
		$shdz_col = [];
		$lxsj_col = [];
		for ($i = 1; $i <= $col_total; $i++) {
			$cell_value  = trim($sheet->getCellByColumnAndRow($i, 1)->getValue());
			if (in_array($cell_value, $pick_cols)) {
				$pick_cols_kv[$i] = $cell_value;
			}
		}
		for ($i = 1; $i <= $col_total; $i++) {
			for ($j = 2; $j <= $row_total; $j++) {
				if (in_array($i, array_keys($pick_cols_kv))) {
					$cell_value  = trim($sheet->getCellByColumnAndRow($i, $j)->getValue());
					switch ($pick_cols_kv[$i]) {
						case '收货人姓名':
							$shrxm_col[] = $cell_value;
							break;
						case '收货地址':
							$shdz_col[] = str_replace('null', '', $cell_value);
							break;
						case '联系手机':
							$lxsj_col[] = $cell_value;
							break;
						case '订单编号':
							$order_col[] = $cell_value;
							break;
					}
				}
			}

		}
		$rows = [];
		// col wrap row
		foreach ($shrxm_col as $k => $v) {
			$address = $shdz_col[$k];
			$province = explode(' ', $address)[0];
			$city = explode(' ', $address)[1];
			$district = explode(' ', $address)[2];
			$rows[] = [
				'consignee' => $v,
				'mobile' =>$lxsj_col[$k],
//				'province' => $province,
//				'city' => $city,
//				'district' => $district,
//				'address' => trim(str_replace($district, '', str_replace($city, '', str_replace($province, '', $address)))),
				'address'=>$address,
				'platform_order_sn' => $order_col[$k]
			];
		}
		return $rows;
	}
}

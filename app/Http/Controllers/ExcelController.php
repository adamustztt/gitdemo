<?php


namespace App\Http\Controllers;
use App\Models\OrderConsignee;
use App\Models\UserOrder;
use Illuminate\Http\Request;
use Tool\ShanTaoTool\ExcelTool;

class ExcelController extends BaseController
{
	public function getOrderSource($source) {
		switch ($source){
			case "taobao":$source = "淘宝";break;
			case "tmall":$source ="天猫";break;
			case "pdd":$source ="拼多多";break;
			case "jd":$source ="京东";break;
			case "other":$source ="其他";break;
		}
		return $source;
	}
	public function exportOrderConsigneeExcel(Request $request)
	{
		$params = $this->validate($request, [
			"id" => "required|integer",
		]);
		$data = OrderConsignee::listOrderConsignee(["order_id"=>$params["id"]])->toArray();
		$cellData = [];
		foreach ($data as $k=>$v) {
			$cellData[$k]["id"] =$v["id"];
			$cellData[$k]["product_name"] =$v["product_name"];
			$cellData[$k]["ext_platform_order_sn"] =$v["ext_platform_order_sn"]." ";
			$cellData[$k]["consignee"] =$v["consignee"];
			$cellData[$k]["mobile"] =" ".$v["mobile"]." ";
			$cellData[$k]["total_price"] =$v["price"] + $v["shipping_fee"];
			$cellData[$k]["express_no"] =$v["express_no"]." ";
			$cellData[$k]["express_company_name"] =$v["express_company_name"];
			$cellData[$k]["source"] = $this->getOrderSource($v["source"]);
			$cellData[$k]["address"] = $v["province"].$v["city"].$v["district"].$v["address"];
		}
		$head = ["包裹编号","礼品信息（名称）","第三方订单编号","收件人姓名","收件手机号","总计费用","快递单号","快递","订单来源","收货地址"];
		ExcelTool::exportToExcel($head,$cellData,"订单包裹");
	}
}

<?php


namespace App\Services\Warehouses;


use App\Enums\ErrorEnum;
use App\Exceptions\ApiException;
use App\Exceptions\OuterApiException;
use App\Http\Bean\Utils\CustomExpress\YundaCreateBmOrderBean;
use App\Http\Utils\CustomExpress\YunDaExpressUtil;
use GuzzleHttp\Exception\GuzzleException;
use Tool\ShanTaoTool\QiWeiTool;

class YundaWarehouse extends AbstractWarehouse
{

	protected function requestWarehouse()
	{
		return false;
		// TODO: Implement requestWarehouse() method.
	}

	protected function requestProduct($page = 1, $page_size = 100)
	{
		return false;
		// TODO: Implement requestProduct() method.
	}
	private function searchBmCount(){
		$api_result = YunDaExpressUtil::searchBmCount();
	}
	protected function requestOrder($product, $userOrder, $orderConsignee)
	{
		$params["orderNumber"]=$orderConsignee->id;
		$params["sendName"]="李薇薇";
		$params["sendProvince"]="河南省";
		$params["sendCity"]="郑州市";
		$params["sendCountry"]="中牟县";
		$params["sendAddress"]="九龙镇席庄村润之新物流园";
		$params["receivePhone"]=$orderConsignee->mobile;
		$params["receiveName"]=$orderConsignee->consignee;
		$params["receiveProvince"]=$orderConsignee->province;
		$params["receiveCity"]=$orderConsignee->city;
		$params["receiveCountry"]=$orderConsignee->district;
		$params["receiveAddress"]=$orderConsignee->address;
		$YundaCreateBmOrderBean = new YundaCreateBmOrderBean($params);
		$api_result = YunDaExpressUtil::createBmOrder($YundaCreateBmOrderBean);
		if($api_result[0]["status"] == 1) {
			return [
				"third_order_sn" => $api_result[0]["orderId"],
				"ext_order_sn" =>  $api_result[0]["orderId"],
				"express_no" => $api_result[0]["mail_no"],
				"status" => PACKAGE_STATUS_SHIPPED,
				"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
			];
		}
		$base_url = $config = config("customExpress.yunda");
		$policy_msg["功能"] = "请求下单";
		$policy_msg["错误"] = "请求下单成功返回数据错误";
		$policy_msg["请求链接"] = $base_url["url"] . "accountOrder/createBmOrder";
		$policy_msg["请求参数"] = $params;
		$policy_msg["响应结果"] = $api_result;
		$policy_msg['信息时间']=date("Y-m-d H:i:s");
		$policy_msg['damaijai_user_id']=$this->damaijia_user_id;
		$policy_msg["商品id"] = $this->baseProductId;
		$policy_msg["仓库id"] = $this->baseExpressId;
		$policy_msg["仓源id"] = $this->baseWarehouseId;
		QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."韵达苍源".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
		throw new ApiException(ErrorEnum::ERROR_YUNDA_WAREHOUSE_RESULT);
	}

	protected function requestOrderQuery($orderConsignee)
	{
		return false;
		// TODO: Implement requestOrderQuery() method.
	}

	protected function requestCancelOrder($orderConsignee)
	{
		$api_result = YunDaExpressUtil::cancelBmOrder($orderConsignee->id,$orderConsignee->express_no);
		if($api_result[0]["status"] == 1) {
			return true;
		}
		return false;
	}
}

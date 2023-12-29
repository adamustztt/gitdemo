<?php


namespace App\Http\Validate\External;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Validate\BaseValidate;
use App\Models\CustomWarehouseModel;
use App\Models\DamaijiaWarehouseUserSource;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use WareHouse;

class OrderControllerValidate extends BaseValidate
{
    /**
     * 新版创建订单参数校验
     * @param $data
     */
    public function createV1($data)
    {
        $validate = Validator::make($data,
            [
                "product_id"=>"required|int",
//                "site_order_id"=>"",
                "product_number"=>"required|int|min:1|max:1000",
                "consignees"=>"required|array",
                "source"=>"required|in:taobao,tmall,pdd,jd,other",
            ]
        );
        if($validate->fails()){
            //验证错误
            $this->setError($validate->errors()->first());
            return false;
        }
        //判断收货人信息
        foreach ($data["consignees"] as $consignee){
            $validate = Validator::make($consignee,
                [
//                    "site_order_consignee_id"=>"",
                    "consignee"=>"required",
                    "mobile"=>"required",
                    "province"=>"required",
                    "city"=>"required",
                    "district"=>"required",
                    "address"=>"required",
                    "platform_order_sn"=>"required",
                ]
            );
            if($validate->fails()){
                //验证错误
                $this->setError($validate->errors()->first());
                return false;
            }
			if(strlen(trim($consignee["mobile"]))>16) {
				$this->setError("手机号长度不正确");
				return false;
			}
			$preg2= '/[a-zA-Z]/';
			if(preg_match($preg2,$consignee["mobile"])){
				$this->setError("手机号格式不正确 包含了字母");
				return false;
			}
			if(preg_match("/[\x7f-\xff]/",$consignee["mobile"])){
				$this->setError("手机号格式不正确 包含了中文");
				return false;
			}
        }

        // 接口参数判断
        $is_product = Product::getById($data["product_id"]);
        if (!$is_product){
            $this->setError("该商品不存在");
            return false;
        }

        // 判断商品是否下线
        if($is_product->status != PRODUCT_STATUS_ONLINE) {
            $this->setError("该商品已下架");
            return false;
        }
        $warehouse_info = WareHouse::getInfo($is_product["warehouse_id"]);

        // 判断仓库是否下线
        if($warehouse_info["status"] !=WARE_HOUSE_STATUS_NORMAL) {
            $this->setError("该商品已下架1");
            return false;
        }
        // 判断商品发货来源是否合法
        if(!empty($is_product["user_source"])) {
            if(!in_array($data["source"],explode(",",$is_product["user_source"]))) {
                $this->setError("当前商品暂不支持该发货平台");
                return false;
            }
        } else {
            // 判断仓库发货来源是否合法
            if(!DamaijiaWarehouseUserSource::getByWhere(["warehouse_id"=>$is_product["warehouse_id"],"user_source"=>$data["source"],"user_source_status"=>1])) {
                $this->setError("当前商品暂不支持该发货平台");
                return false;
            }
        }

        return true;
    }
}

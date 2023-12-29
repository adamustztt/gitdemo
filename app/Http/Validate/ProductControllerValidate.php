<?php


namespace App\Http\Validate;


use App\Models\CustomWarehouseExpressModel;
use App\Models\CustomWarehouseModel;
use Illuminate\Support\Facades\Validator;

class ProductControllerValidate extends BaseValidate
{
    /**
     * 商品列表参数校验
     * @param $data
     */
    public function getListV1($data)
    {
        $validate = Validator::make($data,
            [
                "warehouseId"=>"sometimes|required|numeric",
                "expressSendId"=>"sometimes|required|numeric",
                "page"=>"required|numeric|min:1",
                "pageSize"=>"required|numeric|min:1",
            ]
        );
        if($validate->fails()){
            //验证错误
            $this->setError($validate->errors()->first());
            return false;
        }
        //存在排序则验证字段
        if(isset($data["sort"])&&$data["sort"]){
            foreach ($data["sort"] as $val){
                $validate = Validator::make($val,
                    [
                        "field"=>"required|in:price,weight,sales,create_time",
                        "reverse"=>"required",
                    ]
                );
                if($validate->fails()){
                    //验证错误
                    $this->setError($validate->errors()->first());
                    return false;
                }
            }
        }
        if(isset($data["warehouseId"])){
            //判断快递是否存在
            $count = CustomWarehouseModel::query()->find($data["warehouseId"]);
            if(!$count){
                //验证错误
                $this->setError("快递不存在");
                return false;
            }
            if(isset($data["expressSendId"])){
                $count = CustomWarehouseExpressModel::query()->where("express_id",$data["expressSendId"])->count();
                if(!$count){
                    //验证错误
                    $this->setError("仓库不存在");
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 商品详情数据校验
     * @param $data
     */
    public function getInfoV1($data)
    {
        $validate = Validator::make($data,
            [
                "id"=>"required|int"
            ]
        );
        if($validate->fails()){
            //验证错误
            $this->setError($validate->errors()->first());
            return false;
        }
        return true;
    }
}

<?php


namespace App\Http\Validate\External;


use App\Http\Validate\BaseValidate;
use App\Models\CustomWarehouseModel;
use App\Models\ExpressModel;
use Illuminate\Support\Facades\Validator;

class ProductControllerValidate extends BaseValidate
{
    /**
     * 获取商品列表接口参数校验
     * @param $data
     */
    public function getListV1($data)
    {
        $validate = Validator::make($data,
            [
                "id"=>"required",
                "page"=>"numeric|min:1",
                "pageSize"=>"numeric|min:1"
            ]
        );
        if($validate->fails()){
            //验证错误
            $this->setError($validate->errors()->first());
            return false;
        }
        //判断仓库是否存在
        $count = ExpressModel::query()->where("id",$data["id"])->count();
        if(!$count){
            $this->setError("仓库不存在");
            return false;
        }
        return true;
    }
}

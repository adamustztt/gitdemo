<?php


namespace App\Http\Validate\External;


use App\Http\Validate\BaseValidate;
use Illuminate\Support\Facades\Validator;

class ErpControllerValidate extends BaseValidate
{
    /**
     * 云打印
     * @param $data
     */
    public function waybill($data)
    {
        $validate = Validator::make($data,
            [
                "shop_id"=>"required",
                "params"=>"required|array"
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
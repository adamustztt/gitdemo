<?php


namespace App\Http\Validate;


use Illuminate\Support\Facades\Validator;

class CartControllerValidate extends BaseValidate
{
    /**
     * 计算订单金额参数校验
     * @param $data
     */
    public function computeAmountV1($data)
    {
        $validate = Validator::make($data,
            [
                "consignees"=>"required|array",
                "count"=>"required|int",
                "items"=>"required|array",
                "order_sent_type"=>"required|int",
                "source"=>"required"
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
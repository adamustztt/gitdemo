<?php


namespace App\Http\Validate;


use Illuminate\Support\Facades\Validator;

class RechargeRecordControllerValidate extends BaseValidate
{
    protected $message = [
        "amount.required"=>"支付金额不能为空",
        "amount.integer"=>"支付金额必须为整数",
        "amount.min"=>"金额不在范围",
        "amount.max"=>"金额不在范围",
    ];

    /**
     * 支付唤起参数校验
     * @param $data
     */
    public function applyPaymentCode($data)
    {
        $validate = Validator::make($data,
            [
                "amount"=>"required|integer|min:1|max:5000000",
            ],
            $this->message
        );
        if($validate->fails()){
            //验证错误
            $this->setError($validate->errors()->first());
            return false;
        }
        return true;
    }
}
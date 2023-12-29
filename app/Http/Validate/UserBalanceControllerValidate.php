<?php


namespace App\Http\Validate;


use Illuminate\Support\Facades\Validator;

class UserBalanceControllerValidate extends BaseValidate
{
    /**
     * 点券明细v1参数校验
     * @param $data
     */
    public function getListV1($data)
    {
        $validate = Validator::make($data,
            [
                "page"=>"required|numeric|min:1",
                "pageSize"=>"required|numeric|min:1",
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
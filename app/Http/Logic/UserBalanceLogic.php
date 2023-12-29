<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\UserBalanceLog;

class UserBalanceLogic extends BaseLogic
{
    /**
     * 点券明细逻辑
     */
    public static function getListV1Logic($userId)
    {
        $params = app("request")->all();
        $query = UserBalanceLog::query()->join("user","user.id","=","user_balance_log.user_id")
            ->orderBy("user_balance_log.id","desc")
            ->select(["user_balance_log.*","user.username","user.mobile"]);

        //判断开始时间
        if(isset($params["startTime"])&&$params["startTime"]){
            $query->where("user_balance_log.create_time",">=",$params["startTime"]);
        }
        //判断结束时间
        if(isset($params["endTime"])&&$params["endTime"]){
            $query->where("user_balance_log.create_time","<=",$params["endTime"]);
        }
        //判断交易类型
        if(isset($params["type"]) && $params["type"]){
            $query->where("user_balance_log.type",$params["type"]);
        }
        //判断交易单号
        $baseTradeNumber = env("BASE_TRADE_NUMBER");
        if(isset($params["tradeNumber"]) && $params["tradeNumber"]){
            if(!is_numeric($params["tradeNumber"])){
                CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR,"交易单号格式不正确");
            }
            $id = $params["tradeNumber"]-$baseTradeNumber;
            $query->where("user_balance_log.id",$id);
        }

        $pageRes = $query->paginate($params["pageSize"]);

        $datas = $pageRes->items();
        foreach ($datas as &$data){
            $data["trade_number"] = $baseTradeNumber+$data["id"];
        }

        return [
            "total"=>$pageRes->total(),
            "list"=>$datas
        ];
    }
}
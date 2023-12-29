<?php


namespace App\Http\Logic;


use App\Models\User;
use App\Models\UserInviteCountModel;
use App\Models\UserInviteLogModel;

class InviteLogic extends BaseLogic
{
	/*
	 * 
	 */
	public static function recordUserInviteCount($user_id,$site_id)
	{
		$find = UserInviteCountModel::query()
			->where(["user_id"=>$user_id,"site_id"=>$site_id])
			->where("create_time",">",date("Y-m-d"))
			->first();
		if($find) {
			UserInviteCountModel::query()
				->where(["user_id"=>$user_id,"site_id"=>$site_id])
				->where("create_time",">",date("Y-m-d"))
				->increment("count");
		} else {
			$map["user_id"] = $user_id;
			$map["count"] = 1;
			$map["site_id"] = $site_id;
			UserInviteCountModel::create($map);
		}
		return true;
	}

	public static function listInviteLog($user_id)
	{
		$params = app("request")->all();
		$page = empty($params['page']) ? 1 : $params['page'];
		$pageSize = empty($params['pageSize']) ? 10 : $params['pageSize'];
		$query = UserInviteLogModel::query()
			->where("invite_user_id",$user_id);
		if(!empty($params["mobile"])) {
			$invited_user_id = User::query()->where("mobile",$params["mobile"])->value("id");
			$query->where("invited_user_id",$invited_user_id);
		}
		if(!empty($params["create_time"])) {
			$query->whereBetween("create_time",$params["create_time"]." 23:59:59");
		}
		$count = $query->count();
		$list = $query->with("invitedUser:id,mobile")
			->orderBy("create_time","desc")
			->offset(($page-1)*$pageSize)->limit($pageSize)
			->get();
		$today_count = UserInviteLogModel::query()
			->where("invite_user_id",$user_id)->where("create_time",">",date("Y-m-d"))->count();
		$total_count = UserInviteLogModel::query()
			->where("invite_user_id",$user_id)->count();
		return ['total_count'=>$total_count,"count"=>$count,"today_count"=>$today_count,"list"=>$list];
	}
}

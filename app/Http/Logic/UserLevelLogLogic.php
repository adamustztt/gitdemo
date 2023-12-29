<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\UserLevelLogModel;
use App\Models\UserLevelModel;

class UserLevelLogLogic extends BaseLogic
{
	public static function userLevelChangePopup($user_id)
	{
		$data = UserLevelLogModel::query()->where(["user_id"=>$user_id,"status"=>1,"change_type"=>1])->orderBy("id","desc")->first();
		if($data) {
			UserLevelLogModel::query()->where(["user_id"=>$user_id,"status"=>1])->update(["status"=>2]);
			$level_info = UserLevelModel::getById($data->level_id);
			return $level_info;
		}
	}
}

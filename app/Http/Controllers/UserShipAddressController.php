<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/10/21
 * Time: 14:04
 */

namespace App\Http\Controllers;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\UserShipAddress;
use Illuminate\Http\Request;

class UserShipAddressController extends BaseController
{
	public function getList(Request $request)
	{		
		// todo 未完成

		$data = $this->validate($request, [
			
		]);
		$where = [];
		
		$where['user_id'] = $this->_user_info['id'];
		$list = UserShipAddress::listPage(['*'], $where, 1, 20);
		
		return $list;
	}

	public function create(Request $request)
	{		
		// todo 未完成

		$data = $this->validate($request, [
			'name' => 'required|string',
			'mobile' => 'required|phone',
			'is_default' => 'required|int',
		]);

		$data['user_id'] = $this->_user_info['id'];
		$bool = UserShipAddress::create($data);
		if (!$bool){
			CommonUtil::throwException(ErrorEnum::DATABASE_HANDLE_ERROR);
		}
	}

	public function update(Request $request)
	{
		// todo 未完成

		$data = $this->validate($request, [

		]);
		$where = [];

		$where['user_id'] = $this->_user_info['id'];
		$list = UserShipAddress::listPage(['*'], $where, 1, 20);

		return $list;
	}

	public function delete(Request $request)
	{
		// todo 未完成
		$data = $this->validate($request, [

		]);
		$where = [];

		$where['user_id'] = $this->_user_info['id'];
		$list = UserShipAddress::listPage(['*'], $where, 1, 20);

		return $list;
	}
}

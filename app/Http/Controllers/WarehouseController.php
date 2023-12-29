<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Base;
use Filter;
use Illuminate\Http\Request;
use Param;

class WarehouseController extends BaseController
{
	public function getList(Request $request)
	{
		$data = $this->validate($request, [
			'filter.warehouse_id' => 'int',
			'sort' => 'sort_array',
			'range' => 'range_array',
		]);
		$data['filter'][] = ['warehouse.status',WARE_HOUSE_STATUS_NORMAL];
		
		$sort = ['sort','asc'];
		$list = Warehouse::getListByOld($data['filter'], $data['range'], $sort);
		foreach ($list as $k => &$v) {
			$list[$k]['warehouse_id'] = $v['id'];
			unset($v['ext_id']);
		}
		return $this->responseJson([
			'index' => $data['range'][0],
			'list' => $list
		]);
	}
}

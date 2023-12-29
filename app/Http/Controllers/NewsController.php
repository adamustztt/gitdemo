<?php


namespace App\Http\Controllers;


use App\Models\UserLevelLogModel;
use Illuminate\Http\Request;

class NewsController extends BaseController
{
	public function listNews(Request $request) {
		$params = $request->all();
		$page = isset($params["page"]) ? $params["page"] : 1;
		$pageSize = isset($params["pageSize"]) ? $params["pageSize"] : 10;
	}
}

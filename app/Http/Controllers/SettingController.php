<?php
namespace App\Http\Controllers;

use Base;
use Param;
use Setting;
use Site;
use Filter;

class SettingController extends BaseController
{
	public function getList()
	{
		$req = Base::getRequestJson();
		Base::checkAndDie([
			'filter' => [ Param::OPTIONAL . Param::POST_ACTION_REPLACE_TO_DB_FILTERS . ERROR_INVALID_FILTER,
				'skey' => Param::OPTIONAL . Param::IS_STRING . ERROR_INVALID_DATA,
			],
			'sort' => Param::OPTIONAL . Param::POST_ACTION_REPLACE_TO_DB_SORTS . ERROR_INVALID_SORT,
			'range' => Param::IS_RANGE_INT . ERROR_INVALID_RANGE,
		], $req);
		$filter = array_merge( $req['filter'], [
			Filter::makeDBFilter('site_id', Site::getCurrentSiteID(), Filter::TYPE_EQUAL),
		]);
		$list = Setting::getList($filter, $req['range'], $req['sort']);
		Base::dieWithResponse([
			'index' => $req['range'][0],
			'list' => $list
		]);
	}
}

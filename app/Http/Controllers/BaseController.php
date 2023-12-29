<?php

namespace App\Http\Controllers;

use App\Enums\ErrorEnum;
use App\Exceptions\ApiException;
use App\Exceptions\ValidateException;
use App\Helper\CommonUtil;
use App\Http\Service\GetInstanceService;
use App\Http\Utils\BaseUtil;
use App\Models\DamaijiaBlacklistModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use User;

/**
 * @SWG\Swagger(
 * schemes={"http"},
 * host="admin.taobao2622.com/",
 * basePath="/",
 * @SWG\Info(
 * title="大麦家前台",
 * version="1.0.0",
 * )
 * )
 */
class BaseController extends Controller
{

	public function __construct(Request $request)
	{
		$this->setToken($request->header('X-TOKEN'));
		$user_info = User::tokenWrapInfo($this->_token);
		$this->setUserInfo($user_info);

		//进行统一的参数验证
        $controllerName = $request->route();
        $controllerName = $controllerName[1]["uses"];
        $flag = preg_match("/Controllers\\\(.*)@(.*)/",$controllerName,$match);
        if($flag){
            $controllerName = $match[1];
            $actionName = $match[2];
            $class = "\\App\\Http\\Validate\\$controllerName"."Validate";
            //判断验证是否存在
            if(class_exists($class) && method_exists($class,$actionName)){
                $instance = new $class();
                $validateFlag = $instance->$actionName($request->all());
                if(!$validateFlag){
                    throw new ApiException([ErrorEnum::VALIDATE_ERROR[0],$instance->getError()]);
                }
            }
        }
		$this->setSiteId($request->g_site_id);
		$this->setDomain($request->g_domain);
	}

	private function setToken($token)
	{
		$this->_token = $token;
	}

	private function setUserInfo($user)
	{
		$this->_user_info = $user;
	}
	public function getUserInfo() {
		return $this->_user_info;
	}
	private function setSiteId($site_id)
	{
		$this->_site_id = $site_id;
	}
	private function setDomain($domain)
	{
		$this->_domain = $domain;
	}
	protected $_token = null;
	protected $_user_info = null;
	protected $_site_id= null;
	protected $_domain= null;


	/**
	 * 返回json响应
	 * @author wzz
	 * @param array $data
	 * @param int $status
	 * @return JsonResponse
	 */
	protected function responseJson($data = [], $status = 0)
	{
		return response()->json([
			'data' => $data,
			'status' => $status,
		]);
	}

	/**
	 * Validate the given request with the given rules.
	 * @param Request $request
	 * @param array $rules
	 * @param array $messages
	 * @param array $customAttributes
	 * @return array
	 */
	public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
	{
		$validator = $this->getValidationFactory()->make($request->all(), $rules, $messages, $customAttributes);
		if ($validator->fails()) {
			$this->throwValidateException($validator);
		}

		return $this->extractInputFromRules($request, $rules);
	}

	/**
	 * @param $validator
	 * @throws ApiException
	 */
	protected function throwValidateException($validator)
	{
		$fail = $validator->errors()->first();
		$data = ErrorEnum::VALIDATE_ERROR;
//		$data['1'] .= ': ' . $fail;
		$data['1'] = $fail;
		CommonUtil::throwException($data);

	}
	
    /**
     * 生成swagger文档
     */
    public function swagger()
    {
    	
    	$ins = GetInstanceService::getInstance();
    	dd($ins);
//        $swagger=\Swagger\scan(__DIR__."/../");
//        $swagger->saveAs('./swagger.json');
//        return $this->responseJson();
		$aa = array('aa', 'bb');
		$f = function($item)
		{
			$item = $item . 'aa' ;
			return $item;
		};
		$bb = array_map($f,$aa);
		dd($bb);
		
    }

    /**
     * 加载swagger文档
     */
    public function loadSwagger()
    {
        $data = json_decode(file_get_contents("./swagger.json"),true);
        return $data;
    }
    public static function getUserId()
	{
		$user_info = User::tokenWrapInfo(request()->header('X-TOKEN'));
		return $user_info["id"];
	}
	public static function getSiteId()
	{
		$params = request()->all();
		return $params["g_site_id"];
	}
	public static function generateSN($prefix = '')
	{
		$prefix = sprintf("%03d", $prefix);
		return date('YmdHms') . $prefix . rand(10000, 99999);
	}

    public function registerCaptcha()
    {
        $data = app('captcha')->create();
        return $this->responseJson($data);
	}
	
	
	public function importBlackPhone(Request $request)
	{
		$file = $request->file('file');
		$file_ext = $file->getClientOriginalExtension();
		$file_store_name = date('YmdHms') . '.' . $file_ext;
		$file->move(base_path() . '/storage/uploads/', $file_store_name);
//		$datas = ExcelTool::importCsv(base_path() . '/storage/uploads/' . $file_store_name);
		$content = file_get_contents(base_path() . '/storage/uploads/' . $file_store_name);
//		$encode = $this->get_encoding(file_get_contents(base_path() . '/storage/uploads/' . $file_store_name));
//		if($encode == "GBK"){
//			$content = mb_convert_encoding($content,"UTF-8",$encode);
//			file_put_contents(base_path() . '/storage/uploads/'.$file_store_name,$content);
//		}
		$spreadsheet = IOFactory::load(base_path() . '/storage/uploads/' . $file_store_name);
		$sheet = $spreadsheet->getSheet(0);
		$row_total = $sheet->getHighestRow();			// 总行数
		$col_last_index = $sheet->getHighestColumn();	// 最后一列 如BJ
		$col_total = Coordinate::columnIndexFromString($col_last_index);	// 总列数
		$pick_cols = [ '黑名单'];
		// k => 列 v => 中文名称
		$pick_cols_kv = [];
		// 收货人姓名列
		$order_col = [];
		$shrxm_col = [];
		
		for ($i = 1; $i <= $col_total; $i++) {
			$cell_value  = trim($sheet->getCellByColumnAndRow($i, 1)->getValue());
			if (in_array($cell_value, $pick_cols)) {
				$pick_cols_kv[$i] = $cell_value;
			}
		}
		for ($i = 1; $i <= $col_total; $i++) {
			for ($j = 2; $j <= $row_total; $j++) {
				if (in_array($i, array_keys($pick_cols_kv))) {
					$cell_value  = trim($sheet->getCellByColumnAndRow($i, $j)->getValue());
					switch ($pick_cols_kv[$i]) {
						case '黑名单':
							$shrxm_col[] = $cell_value;
							break;
						
					}
				}
			}

		}
		$maps = [];
		foreach ($shrxm_col as $v){
			$map = [];
			$map["phone"] = $v;
			$map["remark"] = "投诉";
			$map["admin_name"] = "系统导入";
			$map["create_time"] = date("Y-m-d H:i:s");
			$map["update_time"] = date("Y-m-d H:i:s");
			$maps[] = $map;
		}
		DamaijiaBlacklistModel::query()->insert($maps);
		return "success";
	}
}

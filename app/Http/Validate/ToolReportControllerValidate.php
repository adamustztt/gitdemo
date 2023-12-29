<?php


namespace App\Http\Validate;


use Illuminate\Support\Facades\Validator;

class ToolReportControllerValidate extends BaseValidate
{
	protected $message = [
		"account.required"=>"账号不能为空",
		"account.max"=>"账号最多50位长度",
		"content.required"=>"内容不能为空",
		"content.max"=>"账号最多100位长度",
		"img.required"=>"图片不能为空",
		"type.required"=>"分类不能为空",
	];

	/**
	 * @author ztt
	 * 猜你喜欢
	 * @param $data
	 * @return bool
	 */
	public function addToolReport($data)
	{
		$validate = Validator::make($data,
			[
				"account"=>"required|max:50",
				"content"=>"required|max:100",
				"img"=>"required",
				"type"=>"required"
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

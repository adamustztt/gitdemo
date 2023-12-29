<?php


namespace App\Http\Validate;


use Illuminate\Support\Facades\Validator;

class ToolControllerValidate extends BaseValidate
{
	protected $message = [
		"id.required"=>"工具ID不能为空",
		"keyword.required"=>"宝贝标题不能为空",
		"keyword.max"=>"宝贝标题不能超过50位",
		"link_url.required"=>"宝贝链接不能为空",
		"data_json.required"=>"data_json不能为空"
	];

	/**
	 * @author ztt
	 * 猜你喜欢
	 * @param $data
	 * @return bool
	 */
	public function createOrderGuessLike($data)
	{
		$validate = Validator::make($data,
			[
				"id"=>"required|integer",
				"keyword"=>"required|max:50",
				"link_url"=>"required"
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
	/**
	 * @author ztt
	 * 洋淘秀卡首屏
	 * @param $data
	 * @return bool
	 */
	public function createOrderFirstScreen($data)
	{
		$validate = Validator::make($data,
			[
				"id"=>"required|integer",
				"link_url"=>"required"
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
	/**
	 * @author ztt
	 * 关键词卡首屏
	 * @param $data
	 * @return bool
	 */
	public function createOrderKeywordFirstScreen($data)
	{
		$validate = Validator::make($data,
			[
				"id"=>"required|integer",
				"link_url"=>"required",
				"keyword"=>"required|max:50",
				"data_json"=>"required"
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

	/**
	 * @author ztt
	 * @param $data
	 * @return bool
	 * 找相似入口
	 */
	public function createOrderSimilar($data)
	{
		$validate = Validator::make($data,
			[
				"id"=>"required|integer",
				"keyword"=>"required|max:50",
				"link_url"=>"required"
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

	/**
	 * @author ztt
	 * @param $data
	 * @return bool
	 * 验号工具
	 */
	public function createOrderSearchplus($data)
	{
		$validate = Validator::make($data,
			[
				"id"=>"required|integer",
				"keyword"=>"required|max:50",
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

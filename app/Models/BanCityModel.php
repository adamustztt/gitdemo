<?php


namespace App\Models;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;

class BanCityModel extends BaseModel
{
	protected $table = "ban_city";
	protected $fillable = [];

	protected $guarded = [
		'id'
	];
	public function warehouse()
	{
		return $this->hasOne(Warehouse::class,'id', 'warehouse_id');
	}
	public function customWarehouse()
	{
		return $this->hasOne(customWarehouseModel::class,'id', 'custom_warehouse_id');
	}
	public function express()
	{
		return $this->hasOne(ExpressModel::class,'id', 'express_id');
	}
	public function city()
	{
		return $this->hasOne(AddressCity::class,'id', 'city_id');
	}
	public function province()
	{
		return $this->hasOne(AddressProvince::class,'id', 'province_id');
	}

	public static function listBanCity($where, $page, $pageSize, $column = "*")
	{
		$that = (new static);
		$query = static::query();
		$that->superWhere($query, $where);
		$query->with("warehouse:id,alias_name");
		$query->with("customWarehouse:id,express_name");
		$query->with("express:id,express_alias_name");
		$query->with("city:id,name");
		$query->with("province:id,name");
		return $query->offset(($page - 1) * $pageSize)->limit($pageSize)->get();
	}
	public static function getBanCityByExpressId($express_id,$city_name='')
	{
		$city_name = $c_city = mb_substr($city_name,0,2,"utf-8"); //城市值验证前两个字段
		$query = self::query()->where("express_id",$express_id);
		if(!empty($city_name)) {
			$query->where("city_names","like","%".$city_name."%");
		}
		$data = $query->where("is_delete",1)->first();
		if($data) {
			$date_time = date("Y-m-d H:i:s");
			if($data->open_time == null || empty($data->open_time)) {
				return $data;
			}
			if($data->open_time<$date_time && $data->off_time>$date_time) {
				return $data;
			}
		}
		
		return false;
	}
	public static function getBanCityByExpressIdV1($express_id)
	{
		$date_time = date("Y-m-d H:i:s");
		$data = self::query()
			->where("express_id",$express_id)
			->where("is_delete",1)
			->where("open_time","<",$date_time)
			->where("off_time",">",$date_time)
			->get();
		return $data;
	}
	//
	public static function getBanAddressExpress($express_id,$type,$province_name,$city_name='',$address_name="")
	{
		if(!in_array($type,[1,2,3])) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
		}
		$province_name = $c_city = mb_substr($province_name,0,2,"utf-8");
		if($type == 1) {
			$code= AddressProvince::query()->where("name","like","%".$province_name."%")->value("code");
		} else if($type == 2) {
			$province_name = mb_substr($province_name,0,2,"utf-8");
			$city_name = mb_substr($city_name,0,2,"utf-8");
			$province_code = AddressProvince::query()->where("name","like","%".$province_name."%")->value("code");
			$code= AddressCity::query()->where("name","like","%".$city_name."%")->where("provinceCode",$province_code)->value("code");
		} else {
			$province_name = mb_substr($province_name,0,2,"utf-8");
			$city_name = mb_substr($city_name,0,2,"utf-8");
//			$address_name = mb_substr($address_name,0,2,"utf-8");
			$province_code = AddressProvince::query()->where("name","like","%".$province_name."%")->value("code");
			$city_code = AddressCity::query()->where("name","like","%".$city_name."%")->where("provinceCode",$province_code)->value("code");
			if(empty($city_code)) {
				$city_code= AddressCity::query()->where("name","like","%".mb_substr($city_name,0,2,"utf-8")."%")->value("code");
			}
			$code= AddressTown::query()->where("cityCode",$city_code)->where("name",$address_name)->value("code");
		}
		$data = self::query()
			->where("ban_address_express.express_id",$express_id)
			->join("ban_address_express","ban_address_express.ban_city_id" ,"=","ban_city.id")
			->where("ban_address_express.code",$code)
			->get();
		$date_time = date("Y-m-d H:i:s");
		foreach ($data as $k=>$v) {
			if($v->open_time == null || empty($v->open_time)) {
				return $data;
			}
			if($v->open_time<$date_time && $v->off_time>$date_time) {
				return $data;
			}
		}
		
		return false;
	}
	public static function getBanAddressExpressV1($express_id,$type,$province_name,$city_name='',$address_name="")
	{
		if(!in_array($type,[1,2,3])) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
		}
		$province_name = $c_city = mb_substr($province_name,0,2,"utf-8");
		if($type == 1) {
			$code= AddressProvince::query()->where("name","like","%".$province_name."%")->value("code");
		} else if($type == 2) {
			$province_name = mb_substr($province_name,0,2,"utf-8");
			$city_name = mb_substr($city_name,0,2,"utf-8");
			$province_code = AddressProvince::query()->where("name","like","%".$province_name."%")->value("code");
			$code= AddressCity::query()->where("name","like","%".$city_name."%")->where("provinceCode",$province_code)->value("code");
		} else {
			$province_name = mb_substr($province_name,0,2,"utf-8");
			$city_name = mb_substr($city_name,0,2,"utf-8");
//			$address_name = mb_substr($address_name,0,2,"utf-8");
			$province_code = AddressProvince::query()->where("name","like","%".$province_name."%")->value("code");
			$city_code = AddressCity::query()->where("name","like","%".$city_name."%")->where("provinceCode",$province_code)->value("code");
			if(empty($city_code)) {
				$city_code= AddressCity::query()->where("name","like","%".mb_substr($city_name,0,2,"utf-8")."%")->value("code");
			}
			$code= AddressTown::query()->where("cityCode",$city_code)->where("name",$address_name)->value("code");
		}
		$data = self::query()
			->where("ban_address_express.express_id",$express_id)
			->join("ban_address_express","ban_address_express.ban_city_id" ,"=","ban_city.id")
			->where("ban_address_express.code",$code)
			->whereIn("ban_city.ban_type",[3,5])
			->get();
		$date_time = date("Y-m-d H:i:s");
		foreach ($data as $k=>$v) {
			if($v->open_time == null || empty($v->open_time)) {
				return $data;
			}
			if($v->open_time<$date_time && $v->off_time>$date_time) {
				return $data;
			}
		}
		return false;
	}
}

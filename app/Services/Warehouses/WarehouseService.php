<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/10/21
 * Time: 17:04
 */

namespace App\Services\Warehouses;


use App\Enums\WarehouseChannelEnum;

class WarehouseService
{
	const CLASS_MAP = [
		WarehouseChannelEnum::MUHUO => MuhuoWarehouse::class,
		WarehouseChannelEnum::CAOSHUDAIFA => CaoshudaifaWarehouse::class,
		WarehouseChannelEnum::YUNLIPIN => YunlipinWarehouse::class,
		WarehouseChannelEnum::LISUTONG => LisutongWarehouse::class,
		WarehouseChannelEnum::FAB => FabWarehouse::class,
		WarehouseChannelEnum::KUAIDIYUN => KuaidiyunWarehouse::class,
		WarehouseChannelEnum::KUAIDIYUNNEW => KuaidiyunNewWarehouse::class,
		WarehouseChannelEnum::SHUNFENG => ShunfengWarehouse::class,
		WarehouseChannelEnum::KUAIXIAOJIAN => KuaixiaojianWarehouse::class,
		WarehouseChannelEnum::YUNDA => YundaWarehouse::class,
		WarehouseChannelEnum::SHENZHENYUNDA => ShenzhenYundaWarehouse::class,
		WarehouseChannelEnum::YINLIUHELIPW => YinliuheLipwWarehouse::class,
		WarehouseChannelEnum::LI_PIN_DAO => LipindaoWarehouse::class,
		WarehouseChannelEnum::YUNDA_GUANGZHOU_QINGTIAN => YundaGuangzhouQingtianWarehouse::class,
	];

	/**
	 * 
	 * @author wzz
	 * @param $channel
	 * @return AbstractWarehouse
	 */
	public static function getClass($channel)
	{
		if (!isset(self::CLASS_MAP[$channel])){
			return null;
		}
		$class = self::CLASS_MAP[$channel];
		return new $class();
	}
	public static function strFilter($str){
		$str = str_replace(' ', '', $str);
		$str = str_replace("'", '', $str);
		$str = str_replace('`', '', $str);
		$str = str_replace('·', '', $str);
		$str = str_replace('~', '', $str);
		$str = str_replace('!', '', $str);
		$str = str_replace('！', '', $str);
		$str = str_replace('@', '', $str);
		$str = str_replace('#', '', $str);
		$str = str_replace('$', '', $str);
		$str = str_replace('￥', '', $str);
		$str = str_replace('%', '', $str);
		$str = str_replace('^', '', $str);
		$str = str_replace('……', '', $str);
		$str = str_replace('&', '', $str);
		$str = str_replace('*', '', $str);
		$str = str_replace('(', '', $str);
		$str = str_replace(')', '', $str);
		$str = str_replace('（', '', $str);
		$str = str_replace('）', '', $str);
		$str = str_replace('_', '', $str);
		$str = str_replace('——', '', $str);
		$str = str_replace('+', '', $str);
		$str = str_replace('=', '', $str);
		$str = str_replace('|', '', $str);
		$str = str_replace('\\', '', $str);
//		$str = str_replace('[', '', $str);
//		$str = str_replace(']', '', $str);
		$str = str_replace('【', '[', $str);
		$str = str_replace('】', ']', $str);
		$str = str_replace('{', '', $str);
		$str = str_replace('}', '', $str);
		$str = str_replace(';', '', $str);
		$str = str_replace('；', '', $str);
		$str = str_replace(':', '', $str);
		$str = str_replace('：', '', $str);
		$str = str_replace('\'', '', $str);
		$str = str_replace('"', '', $str);
		$str = str_replace('“', '', $str);
		$str = str_replace('”', '', $str);
		$str = str_replace(',', '', $str);
		$str = str_replace('，', '', $str);
		$str = str_replace('<', '', $str);
		$str = str_replace('>', '', $str);
		$str = str_replace('《', '', $str);
		$str = str_replace('》', '', $str);
		$str = str_replace('.', '', $str);
		$str = str_replace('。', '', $str);
		$str = str_replace('/', '', $str);
		$str = str_replace('、', '', $str);
		$str = str_replace('?', '', $str);
		$str = str_replace('？', '', $str);
		$str = str_replace('\n', '', $str);
		$str = str_replace('\r', '', $str);
		return trim($str);
	}
}

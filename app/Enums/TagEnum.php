<?php


namespace App\Enums;


class TagEnum
{
	const TAG_ARRAY = [
		"grey" => 0,
		"red" => 1,
		"yellow" => 2,
		"green" => 3,
		"blue" => 4,
		"pink" => 5,
	];
	// 卖家备注旗帜（与淘宝网上订单的卖家备注旗帜对应，只有卖家才能查看该字段）红、黄、绿、蓝、紫 分别对应 1、2、3、4、5	
	const TB_TAG = [
		1=>"red",
		2=>"yellow",
		3=>"green",
		4=>"blue",
		5=>"purple",
	];
	// pdd 订单备注标记，1-红色，2-黄色，3-绿色，4-蓝色，5-紫色
	const PDD_TAG = [
		1=>"red",
		2=>"yellow",
		3=>"green",
		4=>"blue",
		5=>"purple",
	];
	// 插旗颜色。red_flag_tag_order：红 grey_flag_tag_order：灰 yellow_flag_tag_order：黄 green_flag_tag_order：绿 blue_flag_tag_order：蓝 purple_flag_tag_order：紫（该字段仅在获取订单详情API中返回，不在获取订单列表（游标方式）API返回）
	const KS_TAG = [
		"red_flag_tag_order"=>"red",
		"grey_flag_tag_order"=>"grey",
		"yellow_flag_tag_order"=>"yellow",
		"green_flag_tag_order"=>"green",
		"blue_flag_tag_order"=>"blue",
		"purple_flag_tag_order"=>"purple",
	];
	const DY_TAG = [
		// grey    purple cyan green  orange red
		// 标星等级，范围0～5 0为灰色旗标，5为红色旗标，数字越大颜色越深 0灰 1紫 2青 3绿 4橙 5红
		"grey"=>0,
		"purple"=>1,
		"cyan"=>2,
		"green"=>3,
		"orange"=>4,
		"red"=>5,
	];
	const JD_TAG = [
		//旗标。颜色标识，不填默认为0，枚举值：（GRAY(0),RED(1),YELLOW(2),GREEN(3),BLUE(4),PURPLE(5)）
		"grey"=>0,
		"purple"=>5,
		"green"=>3,
		"blue"=>4,
		"red"=>1,
		"yellow"=>2,
	];
}

<?php


namespace App\Enums;


class CustomWarehouseEnum
{
	//申通快递		STO
	//韵达快递		YUNDA
	//邮政快递		YZXB
	//顺丰快递		SF
//圆通快递		YTO
//中通快递		ZTO
	const EXPRESS_MAP = [
		"圆通快递"=>"YTO",
		"中通快递"=>"ZTO",
		"申通快递"=>"STO",
		"韵达快递"=>"YUNDA",
		"顺丰快递"=>"SF",
		"邮政快递"=>"YZXB",
	];
	const TB_EXPRESS_MAP = [
		"圆通快递"=>"YTO",
		"中通快递"=>"ZTO",
		"申通快递"=>"STO",
		"韵达快递"=>"YUNDA",
		"顺丰快递"=>"SF",
		"邮政快递"=>"POSTB",
	];
	const PDD_EXPRESS_MAP = [
		"圆通快递"=>85,
		"中通快递"=>115,
		"申通快递"=>1,
		"韵达快递"=>121,
		"顺丰快递"=>44,
		"邮政快递"=>132,
	];
	const DY_EXPRESS_MAP = [
		"圆通快递"=>"yuantong",
		"中通快递"=>"zhongtong",
		"申通快递"=>"shentong",
		"韵达快递"=>"yunda",
		"顺丰快递"=>"shunfeng",
		"邮政快递"=>"youzhengguonei",

	];
	const JD_EXPRESS_MAP = [
		"圆通快递"=>463,
		"中通快递"=>1499,
		"申通快递"=>470,
		"韵达快递"=>1327,
		"顺丰快递"=>467,
		"邮政快递"=>2170,

	];
}

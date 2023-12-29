<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/8
 * Time: 19:08
 */

return [
	'muhuo' => [
		'username' => env('MUHUO_USERNAME'),
		'password' => env('MUHUO_PASSWORD'),
	],
	'xiaoma' => [
		'token' => env('XIAOMA_TOKEN'),
		'domain' => env('XIAOMA_DOMAIN'),
		'tel' => env('XIAOMA_TEL'),
	],
	'caoshudaifa' => [
		'app_id' => '5C2B7E63E402D180',
		'secret' => 'a96c17ba8f7e6ea033fd5f09ec5425ad',
	],
	'yunlipin' => [
		'app_id' => env('YUNLIPIN_APP_ID'),
		'app_key' => env('YUNLIPIN_KEY'),
	],
//	'lisutong' => [
//		'appkey'=>'20201013162600029332913',
//		'appSecret'=>'637D0261F4394D92FE20A2E3D10B9894'
//	]
	'lisutong' => [
		'domain'=>'http://openapi.qingxiaojian.shop',
		'appkey'=>'20201111200800014576898',
		'appSecret'=>'18E61E4B95B570FD7CFA3502FE360FD7'
	],
	'fab' => [
//		'domain'=>'http://api3459.fabwang.com',
//		'domain'=>'http://api3459.youmaidan.com',
		'domain'=>'http://api3459.fufaidaifa.com',
		'account'=>'13291862254',
		'password'=>'lqqc3517294',
		'secret'=>"+IxqEDXAQ1jIVg9ynEf1Bg=="
	],
//	'kuaixiaojian' => [
//		'domain'=>'https://www.kuaixiaojian.com',
//		'account'=>'13291862254 ',
//		'password'=>'lqqc3517294',
//		'secret'=>"yJMFdSFFtlPnZmc756nlRQ=="
//	],
//	'kuaixiaojian' => [
//		'domain'=>'https://www.kuaixiaojian.com',
//		'account'=>'17185714920',
//		'password'=>'w159968',
//		'secret'=>"BnidcnzSRmvB+KSiKJpZ2w=="
//	],
	'kuaixiaojian' => [
		'domain'=>'http://api.tztzo.com',
		'account'=>'18770221233',
		'password'=>'wwc159968',
		'secret'=>"A3XuGmJfhZP0N/EYAWzzuw=="
	],
	'Kuaidiyun' => [
//		'domain'=>'http://daifa996.com',
//		'domain'=>'http://598kd.com',
//		'domain'=>'http://1.117.233.30:7022',
//		'domain'=>'http://119.23.109.104:2031',
		'domain'=>'http://103.36.193.117:2031',
		'userName'=>'13291678475',
		'key'=>'c12356789',
	],
	'shunfeng' => [ //顺丰礼品
		'domain'=>'https://user.api.xuanlipin.com',
		'userId'=>'373',
		'secret'=>'b1d518d6578c7289ff10d1f05c0b51ed',
	],
	'shenzhenyunda' => [ //深圳韵达
		'domain'=>'http://zz.lipin100.vip',
		'username'=>'yaodoudou',
		'password'=>'lqqc3517294',
	],
	'yinliuhelipw' => [ //引流河礼品网
		'domain'=>'http://yinliuhe.lipw.com/api/app',
		'appKey'=>'90735011',
		'secret'=>'79f37b6bdf77f94a8f2696d0846cf651',
	]
];

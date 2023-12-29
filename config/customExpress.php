<?php
return [
    "yunda"=>[//韵达快递
        "url"=>env("USTOM_YUNDA_URL","https://openapi.yundaex.com/openapi-api/v1/"),//请求地址
        "appKey"=>env("CUSTOM_YUNDA_APP_KEY","000860"),//appkey
        "appSecret"=>env("CUSTOM_YUNDA_APP_SECRET","ed96cc996a9f4161915c41e67867cbd0"),
//        "partnerId"=>env("CUSTOM_YUNDA_PARTNER_ID","45089710044"),
        "partnerId"=>env("CUSTOM_YUNDA_PARTNER_ID","450584100735"),
//        "secret"=>env("CUSTOM_YUNDA_SECRET","MDmCEBfK8sY4NuXkzUGx7diQ2HtITA"),
        "secret"=>env("CUSTOM_YUNDA_SECRET","ph6zCIwixHe25GU8cYF73WQmnANPRq"),
    ],
	"yunda_guangzhou_qingtian"=>[//韵达广州擎天
		"url"=>env("USTOM_YUNDA_URL","https://openapi.yundaex.com/openapi-api/v1/"),//请求地址
		"appKey"=>env("GUANGZHOU_CUSTOM_YUNDA_APP_KEY","000860"),//appkey
		"appSecret"=>env("GUANGZHOU_CUSTOM_YUNDA_APP_SECRET","ed96cc996a9f4161915c41e67867cbd0"),
//        "partnerId"=>env("CUSTOM_YUNDA_PARTNER_ID","45089710044"),
		"partnerId"=>env("GUANGZHOU_CUSTOM_YUNDA_PARTNER_ID","6813249984"),
//        "secret"=>env("CUSTOM_YUNDA_SECRET","MDmCEBfK8sY4NuXkzUGx7diQ2HtITA"),
		"secret"=>env("GUANGZHOU_CUSTOM_YUNDA_SECRET","Z9jnRDHE3h6GPuSFCKAXq7kwb4Qpvc"),
	]
];

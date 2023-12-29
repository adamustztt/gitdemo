<?php

return [
//	'access_key' => config(env('QINIU_ACCESS_KEY')),
//	'secret_key' => config(env('QINIU_SECRET_KEY')),
//	'bucket' => config(env('QINIU_BUCKET')),

	'access_key' => env('QINIU_ACCESS_KEY'),
	'secret_key' => env('QINIU_SECRET_KEY'),
	'bucket' => env('QINIU_BUCKET'),
	'domain' => env('QINIU_DOMAIN')
];

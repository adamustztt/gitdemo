<?php
/*
	|--------------------------------------------------------------------------
	| Third Party Services
	|--------------------------------------------------------------------------
	|
	| This file is for storing the credentials for third party services such
	| as Stripe, Mailgun, SparkPost and others. This file provides a sane
	| default location for this type of information, allowing packages
	| to have a conventional place to find your various credentials.
	|
	*/

return [
	'domain' => env("FLOW_DOMAIN"),
	'client_id' =>env("FLOW_CLIENT_ID"),
	'client_secret' =>env("FLOW_CLIENT_SECRET"),
];

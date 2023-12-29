<?php

namespace App\Providers;

use App\Helper\CommonUtil;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * 引导任何应用服务。
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('phone', function ($attribute, $value, $parameters, $validator) {
            return (bool)preg_match('/^1\d{10}$/', $value);
        });
        Validator::extend('id_number', function ($attribute, $value, $parameters, $validator) {
            return (bool)preg_match('/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X|x)$/', $value);
        });

        Validator::extend('in_constant', function ($attribute, $value, $parameters, $validator) {
            if (!isset($parameters[0]))
                return false;

            return in_array($value, CommonUtil::constantsInClass($parameters[0]));
        });
        Validator::extend('password', function ($attribute, $value, $parameters, $validator) {
            return (bool)preg_match('/^[0-9A-Za-z-*+.~!@#$%^&*()]{6,18}$/', $value);
        });
        
		Validator::extend('date_array', function ($attribute, $value, $parameters, $validator) {
			if (!is_array($value) || count($value) != 2){
				return false;
			}
			if (strtotime($value[0]) <= 0 || strtotime($value[1]) <= 0){
				return false;
			}
			return true;
		});
		Validator::extend('sort_array', function ($attribute, $value, $parameters, $validator) {
			if (!is_array($value) || count($value[0]) != 2){
				return false;
			}
			return true;
		});
		
		Validator::extend('range_array', function ($attribute, $value, $parameters, $validator) {
			if (!is_array($value) || count($value) != 2){
				return false;
			}
			if ($value[0] < 0 || $value[1] <= 0){
				return false;
			}
			return true;
		});
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }
}

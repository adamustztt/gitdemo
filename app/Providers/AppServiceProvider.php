<?php

namespace App\Providers;

use App\Helper\CommonUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		if ($this->app->environment() !== 'production') {
			$this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);

			DB::listen(function ($query) {
				$sql = vsprintf(str_replace("?", "'%s'", $query->sql), $query->bindings);
//                $sql = Str::replaceArray('?', $query->bindings, $query->sql);
				$sql = str_replace([PHP_EOL,"\t"], ' ', $sql);
				$sqlArr = [
					'time' => $query->time,
					'sql' => $sql,
				];
				\Log::info('query sql:' . CommonUtil::jsonEncode($sqlArr));
			});
		}
//		$this->app->singleton('redis',function ($app) {
//			return Redis::connection();
//		});
		$this->app->singleton('redis', function () {
			$redis = new \Redis();
			$redis->pconnect(config('database.redis.default.host'), config('database.redis.default.port'));
			$redis->auth(config('database.redis.default.password'));
			return $redis;
		});
//		unset($this->app->availableBindings['redis']);
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		error_reporting(E_ALL ^ E_NOTICE);
	}
}

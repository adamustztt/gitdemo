<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
	dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'PRC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
	dirname(__DIR__)
);

$app->withFacades();

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
	Illuminate\Contracts\Debug\ExceptionHandler::class,
	App\Exceptions\Handler::class
);

$app->singleton(
	Illuminate\Contracts\Console\Kernel::class,
	App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('app');
$app->configure('xiupinjie');
$app->configure('daifatu');
$app->configure('kb658');
$app->configure('qidianyun');
$app->configure('qiniu');
$app->configure('warehouse');
$app->configure('tool');
$app->configure('customExpress');
$app->configure('yinliuhe');
/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
// 	'ext_auth' =>App\Http\Middleware\ExtAuthMiddleware::class
// ]);

$app->routeMiddleware([
'checkToken' => App\Http\Middleware\CheckTokenMiddleware::class,
'checkExtToken' =>App\Http\Middleware\CheckExtTokenMiddleware::class,
 "requestLog" =>App\Http\Middleware\RequestLogMiddlleware::class,
"checkSiteDomain"=>App\Http\Middleware\CheckSiteDomainMiddleware::class,
"checkApiUserErpShopMiddleware"=>App\Http\Middleware\CheckApiUserErpShopMiddleware::class,
"checkApiUserPddErpShopMiddleware"=>App\Http\Middleware\CheckApiUserPddErpShopMiddleware::class,
"checkApiUserKsErpShopMiddleware"=>App\Http\Middleware\CheckApiUserKsErpShopMiddleware::class,
"checkApiUserDyErpShopMiddleware"=>App\Http\Middleware\CheckApiUserDyErpShopMiddleware::class
]);


/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/
$app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(App\Providers\ValidationServiceProvider::class);

$app->register(Youngyezi\Captcha\CaptchaServiceProvider::class);


// 添加别名
$app->alias('captcha', 'Youngyezi\Captcha\CaptchaServiceProvider');

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
	'namespace' => 'App\Http\Controllers',
], function ($router) {
	require __DIR__.'/../routes/api.php';
});

return $app;

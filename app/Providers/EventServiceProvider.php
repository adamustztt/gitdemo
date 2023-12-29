<?php

namespace App\Providers;

use App\Http\Utils\BaseUtil;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
	/**
	 * The event listener mappings for the application.
	 *
	 * @var array
	 */
	protected $listen = [
		\App\Events\ExampleEvent::class => [
			\App\Listeners\ExampleListener::class,
		],
	];

	/**
	 * Register any events for your application.
	 *
	 * @return void
	 */
	public function boot()
	{
		parent::boot();

		//
		Event::listen(StatementPrepared::class, function ($event) {
			$event->statement->setFetchMode(\PDO::FETCH_ASSOC);
		});

		//监听sql
        BaseUtil::listenSql();
	}
}

<?php

namespace App\Console;

use App\Console\Commands\Orders\OrderConsigneeSend;
use App\Console\Commands\Orders\TbErp;
use App\Console\Commands\Orders\UserOrderTimeOut;
use App\Console\Commands\Orders\OrderConsigneeStatusUpdate;
use App\Models\UserOrder;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		OrderConsigneeStatusUpdate::class,
		OrderConsigneeSend::class,
		UserOrderTimeOut::class,
		TbErp::class,
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command(OrderConsigneeStatusUpdate::class)->everyFiveMinutes();
		$schedule->command(UserOrderTimeOut::class)->everyMinute();
	}

	/**
	 * Register the commands for the application.
	 *
	 * @return void
	 */
	protected function commands()
	{
		$this->load(__DIR__.'/Commands');
	}
}

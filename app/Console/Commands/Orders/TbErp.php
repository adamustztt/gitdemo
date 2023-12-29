<?php


namespace App\Console\Commands\Orders;


use App\Http\Controllers\Fix\TbErpFixController;
use Illuminate\Console\Command;

class TbErp extends Command
{
	protected $signature = 'order:fixTbErp';

	protected $description = '新增tbapi时更新用户api';

	/**
	 */
	public function handle()
	{
		TbErpFixController::fixTbErp();
	}
}

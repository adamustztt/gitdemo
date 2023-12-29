<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class WarehouseTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testMuhuo()
    {
		$warehouse = new \App\Services\Warehouses\MuhuoWarehouse();
//		$warehouse->saveWarehouse();
		$warehouse->saveProduct();
//		$warehouse->requestOrder(1,1,1);
//		$warehouse->createOrder(1,1,1);
//		$warehouse->cancelOrder(1);
    }

	/**
	 * A basic test example.
	 *
	 * @return void
	 */
	public function testXiaoma()
	{
		$warehouse = new \App\Services\Warehouses\XiaomaWarehouse();
		$warehouse->saveWarehouse();
//		$warehouse->saveProduct();
	}
	
	public function testCsdf()
		{
		$warehouse = new \App\Services\Warehouses\CaoshudaifaWarehouse();
//		$warehouse->saveWarehouse();
//		$warehouse->saveProduct();
		$orderConsignee = \App\Models\OrderConsignee::getById(281);
//		$warehouse->createOrder($orderConsignee);
		$warehouse->cancelOrder($orderConsignee);
	}

	public function testYunlipin()
	{
		$warehouse = new \App\Services\Warehouses\YunlipinWarehouse();
//		$warehouse->saveWarehouse();
//		$warehouse->saveProduct();
		$orderConsignee = \App\Models\OrderConsignee::getById(702);
		$warehouse->createOrder($orderConsignee);
//		$warehouse->requestOrderQuery($orderConsignee);
//		$warehouse->cancelOrder($orderConsignee);
	}
}

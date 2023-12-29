<?php

use Illuminate\Support\Facades\DB;

class ProductWarehouse
{
	public static function addInternal($product_id, $warehouse_id)
	{
		$sql = 'INSERT INTO product_warehouse(product_id, warehouse_id)
				VALUES (?,?)';
		$bind = [ $product_id, $warehouse_id ];
		return DB::insert($sql, $bind);
	}
}

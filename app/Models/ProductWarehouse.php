<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/9
 * Time: 11:07
 */

namespace App\Models;


class ProductWarehouse extends BaseModel
{
	protected $table = 'product_warehouse';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'product_id',
		'warehouse_id',
	];
}

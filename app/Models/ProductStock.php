<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/9
 * Time: 11:07
 */

namespace App\Models;


class ProductStock extends BaseModel
{
	protected $table = 'product_stock';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
	];
	protected $guarded = [
		'id'
	];
}

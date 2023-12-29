<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/9
 * Time: 11:07
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\Integer;

class User extends BaseModel
{

	protected $table = 'user';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [];
	protected $guarded = [
		'id'
	];

	/**
	 * @param $where
	 * @return Builder|Model|object|null
	 */
	public static function getUserData($where)
	{
		return static::query()->where($where)->first();
	}
	public static function inviteCode()
	{
		$invite_code = rand(100000,999999);
		$code = static::query()->where('invite_code',$invite_code)->first();
		if($code){
			self::inviteCode();
		}
		return $invite_code;
	}
	

	/**
	 * @author ztt
	 * @param $amount
	 * @return int
	 */
	public static function incrementBalance(int $id,int $amount) {
		return static::query()->where('id',$id)->increment('balance', $amount);
	}
	public function site()
	{
		return $this->belongsTo(Site::class, "id", "user_id");
	}
	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 * @author ztt
	 */
	public function userLevel()
	{
		return $this->hasOne(UserLevelModel::class, "id", "level_id");
	}

}

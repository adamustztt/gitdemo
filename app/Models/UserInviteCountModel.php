<?php


namespace App\Models;


class UserInviteCountModel extends BaseModel
{
	protected $table = 'user_invite_count';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [];

	protected $guarded = [
		'id'
	];
}

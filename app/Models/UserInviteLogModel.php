<?php


namespace App\Models;


class UserInviteLogModel extends BaseModel
{
	protected $table = 'user_invite_log';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [];

	protected $guarded = [
		'id'
	];
	public function inviteUser()
	{
		return $this->hasOne(User::class, "id", "invite_user_id");
	}
	public function invitedUser()
	{
		return $this->hasOne(User::class, "id", "invited_user_id");
	}
}

<?php

namespace App\Services;

use App\Constants\RedisKey\UserKey;

class UserFollowService
{

	/**
	 * 关注/取消关注
	 * @param string $type
	 * @param int $userId
	 * @param int $otherId
	 * @return mixed
	 */
	public function follow($type = 'follow', int $userId, int $otherId)
	{
		/**
		 * @var \Redis $redis
		 */
		$redis = app("redis");
		// 关注
		if ($type === 'follow') {
			$redis->sAdd(UserKey::FOLLOW.$userId, time(), $otherId."_".time());
			$redis->zAdd(UserKey::FANS.$otherId., time(), $userId."_".time());
		}
		// 取消关注
		if ($type === 'remove') {
			$redis->zRem(UserKey::FOLLOW.$userId, $otherId);
			$redis->zRem(UserKey::FOLLOW,$otherId);
		}
	}

	/**
	 * 我的关注/粉丝
	 * @param int $userId 当前登录用户的ID
	 * @param string $type 要获取的数据
	 * @param int $page 页码
	 * @param int $limit 限制条数
	 * @return array
	 */
	public function myFollowAndFans(int $userId, $type = 'follow', $page = 1, $limit = 10)
	{
		/**
		 * @var \Redis $redis
		 */
		$redis = app("redis");
		$start = $limit * ($page - 1);
		$end = $start + $limit - 1;
		$res = [];
		if ($type === 'follow') {
			return $redis->zRange(UserKey::FOLLOW.$userId);
		}
		if ($type === 'fans') {
			$res = $this->zRangeFans($userId, $start, $end);
		}
		return $res;
	}


	/**
	 * 增加关注
	 * @param $userId
	 * @param $otherId
	 */
	public function zAddFollow($userId, $otherId)
	{
		/**
		 * @var \Redis $redis
		 */
		$redis = app("redis");

		$redis->sAdd($this->prefix() . $userId, time(), $otherId);
	}




	/**
	 * 我的关注 | 倒序
	 * @param int $userId
	 * @param int $start
	 * @param int $end
	 * @return array
	 */
	public function zRevRangeFollow(int $userId, int $start = 0, int $end = 9)
	{
		return $this->redis->zRevRange(sprintf($this->prefix . $this->followKey, $userId), $start, $end);
	}



	/**
	 * 移除粉丝
	 * @param $userId
	 * @param $otherId
	 */
	public function zRemFans($userId, $otherId)
	{
		$this->redis->zRem(sprintf($this->prefix . $this->fansKey, $userId), $otherId);
	}

	/**
	 * 我的粉丝 | 正序
	 * @param int $userId
	 * @param int $start
	 * @param int $end
	 * @return array
	 */
	public function zRangeFans(int $userId, int $start = 0, int $end = 9)
	{
		return $this->redis->zRange(sprintf($this->prefix . $this->fansKey, $userId), $start, $end);
	}

	/**
	 * 我的粉丝 | 倒序
	 * @param int $userId
	 * @param int $start
	 * @param int $end
	 * @return array
	 */
	public function zRevRangeFans(int $userId, int $start = 0, int $end = 9)
	{
		return $this->redis->zRevRange(sprintf($this->prefix . $this->fansKey, $userId), $start, $end);
	}
}

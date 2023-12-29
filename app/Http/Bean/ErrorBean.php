<?php


namespace App\Http\Bean;

/**
 * @SWG\Definition(
 * )
 */
class ErrorBean
{
    /**
     * @SWG\Property(
     *     description="状态码(0请求成功 其他请求失败)"
     * )
     * @var bool
     */
    public $status = 0;

    /**
     * @SWG\Property(
     *     description="数据"
     * )
     * @var bool
     */
    public $data = "";

    /**
     * @SWG\Property(
     *     description="错误信息"
     * )
     * @var bool
     */
    public $err = "";
}
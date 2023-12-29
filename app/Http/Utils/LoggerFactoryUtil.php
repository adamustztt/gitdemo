<?php


namespace App\Http\Utils;


class LoggerFactoryUtil extends BaseUtil
{
    /**
     * 用户ID
     * @var int $userId
     */
    private static $userId;

    /**
     * 请求开始时间
     * @var string $startTime
     */
    private static $startTime;

    /**
     * 类文件名
     * @var string $class
     */
    protected $class;

    /**
     * 自定义日志
     * @var array $message
     */
    private static $message=[];

    /**
     * 执行的sql语句
     * @var array $sqlMessage
     */
    private static $sqlMessage=[];

    /**
     * @var string $requestId
     */
    private static $requestId;

    /**
     * 上传标识
     * @var int $isPush
     */
    private static $isPush=0;

    /**
     * @return int
     */
    public static function getUserId()
    {
        return self::$userId;
    }

    /**
     * @param int $userId
     */
    public static function setUserId($userId): void
    {
        self::$userId = $userId;
    }

    /**
     * @return int
     */
    public static function getIsPush()
    {
        return self::$isPush;
    }

    /**
     * @param int $isPush
     */
    public static function setIsPush($isPush)
    {
        self::$isPush = $isPush;
    }

    /**
     * @return string
     */
    public static function getRequestId()
    {
        return self::$requestId;
    }

    /**
     * @param string $requestId
     */
    public static function setRequestId($requestId)
    {
        self::$requestId = $requestId;
    }

    /**
     * LoggerFactoryUtil constructor.
     */
    public function __construct(string $class)
    {
        $this->class;
    }

    /**
     * 记录自定义日志信息
     * @param string $msg
     */
    public function info(string $msg)
    {
        $time = date("Y-m-d H:i:s");
        self::$message[] = "[$time]".$this->class."：".$msg;
    }

    /**
     * 记录sql日志
     * @param string $sql
     */
    public static function addSqlMessage(string $sql)
    {
        self::$sqlMessage[] = $sql;
    }

    /**
     * @return array
     */
    public static function getMessage(): array
    {
        return self::$message;
    }

    /**
     * @return array
     */
    public static function getSqlMessage(): array
    {
        return self::$sqlMessage;
    }

    /**
     * @return string
     */
    public static function getStartTime()
    {
        return self::$startTime;
    }

    /**
     * @param string $startTime
     */
    public static function setStartTime(string $startTime): void
    {
        self::$startTime = $startTime;
    }
}
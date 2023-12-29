<?php


namespace App\Http\Middleware;


use App\Http\Utils\LoggerFactoryUtil;
use App\Models\RequestLog;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tool\ShanTaoTool\MqTool;

/**
 * 请求日志中间件
 * Class RequestLogMiddlleware
 * @package App\Http\Middleware
 */
class RequestLogMiddlleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        //获取请求数据
        $param = $request->all();
        //判断请求参数中是否存在traceId
        if($request->header("traceId")){
            $traceId= $request->header("traceId");
        }else{
            $traceId = md5(uniqid().time());
        }
        $requestId = floor(microtime(true)*1000);
        LoggerFactoryUtil::setRequestId($requestId);

        $_GET["traceId"] = $traceId;

        $startTime = microtime(true);
        //设置请求开时间
        LoggerFactoryUtil::setStartTime($startTime);
        /**
         * @var JsonResponse $response
         */
        $response = $next($request);

        //获取返回数据
        $endTime = microtime(true);
        $responseData = $response->getContent();

        //获取自定义的日志
        $requestLog = "";
        if(LoggerFactoryUtil::getMessage()){
            $requestLog = json_encode(LoggerFactoryUtil::getMessage());
        }
        //获取监听到的sql语句
        $requestSqlLog = "";
        if(LoggerFactoryUtil::getSqlMessage()){
            $requestSqlLog = json_encode(LoggerFactoryUtil::getSqlMessage());
        }
//        $logData = [
//            "request_path"=>$request->getRequestUri(),
//            "request_param"=>json_encode($param),
//            "request_response"=>$responseData,
//            "request_log"=>$requestLog,
//            "request_sql_log"=>$requestSqlLog,
//            "create_time"=>$startTime,
//            "update_time"=>$endTime
//        ];
        //忽略的路由
        $ignorRoute = [
//            "/external/payment_notify"
        ];

        if(env("REQUEST_LOG_FLAG") && (!in_array($request->getRequestUri(),$ignorRoute))){
//            RequestLog::insert($logData);

            $paramData = [
                "header"=>$request->header(),
                "param"=>$param,
                "user_id"=>LoggerFactoryUtil::getUserId(),
                "origin_user_ip"=>$request->getClientIp()
            ];
            $mqMessage = [
                "requestPath"=>$request->path(),
                "requestParam"=>json_encode($paramData),
                "requestResponse"=>$responseData,
                "requestSqlLog"=>$requestSqlLog,
                "requestLog"=>$requestLog,
                "requestProjectName"=>"damaijiaHome",
                "createdAt"=>$startTime,
                "updatedAt"=>$endTime,
                "requestId"=>$requestId,
                "uniqueTraceId"=>$traceId
            ];
            try{
                if(LoggerFactoryUtil::getIsPush()==0) {
                    //没有走异常处理才会推送日志,要不然会出现两边日志,这是框架的原因
                    MqTool::pushLogMessage(json_encode($mqMessage),"damaijiaHomeLogKey");
                }
            }catch (\Exception $exception){}finally{}
        }
        return $response;
    }
}

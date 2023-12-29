<?php

namespace App\Exceptions;

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\RequestLog;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tool\ShanTaoTool\MqTool;

class Handler extends ExceptionHandler
{

	protected $dontReport = [
		ApiException::class,
		ValidationException::class,
	];


	public function report(Exception $exception)
	{
		parent::report($exception);
	}

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
	public function render($request, Exception $exception)
	{
//		return parent::render($request, $exception);
        //拦截异常记录请求日志
		dd($exception);
        $endTime = microtime(true);
        $responseData = json_encode([$exception->getMessage()]);

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
        $startTime = "";
        if(LoggerFactoryUtil::getStartTime()){
            $startTime = LoggerFactoryUtil::getStartTime();
        }else{
            $startTime = $endTime;
        }
//        $logData = [
//            "request_path"=>$request->getRequestUri(),
//            "request_param"=>json_encode(request()->all()),
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
            $requestId = LoggerFactoryUtil::getRequestId()?LoggerFactoryUtil::getRequestId():floor(microtime(true)*1000);
            $param = request()->all();
            //判断请求参数中是否存在traceId
            if(isset($_GET["traceId"])){
                $traceId= $_GET["traceId"];
            }else{
                $traceId = md5(uniqid().time());
            }

            $paramData = [
                "header"=>$request->header(),
                "param"=>$param
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
                MqTool::pushLogMessage(json_encode($mqMessage),"damaijiaHomeLogKey");
                //lumen框架问题(进入异常之后居然还可以回到中间件)
                LoggerFactoryUtil::setIsPush(1);
            }catch (\Exception $exception){}finally{}
//            RequestLog::insert($logData);
        }
		if ($exception instanceof NotFoundHttpException) {
			$data = $this->formatResponse(ErrorEnum::NOT_FOUND);
		} elseif ($exception instanceof MethodNotAllowedHttpException) {
			$data = $this->formatResponse(ErrorEnum::METHOD_NOT_FOUND);
		} elseif ($exception instanceof OuterApiException) {
			$data = $this->formatResponse(ErrorEnum::OUTER_API_ERROR);
		} elseif ($exception instanceof ApiException) {
			$data = $this->formatResponse([$exception->getCode(), $exception->getMessage()]);
		} elseif ($exception instanceof ConnectException) {
			if (false !== strpos($exception->getMessage(), 'Operation timed out')){
				$data = $this->formatResponse(ErrorEnum::REQUEST_TIMEOUT);
			}else{
				$data = $this->formatResponse([ErrorEnum::EXCEPTION_ERROR[0], $exception->getMessage()]);
			}
		} else{
			// 如果是真实环境 需直接返回500
			if (CommonUtil::envIsProduction()) {
//				$data = $this->formatResponse(ErrorEnum::EXCEPTION_ERROR);
				$data = $this->formatResponse([500, $exception->getMessage()]);
			}else{
				$data = $this->formatResponse([$exception->getCode(), $exception->getMessage()]);
			}
		}
        if (!empty($data)) {
			return response()->json($data);
		}

		return parent::render($request, $exception);
//		\Base::dieWithError(ERROR_INTERNAL, '系统发生未知错误，已被捕获');
	}
	/**
	 * 格式化响应
	 * @param array $errorConst
	 * @return array
	 * @author baixuan
	 */
	private function formatResponse(array $errorConst)
	{
		return [
			'status' => $errorConst[0],
			'err' => $errorConst[1],
			'data' => $errorConst[2] ?? [],
		];
	}
}

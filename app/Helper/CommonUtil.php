<?php
/**
 * 公用工具类
 *
 * User: Dollar
 * Date: 2019/05/27
 * Time: 14:06
 */

namespace App\Helper;

use App\Constants\BuzzTypeConst;
use App\Constants\CommonConst;
use App\Constants\ErrorConst;
use App\Exceptions\ApiException;
use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionClass;
use SimpleSoftwareIO\QrCode\BaconQrCodeGenerator;

class CommonUtil
{
    /**
     * 抛出异常
     *
     * @param array $errorMsg [code, msg] 例 [100, "系统错误"]
     * @param array $replace
     * @throws ApiException
     */
    public static function throwException($errorMsg, ...$replace)
    {
        if (!empty($replace)) {
            $errorMsg[1] = sprintf($errorMsg[1], ... $replace);
        }
        throw new ApiException($errorMsg);
    }

    /**
     * 抛出异常携带数据
     *
     * @param array $errorMsg [code, msg] 例 [100, "系统错误"]
     * @param $data
     * @throws ApiException
     */
    public static function throwExceptionData($errorMsg, $data)
    {
        $apiExceptionObj = new ApiException($errorMsg);
        if (!empty($data)) {
            $apiExceptionObj->setData($data);
        }

        throw $apiExceptionObj;
    }

    /**
     * 得到分页的offset值
     *
     * @param $page
     * @param $pageSize
     * @param int $decrementNum
     * @return int
     */
    public static function getPageOffset($page, $pageSize = 0, $decrementNum = 0)
    {
        $pageSize = $pageSize > 0 ? $pageSize : CommonConst::DEFAULT_PAGE_SIZE;
        $offset = ($page - 1) * $pageSize - $decrementNum;
        return $offset > 0 ? $offset : 0;
    }

    /**
     * 取得随机代码
     *
     * @param int $length
     * @param string $type
     * @param string $prefix
     * @return string
     */
    public static function getRandStr($length = 32, $type = '', $prefix = '')
    {
        if ($type == 'num') {
            $chars = "123456789";
        } elseif ($type == 'str') {
            $chars = "abcdefghijklmnopqrstuvwxyz";
        } elseif ($type == 'cap') {
            $chars = "ABCDEFGHIJKLMNPQRSTUVWXYZ0123456789";
        } else {
            $chars = "abcdefghjklmnpqrstuvwxyz0123456789";
        }

        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        if ($prefix) {
            $str = $prefix . $str;
        }

        return $str;
    }

    /**
     * 获取业务编号
     *
     * @param int $length
     * @param string $prefix
     * @return string
     */
    public static function getBuzzNum($prefix, $length = 28)
    {
        $chars = "0123456789";
        $time = date('YmdHis') . intval(explode(" ", microtime())[0] * 1000000);
        $length = $length - strlen($prefix) - strlen($time);//"20190822" . len = 8

        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        $str = $prefix . $time . $str;

        return $str;
    }

    /**
     * 截取UTF-8编码下字符串的函数
     *
     * @param string $str 被截取的字符串
     * @param int $length 截取的长度
     * @param bool $append 是否附加省略号
     *
     * @return  string
     */
    public static function subStr($str, $length = 0, $append = true)
    {
        $str = str_replace("\n", '', str_replace("\r", '', trim($str)));
        $strlength = strlen($str);

        if ($length == 0 || $length >= $strlength) {
            return $str;
        } elseif ($length < 0) {
            $length = $strlength + $length;
            if ($length < 0) {
                $length = $strlength;
            }
        }

        if (function_exists('mb_substr')) {
            $newstr = mb_substr($str, 0, $length, 'utf-8');
        } elseif (function_exists('iconv_substr')) {
            $newstr = iconv_substr($str, 0, $length, 'utf-8');
        } else {
            $newstr = substr($str, 0, $length);
        }

        if ($append && $str != $newstr) {
            $newstr .= '...';
        }

        return $newstr;
    }

    /**
     * 格式人性化时间
     *
     * @param $time
     * @return string
     */
    public static function formatHumanizeTime($time)
    {
        $diffTime = time() - $time;
        switch ($diffTime) {
            case $diffTime < 60:
            case 0:
                return '刚刚';
                break;
            case $diffTime < 3600:
                return intval($diffTime / 60) . '分钟前';
                break;
            case $diffTime < 3600 * 24:
                return intval($diffTime / 3600) . '小时前';
                break;
            case $diffTime < 3600 * 24 * 30:
                return intval($diffTime / 3600 / 24) . '天前';
                break;
            case $diffTime < 3600 * 24 * 30 * 12:
                return intval($diffTime / 3600 / 24 / 30) . '月前';
                break;
            case $diffTime < 3600 * 24 * 30 * 12 + 5 * 3600 * 24:
                return '12月前';
                break;
            case $diffTime >= 3600 * 24 * 30 * 12 + 5 * 3600 * 24:
                return intval($diffTime / (3600 * 24 * 365)) . '年前';
                break;
            default:
                return '';
                break;
        }

    }

    /**
     * http  post 提交
     *
     * @param $url
     * @param $data
     * @param int $timeout
     * @return mixed|string
     */
    public static function postSSLPage($url, $data, $timeout = 30)
    {
        $errorResult = json_decode('{"error_code": 0, "error_msg": "ok"}');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // 设置超时限制防止死循环

        curl_setopt($ch, CURLOPT_POST, 1); // 发送一个常规的Post请
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : http_build_query($data)); // Post提交的数据包求
        $i = 0;
        do {
            $i++;
            $result = curl_exec($ch);
            $error_no = curl_errno($ch);
            if ($error_no == 0) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($http_code == 200) {
                    $errorResult->error_code = 0;
                    $errorResult->error_msg = "";
                    break;
                } else {
                    $errorResult->error_code = $http_code;
                    $errorResult->error_msg = "http_code != 200";
                }
            } else {
                $errorResult->error_code = $error_no;
                $errorResult->error_msg = "curl_error_no > 0";
            }
        } while ($i < 9);
        curl_close($ch);
        if ($errorResult->error_code > 0) {
            return json_encode($errorResult);
        }

        return $result;
    }


    /**
     * curl 模拟http get 请求
     *
     * @param $url
     * @param int $timeout
     * @param string $refer
     * @return mixed|string
     */
    public static function getSSLPage($url, $timeout = 30, $refer = '')
    {
        $errorResult = json_decode('{"error_code": 0, "error_msg": "ok"}');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // 设置超时限制防止死循环

        if ($refer) {
            curl_setopt($ch, CURLOPT_REFERER, $refer); //构造来路
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
        }

        $i = 0;
        do {
            $i++;
            $result = curl_exec($ch);
            $error_no = curl_errno($ch);
            if ($error_no == 0) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($http_code == 200) {
                    $errorResult->error_code = 0;
                    $errorResult->error_msg = "";
                    break;
                } else {
                    $errorResult->error_code = $http_code;
                    $errorResult->error_msg = "http_code != 200";
                }
            } else {
                $errorResult->error_code = $error_no;
                $errorResult->error_msg = "curl_error_no > 0";
            }
        } while ($i < 9);
        curl_close($ch);
        if ($errorResult->errcode > 0) {
            return json_encode($errorResult);
        }
        return $result;
    }

    /**
     * 手机格式检测
     *
     * @param $telephone
     * @return bool
     */
    public static function checkPhoneNum($telephone)
    {
        $result = false;

        $phoneFormat = '/^1[3456789]\d{9}$/';
        if (preg_match($phoneFormat, $telephone)) {
            $result = true;
        }

        return $result;
    }

    /**
     * 淘宝订单号格式检测
     *
     * @param $tradeNo
     * @return bool
     */
    public static function checkTaobaoTradeNo($tradeNo)
    {
        $result = false;

        $tradeFormat = '/^\d{18,19}$/';
        if (preg_match($tradeFormat, $tradeNo)) {
            $result = true;
        }

        return $result;
    }

    /**
     * 拼多多订单号格式检测
     *
     * @param $tradeNo
     * @return bool
     */
    public static function checkPddTradeNo($tradeNo)
    {
        $result = false;

        $tradeFormat = '/^\d{6}-\d{15}$/';
        if (preg_match($tradeFormat, $tradeNo)) {
            $result = true;
        }

        return $result;
    }

    /**
     * 匹配身份证
     *
     * @param $idCode
     * @return bool
     */
    public static function checkIdCode($idCode)
    {
        $result = false;

        $idCodeFormat = '/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/i';
        if (preg_match($idCodeFormat, $idCode)) {
            $result = true;
        }

        return $result;
    }

    /**
     * 转化金额 100010分  ->   1000.10 元 *注意精度丢失
     *
     * @param $money
     * @return string
     */
    public static function formatToYuan($money)
    {
        return number_format(intval($money) / CommonConst::PRICE_UNIT, 2, '.', '');
    }

    /**
     * 元转分
     *
     * @param $money
     * @return mixed
     */
    public static function formatToFen($money)
    {
        if (empty($money)) {
            $money = 0;
        }

        return intval(strval($money * CommonConst::PRICE_UNIT));
    }

    /**
     * 商家提现手续费
     *
     * @param int $amount
     * @return float|int
     */
    public static function cashFee($amount)
    {
        return bcmul($amount, (CommonConst::MERCHANT_CASH_FEE_RATE / 100));
    }

    /**
     * 商家充值手续费
     * @param $amount
     * @return string
     * @author 苏阳
     * @date 2019/8/30 17:01
     */
    public static function rechargeFee($amount)
    {
        return bcmul($amount, bcdiv(CommonConst::MERCHANT_RECHARGE_FEE_RATE, 100, 2));
    }

    /**
     * 获取访问源
     *
     * @param $defaultRefer
     * @return mixed 优先返回url中指定的referer路径，其次返回本站中的上个页面路径，都没有的话，返回参数$defaultRefer指定的url
     */
    public static function getReferUrl($defaultRefer = "/")
    {
        $referer = Request::input('referer');
        if (empty($referer)) {
            $referer = Request::input('refer');
            if (empty($referer)) {
                if (!empty($_SERVER['HTTP_REFERER'])) {
                    $url = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
                    if (strpos(config('app.WEI_HOST'), $url)) {
                        $referer = $_SERVER['HTTP_REFERER'];
                    }
                }
                if (empty($referer)) {
                    $referer = $defaultRefer;
                }
            }
        }
        return $referer;
    }

    /**
     * 解析url中参数
     *
     * @param string $url
     * @return array
     */
    public static function getUrlParams($url)
    {
        if (empty($url)) {
            return [];
        }

        $paramInfos = parse_url($url);
        if (!isset($paramInfos['query'])) {
            return [];
        }

        $paramStr = explode('&', $paramInfos['query']);

        $output = [];
        foreach ($paramStr as $str) {
            $temp = explode('=', $str);
            $output[$temp[0]] = $temp[1] ?? '';
        }

        return $output;
    }

    /**
     * 取一个空对象
     *
     * @return \stdClass
     */
    public static function getEmptyObject()
    {
        return new \stdClass();
    }

    /**
     * GET 请求
     *
     * @param string $url
     * @param null $header
     * @param bool $isUseProxy
     * @return bool|mixed
     */
    public static function http_get($url, $header = null, $isUseProxy = false)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (!empty($header)) {
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, $header);
        }

        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 10);
        if ($isUseProxy) {
            $proxy = "122.224.84.38";
            $proxyPort = "31288";
            curl_setopt($oCurl, CURLOPT_PROXY, $proxy);
            curl_setopt($oCurl, CURLOPT_PROXYPORT, $proxyPort);
        }
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * POST 请求
     *
     * @param string $url
     * @param array $param
     * @param null $header
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    public static function http_post($url, $param, $header = null, $post_file = false)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        if (!empty($header)) {
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 10);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * 对字符串通过to_entities处理
     *
     * @param $str
     * @return string
     */
    public static function entities($str)
    {
        $len = strlen($str);
        $buf = "";
        for ($i = 0; $i < $len; $i++) {
            if (ord($str[$i]) <= 127) {
                $buf .= $str[$i];
            } else if (ord($str[$i]) < 192) {
                //unexpected 2nd, 3rd or 4th byte
                $buf .= "&#xfffd";
            } else if (ord($str[$i]) < 224) {
                //first byte of 2-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($str[$i + 0]) & 31) << 6) +
                    (ord($str[$i + 1]) & 63)
                );
                $i += 1;
            } else if (ord($str[$i]) < 240) {
                //first byte of 3-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($str[$i + 0]) & 15) << 12) +
                    ((ord($str[$i + 1]) & 63) << 6) +
                    (ord($str[$i + 2]) & 63)
                );
                $i += 2;
            } else {
                //first byte of 4-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($str[$i + 0]) & 7) << 18) +
                    ((ord($str[$i + 1]) & 63) << 12) +
                    ((ord($str[$i + 2]) & 63) << 6) +
                    (ord($str[$i + 3]) & 63)
                );
                $i += 3;
            }
        }
        return $buf;
    }

    /**
     * 对提供的数据进行urlsafe的base64编码。
     *
     * @param string $data 待编码的数据，一般为字符串
     * @return string 编码后的字符串
     */
    public static function base64_urlSafeEncode($data)
    {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($data));
    }

    /**
     * 对提供的urlsafe的base64编码的数据进行解码
     *
     * @param string $str 待解码的数据，一般为字符串
     * @return string 解码后的字符串
     */
    public static function base64_urlSafeDecode($str)
    {
        $find = array('-', '_');
        $replace = array('+', '/');
        return base64_decode(str_replace($find, $replace, $str));
    }

    /**
     * 获取url重定向后的原本链接
     * @param $url
     * @return mixed
     */
    public static function get_302_url($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $data = curl_exec($ch);
        $headers = curl_getinfo($ch);
        curl_close($ch);
        return $data != $headers ? $headers['url'] : $url;
    }

    /**
     * 获取一个类的所有常量
     * @param $class
     * @return array
     * Author 苏阳
     * Date 2019/8/21 09:29
     */
    public static function constantsInClass($class)
    {
        try {
            $reflectionClass = new ReflectionClass($class);
            $constants = $reflectionClass->getConstants();
        } catch (\ReflectionException $e) {
            $constants = [];
        }
        return $constants;
    }

    /**
     * json编码默认不处理中文
     * @param $value
     * @param int $options
     * @return false|string
     * @author baixuan
     */
    public static function jsonEncode($value, $options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($value, $options);
    }

    /**
     * json解码默认输出数组
     * @param $value
     * @param bool $toArray
     * @return mixed
     * @author baixuan
     */
    public static function jsonDecode($value, $toArray = true)
    {
        return json_decode($value, $toArray);
    }

    /**
     * 抽离token
     * @param \Illuminate\Http\Request $request
     * @return string
     * @author baixuan
     */
    public static function bearerToken($request)
    {
        $header = $request->header('Authorization', '');

        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }
        return '';
    }

    public static function uuid()
    {
        $charId = md5(uniqid(config('app.hostname') . mt_rand(), true));
        $hyphen = chr(45);// "-"
        $uuid = substr($charId, 0, 8) . $hyphen
            . substr($charId, 8, 4) . $hyphen
            . substr($charId, 12, 4) . $hyphen
            . substr($charId, 16, 4) . $hyphen
            . substr($charId, 20, 12);
        return $uuid;

    }

    /**
     * 获取类名
     * @param $class
     * @return string
     * @author baixuan
     */
    public static function getClassName($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }


    /**
     * 通过编号 获取 Buzz 前缀
     * @param $num
     * @return false|string
     * @auther 苏阳
     * @date 2019/10/23 8:34
     */
    public static function getBuzzPrefixByNum($num)
    {
        // 获取前缀
        return substr($num, 0, 4);
    }

    /**
     * 通过编号 获取 BuzzType
     * @param $num
     * @return mixed
     * @throws ApiException
     * @auther 苏阳
     * @date 2019/10/22 21:16
     */
    public static function getBuzzTypeByNum($num)
    {
        // 获取前缀
        $prefix = self::getBuzzPrefixByNum($num);
        if (empty($prefix))
            self::throwException(ErrorConst::NUM_PREFIX_NOT_FOUND);

        // 获取所有的Type
        $buzzTypes = self::constantsInClass(BuzzTypeConst::CLASS);
        if (!isset($buzzTypes[$prefix]))
            self::throwException(ErrorConst::BUZZ_TYPE_NOT_DEFINE);

        return $buzzTypes[$prefix];
    }

    /**
     * 环境是正式环境
     * @return bool
     */
    public static function envIsProduction()
    {
        return config('app.env') == 'production';
    }

    /**
     * 下划线转驼峰
     * @param string $uncamelize
     * @param string $separator
     * @return string
     */
    public static function camelize($uncamelize, $separator = '_')
    {
        $uncamelize = $separator . str_replace($separator, " ", strtolower($uncamelize));
        return ltrim(str_replace(" ", "", ucwords($uncamelize)), $separator);
    }

    /**
     * 驼峰转下划线
     * @param string $camelize
     * @param string $separator
     * @return string
     */
    public static function uncamelize($camelize, $separator = '_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelize));
    }

    /**
     * 获取毫秒级时间
     *
     * @return int
     */
    public static function msecTime()
    {
        list($msec, $sec) = explode(' ', microtime());
        return (int)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    /**
     * 转短链接
     * @param $url
     * @param bool $isDecode
     * @return string
     */
    public static function getDwz($url, $isDecode = true)
    {
        $isDecode && $url = urlencode($url);
        $client = new Client();
        $ret = $client->get('https://d.vjiage.com/shorten?url=' . $url);
        $ret = json_decode($ret->getBody()->getContents(), true);
        if ($ret['status'] == 1) {
            return Arr::get($ret, 's_url');
        }
        return $url;
    }

    /**
     * 判断值是空的
     * @param $value
     * @param bool $filterZero 是否过滤0的值
     * @return bool
     */
    public static function valueNull($value, $filterZero = false)
    {
        $value = is_string($value) ? trim($value) : $value;
        $nullArr = [null, 'null', '', 'undefined'];
        if ($filterZero) {
            $nullArr[] = 0;
            $nullArr[] = '0';
        }
        return in_array($value, $nullArr, true);
    }


    /**
     * 获取指定日期周的开始结束时间戳
     *
     * @param int $defaultTime 时间戳
     * @return array [1575849600, 1576454399]
     * @auther 苏阳
     * @date 2019/12/11 15:49
     */
    public static function getWeekTime($defaultTime = 0)
    {
        if (empty($defaultTime)) {
            $defaultTime = time();
        }
        $defaultDate = date("Y-m-d", $defaultTime);

        //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $first = 1;

        //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $w = date('w', strtotime($defaultDate));

        //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $weekStart = strtotime("$defaultDate -" . ($w ? $w - $first : 6) . ' days');

        //本周结束日期
        $weekEnd = strtotime(date('Y-m-d', $weekStart) . " +6 days") + 86399;

        return [$weekStart, $weekEnd];
    }

    /**
     * 获取指定日期月的开始结束时间戳
     *
     * @param int $defaultTime 时间戳
     * @return array [1575158400, 1578700799]
     * @auther 苏阳
     * @date 2019/12/11 17:09
     */
    public static function getMonthTime($defaultTime = 0)
    {
        if (empty($defaultTime)) {
            $defaultTime = time();
        }
        $defaultDate = date("Y-m-d", $defaultTime);

        $monthStart = strtotime(date('Y-m-01 00:00:00', strtotime($defaultDate)));
        $monthEnd = strtotime(date('Y-m-d 23:59:59', strtotime(date('Y-m-d H:i:s', $monthStart) . " +1 month -1 day")));

        return [$monthStart, $monthEnd];
    }

    /**
     * 数据脱敏
     * @param string $string 需要脱敏值
     * @param int $start 开始
     * @param int $length 结束
     * @param string $re 脱敏替代符号
     * @return bool|string
     * 例子:
     * dataDesensitization('18811113683', 3, 4); //188****3683
     * dataDesensitization('乐杨俊', 0, -1); //**俊
     */
    static function dataDesensitization($string, $start = 0, $length = 0, $re = '*')
    {
        if (empty($string)) {
            return false;
        }
        $strarr = array();
        $mb_strlen = mb_strlen($string);
        while ($mb_strlen) {//循环把字符串变为数组
            $strarr[] = mb_substr($string, 0, 1, 'utf8');
            $string = mb_substr($string, 1, $mb_strlen, 'utf8');
            $mb_strlen = mb_strlen($string);
        }
        $strlen = count($strarr);
        $begin = $start >= 0 ? $start : ($strlen - abs($start));
        $end = $last = $strlen - 1;
        if ($length > 0) {
            $end = $begin + $length - 1;
        } elseif ($length < 0) {
            $end -= abs($length);
        }
        for ($i = $begin; $i <= $end; $i++) {
            $strarr[$i] = $re;
        }
        if ($begin >= $end || $begin >= $last || $end > $last) return false;
        return implode('', $strarr);
    }

    /**
     * 求一个数的平方
     * @param $n
     * @return float|int
     */
    public static function sqr($n)
    {
        return $n * $n;
    }

    /**
     * 生产min和max之间的随机数，但是概率不是平均的，从min到max方向概率逐渐加大。
     * 先平方，然后产生一个平方值范围内的随机数，再开方，这样就产生了一种“膨胀”再“收缩”的效果。
     * @param $bonus_min
     * @param $bonus_max
     * @return int
     */
    public static function xRandom($bonus_min, $bonus_max)
    {
        $sqr = intval(self::sqr($bonus_max - $bonus_min));
        $rand_num = rand(0, ($sqr - 1));
        return intval(sqrt($rand_num));
    }

    /**
     * 计算红包
     * @param int $bonus_total 红包总额
     * @param int $bonus_count 红包个数
     * @param int $bonus_max 每个小红包的最大额
     * @param int $bonus_min 每个小红包的最小额
     * @return array 存放生成的每个小红包的值的一维数组
     */
    public static function computeRedPack($bonus_total, $bonus_count, $bonus_max, $bonus_min)
    {
        $result = array();

        $average = $bonus_total / $bonus_count;

        for ($i = 0; $i < $bonus_count; $i++) {
            //因为小红包的数量通常是要比大红包的数量要多的，因为这里的概率要调换过来。
            //当随机数>平均值，则产生小红包
            //当随机数<平均值，则产生大红包
            if (rand($bonus_min, $bonus_max) > $average) {
                // 在平均线上减钱
                $temp = $bonus_min + self::xRandom($bonus_min, $average);
                $result[$i] = $temp;
                $bonus_total -= $temp;
            } else {
                // 在平均线上加钱
                $temp = $bonus_max - self::xRandom($average, $bonus_max);
                $result[$i] = $temp;
                $bonus_total -= $temp;
            }
        }
        // 如果还有余钱，则尝试加到小红包里，如果加不进去，则尝试下一个。
        while ($bonus_total > 0) {
            for ($i = 0; $i < $bonus_count; $i++) {
                if ($bonus_total > 0 && $result[$i] < $bonus_max) {
                    $result[$i]++;
                    $bonus_total--;
                }
            }
        }
        // 如果钱是负数了，还得从已生成的小红包中抽取回来
        while ($bonus_total < 0) {
            for ($i = 0; $i < $bonus_count; $i++) {
                if ($bonus_total < 0 && $result[$i] > $bonus_min) {
                    $result[$i]--;
                    $bonus_total++;
                }
            }
        }
        return $result;
    }

    /**
     * 两个多维数组合并
     *
     * @param $arr1
     * @param $arr2
     * @return array
     */
    public static function arrayMergeDeep($arr1, $arr2)
    {
        $merged = $arr1;

        foreach ($arr2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeDeep($merged[$key], $value);
            } elseif (is_numeric($key)) {
                if (!in_array($value, $merged)) {
                    $merged[] = $value;
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * 加密签名
     *
     * @param $array
     * @param $secret
     * @return string
     * @auther 苏阳
     * @date 2020/1/17 15:42
     */
    public static function encryptApiSign($array, $secret)
    {
        ksort($array);
        $text = '';
        foreach ($array as $k => $v) {
            $text .= $k . $v;
        }

        return md5($secret . $text . $secret);
    }

    /**
     * 创建用户邀请码
     *
     * @param $userId
     * @return string
     */
    public static function createInviteCode($userId)
    {
        $source_string = 'OCXBGIF2JWNM5ESZKT9LDPQ1YRV84AU36H7';
        $num = $userId;
        $code = '';
        while ($num > 0) {
            $mod = $num % 35;
            $num = ($num - $mod) / 35;
            $code = $source_string[$mod] . $code;
        }
        $code = str_pad($code, '6', '0', STR_PAD_LEFT);
        return $code;
    }

    /**
     * 查找字符串里面所有数字
     *
     * @param string $str
     * @return string
     */
    public static function findNum($str = '')
    {
        preg_match_all('/\d+/', $str, $arr);
        return intval(join('', $arr[0] ?? []));
    }

    /**
     * utf8字符串分割
     *
     * @param $string
     * @param int $len
     * @return array
     */
    public static function mbStrSplit($string, $len = 1)
    {
        if (empty($string)) {
            return [];
        }

        $start = 0;
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string, $start, $len, "utf8");
            $string = mb_substr($string, $len, $strlen, "utf8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }

    /**
     * 普通的锁
     *
     * @param string $name 锁名
     * @param callable $callback 闭包方法
     * @param int $seconds 锁多少秒
     * @return mixed 返回闭包方法的返回值
     * @static
     */
    public static function lock($name, $callback, $seconds = 60)
    {
        $owner = uniqid('', true);
        $lock = Cache::lock($name, $seconds, $owner);
        return $lock->get($callback);
    }

    /**
     * 阻塞锁
     * 在 $blockSeconds 时间内会一直尝试加锁，加锁成功会执行方法，否则直到抛出锁超时
     * @param string $name 锁名
     * @param callable $callback 闭包方法
     * @param int $blockSeconds 阻塞多少秒
     * @param int $seconds 锁多少秒
     * @return bool
     * @static
     */
    public static function block($name, $callback, $blockSeconds, $seconds = 60)
    {
        $owner = uniqid('', true);
        $lock = Cache::lock($name, $seconds, $owner);
        return $lock->block($blockSeconds, $callback);
    }

    /**
     *  获取缓存锁的键
     * @return string
     */
    public static function getRedisLockKey()
    {
        $args = func_get_args();
        return implode('::', $args);
    }

    /**
     * 获取文件的全路径
     * @param $fileName
     * @return string
     */
    public static function getFileFullPath($fileName)
    {
        return config('filesystems.disks.local.root') . '/temp/' . $fileName;
    }

    /**
     *  检测时间戳是否是一天的开始
     * @param int $time
     * @return bool
     */
    public static function checkDayOfStart(int $time)
    {
        return strtotime(date('Y-m-d', $time)) - $time == 0;
    }

    /**
     * 是否是Url
     * @param $url
     * @return string
     */
    public static function isUrl($url)
    {
        $pattern = "#^(http|https)://(.*\.)?.*\..*#i";
        if (preg_match($pattern, $url)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取二维码图片地址
     * @param $content
     * @return string
     */
    public static function getQrCodeImgUrl($content)
    {
        $qrCode = new BaconQrCodeGenerator();
        $ret = $qrCode->format('png')->size(250)->margin(0)->generate($content);
        $oss = Storage::disk('oss');
        $prefix = 'uploadV3/' . date('Ymd') . '/' . md5(time() . uniqid()) . '.jpg';
        $oss->put($prefix, $ret);
        if (self::envIsProduction()) {
            $url = 'https://img.daliangju.com/' . $prefix;
        } else {
            $url = $oss->getUrl($prefix);
        }
        return $url;
    }


}

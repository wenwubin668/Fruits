<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class Service
{
    //需要子类定义
    private static $instance;
    private static $appId = '';

    protected function __construct() {}

    /**
     * @return \App\Services\Service
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }


    public static function result($data = '', $msg = '请求成功', $status = 0)
    {
        if (empty($data)) {
            $data = [];
        }
        return [
            'data' => $data,
            'msg' => $msg,
            'code' => $status,
        ];
    }

    public static function formatResult($result = []){
        if(empty($result)){
            return ['code'=>Error::FORMAT_RESULT_FAIL,'msg'=>'格式化返回结果失败','data'=>[]];
        }
        if(isset($result['data']) && !$result['data']){
            $result['data'] = [];
        }
        if(!isset($result['code'])){
            //不是SDK返回的标准格式，部分业务不需要访问后端接口
            return ['code' => 0 , 'msg' => '执行成功' , 'data' => $result];
        }
        return ['code'=>$result['code'],'msg'=>$result['msg'],'data'=>$result['data']];
    }

    public static function setWechatCookie($key, $value, $expire = CommonConf::COOKIE_EXPIRE_FOREVER) {
        $appid = self::getWechatAppid();
        //获取cookie APPID,并做为所有其他cookie的前缀
		if($key != CommonConf::COOKIE_KEY_OFFICIAL_ACCOUNT_APP_ID) {
			$key = $appid."_".$key;
		} else {
		    self::$appId = $value;
        }
		Log::info(__CLASS__ . '.setWechatCookie', compact('key', 'value'));
		Cookie::queue($key, $value, $expire, null, null, false, false);
    }

    public static function getWechatCookie($key) {
        $appid = self::getWechatAppid();
		if($key != CommonConf::COOKIE_KEY_OFFICIAL_ACCOUNT_APP_ID) {
			$key = $appid."_".$key;
		} else {
		    return self::getWechatAppid();
        }
        return Cookie::get($key);
    }

    public static function forgetWechatCookie($key) {
        $appid = self::getWechatAppid();
		if($key != CommonConf::COOKIE_KEY_OFFICIAL_ACCOUNT_APP_ID) {
			$key = $appid."_".$key;
		}
        Cookie::queue(Cookie::forget($key));
    }

    public static function setWechatAppid($appId) {
        if ($appId == CommonConf::COOKIE_KEY_WEIXIN_SCHOOL_ID) {
            return false;
        }
        $lastAppid = Cookie::get(CommonConf::COOKIE_KEY_OFFICIAL_ACCOUNT_APP_ID);
        if ($appId != $lastAppid) {
            //如果之前在其他公众号，先清除session，避免多个公众号之间ession干扰
            session()->forget(Auth::guard('web')->getName());
            //session()->invalidate();
        }
        self::$appId = $appId;
        self::setWechatCookie(CommonConf::COOKIE_KEY_OFFICIAL_ACCOUNT_APP_ID, $appId);
    }

    public static function getWechatAppid() {
        return self::$appId ?: Cookie::get(CommonConf::COOKIE_KEY_OFFICIAL_ACCOUNT_APP_ID);
    }

    /**
     * 设置cookie
     * @param $key
     * @param $value
     * @param int $expire
     */
    public static function setCookie($key, $value, $expire = CommonConf::COOKIE_EXPIRE_FOREVER){
        Log::info(__CLASS__ . '.setCookie', compact('key', 'value'));
        Cookie::queue($key, $value, $expire, null, null, false, false);
    }

    /**
     * 获取cookie
     * @param $key
     * @return string
     */
    public static function getCookie($key){
        Log::info(__CLASS__ . '.getCookie-'.$key);
        return Cookie::get($key);
    }

    /**
     * 清除cookie
     * @param $key
     */
    public static function forgetCookie($key){
        Log::info(__CLASS__ . '.forgetCookie-'.$key);
        Cookie::queue(Cookie::forget($key));
    }



    /**
     * 生成手机验证码，一天内保持不变
     * @param $mobile
     * @return int
     */
    public static function genSuperMobileCaptcha($mobile) {
        Log::info(__CLASS__ . '.genMobileCaptcha' . $mobile);
        $captcha = $mobile % date('Ymd');
        $captcha = substr($captcha, 0, 4);
        Log::info(__CLASS__ . '.genMobileCaptcha ret ' . $captcha);

        return $captcha;
    }

    /**
     * 是不是空间站
     * @param $appId
     * @return bool
     */
    public static function isSpaceStation($appId = '') {
        $appId = $appId ?: self::getWechatAppid();
        return $appId == WechatConf::getSpaceStationAppId();
    }

    /**
     * 获取迁移到空间站公众号到二维码
     * @param string $schoolId 学校ID
     * @param int $uid 用户ID，没有默认0
     * @param int $roleId 要迁移到身份ID，没有默认0
     * @param int $primaryIdentity 主身份，默认0
     * @return mixed
     */
    public static function getTransferToSpaceStationQrCode($schoolId, $uid = 0, $roleId = 0, $primaryIdentity = 0) {
        Log::info(__CLASS__ . '.getTransferToSpaceStationQrCode', compact('schoolId', 'uid', 'roleId', 'primaryIdentity'));

        $openid = self::getWechatCookie(CommonConf::COOKIE_KEY_USER_OPEN_ID);
        if (false && !WechatMessageService::checkOnlineTestOpenid($openid)) {
            return '';
        }
        $sceneStr = sprintf('%s#%d|%d|%d|%d', WechatConf::QRCODE_SCENE_PREFIX_TRANSFER_TO_SPACE_STATION, $schoolId, $uid, $roleId, $primaryIdentity);
        return OpenPlatformService::getInstance()->getTemporaryQrcode(WechatConf::getSpaceStationAppId(), $sceneStr, 86400 * 30, true);
    }
}

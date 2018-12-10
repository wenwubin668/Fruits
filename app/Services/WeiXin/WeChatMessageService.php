<?php
/**
 * 公众号消息管理服务层.
 * User: yanghuichao
 * Date: 2018/6/22
 * Time: 下午5:39
 */

namespace App\Services\WeiXin;


use App\Common\CommonConf;
use App\Services\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WeChatMessageService extends Service
{
    protected static $instance;
    private $officialAccount;
    private $message;
    protected $appid;//当前公众号appid
    protected $openid;//当前用户openid
    protected $type;//1:默认第三方 2:开发者

    /**
     * @param $officailAccount
     * @param $message
     * @param int $type 1:默认第三方 2:开发者
     * @return mixed|string
     */
    public function handleTextMsg($officailAccount, $message) {
        Log::info(__CLASS__ . '.handleTextMsg', $message);

        $this->officialAccount = $officailAccount;
        $this->message = $message;
        $this->appid = $this->officialAccount->getConfig()['app_id'];
        $this->openid = $message['FromUserName'];

        if ($message['Content'] == 'openid') {
            return $message['FromUserName'];
        }
        if ($message['Content'] == '#信用卡#') {
            $url = route('CardList',['type'=>CommonConf::WECHAT_AUTH_WAY_1]);
            return "<a href='{$url}'>信用卡列表</a>";
        }
        if ($msg = $this->directive()) {
            return $msg;
        }
    }

    private function directive() {
        //@todo 可以使用白名单，比如指令字典，key是指令，值是openid白名单
        $ptn = '/#(\d{11})#/';
        if (preg_match($ptn, $this->message['Content'], $matches)) {
            return $this->captcha($matches[1]);
        }
        if ($this->message['Content'][0] == '#') {
            return $this->onlineTest();
        }
        return '';
    }

    private function onlineTest() {
        if ($this->message['Content'] == '#开启内测#') {
            if (Service::isSpaceStation($this->appid)) {
                Cache::put('start_online_test', 1, 1440);
                return '已开启内测';
            }
            return '请到空间站开启';
        } elseif ($this->message['Content'] == '#关闭内测#') {
            Cache::forget('start_online_test');
            return '已关闭内测';
        } elseif ($this->message['Content'] == '#加入内测#') {
            Cache::put('online_test_openid.' . $this->message['FromUserName'], 1, 1440);
            return '您的openid[' . $this->message['FromUserName'] . ']已加入内测白名单';
        } elseif ($this->message['Content'] == '#退出内测#') {
            Cache::forget('online_test_openid.' . $this->message['FromUserName']);
            return '您已退出内测白名单';
        }

        return '';
    }

    public static function checkOnlineTestOpenid($openId) {
        if (!Cache::has('start_online_test')) {
            return true;
        }
        if (!Cache::has('online_test_openid.' . $openId)) {
            Log::info(__CLASS__ . "checkOnlineTestOpenid openid[$openId]不在内测白名单");
            return false;
        }
        return true;
    }

    /**
     * 获取手机验证码
     * @param string $mobile 手机号
     * @return int|string
     */
    private function captcha($mobile) {
        Log::info(__CLASS__ . 'captcha ' . $mobile);
        $dict = DictService::getInstance()->getSingleDict(DictConf::DICT_WHITELIST);
        Log::info(__CLASS__ . '.captcha dict', (array) $dict);
        if (!$dict) {
            return '获取白名单失败';
        }
        if ($dict['code'] != 0) {
            return $dict['msg'];
        }
        $whitelist = $dict['data']['openid']['detailContent'] ?? '';
        if (!$whitelist) {
            return $mobile;
        }
        $whitelist = explode(',', $whitelist);
        Log::info(__CLASS__ . '.captcha whitelist ', (array) $whitelist);
        if (!$whitelist || !in_array($this->openid, $whitelist)) {
            return $this->openid;
        }
        $captcha = Service::genSuperMobileCaptcha($mobile);

        return "验证码：$captcha ，当天有效";
    }
}
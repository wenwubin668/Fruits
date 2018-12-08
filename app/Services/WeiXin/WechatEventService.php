<?php
/**
 * 微信公众号事件处理服务层.
 * User: yanghuichao
 * Date: 2018/6/21
 * Time: 下午11:47
 */

namespace App\Services\WeiXin;

use App\Services\Service;
use App\Services\User\UserService;
use EasyWeChat\Kernel\Messages\Media;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\NewsItem;
use Illuminate\Support\Facades\Log;
use EasyWeChat\Kernel\Messages\Text;

class WeChatEventService extends Service
{
    protected static $instance;
    protected $appid;//当前公众号appid
    protected $officialAccount;//当前公众号实例
    protected $message;//公众号推送来的消息
    protected $eventKeyParsers = [];//事件处理方法数组
    protected $returnMsg;//回复公众号的消息
    protected $potentialUserInfo;//潜客信息
    protected $openid;//当前用户openid
    protected $type;//处理消息方式  1:默认第三方 2:开发者
    protected $returnImgTxtMsg = [];//图文参数


    /**
     * @todo 记录用户最后活跃时间 潜客表，用户表，这块扫码业务会越来越多，需要梳理优化，尤其是扫码，现在都是两个地方重复写
     * 处理微信公众号事件类型消息
     * @param $officialAccount
     * @param $message
     * @param int $type
     * @return string
     */
    public function handleEvent($officialAccount, $message)
    {
        Log::debug('WechatEventService::handleEvent');

        $this->officialAccount = $officialAccount;
        $this->message = $message;
        $this->appid = $this->officialAccount->getConfig()['app_id'];
        $this->openid = $message['FromUserName'];

        //注册事件类型处理方法
        $this->regiserEventKeyParser();

        $event = $message['Event'];
        try {
            if (!method_exists($this, $event)) {
                Log::warning('WechatEventService::handleEvent has no event: ' . $event);
                return '';
            }
            call_user_func([$this, $event], $officialAccount, $message);
            //添加/更新潜客信息
            if ($this->potentialUserInfo) {
                $info = UserService::getInstance()->getOneUser($this->openid);
                if(empty($info)){
                    UserService::getInstance()->addUser($this->potentialUserInfo);
                }else{
                    UserService::getInstance()->updateUser($this->potentialUserInfo);
                }
            }
            //发送消息
            if ($this->returnMsg) {
                Log::info(__CLASS__ . '.handleEvent return msg ' . $this->returnMsg);
                return $this->returnMsg;
            }
        } catch (\Exception $e) {
            $err['file'] = $e->getFile();
            $err['line'] = $e->getLine();
            $err['msg'] = $e->getMessage();
            Log::warning('WechatEventService::handleEvent error:', $err);
        }
        return '';
    }

    /**
     * 注册事件类型处理方法
     * 关注和浏览分开，可能处理逻辑不完全一样
     */
    private function regiserEventKeyParser() {
        $this->eventKeyParsers = [
            'subscribe' => [

            ],
            'scan' => [

            ],
        ];
    }

    //调用事件处理方法
    private function callEventKeyParser($eventType) {
        if (!$this->eventKeyParsers || !isset($this->eventKeyParsers[$eventType])) {
            return '';
        }
        $message = $this->message;
        foreach ($this->eventKeyParsers[$eventType] as $eventKeyType => $parser) {
            if (method_exists($this, $parser) && strpos($message['EventKey'], $eventKeyType) === 0) {
                //统一二维码key，方便处理
                if ($eventType == 'scan') {
                    $enventKey = 'qrscene_' . $message['EventKey'];
                } else {
                    $enventKey = $message['EventKey'];
                }
                call_user_func([$this, $parser], $eventType, $eventKeyType, $enventKey);
                break;
            }
        }
    }

    /**
     * 获取微信用户信息
     */
    protected function initUserInfo($refreshCache = false) {
        $this->potentialUserInfo = UserService::getInstance()->getUserInfo($this->officialAccount, $this->openid, $refreshCache);
        $this->potentialUserInfo['appid'] = $this->appid;
    }

    /**
     * 关注，添加潜客用户
     * @param $officialAccount
     * @param $message
     * @return string
     */
    public function subscribe($officialAccount, $message)
    {
        Log::debug('WechatEventService::subscribe', [$message,$this->appid]);
        //初始化微信用户信息
        $this->initUserInfo(true);

        //调用事件处理方法(放到下面如果有参数，则覆盖上面的returnmsg)
        $this->callEventKeyParser('subscribe');

        $this->returnMsg = '欢迎关注~~~[Hey][Hey][Hey]';
    }

    /**
     * 取消关注
     * @param array $message
     */
    public function unsubscribe($officialAccount, $message)
    {
        $message['appid'] = $this->appid;
        Log::debug('WechatEventService::unsubscribe', $message);

        $this->potentialUserInfo['status'] = 0;
        UserService::getInstance()->updateUser($this->potentialUserInfo);

        //$this->potentialUserInfo = [];
        //PotentialUserService::getInstance()->unsubscribe($this->openid);
    }

    /**
     * 扫描二维码
     * @param array $message
     * @param bool $subscribe 是否是关注
     */
    public function scan($officialAccount, $message, $subscribe = false)
    {
        Log::debug('WechatEventService::scan',$message);

        //初始化微信用户信息
        $this->initUserInfo();
        //调用事件处理方法
        $this->callEventKeyParser('scan');
    }



    //发送客服图文消息
    private function sendImgTxtMsg($msgParam){
        $items = [
            new NewsItem($msgParam),
        ];
        $news = new News($items);
        $result = OpenPlatformService::getInstance()->sendKefu($this->appid,$this->openid,$news);
        Log::info('recruitShare sendImgTxtMsg_res',[$result,$msgParam]);
    }
    //发送普通文本消息
    private function sendTxtMsg($returnMsg){
        $message = new Text($returnMsg);
        $result = $this->officialAccount->customer_service->message($message)->to($this->openid)->send();
        Log::info('recruitShare sendImgTxtMsg_res',[$result,$returnMsg]);
    }

}

<?php
/**
 * 微信公众号事件处理服务层.
 * User: yanghuichao
 * Date: 2018/6/21
 * Time: 下午11:47
 */

namespace App\Services;

use App\Services\Service;
use EasyWeChat\Kernel\Messages\Media;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\NewsItem;
use Illuminate\Support\Facades\Log;
use EasyWeChat\Kernel\Messages\Text;

class WechatEventService extends Service
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
    public function handleEvent($officialAccount, $message,$type=1)
    {
        Log::debug('WechatEventService::handleEvent');

        $this->officialAccount = $officialAccount;
        $this->message = $message;
        $this->appid = $this->officialAccount->getConfig()['app_id'];
        $this->openid = $message['FromUserName'];
        $this->type = $type;

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
                //PotentialUserService::getInstance()->addUser($this->potentialUserInfo);
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
                'qrscene_wxShareReg' => 'wxShareReg',//分享邀请码
            ],
            'scan' => [
                'conpoint' => 'conPointEntrance',//管控点入口
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
        $this->potentialUserInfo = WechatUserService::getInstance()->getUserInfo($this->officialAccount, $this->openid, $refreshCache);
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

        if (Service::isSpaceStation($this->appid) && $this->type==1) {
            if (env('WECHAT_SUBSCRIBE_NEWS_MEDIA_ID_SPACE_STATION')) {
                $media = new Media(env('WECHAT_SUBSCRIBE_NEWS_MEDIA_ID_SPACE_STATION'), 'mpnews');
                $ret = $officialAccount->customer_service->message($media)->to($this->openid)->send();
                Log::info(__CLASS__ . '.subscribe 发送图文消息 ret', (array) $ret);
            }
        } elseif($this->appid == 'wx23a2e45470239e65' && $this->type==1){
            return $this->returnMsg = "Hi, 欢迎来到壹点壹滴——中国最具影响力的幼教互联网平台！\n\n以技术引领幼教产业全面升级，用爱和责任推动幼儿教育公平。在幼教互联新时代，让我们一起成为变革的先锋，赢得中国幼教下半场。\n\n更多精彩，请点击下方菜单～[Yeah!]";
        }else {


            $isSYS = OfficialAccountService::getInstance()->isYdYd($this->appid);

            if ($isSYS) {  //大号
                $returnMsg = $this->getSubContent(0);
            } else { //小号
                $schoolInfo = SchoolInfoService::getInstance()->getSchoolInfosByAppid($this->appid);
                if (isset($schoolInfo['code']) && $schoolInfo['code'] == 0) {
                    $schoolData = $schoolInfo['data'];
                    if (count($schoolData) > 0) { //如果有学校信息
                        $returnMsg = $this->getSubContent($schoolData['0']['schoolId'],$schoolData['0']['name']);
                    } else {
                        $returnMsg = $this->getSubContent(0);
                    }
                }
            }
            $this->returnMsg = $returnMsg;


            //调用事件处理方法(放到下面如果有参数，则覆盖上面的returnmsg)
            $this->callEventKeyParser('subscribe');

        }

    }

    /**
     * 取消关注
     * @param array $message
     */
    public function unsubscribe($officialAccount, $message)
    {
        $message['appid'] = $this->appid;
        Log::debug('WechatEventService::unsubscribe', $message);
        $this->potentialUserInfo = [];
        PotentialUserService::getInstance()->unsubscribe($this->openid);
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

    //获取地址
    private function getResourceUrl($resourceId,$schoolId,$openid,$appid){
        if(intval($resourceId)==0){
            $init_url = env('YDYD_FROENTEDN_HBSQ').'?school_id='.$schoolId;
        }else{
            $resource = CoaxBabyService::getInstance()->getDetail($resourceId)['data'];
            if($resource['resourceType']==2){//视频
                $init_url = env('YDYD_FROENTEDN_HBSQ').'/play-video?id='.$resourceId.'&school_id='.$schoolId;
            }else{
                $init_url = env('YDYD_FROENTEDN_HBSQ').'/audio?id='.$resourceId.'&school_id='.$schoolId;
            }
        }
        $arr['openid'] = $openid;
        $arr['appid'] = $appid;
        $arr['url'] = $init_url;
        $url = RedirectService::getInstance()->redirectUrl($arr);
        return $url;
    }

    /**
     * 微信网页授权回调
     * @param $officialAccount
     * @param $message
     */
    public function view($officialAccount, $message) {
        Log::debug('WechatEventService::view');

        //初始化微信用户信息
        $this->initUserInfo();
        //修正绑定表中openid的appid
        BindUserService::getInstance()->updateBindUserAppid($this->openid, $this->appid);
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

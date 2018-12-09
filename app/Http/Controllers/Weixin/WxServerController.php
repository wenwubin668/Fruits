<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/12/2
 * Time: 2:02 PM
 */

namespace App\Http\Controllers\WeiXin;


use App\Common\CommonConf;
use App\Http\Controllers\Controller;
use App\Services\WeiXin\WechatEventService;
use App\Services\WeiXin\WeChatMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WxServerController extends Controller
{

    //开启微信开发者
    public function check(){
        $app = app('wechat.official_account.zhenhao');

        $app->server->push(function ($message) use ($app) {
            return self::weChatListen($app,$message);
        });

        $response = $app->server->serve();
        return $response;
    }
    //事件处理
    public static function weChatListen($app, $message)
    {
        Log::info('WxServerController::wechatListen', $message);
        switch ($message['MsgType']) {
            case 'event':
                //@todo 关注、取关、扫描二维码、点击菜单
                return WeChatEventService::getInstance()->handleEvent($app, $message);
                break;
            case 'text':
                return WeChatMessageService::getInstance()->handleTextMsg($app, $message);
                break;
            case 'image': break;
            case 'voice': break;
            case 'video': break;
            case 'location': break;
            case 'link': break;
            case 'file': break;
            default: break;
        }
    }

    //微信页面授权入口
    public function index(Request $request){
        $type = $request->get('type',CommonConf::WECHAT_AUTH_WAY_1);
        $url = route('WeChatIndex',['type'=>$type]);

        if(strpos($_SERVER['HTTP_HOST'],'sg.ffbin.com') === 0 ){
            $this->saveTest();
        }
        //页面授权获取支付的openid
        $info = session()->get(CommonConf::MEMCACHE_KEY_YDYD_RECRUIT_USER_INFO);

        if (empty($info)){
            $app = app('wechat.official_account.zhenhao');
            $oauth = $app->oauth;

            $redirect_uri = route('cardCallBack').'?url='.urlencode($url);

            return $oauth->withRedirectUrl($redirect_uri)->redirect();
        }
        switch ($type) {
            case CommonConf::WECHAT_AUTH_WAY_1: //卡片管理
                $jumpUrl = route('CardList');
                break;
        }
        return redirect($jumpUrl);
    }
    public function saveTest(){
        $userInfo = [
        'openid'=>'oWuSv02XeiYTidgZAp1uhiZhHSZc',
        'nickname'=>'房佳斌',
        'headimgurl'=>'http://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJBbibC6Iyyib0an6eyKdqlmPrcYgNkgdRS43gVkZCbqicM8AWMXrt0w7lbDiafte3c4YDWlAXmxGKeJA/132',
        ];
        session()->put(CommonConf::MEMCACHE_KEY_YDYD_RECRUIT_USER_INFO,$userInfo);

        return true;
    }

    //回调
    public function callBack(Request $request){

        $url = $request->get('url');

        $app = app('wechat.official_account.zhenhao');
        $oauth = $app->oauth;
        // 获取 OAuth 授权结果用户信息
        $user = $oauth->user();

        $userInfo = $user->toArray();

        //存储微信信息
        session()->put(CommonConf::MEMCACHE_KEY_YDYD_RECRUIT_USER_INFO,$userInfo['original']);

        return redirect(urldecode($url));
    }


}
<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/12/2
 * Time: 2:02 PM
 */

namespace App\Http\Controllers\WeiXin;


use App\Http\Controllers\Controller;
use App\Services\User\UserService;
use App\Services\WeiXin\WechatEventService;
use App\Services\WeiXin\WeChatMessageService;
use Illuminate\Support\Facades\Log;

class WxServerController extends Controller
{

    public function index(){


        $arr = [
            'subscribe'=>1,
            'nickname'=>'房佳斌',
            'openid'=>'oWuSv02XeiYTidgZAp1uhiZhHSZc',
            'sex'=>1,
            'province'=>'北京',
            'country'=>'中国',
            'city'=>'东城',
            'headimgurl'=>'http://thirdwx.qlogo.cn/mmopen/wbKdib81ny6ibW5fUd99eegVp1xHIx7j1pNva0L3ZWlBwbsKB8xLibapU8HhewJiaNiaNwXACjiafGVVp8hoqW9zwhdmCibSZIkCJfh/132',
            'appid'=>'wx569d05951ef042ad',
        ];

        $res = UserService::getInstance()->addUser($arr);

        dd($res);


        echo phpinfo();
    }

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


}
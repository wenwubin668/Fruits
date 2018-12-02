<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/12/2
 * Time: 2:02 PM
 */

namespace App\Http\Controllers\WeiXin;


use App\Http\Controllers\Controller;
use App\Services\WeiXin\WechatEventService;
use App\Services\WeiXin\WeChatMessageService;
use Illuminate\Support\Facades\Log;

class WxServerController extends Controller
{
    //开启微信开发者
    public function check(){
        $app = app('wechat.official_account.zhenhao');
        $app->server->push(function ($message) use ($app) {
            return self::wechatListen($app,$message);
        });
        $response = $app->server->serve();
        return $response;
    }


    //事件处理
    public static function wechatListen($officailAccount, $message)
    {
        Log::info('WxServerController::wechatListen ', $message);
        switch ($message['MsgType']) {
            case 'event':
                //@todo 关注、取关、扫描二维码、点击菜单
                return WeChatEventService::getInstance()->handleEvent($officailAccount, $message,$type=2);
                break;
            case 'text':
                return WeChatMessageService::getInstance()->handleTextMsg($officailAccount, $message,$type=2);
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
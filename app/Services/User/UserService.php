<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/12/2
 * Time: 10:45 PM
 */

namespace App\Services\User;


use App\Services\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService extends Service
{
    protected static $instance;
    protected $db;
    public function __construct()
    {
       $this->db = DB::connection('mysql');
    }

    /**
     * 添加微信用户
     * @param $info
     * @return mixed
     */
    public function addUser($info) {
        Log::info('UserService::addUser ', $info);

        $info['created_at'] = date('Y-m-d H:i:s');
        $res = $this->db->table('sg_user')->insert($info);


        Log::info('UserService::addUser res:', $res);
    }

    /**
     * 根据openid获取用户信息
     * @param $officialAccount
     * @param $openId
     * @return array
     */
    public function getUserInfo($officialAccount, $openId, $refreshCache = false) {
        //根据openid获取用户信息
        $key = 'wechat.user.' . $openId;
        //每天500000次，加缓存，以防万一
        if (!$refreshCache && ($cache = Cache::get($key))) {
            return $this->formatUserInfo($cache);
        } else {
            try {
                $info = $officialAccount->user->get($openId);
                if ($info && !isset($info['errcode'])) {
                    Cache::put($key, $info, 60 * 24 * 3);
                }
                return $this->formatUserInfo($info);
            } catch (\Exception $e) {
                Log::error(__CLASS__ . '.getUserInfo error ' . $e->getMessage());
            }
        }
        return [];
    }

    /**
     * @param $user
     * {"subscribe":1,"openid":"oG3oIxOxPfi06VR1xIdhLB5AEa5c","nickname":"啊哈","sex":1,"language":"zh_CN","city":"昌平","province":"北京","country":"中国","headimgurl":"http://thirdwx.qlogo.cn/mmopen/6x6KvRhqKyZ8pyd244WDibJib4t57RfZYbQ6ic2k0RSxKW6EtrZicBeGLPL9XFfte4U9y1sOKnveTGsacoohNmVVZpv4wOibOb2tl/132","subscribe_time":1529935400,"remark":"","groupid":0,"tagid_list":[],"subscribe_scene":"ADD_SCENE_PROFILE_CARD","qr_scene":0,"qr_scene_str":""} {"logId":"5b30f6288ccc3","ip":"223.166.222.108"}
     * @return array
     */
    public function formatUserInfo($user) {
        $arr = [];
        if (isset($user['subscribe'])) {
            $arr['subscribe'] = $user['subscribe'];
        }
        if (isset($user['nickname'])) {
            $arr['nickname'] = $user['nickname'];
        }
        if (isset($user['openid'])) {
            $arr['openid'] = $user['openid'];
        }
        if (isset($user['sex'])) {
            $arr['sex'] = $user['sex'];
        }
        if (isset($user['province'])) {
            $arr['province'] = $user['province'];
        }
        if (isset($user['country'])) {
            $arr['country'] = $user['country'];
        }
        if (isset($user['city'])) {
            $arr['city'] = $user['city'];
        }
        if (isset($user['headimgurl'])) {
            $arr['headimgurl'] = $user['headimgurl'];
        }

        return $arr;
    }

}
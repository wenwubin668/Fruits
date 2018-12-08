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
    protected $cacheTime = 60*24*3;
    protected $cacheKey = 'v1';

    /**
     * 添加微信用户
     * @param $info
     * @return mixed
     */
    public function addUser($info) {
        Log::info('UserService::addUser', $info);
        $info['created_at'] = date('Y-m-d H:i:s');
        $res = DB::table('sg_user')
            ->insert($info);
        return $res;
    }

    /**
     * 更新用户数据
     * @param $info
     * @return bool
     */
    public function updateUser($info){
        Log::info('UserService::updateUser', $info);

        $info['updated_at'] = date('Y-m-d H:i:s');
        $res = DB::table('sg_user')
            ->where('openid',$info['openid'])
            ->update($info);
        if($res){
            $this->getOneUser($info['openid'],0);
        }
        return true;
    }

    /**
     * 根据openid获取用户信息
     * @param string $openid
     * @param bool $caching
     * @return array|\Illuminate\Database\Query\Builder|mixed
     */
    public function getOneUser($openid='',$caching=true){
        Log::info('UserService::getOneUser :'.$openid);
        if(empty($openid)){
            return [];
        }
        $ck = implode('_',['getOneUser',$openid,$this->cacheKey]);
        if ($caching === 0) {Cache::forget($ck);return;}
        if ($caching === false) Cache::forget($ck);
        if ($re = Cache::get($ck)) return $re;

        $res = DB::table('sg_user')
            ->where('openid',$openid)
            ->first();
        $res = (array) $res;

        Cache::put($ck,$res,$this->cacheTime);

        return $res;
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
     * 整理微信用户数据
     * @param $user
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
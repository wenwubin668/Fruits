<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/12/2
 * Time: 10:45 PM
 */

namespace App\Services\Card;


use App\Services\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CardService extends Service
{
    protected static $instance;
    protected $cacheTime = 60*24*3;
    protected $cacheKey = 'v1';

    /**
     * 添加卡片
     * @param $info
     * @return mixed
     */
    public function addCard($info) {
        Log::info('CardService::addCard', $info);
        $info['created_at'] = date('Y-m-d H:i:s');
        $res = DB::table('sg_card')
            ->insert($info);
        if($res){
            $this->getOneCardList($info['uid'],0);
        }
        return $res;
    }
    /**
     * 删除
     * @param int $id
     * @return bool
     */
    public function delCard($id=0){
        Log::info('CardService::delCard'.$id);
        if(intval($id)==0){
            return false;
        }
        $info['updated_at'] = date('Y-m-d H:i:s');
        $info['status'] = 2;
        $res = DB::table('sg_card')
            ->where('id',$id)
            ->update($info);
        if($res){
            $this->getOneCard($id,0);
            $this->getOneCardList($info['uid'],0);
        }
        return true;

    }

    /**
     * 更新卡片
     * @param $info
     * @return bool
     */
    public function updateCard($id,$info){
        Log::info('CardService::updateCard', $info);

        $info['updated_at'] = date('Y-m-d H:i:s');
        $res = DB::table('sg_card')
            ->where('id',$id)
            ->update($info);
        if($res){
            $this->getOneCard($id,0);
            $this->getOneCardList($info['uid'],0);
        }
        return true;
    }
    /**
     * 根据id获取账户
     * @param int $id
     * @param bool $caching
     * @return array|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|mixed|null|object|void
     */
    public function getOneCard($id=0,$caching=true){
        Log::info('CardService::getOneCard :'.$id);
        if(intval($id)==0){
            return [];
        }
        $ck = implode('_',['getOneUser',$id,$this->cacheKey]);
        if ($caching === 0) {Cache::forget($ck);return;}
        if ($caching === false) Cache::forget($ck);
        if ($re = Cache::get($ck)) return $re;

        $res = DB::table('sg_card')
            ->where('id',$id)
            ->first();

        Cache::put($ck,$res,$this->cacheTime);

        return $res;
    }
    /**
     * 获取列表不分页
     * @param string $uid
     * @param bool $caching
     * @return array|\Illuminate\Support\Collection|mixed|void
     */
    public function getOneCardList($uid=0,$caching=true){
        Log::info('CardService::getOneCardList :'.$uid);
        if(intval($uid)==0){
            return [];
        }
        $ck = implode('_',['getOneCardList',$uid,$this->cacheKey]);
        if ($caching === 0) {Cache::forget($ck);return;}
        if ($caching === false) Cache::forget($ck);
        if ($re = Cache::get($ck)) return $re;

        $res = DB::table('sg_card')
            ->where('uid',$uid)
            ->get();

        Cache::put($ck,$res,$this->cacheTime);

        return $res;
    }
    /**
     * 获取分页列表
     * @param string $openid
     * @param int $p
     * @param int $size
     * @param bool $caching
     * @return array|mixed|void
     */
    public function getCardPageList($openid='',$p = 1,$size = 5,$caching=true){
        Log::info('CardService::getCardPageList :'.$openid);
        $ck = implode(array(__CLASS__, __FUNCTION__,$openid,$p,$size,$this->cacheKey), '-');
        if ($caching === 0) {Cache::forget($ck);return;}
        if ($caching === false) Cache::forget($ck);
        if ($re=Cache::get($ck))  return $re;

        $start = ($p-1)*$size;
        $res = DB::select('select * from sg_card where openid=? limit ?,?', [$openid,$start,$size]);
        $res = (array) $res;

        Cache::put($ck,$res,$this->cacheTime);

        return $res;
    }


    /**
     * 获取账单列表
     * @param int $cid
     * @param bool $caching
     * @return array|\Illuminate\Support\Collection|mixed|void
     */
    public function getOneCardAmountList($cid=0,$caching=true){
        Log::info('CardService::getOneCardAmountList :'.$cid);
        if(intval($cid)==0){
            return [];
        }
        $ck = implode('_',['getOneCardAmountList',$cid,$this->cacheKey]);
        if ($caching === 0) {Cache::forget($ck);return;}
        if ($caching === false) Cache::forget($ck);
        if ($re = Cache::get($ck)) return $re;

        $res = DB::table('sg_card_amount')
            ->where('cid',$cid)
            ->get();

        Cache::put($ck,$res,$this->cacheTime);

        return $res;
    }



}
<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/12/8
 * Time: 5:46 PM
 */

namespace App\Http\Controllers\Weixin;


use App\Common\CommonConf;
use App\Http\Controllers\Controller;
use App\Services\Card\CardService;
use App\Services\User\UserService;
use Illuminate\Http\Request;

class CardController extends Controller
{
    protected $switch;
    protected $userInfo;
    public function __construct(Request $request)
    {
        $this->middleware(function ($request,$next){
            $userInfo = session()->get(CommonConf::MEMCACHE_KEY_YDYD_RECRUIT_USER_INFO);
            if (empty($userInfo)){
                $url = route('WeChatIndex',['return_url'=>urlencode($request->fullUrl())]);
                return redirect($url);
            }else{
                $this->userInfo = UserService::getInstance()->getOneUser($userInfo['openid']);
                return $next($request);
            }
        });
    }
    //卡片列表
    public function list(){
        $list = CardService::getInstance()->getOneCardList($this->userInfo['id']);

        //整理还款日
        foreach ($list as $item){
            $item->pay_day = CardService::getInstance()->getPayTime($item->account_day,$item->pay_day,$item->pay_type);
            $item->account_day = CardService::getInstance()->getAccountDay($item->account_day,$item->pay_type);
        }
        $data = [
            'list'=>$list,
            'date'=>date('m-d'),
            'title'=>'信用卡列表',
            //'left'=>['name'=>'返回','url'=>'javascript:history.go(-1);'],
            'right'=>['name'=>'添加','url'=>route('CardAction')],
        ];
        //dump($data);
        return view('card.list',$data);
    }
    //添加卡片
    public function action(Request $request){
        $post = $request->post();
        if($request->ajax()){
            $post['uid'] = $this->userInfo['id'];
            $res = CardService::getInstance()->addCard($post);
            if($res){
                return ['code'=>0,'msg'=>'提交成功','data'=>$res,'url'=>route('CardList')];
            }else{
                return ['code'=>-1,'msg'=>'提交失败','data'=>$res];
            }
        }
        $data = [
            'title'=>'添加信用卡',
            'left'=>['name'=>'返回','url'=>route('CardList')],
            //'right'=>['name'=>'添加','url'=>route('CardAction')],
        ];
        return view('card.action',$data);
    }
    //查看卡片
    public function info(Request $request){
        $id = $request->get('id');
        $info = CardService::getInstance()->getOneCard($id);

        $info->pay_day = CardService::getInstance()->getPayTime($info->account_day,$info->pay_day,$info->pay_type);

        $info->account_day = CardService::getInstance()->getAccountDay($info->account_day,$info->pay_type);

        $data = [
            'info'=>$info,
            'title'=>$info->name,
            'left'=>['name'=>'返回','url'=>route('CardAmountList',['id'=>$id])],
        ];
        //dump($data);
        return view('card.info',$data);
    }
    //账单列表
    public function amountList(Request $request){
        $id = $request->get('id');

        $list = CardService::getInstance()->getOneCardAmountList($id);

        $info = CardService::getInstance()->getOneCard($id);
        $data = [
            'list'=>$list,
            'title'=>$info->name,
            'date'=>date('Y-m-d'),
            'left'=>['name'=>'返回','url'=>route('CardList')],
            'right'=>['name'=>'详情','url'=>route('CardInfo',['id'=>$id])],
        ];

        //dump($data);
        return view('card.amountlist',$data);
    }
    //账单详情
    public function amountInfo(Request $request){

        $id = $request->get('id');

        $info = CardService::getInstance()->getOneAmount($id);

        if($request->ajax()){
            $post = $request->post();
            if(!empty($post['pay_money'])){
                $post['status'] = 2;
            }else{
                unset($post['pay_money']);
            }
            $res = CardService::getInstance()->updateAmountAction($id,$post);
            if($res){
                return ['code'=>0,'msg'=>'提交成功','data'=>$res,'url'=>route('CardAmountList',['id'=>$info->cid])];
            }else{
                return ['code'=>-1,'msg'=>'提交失败','data'=>$res];
            }
        }

        $data = [
            'info'=>$info,
            'title'=>$info->amount_name.'#'.date('m',strtotime($info->pay_time)).'月代还',
            'left'=>['name'=>'返回','url'=>route('CardAmountList',['id'=>$info->cid])],
            //'right'=>['name'=>'添加','url'=>route('CardAction')],
        ];
        return view('card.amountinfo',$data);
    }
}
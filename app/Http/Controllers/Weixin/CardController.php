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
                $url = route('WeChatIndex',['type'=>CommonConf::WECHAT_AUTH_WAY_1]);
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
            $item->pay_day = $this->getPayTime($item->account_day,$item->pay_day,$item->pay_type);
            $item->account_day = $this->getAccountDay($item->account_day,$item->pay_type);
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
    //账单列表
    public function amountList(Request $request){
        $id = $request->get('id');

        $list = CardService::getInstance()->getOneCardAmountList($id);

        $info = CardService::getInstance()->getOneCard($id);
        $data = [
            'list'=>$list,
            'title'=>$info->name,
            'left'=>['name'=>'返回','url'=>route('CardList')],
            'right'=>['name'=>'详情','url'=>route('CardInfo',['id'=>$id])],
        ];

        //dump($data);
        return view('card.amountlist',$data);
    }

    //查看卡片
    public function info(Request $request){
        $id = $request->get('id');
        $info = CardService::getInstance()->getOneCard($id);

        $info->pay_day = $this->getPayTime($info->account_day,$info->pay_day,$info->pay_type);

        $info->account_day = $this->getAccountDay($info->account_day,$info->pay_type);

        $data = [
            'info'=>$info,
            'title'=>$info->name,
            'left'=>['name'=>'返回','url'=>route('CardAmountList',['id'=>$id])],
        ];
        //dump($data);
        return view('card.info',$data);
    }


    //计算还款日
    private function getPayTime($account_day,$pay_day,$type=1){
        if($type==2){
            $time = strtotime(date('Y').'-'.date('m').'-'.$account_day) + $pay_day*60*60*24;
            return date('m-d',$time);
        }else{
            return date('m-d',strtotime(date('Y').'-'.date('m').'-'.$pay_day));
        }
    }

    private function getAccountDay($account_day,$type=1){
        if($type==3){
            $days = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
            return date('m-d',strtotime(date('Y').'-'.date('m').'-'.$days));
        }else{
            return date('m-d',strtotime(date('Y').'-'.date('m').'-'.$account_day));
        }
    }


}
<?php

namespace App\Console\Commands\Task;

use App\Common\CommonConf;
use App\Services\Card\CardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OutAmount extends Command
{
    use TemplateMsgTraits;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:OutAmount';
    protected $msgScenes = 'OutAmount';
    
    protected $defaultNum = CommonConf::TASK_PARSE_MAX;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '定时出账单';
    protected $dataBase;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->dataBase = DB::reconnect("mysql");
    	//若有需要提醒的课程,生成task_log
        $now = date('j',strtotime('-1 day'));
        $sql = "select * from sg_card where account_day='{$now}' and status=1";
        $list = $this->dataBase->select($sql);
        if(empty($list)){
            return false;
        }
        $taskNum = count($list);
        $sendNum = 0;
        foreach ($list as $item){
            $item = (array)$item;
            //加锁处理
            $lockKey = implode('_',[$this->msgScenes,$item['id'],'v2']);
            $taskLock = $this->getTaskLock($lockKey);
            if($taskLock || 1){
                $payDate = CardService::getInstance()->getPayTime($item['account_day'],$item['pay_day'],$item['pay_type']);
                $param = [
                    'cid'=>$item['id'],
                    'amount_name'=>$item['name'],
                    'pay_time'=>date('Y').'-'.$payDate,
                ];
                $res = CardService::getInstance()->outAmountAction($param);
                if($res){
                    $sendNum++;
                }
            }
        }

    	$resultMsg = "本次共处理 %d 个出账任务，\r\n 完成 %d 个";
    	
    	$this->info(sprintf($resultMsg, $taskNum, $sendNum));
    }



}

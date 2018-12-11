<?php


namespace App\Console\Commands\Task;



use Illuminate\Support\Facades\Cache;
use App\Services\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


trait TemplateMsgTraits{

    /*//根据条件获取tasklog
    public function getTaskLogByCondition($condition, $max){

        $selectSql = "SELECT %s FROM %s WHERE %s LIMIT %d";
        $sqlFields = "id,scenes,school_id,send_param,send_time";
        $sqlTable = self::DB_BASE. ".task_log";
        $sqlWhere = '';
        $whereArr = [];
        if(is_array($condition)){
            foreach ($condition as $key => $value) {
                $whereArr[] = "$key='$value'";
            }
            $sqlWhere .= implode(' AND ', $whereArr);
        }else{
            $sqlWhere .= $condition;
        }

        $selectSql = sprintf($selectSql, $sqlFields, $sqlTable, $sqlWhere, $max);
        $taskLogs = $this->dataBase->select($selectSql);
//        echo $selectSql;

        return $taskLogs;
    }

    //
    public function handle()
    {
        // 处理命令行参数
        $max			= $this->option('max')			 ?? $this->defaultNum;
        $taskId			= $this->option('taskId')		 ?? 0;
        $schoolId		= $this->option('schoolId')		 ?? 0;
        $intervalTime	= $this->option('interval-time') ?? 30;

        $this->taskLogServ    = TaskLogService::getInstance();
        $this->sendMsgLogServ = SendMsgLogService::getInstance();
        $this->dataBase       = DB::reconnect('db_task');

        // 查询条件
        $condition = [];
        $condition['scenes'] = $this->msgScenes;
        $condition['status'] = 1;
        if ($taskId > 0) {
            $condition['id'] = $taskId;
        }
        if ($schoolId > 0) {
            $condition['school_id'] = $schoolId;
        }

        // 死循环
        while (true) {

            try {
                $taskLogs = $this->getTaskLogByCondition($condition, $max);

                // 开始时间
                $startTime = time();

                if (!empty($taskLogs)) {

                    foreach ($taskLogs as $taskLog) {

                        $taskLog = (array) $taskLog;

                        $thread = $this->sendMsgLogData($taskLog);
                        if (!empty($thread)) {
                            Log::info(__CLASS__ . '.sendMsgLogServ->createBatchSendMsgLog.param:', $thread);
                            $sendResult = $this->sendMsgLogServ->createBatchSendMsgLog($thread);
                            Log::info(__CLASS__ . '.sendMsgLogServ->createBatchSendMsgLog.result:', $sendResult);
                            if ($sendResult['code'] > 0) {
                                $this->dataBase->table('base.task_log')->where('id', $taskLog['id'])->update(['status' => 4]);
                                $this->line("批量错误:".$sendResult['msg']);
                            } else {
                                $this->dataBase->table('base.task_log')->where('id', $taskLog['id'])->update(['status' => 2]);
                                $this->line("批量成功:".$sendResult['msg']);
                            }
                        }else{

                            $this->dataBase->table('base.task_log')->where('id', $taskLog['id'])->update(['status' => 4]);
                            $this->line("批量失败:解析数据为空");
                        }
                    }
                }

                // 执行时间
                $executeTime = time() - $startTime;

            } catch (\Exception $e) {
                // 未知的异常
                $executeTime = 0;
                Log::error('unknown exception: ' . $e);
            }

            // 休眠时间
            $sleepTime = $intervalTime - $executeTime;
            if ($sleepTime > 0) {
                sleep($sleepTime);
            }
        }
    }


    public function getStudentParents($studentID, $withMobile=false) {
        $fields = 'r.id,r.uid,r.school_id,r.class_id,r.class_name,r.name,r.student_id,r.student_name,r.primary_identity,r.sub_identity';
        $fields .= ',b.openid,b.appid';
        $fields .= $withMobile==true? ',u.mobile' : '';

        $tableUnion = self::DB_KINDERGARTEN.'.user_role AS r';
        $tableUnion .= ' LEFT JOIN '.self::DB_UCENTER.'.user_bindinfo AS b ON r.uid=b.uid';
        $tableUnion .= $withMobile==true? ' LEFT JOIN '.self::DB_UCENTER.'.user_info AS u ON r.uid=u.uid' : '';
        $condition = [];
        if (strpos($studentID,',') === false)
        {
            $studentID = intval($studentID);
            $condition[] = "r.student_id=".$studentID;

        } else {
            $studentID = explode(',', $studentID);
            $condition[] = "r.student_id IN (".implode(',', $studentID).")";
        }

        $condition[] = "r.primary_identity=".UcenterConf::USER_PRIMARY_IDENTITY_PARENT;
        $condition[] = "r.del_flag=1";

        $selectRoles = sprintf("SELECT %s FROM %s WHERE %s ",
            $fields,
            $tableUnion,
            implode(" AND ", $condition)
        );

        $roles = $this->dataBase->select($selectRoles);

        return $roles;
    }

    //获取学校园长主身份的人
    public function getSchoolHeadmaster($schoolId, $withMobile=false) {
        $fields = 'r.id,r.uid,r.school_id,r.class_id,r.class_name,r.name,r.student_id,r.student_name,r.primary_identity,r.sub_identity';
        $fields .= ',b.openid,b.appid';
        $fields .= $withMobile==true? ',u.mobile' : '';

        $tableUnion = self::DB_KINDERGARTEN.'.user_role AS r';
        $tableUnion .= ' LEFT JOIN '.self::DB_UCENTER.'.user_bindinfo AS b ON r.uid=b.uid';
        $tableUnion .= $withMobile==true? ' LEFT JOIN '.self::DB_UCENTER.'.user_info AS u ON r.uid=u.uid' : '';
        $condition = [];
        $condition[] = "r.school_id=".$schoolId;
        $condition[] = "r.primary_identity=".UcenterConf::USER_PRIMARY_IDENTITY_HEADMASTER;
        $condition[] = "r.del_flag=1";
        $condition[] = "b.del_flag=1";

        $selectRoles = sprintf("SELECT %s FROM %s WHERE %s ", $fields, $tableUnion, implode(" AND ", $condition));

        $roles = $this->dataBase->select($selectRoles);

        return $roles;
    }


    //格式化单个tasklog的批量sendMsgLog信息
    public function makeSendLogInfo($taskLog, $templateCode, $toIdentity, $remendSender, $subIdentity=[]){
        $scenes = $taskLog['scenes'];
        $schoolId = $taskLog['school_id'];
        $schoolInfo = $this->getSchoolInfo($schoolId);

        $appId = $schoolInfo['appid'];
        $sendTime = $taskLog['send_time'];
        $sendParam = json_decode($taskLog['send_param'], true);

        $roleId = $sendParam['roleId']?? 0;
        $toRoleIds = $sendParam['toRoleId']?? [];
        $primaryIdentity = $sendParam['primaryIdentity'];//发送者角色身份
        $classId = $sendParam['classId']?? '';
        $groupId = $sendParam['groupId']?? '';
        $sendParam['type'] = $sendParam['type']?? CommonConf::TASK_MSGSENDTYPE_WECHAT;
        $withOpenId = true;
        $withMobile = fasle;

        if (isset($sendParam['type']) && in_array($sendParam['type'], [CommonConf::TASK_MSGSENDTYPE_WECHATSMS,CommonConf::TASK_MSGSENDTYPE_WECHATSMSPHONE])) {
            $withMobile = true;
        }
        if (isset($sendParam['type']) && in_array($sendParam['type'], [CommonConf::TASK_MSGSENDTYPE_WECHAT,CommonConf::TASK_MSGSENDTYPE_WECHATSMS,CommonConf::TASK_MSGSENDTYPE_WECHATSMSPHONE])) {
            $withOpenId = true;
        }

        if ($roleId > 0) {
            $this->line("roleId:".$roleId);
            $sendRole = $this->getRoleInfo($roleId, $withMobile);
            $senderInfo = $this->getSenderInfo($sendRole);
            print_r($senderInfo);
        }

        //开始解析接收者身份【给了toRoleIds的】
        if (isset($toRoleIds) && !empty($toRoleIds)) {
            if (strpos($toRoleIds,',') === false)
            {
                $toRoleIds = intval($toRoleIds);
            } else {
                $toRoleIds = explode(',', $toRoleIds);
            }
            $toRoles = $this->getRolesByIds($toRoleIds, $sendTime, $withMobile);
            print_r($toRoles);
        }
        if (empty($toRoles) && !empty($groupId) && !empty($classId)) {
            $toRoles = $this->getGroupRoles($schoolId, $classId, $groupId, $sendTime, $withMobile);
        }
        //上面的有了下面自然不处理【是不是有个bug，如果真没有了呢】
        if (empty($toRoles) && !empty($classId)) {
            $this->line("班级查找...");
            if (!strstr( $classId,','))
            {
                $classId = intval($classId);
            } else {
                $classId = explode(',', $classId);
            }
            $this->line("classId:".$classId);
            $toRoles = $this->getClassRoles($classId, $toIdentity, $sendTime, $withMobile);
        }
        //连班级都没有只能按学校了
        if (empty($toRoles) && !empty($schoolId)) {

            $toRoles = $this->getSchoolRoles($schoolId, $toIdentity, $sendTime, $withMobile);
        }
        $sendMsgLog = [];

        if ($remendSender === true) {
            $toRoles[] = $sendRole;
        }
        $stationAppId = WechatConf::getSpaceStationAppId();

        $toRoles = $this->getUsefullRoles($toRoles, $appId);
        foreach ($toRoles as $key => $roleInfo) {
            $roleInfo = (array) $roleInfo;
            //print_r($roleInfo);
            if ($roleInfo['primary_identity'] ==UcenterConf::USER_PRIMARY_IDENTITY_PARENT && $roleInfo['appid'] == $stationAppId ){

                continue;
            }
            // 限制子身份
            // [之所以没有把子身份限制放到sql里是觉得员工身份一般未提及且数量很少。
            // 当前只考虑家长【没考虑下级身份】、园长、教师]
            if (!empty($subIdentity) && !in_array($roleInfo['sub_identity'], $subIdentity)) {
                continue;
            }
            //限制是否发给自己
            if ($remendSender===false && $roleId == $roleInfo['id']) {
                continue;
            }

            $log = $param = [];
            $receiverInfo = $this->getReceiverInfo($roleInfo);
            unset($sendParam['classId']);
            unset($sendParam['toRoleId']);
            unset($sendParam['roleId']);
            unset($sendParam['groupId']);

            $param = array_merge($senderInfo, $receiverInfo, $sendParam);

            //$param['appId'] = $sendParam['appId'];
            $param['appId'] = $roleInfo['appid'];
            $param['contentId'] = $sendParam['contentId'];
            $param['schoolName'] = $schoolInfo['name'];
            $param['content'] = $sendParam['content']??'发送内容为空';

            $log['schoolId'] = $schoolId;
            $log['classId'] = $roleInfo['class_id']??0;
            $log['mobile'] = $roleInfo['mobile']??'';
            $log['openId'] = $roleInfo['openid']??'';
            $log['studentId'] = $roleInfo['student_id']??0;
            $log['roleId'] = $roleInfo['id']??0;
            $log['sendTime'] = $sendTime;
            $log['thirdId'] = $taskLog['id'];
            $log['scenes'] = $scenes;
            $log['tmplId'] = $templateCode;
            $log['type'] = $sendParam['type'];
            $log['sendContent'] = json_encode($param, JSON_UNESCAPED_UNICODE);
            //$log['status'] = 3;//默认是3新建

            $timeStamp = strtotime($sendTime);
            if ($sendParam['type']==4) {
                $log['smsTime'] = date('Y-m-d H:i:s', $timeStamp+WechatConf::TEMPLATE_SMS_DELAY);
            }
            if ($sendParam['type']==5) {
                $log['smsTime'] = date('Y-m-d H:i:s', $timeStamp+WechatConf::TEMPLATE_SMS_DELAY);
                $log['voiceTime'] = date('Y-m-d H:i:s', $timeStamp+WechatConf::TEMPLATE_VOICE_DELAY);
            }

            $sendMsgLog[] = $log;
        }

        return $sendMsgLog;
    }

    //格式化单个tasklog的批量sendMsgLog信息
    public function makeOfficialInfo($taskLog, $templateCode, $toIdentity, $remendSender){
        $scenes = $taskLog['scenes'];
        $schoolId = $taskLog['school_id'];
        $schoolInfo = $this->getSchoolInfo($schoolId);

        $sendTime = $taskLog['send_time'];
        $sendParam = json_decode($taskLog['send_param'], true);

        $roleId = $sendParam['roleId']?? 0;
        $toRoleIds = $sendParam['toRoleId']?? [];
        $primaryIdentity = $sendParam['primaryIdentity'];//发送者角色身份
        $classId = $sendParam['classId']?? '';
        $groupId = $sendParam['groupId']?? '';
        $sendParam['type'] = CommonConf::TASK_MSGSENDTYPE_WECHAT;
        $withOpenId = true;
        $withMobile = fasle;

        if ($roleId > 0) {
            $sendRole = $this->getRoleInfo($roleId,false,false,$schoolInfo['appid']);
            $senderInfo = $this->getSenderInfo($sendRole);
        }

        //开始解析接收者身份【给了toRoleIds的】
        if (isset($toRoleIds) && !empty($toRoleIds)) {
            if (strpos($toRoleIds,',') === false)
            {
                $toRoleIds = intval($toRoleIds);
            } else {
                $toRoleIds = explode(',', $toRoleIds);
            }
            $toRoles = $this->getRolesByIds($toRoleIds, $sendTime, $withOpenId, $withMobile, $schoolInfo['appid']);
        }

        //上面的有了下面自然不处理【是不是有个bug，如果真没有了呢】
        if (empty($toRoles) && !empty($classId)) {
            if (strpos($classId,',') === false)
            {
                $classId = intval($classId);
            } else {
                $classId = explode(',', $classId);
            }
            $this->line("classId:".$classId);
            $toRoles = $this->getClassRoles($classId, $toIdentity, $sendTime, $withOpenId, $withMobile,$schoolInfo['appid']);
        }
        //连班级都没有只能按学校了
        if (empty($toRoles) && !empty($schoolId)) {

            $toRoles = $this->getSchoolRoles($schoolId, $toIdentity, $sendTime, $withOpenId, $withMobile,$schoolInfo['appid']);
        }
        $sendMsgLog = [];
        if ($remendSender === true) {
            $toRoles[] = $sendRole;
        }
        $stationAppId = WechatConf::getSpaceStationAppId();

        $toRoles = $this->getUsefullRoles($toRoles, $schoolInfo['appid']);

        foreach ($toRoles as $key => $roleInfo) {
            $roleInfo = (array) $roleInfo;
            if ($roleInfo['primary_identity'] ==UcenterConf::USER_PRIMARY_IDENTITY_PARENT && $roleInfo['appid'] == $stationAppId ){

                continue;
            }
            // 限制子身份
            // [之所以没有把子身份限制放到sql里是觉得员工身份一般未提及且数量很少。
            // 当前只考虑家长【没考虑下级身份】、园长、教师]
            if (!empty($subIdentity) && !in_array($roleInfo['sub_identity'], $subIdentity)) {
                continue;
            }
            //限制是否发给自己
            if ($remendSender===false && $roleId == $roleInfo['id']) {
                continue;
            }

            $log = $param = [];
            $receiverInfo = $this->getReceiverInfo($roleInfo);
            unset($sendParam['classId']);
            unset($sendParam['toRoleId']);
            unset($sendParam['roleId']);
            unset($sendParam['groupId']);

            $param = array_merge($senderInfo, $receiverInfo,$sendParam);

            //$param['appId'] = $sendParam['appId'];
            $param['appId'] = $roleInfo['appid'];
            $param['contentId'] = $sendParam['contentId'];
            $param['schoolName'] = $schoolInfo['name'];
            $param['content'] = $sendParam['content']??'发送内容为空';

            $log['schoolId'] = $schoolId;
            $log['classId'] = $roleInfo['class_id'];
            $log['mobile'] = $roleInfo['mobile']??'';
            $log['openId'] = $roleInfo['openid']??'';
            $log['studentId'] = $roleInfo['student_id'];
            $log['roleId'] = $roleInfo['id'];
            $log['sendTime'] = $sendTime;
            $log['thirdId'] = $taskLog['id'];
            $log['scenes'] = $scenes;
            $log['tmplId'] = $templateCode;
            $log['type'] = $sendParam['type'];
            $log['sendContent'] = json_encode($param, JSON_UNESCAPED_UNICODE);
            //$log['status'] = 3;//默认是3新建

            $sendMsgLog[] = $log;
        }

        return $sendMsgLog;
    }

    //根据classID获取role信息
    public function getClassRoles($classId, $primaryIdentity, $sendTime, $withMobile = false)
    {
        $fields = 'r.id,r.uid,r.school_id,r.class_id,r.class_name,r.name,r.student_id,r.student_name,r.primary_identity,r.sub_identity,b.openid,b.appid';
        $fields .= $withMobile==true? ',u.mobile' : '';

        $tableUnion = self::DB_KINDERGARTEN.'.user_role AS r';
        $tableUnion .= ' LEFT JOIN '.self::DB_UCENTER.'.user_bindinfo AS b ON r.uid=b.uid';
        $tableUnion .= $withMobile==true? ' LEFT JOIN '.self::DB_UCENTER.'.user_info AS u ON r.uid=u.uid' : '';
        $condition = [];

        if (is_array($classId)) {
            $condition[] = "r.class_id IN (".implode(',', $classId).")";
        }else{
            $condition[] = "r.class_id=".$classId;
        }
        if (!empty($primaryIdentity)) {
            if (is_array($primaryIdentity)) {
                foreach ($primaryIdentity as $k=>$val){
                    if (empty($val)){
                        unset($primaryIdentity[$k]);
                    }
                }
                if (empty($primaryIdentity)){

                    return [];
                }
                $condition[] = "r.primary_identity IN (".implode(',', $primaryIdentity).")";
            }else{
                $condition[] = "r.primary_identity=".$primaryIdentity;
            }
        }

        $condition[] = "r.del_flag=1";
        $condition[] = "b.del_flag=1";
        $selectRoles = sprintf("SELECT %s FROM %s WHERE %s ", $fields, $tableUnion, implode(" AND ", $condition));

        $roles = $this->dataBase->select($selectRoles);

        return $roles;
    }

    //根据schoolID获取role信息
    public function getSchoolRoles($shcoolId, $primaryIdentity, $sendTime, $withMobile = false)
    {
        $fields = 'r.id,r.uid,r.school_id,r.class_id,r.class_name,r.name,r.student_id,r.student_name,r.primary_identity,r.sub_identity,b.openid,b.appid';
        $fields .= $withMobile===true? ',u.mobile' : '';

        $tableUnion = self::DB_KINDERGARTEN.'.user_role AS r';
        $tableUnion .= ' LEFT JOIN '.self::DB_UCENTER.'.user_bindinfo AS b ON r.uid=b.uid';
        $tableUnion .= $withMobile===true? ' LEFT JOIN '.self::DB_UCENTER.'.user_info AS u ON r.uid=u.uid' : '';
        $sqlWhere = '';
        if (!empty($primaryIdentity)) {
            if (is_array($primaryIdentity)) {

                $sqlWhere .= "AND r.primary_identity IN (".implode(',', $primaryIdentity).")";
            } else {
                $sqlWhere .= "AND r.primary_identity=".$primaryIdentity;
            }
        }
        $sqlWhere.= " AND r.del_flag=1";
        $sqlWhere.= " AND b.del_flag=1";

        $selectRoles = sprintf("SELECT %s FROM %s WHERE r.school_id=%d %s ", $fields, $tableUnion, $shcoolId, $sqlWhere);

        $roles = $this->dataBase->select($selectRoles);

        return $roles;
    }



    //根据groupid获取相关role信息
    public function getGroupRoles($schoolId, $classId, $groupId, $sendTime, $withMobile = false)
    {
        //根据学校和班级和分组查出studentid
        if (empty($schoolId) || empty($classId) || empty($groupId)) {
            return [];
        }
        $studentIds = $this->getGroupStudent($schoolId, $classId, $groupId);

        //按studentid去找role信息
        $fields = 'r.id,r.uid,r.school_id,r.class_id,r.class_name,r.name,r.student_id,r.student_name,r.primary_identity,r.sub_identity,b.openid,b.appid';
        $fields .= $withMobile===true? ',u.mobile' : '';

        $tableUnion = self::DB_KINDERGARTEN.'.user_role AS r';
        $tableUnion .= ' LEFT JOIN '.self::DB_UCENTER.'.user_bindinfo AS b ON r.uid=b.uid';
        $tableUnion .= $withMobile===true? ' LEFT JOIN '.self::DB_UCENTER.'.user_info AS u ON r.uid=u.uid' : '';
        $sqlWhere = '';

        if (is_array($studentIds)) {

            foreach ($studentIds as $k=>$val){
                if (empty($val)){
                    unset($studentIds[$k]);
                }
            }
            if (empty($studentIds)){

                return [];
            }
            $sqlWhere .= "AND r.student_id IN (".implode(',', $studentIds).")";
        } else {
            $sqlWhere .= "AND r.student_id=".$studentIds;
        }
        $sqlWhere .= " AND r.del_flag=1 and b.del_flag=1";

        $selectRoles = sprintf("SELECT %s FROM %s WHERE r.school_id=%d %s ", $fields, $tableUnion, $schoolId, $sqlWhere);

        $roles = $this->dataBase->select($selectRoles);

        return $roles;
    }

    //根据groupid获取孩子编号
    function getGroupStudent($schoolId, $classId, $groupId) {
        //$fields = 'gi.id,gi.name as group_name,gi.class_id,gs.student_id';
        $fields = 'gs.student_id';
        $tableUnion = self::DB_KINDERGARTEN.'.group_info AS gi';
        $tableUnion .= ' LEFT JOIN '.self::DB_KINDERGARTEN.'.group_student AS gs ON gi.id=gs.group_id';

        $condition = [];
        $condition[] = "gi.school_id=".$schoolId;

        if (is_array($classId)) {
            foreach ($classId as $k=>$val){
                if (empty($val)){
                    unset($classId[$k]);
                }
            }
            $condition[] = "gi.class_id IN (".implode(',', $classId).")";
        }else{
            $condition[] = "gi.class_id=".$classId;
        }
        if (is_array($groupId)) {
            foreach ($groupId as $k=>$val){
                if (empty($val)){
                    unset($groupId[$k]);
                }
            }
            if (empty($groupId)){

                return [];
            }
            $condition[] = "gi.id IN (".implode(',', $groupId).")";
        }else{
            $condition[] = "gi.id=".$groupId;
        }
        $condition[] = "gi.del_flag=1";
        $selectStudents = sprintf("SELECT %s FROM %s WHERE %s ", $fields, $tableUnion, implode(' AND ', $condition));
        echo $selectStudents;
        $roles = $this->dataBase->select($selectStudents);
        $students = [];
        if ($roles) {
            foreach ($roles as $key => $student) {
                $students[] = $student->student_id;
            }
        }

        return $students;
    }

    //根据id获取角色信息
    public function getRolesByIds($roleIds, $sendTime, $withMobile = false)
    {
        $fields = 'r.id,r.uid,r.school_id,r.class_id,r.class_name,r.name,r.student_id,r.student_name,r.primary_identity,r.sub_identity,b.openid, b.appid';
        $fields .= $withMobile == true ? ',u.mobile' : '';

        $tableUnion = self::DB_KINDERGARTEN . '.user_role AS r';
        $tableUnion .= ' LEFT JOIN ' . self::DB_UCENTER . '.user_bindinfo AS b ON r.uid=b.uid';
        $tableUnion .= $withMobile == true ? ' LEFT JOIN ' . self::DB_UCENTER . '.user_info AS u ON r.uid=u.uid' : '';
        $condition = [];
        if (is_array($roleIds)) {
            foreach ($roleIds as $k=>$val){
                if (empty($val)){
                    unset($roleIds[$k]);
                }
            }
            if (empty($roleIds)){

                return [];
            }
            $condition[] = "r.id IN (" . implode(',', $roleIds) . ")";
        } else {
            $condition[] = "r.id=" . $roleIds;
        }
        $condition[] = "r.del_flag=1";
        $condition[] = "b.del_flag=1";


        $selectRoles = sprintf("SELECT %s FROM %s WHERE %s ", $fields, $tableUnion, implode(" AND ", $condition));

        $roles = $this->dataBase->select($selectRoles);

        return $roles;
    }

    //根据id获取学校信息
    public function getSchoolInfo($schoolId)
    {
        static $schoolInfo = [];
        if (isset($schoolInfo[$schoolId])&& !empty($schoolInfo[$schoolId])) {

            return $schoolInfo[$schoolId];
        }

        $selectSql = "SELECT school_id,name,appid FROM ".self::DB_KINDERGARTEN.".school_info WHERE school_id=".$schoolId;
        $school = $this->dataBase->select($selectSql);
        if ($school)
        {
            $schoolInfo[$schoolId] = (array) $school[0];

            return $schoolInfo[$schoolId];
        }

        return false;
    }

    //根据roleid获取role信息
    public function getRoleInfo($roleId, $withMobile = false, $schoolAppId='')
    {
        static $roleInfo = [];

        if (isset($roleInfo[$roleId])&& !empty($roleInfo[$roleId])) {

            return $roleInfo[$roleId];
        }

        $fields = 'r.id,r.uid,r.school_id,r.class_id,r.class_name,r.name,r.student_id,r.student_name,r.primary_identity,r.sub_identity,b.openid, b.appid';
        $fields .= $withMobile==true? ',u.mobile' : '';

        $tableUnion = self::DB_KINDERGARTEN.'.user_role AS r';
        $tableUnion .= ' LEFT JOIN '.self::DB_UCENTER.'.user_bindinfo AS b ON r.uid=b.uid';
        $tableUnion .= $withMobile==true? ' LEFT JOIN '.self::DB_UCENTER.'.user_info AS u ON r.uid=u.uid' : '';

        $selectRoles = sprintf("SELECT %s FROM %s WHERE r.id=%d AND r.del_flag=1 and b.del_flag=1", $fields, $tableUnion, $roleId);

        $roles = $this->dataBase->select($selectRoles);
        if ($roles) {
            $roles = $this->getUsefullRoles($roles, $schoolAppId);
            $roleInfo[$roleId] = current($roles);

            return $roleInfo[$roleId];
        } else {

            return [];
        }
    }


    //获取角色字典信息
    public function getDicts($dictCode='')
    {
        $dictsCache = Cache::get('dictRroles');
        $dicts = json_decode($dictsCache, true);

        if (empty($dicts))
        {
            $selectSql = "SELECT dict_name,detail_name,detail_content FROM ".self::DB_BASE. ".common_dict_detail WHERE dict_name IN ('parent_sub', 'school_employ_sub', 'school_employ_pri', 'school_te_sub','school_leader_sub')";
            $data = (array) $this->dataBase->select($selectSql);

            foreach ($data as $key => $value)
            {
                $value = (array) $value;
                $dicts[$value['dict_name']][$value['detail_name']] = $value['detail_content'];
            }
            if (!empty($dicts)) {
                Cache::put('dictRroles', json_encode($dicts), 100);
            }
        }

        return $dictCode==''? $dicts : $dicts[$dictCode];
    }

    //获取子身份名称
    function getSubIdentityName($primaryIdentity, $subIdentity){
        $dicts = $this->getDicts();
        $identityName = '';

        if ($primaryIdentity == UcenterConf::USER_PRIMARY_IDENTITY_PARENT)//家长身份
        {
            $identityName = $dicts[DictConf::PARENT_SUB_IDENTITY][$subIdentity];
        }
        elseif ($primaryIdentity == UcenterConf::USER_PRIMARY_IDENTITY_TEACHER) //如果是老师
        {
            $identityName = $dicts[DictConf::TEACHER_SUB_IDENTITY][$subIdentity];
        }
        elseif ($primaryIdentity == UcenterConf::USER_PRIMARY_IDENTITY_HEADMASTER)//园长身份
        {
            $identityName = $dicts[DictConf::DICT_SCHOOL_LEADER_SUB][$subIdentity];
        }
        elseif ($primaryIdentity == UcenterConf::USER_PRIMARY_IDENTITY_EMPLOY)//员工身份
        {
            $identityName = $dicts[DictConf::DICT_SCHOOL_EMPLOY_PRI][$subIdentity];
        }else{

            return $identityName;
        }

        return $identityName;
    }

    //获取主身份名称
    function getPrimaryIdentityName($primaryIdentity){
        $dicts = $this->getDicts();

        $identityName = $dicts[DictConf::DICT_SCHOOL_EMPLOY_PRI][$primaryIdentity];

        return $identityName;
    }


    public function getSenderInfo($role){
        $result = [];

        if (empty($role)) {
            return $result;
        }

        $result['senderName'] = $role['name'];
        $result['senderclassId'] = $role['class_id'];
        $result['senderclassName'] = $role['class_name'];

        $result['senderPrimaryIdentity'] = $role['primary_identity'];
        $result['senderPrimaryIdentityName'] = $this->getPrimaryIdentityName($role['primary_identity']);
        $result['senderSubIdentity'] = $role['sub_identity'];
        $result['senderSubIdentityName'] = $this->getSubIdentityName($role['primary_identity'], $role['sub_identity']);

        if ($role['primary_identity'] == UcenterConf::USER_PRIMARY_IDENTITY_PARENT)//家长身份
        {
            $result['studentId'] = $role['student_id'];
            $result['studentName'] = $role['student_name'];
        }

        return $result;
    }

    //获取接收者信息
    public function getReceiverInfo($role) {
        $result = [];

        if (empty($role)) {
            return $result;
        }

        $result['recieveName'] = $role['name'];
        $result['recieveOpenId'] = $role['openid'];
        $result['recieveClassId'] = $role['class_id'];
        $result['recieveClassName'] = $role['class_name'];
        $result['mobile'] = $role['mobile'];

        $result['recievePrimaryIdentity'] = $role['primary_identity'];
        $result['recievePrimaryIdentityName'] = $this->getPrimaryIdentityName($role['primary_identity']);
        $result['recieveSubIdentity'] = $role['sub_identity'];
        $result['recieveSubIdentityName'] = $this->getSubIdentityName($role['primary_identity'], $role['sub_identity']);

        if ($role['primary_identity'] == UcenterConf::USER_PRIMARY_IDENTITY_PARENT)//家长身份
        {
            $result['studentId'] = $role['student_id'];
            $result['studentName'] = $role['student_name'];
        }

        return $result;
    }

    //拆分后任务直接进入Redis队列不再入库
    public function createBatchSendLog($sendLogs){
        $num = count($sendLogs);

        if ($num>100) {
            $logBlocks = array_chunk($sendLogs, 100);
            foreach ($logBlocks as $key => $log) {
                Log::info(__CLASS__ . '.sendMsgLogServ->createBatchSendMsgLog.param:', $log);
                $sendResult = $this->sendMsgLogServ->createBatchSendMsgLog($log);
                Log::info(__CLASS__ . '.sendMsgLogServ->createBatchSendMsgLog.result:', $log);
            }

            return $sendResult;
        }else{
            Log::info(__CLASS__ . '.sendMsgLogServ->createBatchSendMsgLog.param:', $sendLogs);
            $sendResult = $this->sendMsgLogServ->createBatchSendMsgLog($sendLogs);
            Log::info(__CLASS__ . '.sendMsgLogServ->createBatchSendMsgLog.result:', $sendResult);

            return $sendResult;
        }
    }*/

    //使用redis 锁
    public function getTaskLock($taskLogId) {
        $redisKey = 'TaskLock-'.$taskLogId;
        $code = uniqid();
        if(Cache::add($redisKey, $code, 30)) {
            return $code;
        }
        $cacheCode = Cache::get($redisKey);
        Log::info(__CLASS__.".getTaskLock.lockExist:",['existCode'=>$cacheCode,'taskLogId'=>$taskLogId]);

        return false;
    }

    //释放 redis 锁
    public function freeTaskLock($taskLogId, $code) {

        return true;

        $redisKey = 'TaskLock-'.$taskLogId;
        $cacheCode = Cache::get($redisKey);
        if ($cacheCode == $code) { //避免释放其它冲突中的锁
            $forgetRes = Cache::forget($redisKey);
            if ($forgetRes) {
                return true;
            }else{
                Log::info(__CLASS__.".freeTaskLock.freeFailed:",['runCode'=>$code,'taskLogId'=>$taskLogId]);

                return false;
            }
        }else{ // 记录日志，告诉系统出现了运行时造成的冲突锁，及时调整运行时处理
            Log::info(__CLASS__.".freeTaskLock.freeOtherExist:",['runCode'=>$code,'existCode'=>$cacheCode,'taskLogId'=>$taskLogId]);

            return false;
        }
    }


    /*public function getSigned($momentType,$contentId, $roleId, $studentId, $needSignUp=1){
        if(empty($studentId)) {
            return;
        }
        if ($needSignUp==0) {

            return true;
        }
        if ($momentType == 10 || $momentType== 11) {
            $signed = $this->dataBase->select("select id from ". self::DB_INTERACTION .".result_record where src='dongtai' and third_id=".$contentId." and sign_role_id=".$roleId." and del_flag=1 limit 1");
        }else{
            $signed = $this->dataBase->select("select id from ". self::DB_INTERACTION .".result_record where src='dongtai' and third_id=".$contentId." and sign_student_id=".$studentId." and del_flag=1 limit 1");
        }
        if (empty($signed)) {

            return false;
        }else{

            return true;
        }
    }

    public function getUserAppId($roleInfo, $schoolAppId = ''){
        $stationAppId = WechatConf::getSpaceStationAppId();
        if (isset($roleInfo['id']) && !empty($roleInfo['id'])) {
            if ($roleInfo['primary_identity'] == WechatConf::USER_PRIMARY_IDENTITY_PARENT) {
                return $roleInfo['appid'];
            } else {
                return $stationAppId;
            }
        } else {
            return $schoolAppId;
        }
    }


    public function getUsefullRoles($userRoles, $schoolAppId) {
        $resultRoles = [];
        $ownOfficialAccount = !WechatConf::isYdYd($schoolAppId);

        foreach ($userRoles as $key => $role) {
            $role = (array) $role;
            if ($role['appid']=='' || $role['openid']==''){
                continue;
            }
            if (!isset($resultRoles[$role['id']])) {
                if ($role['primary_identity'] == UcenterConf::USER_PRIMARY_IDENTITY_PARENT) {
                    if (!WechatConf::isYdYd($role['appid']) && $schoolAppId != $role['appid']) {
                        continue;
                    }
                    if (Service::isSpaceStation($role['appid'])) {
                        continue;
                    }

                }else{
                    if (!Service::isSpaceStation($role['appid']) && !WechatConf::isYdYd($role['appid'])&& $schoolAppId != $role['appid']) {
                        continue;
                    }
                }
                $resultRoles[$role['id']] = $role;
            }else{
                if ($role['primary_identity'] == UcenterConf::USER_PRIMARY_IDENTITY_PARENT) {
                    if ($ownOfficialAccount) {
                        if (!WechatConf::isYdYd($resultRoles[$role['id']]['appid'])) {
                            continue;
                        }
                        if ($schoolAppId == $role['appid']) {
                            $resultRoles[$role['id']] = $role;

                            continue;
                        }
                    }else{
                        if (WechatConf::isYdYd($role['appid'])) {
                            $resultRoles[$role['id']] = $role;

                            continue;
                        }
                    }
                } else {
                    if (Service::isSpaceStation($resultRoles[$role['id']]['appid'])) {
                        continue;
                    }else{
                        if (Service::isSpaceStation($role['appid'])) {
                            $resultRoles[$role['id']] = $role;
                            continue;
                        }else{
                            if($schoolAppId == $role['appid']) {
                                $resultRoles[$role['id']] = $role;
                                continue;
                            }
                        }
                    }
                }
            }
        }

        return $resultRoles;
    }


    //获取文章信息
    public function getArticle($articleId) {
        static $articles = [];
        if (!empty($articles[$articleId])){

            return $articles[$articleId];
        }
        $article = $this->dataBase
            ->table(self::DB_CMS.".content_article")
            ->where('id', $articleId)
            ->first();
        if ($article) {
            $data = [];
            $images = json_decode($article['images'],true);

            $data['title'] = $article['title'];
            $data['description'] = strip_tags($article['summary']);
            $data['image'] = $images[0];
            $data['url'] = '/weixin/unlimit/' .$article['id'];

            if(isset($article['sub_module']) && $article['sub_module']=='activity') {
                $data['description'] = '活动通知';
                $data['image'] = $images[0];
            } elseif(isset($article['sub_module']) && $article['sub_module']=='vote') {
                $data['description'] = '投票通知';
                $data['image'] = env('HOST_IMG_FDFS').'/group1/M00/02/CE/rBGtvlt8eqGAdnqtAACq3DGy-3001.jpeg';
            }

            $articles[$articleId] = $data;
        }

        return $articles[$articleId];
    }*/

}

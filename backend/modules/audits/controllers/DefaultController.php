<?php
namespace backend\modules\audits\controllers;

use Yii;
use yii\web\Controller;
use backend\controllers\SiteController;
//服务器模型
use common\models\Servers;
//DML及DDL模型
use common\models\ExecuteLogs;

use common\models\ExecuteLogsArrange;

//用户模型
use common\models\Users;
//查询模型
use common\models\QueryLogs;
//审核模型
use common\models\AuditLogs;
use vendor\twl\tools\utils\Output;

class DefaultController extends SiteController
{

    /**
     * 提交审核列表
     * @return string
     */
    public function actionAudit(){
        @$server_id = rtrim($_REQUEST['server_id']);
        @$database = rtrim($_REQUEST['database']);
        @$pro_id=!empty($_REQUEST['pro_id'])?trim($_REQUEST['pro_id']):0;
        if(empty($server_id) || empty($database))
            Output::error('非法操作！',3);
        //权限检测
        $this->check_server_permission($server_id,$database);
        $request = Yii::$app->request;
        //用户ID
        $user_id = Yii::$app->users->identity->id;
        //提交审核检测
        if ($request->isAjax) {
            $post = $request->post();
            if(isset($post['ids']) && empty($post['ids'])){
                Output::error('无内容提交审核！');
            }
            @$ids=rtrim($post['ids'],',');

            if(!empty($ids)){
                $AuditLogs = new AuditLogs();

                $AuditInfo = $AuditLogs::find()->where(['user_id' => $user_id,'server_id'=>$server_id,'database'=>$database,'pro_id'=>$pro_id])->one();
                if(!empty($AuditInfo)){
                    if($AuditInfo->status == 1){
                        Output::error('您的审核已经提交且审核通过，需要重新审核请联系管理员！');
                    }elseif($AuditInfo->status == 3) {
                        Output::error('管理员已在审核中，暂时无法提交，需要联系管理员审核完毕或重审后提交！');
                    }
                    $AuditLogs = $AuditInfo;
                }

                $AuditLogs->execute_ids = $ids;
                //$AuditLogs->host = $ids;
                $AuditLogs->server_id = $server_id;
                $AuditLogs->database = $database;
                $AuditLogs->update_time = date('Y-m-d H:i:s',time());
                $AuditLogs->status = 0;
                $AuditLogs->user_id = $user_id;
                $AuditLogs->pro_id = $pro_id;
                $result = $AuditLogs->save();

                if($result === true)
                    Output::success('审核提交成功');
                else
                    Output::error('保存失败！');
            }
        }else{
//            $post = $request->post();
//            @$startTime=trim($post['startTime']);
//            @$endTime=trim($post['endTime']);
//            $where = '';
//            $uri = '';
//            if($startTime){
//                $time1 = strtotime($startTime);
//                $where .= " AND (created_date >= '".$startTime."')";
//                $uri .= "&startTime=".$startTime;
//            }
//            if($endTime) {
//                $time2=strtotime($endTime);
//                if($startTime && $time2 < $time1){
//                    Output::error('开始时间不能大于结束时间，请重新选择！',3);
//                }
//                $where.= " AND (created_date <= '".$endTime."')";
//                $uri .= "&endTime=".$endTime;
//            }

            $Excute_logs = new ExecuteLogs();
            $log_list = $Excute_logs::find()->where(['user_id' => $user_id,'status' => 0,'server_id' => $server_id,'database' => $database,'pro_id'=>$pro_id])->orderBy('log_id')->asArray()->all();

            $data['log_list'] = $log_list;
            $data['ids'] = '';
            $data['server_id'] = $server_id;
            $data['database'] = $database;
            $data['pro_id'] = $pro_id;
            return $this->render('audit',$data);
        }
    }


    /**
     * 审核提交
     * @return string
     */
    public function actionAuditlist(){
        @$server_id = rtrim($_REQUEST['server_id']);
        @$database = rtrim($_REQUEST['database']);
        @$pro_id=!empty($_REQUEST['pro_id'])?trim($_REQUEST['pro_id']):0;
        if(empty($server_id) || empty($database))
            Output::error('非法操作！');
        //权限检测
        $this->check_server_permission($server_id,$database);
        $request = Yii::$app->request;
        //提交审核检测
        if ($request->isAjax) {
            @$action = rtrim($_REQUEST['action']);
            $AuditLogs = new AuditLogs();
            $AuditList = $AuditLogs::find()->where(['server_id' => $server_id,'database' => $database,'pro_id'=>$pro_id])->all();
            if(!empty($AuditList)){
                $result = $AuditLogs->updateAll(['status'=>$action],['server_id' => $server_id,'database' => $database,'pro_id'=>$pro_id]);
            }else{
                Output::error('无数据！');
            }

            if($action == 1){
                $alert = '审核通过成功!';
            }elseif($action == 0){
                $alert = '审核重置或解锁成功!';
            }elseif($action == 3){
                $alert = '锁定成功!';
            }

            if($result !== false)
                Output::success($alert);
            else
                Output::error('操作失败！');
        }else{
            $AuditLogs = new AuditLogs();
            $AuditList = $AuditLogs::find()->where(['server_id'=>$server_id,'database'=>$database,'pro_id'=>$pro_id])->asArray()->all();
            $ids = '';
            $is_check = 0;
            $is_lock = -1;
            foreach($AuditList as $row){
                $ids .= $row['execute_ids'] . ',';
                if ($row['status'] == 1 && $is_check == 0) {
                    $is_check = 1;
                }elseif($row['status'] == 0 && $is_lock == -1){
                    $is_lock = 0;
                }elseif($row['status'] == 3 && $is_lock == -1){
                    $is_lock = 1;
                }
            }

            $ids=rtrim($ids,',');
            $where = "`log_id` in ($ids)";

            if(!empty($ids)) {
                $ExcuteLogs = new ExecuteLogs();
                $log_list = $ExcuteLogs::find()->select('execute_logs.log_id,execute_logs.notes,execute_logs.script,users.username,execute_logs.created_date')->leftJoin('users', 'execute_logs.user_id = users.id')->where($where)->asArray()->all();
                $data['log_list'] = $log_list;
            }else{
                $data['log_list'] = array();
            }
            $data['is_check'] = $is_check;
            $data['is_lock'] = $is_lock;
            $data['server_id'] = $server_id;
            $data['database'] = $database;
            $data['pro_id'] = $pro_id;
            return $this->render('auditlist',$data);
        }
    }


    /**
     * sql整理
     */
    public function actionArrangeAudit(){
        //获取操作类型
        @$action = rtrim($_REQUEST['action']);
        //获取log_id
        @$log_id = rtrim($_REQUEST['log_id']);

        $ExcuteLogs = new ExecuteLogs();
        $log_info = $ExcuteLogs::find()->where(['log_id' => $log_id])->asArray()->one();
        if(!empty($log_info)) {
//            $ExecuteLogsArrange = new ExecuteLogsArrange();
            $ExecuteLogsArrange = ExecuteLogsArrange::findOne($log_info['log_id']);
            if(empty($ExecuteLogsArrange))
                $ExecuteLogsArrange = new ExecuteLogsArrange();
            switch ($action) {
                //执行sql
                case 1:
                    $servers = new Servers();
                    $server_info = $servers::find()->where(['server_id' => $log_info['server_id']])->asArray()->one();
                    if (empty($server_info['mirror_ip']))
                        Output::error('该log对应基准数据库不存在！');
                    //组合数据库配置
                    $connect_config['dsn'] = "mysql:host=" . $server_info['mirror_ip'] . ";dbname=" . $log_info['database'];
//                    $connect_config['username'] = Yii::$app->params['MARKET_USER'];
//                    $connect_config['password'] = Yii::$app->params['MARKET_PASSWD'];
                    $connect_config['username'] = 'root';
                    $connect_config['password'] = 'root';
                    $sqlinfo = rtrim($_REQUEST['script']);
                    try {
                        //数据库连接对象
                        $mirrorConnection = new \yii\db\Connection((Object)$connect_config);
                        $command = $mirrorConnection->createCommand($sqlinfo);
                        $excute_result = $command->execute();
                    } catch (\Exception $e) {
                        Output::error("数据库操作失败: 请检查sql语句:\r\n" . $e->getMessage());
                    }
                    $ExecuteLogsArrange->status = 1;
                    break;
                // 删除SQL
                case 2:
                    $ExecuteLogsArrange->status = 2;
                    break;
                //撤销删除
                case 3:
                    $result = $ExecuteLogsArrange->delete();
                    //var_dump($ExecuteLogsArrange);
                    $r = clone $ExecuteLogsArrange;
                    var_dump($r->createCommand()->getRawSql());
                    //var_dump($result);
                    break;
            }

            if(1 == $action || 2 == $action) {
                $ExecuteLogsArrange->log_id = $log_info['log_id'];
                $ExecuteLogsArrange->user_id = $log_info['user_id'];
                $ExecuteLogsArrange->host = $log_info['host'];
                $ExecuteLogsArrange->database = $log_info['database'];
                $ExecuteLogsArrange->script = $log_info['script'];
                $ExecuteLogsArrange->created_date = $log_info['created_date'];
                $ExecuteLogsArrange->notes = $log_info['notes'];
                $ExecuteLogsArrange->server_id = $log_info['server_id'];
                $result = $ExecuteLogsArrange->save();
            }
//            var_dump($ExecuteLogsArrange->getErrors());
//            if($ExecuteLogsArrange->errors)
//                var_dump($ExecuteLogsArrange->getErrors());
        }else{
            Output::error('无数据！');
        }

        if(1 == $action){
            $alert = '执行成功!';
        }elseif(2 == $action){
            $alert = '删除成功!';
        }elseif(3 == $action){
            $alert = '撤销删除成功!';
        }

        if($result !== false)
            Output::success($alert);
        else
            Output::error('操作失败！');
    }
}
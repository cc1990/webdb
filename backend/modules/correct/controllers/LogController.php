<?php 
namespace backend\modules\correct\controllers;

use Yii;
use backend\controllers\SiteController;

use backend\modules\correct\models\Log;
use backend\modules\correct\models\LogInfo;
use backend\modules\correct\models\LogSearch;
use backend\modules\correct\models\SelfHelp;

use vendor\twl\tools\utils\Output;
/**
* 自助订正日志管理
*/
class LogController extends SiteController
{
    public function actionIndex()
    {
        $searchModel = new LogSearch();

        $data = SelfHelp::find()->select(['status'])->asArray()->one();
        $status = $data['status'] == '1' ? 'on' : 'off';
        $dataProvider = $searchModel->search( Yii::$app->request->queryParams );
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'allowSelfhelp' => $status
        ]);
    }

    public function actionCreate()
    {
        $model = new Log();
        if( $model->load( Yii::$app->request->post() ) && $model->validate() && $model->save() ){
            $log_id = $model->log_id;
            $server_ip = $_POST['server_ip'];
            foreach ($server_ip as $key => $value) {
                $log_info = new LogInfo();
                $log_info->log_id = $log_id;
                $log_info->server_ip = $value;
                $log_info->db_name = $_POST['db_name'][$key];
                $log_info->scripts_number = $_POST['scripts_number'][$key];
                $log_info->influences_number = $_POST['influences_number'][$key];
                if( !empty( rtrim( $value ) ) ){
                    $log_info->insert();
                }
            }
            return $this->redirect('index');
        }else{
            return $this->render('create', ['model' => $model]);
        }
    }

    public function actionView( $log_id ){
        $data = LogInfo::find()->where(['log_id' => $log_id])->asArray()->all();
        return $this->renderAjax("view", ['data' => $data]);
    }

    public function actionUpdate( $log_id )
    {
        $log_data = Log::findOne( $log_id );
        if( empty( $log_data->workorder_no ) ){
            echo "该工单不存在！"; exit;
        }

        $request = Yii::$app->request;

        if( $request->isPost ){
            $post = $request->post();
            $log_id = $_POST['log_id'];
            $db_names = array();
            $scripts_number = $influences_number = 0;

            LogInfo::deleteAll(['log_id' => $log_id]);
            $result = true;
            $model = new LogInfo();
            if( !empty( $post['db_name'] ) ){
                foreach ( $post['db_name'] as $key => $db) {
                    if( !empty(rtrim( $db )) ){
                        $model->isNewRecord = true;
                        $model->log_id = $log_id;
                        $model->server_ip = $_POST['server_ip'][$key];
                        $model->db_name = $_POST['db_name'][$key];
                        $model->scripts_number = $_POST['scripts_number'][$key];
                        $model->influences_number = $_POST['influences_number'][$key];
                        if( $model->validate() && $model->insert() ){
                            $result = true;
                        }else{
                            $result = false;
                            break;
                        }
                        $db_names[] = $db;
                        $scripts_number += $_POST['scripts_number'][$key];
                        $influences_number += $_POST['influences_number'][$key];
                    }
                    
                }
            }

            $log_data->module_name = $_POST['module_name'];
            $log_data->work_line = $_POST['work_line'];
            $log_data->db_names = implode(",", $db_names);
            $log_data->script_number = $scripts_number;
            $log_data->influences_number = $influences_number;
            $log_data->use_time = $post['use_time'];
            $log_data->remark = $post['remark'];
            $log_data->save();

            if( $result == false ){
                Output::error('脚本数量和影响行数值只能为整数');
            }else{
                Output::success("保存成功");
            }
        }else{
            $data = LogInfo::find()->where(['log_id' => $log_id])->asArray()->all();
            return $this->renderAjax("update", ['data' => $data, 'log_id' => $log_id, 'log_data' => $log_data]);
        }
    }

    public function actionDelete( $id )
    {

    }

    public function actionLogview()
    {
        $sql_database = "select cll.db_name,
            sum(if(workorder_end_time between concat(date_format(now(),'%Y-%m'),'-01 00:00:00') and concat(date_format(now() + interval 1 month,'%Y-%m'),'-01') - interval 1 second, 1, 0 )) as sumary1, 
            sum(if(workorder_end_time between concat(date_format(now() - interval 1 month,'%Y-%m'), '-01 00:00:00') and concat(date_format(now(),'%Y-%m'),'-01') - interval 1 second, 1, 0 )) as sumary2, 
            sum(if(workorder_end_time between concat(date_format(now() - interval 2 month,'%Y-%m'), '-01 00:00:00') and concat(date_format(now() - interval 1 month,'%Y-%m'),'-01') - interval 1 second, 1, 0 )) as sumary3
            from correct_logs cl join correct_logs_info cll on cl.log_id=cll.log_id 
            group by cll.db_name;";

        $sql_workline = "select cl.work_line,
            sum(if(workorder_end_time between concat(date_format(now(),'%Y-%m'),'-01 00:00:00') and concat(date_format(now() + interval 1 month,'%Y-%m'),'-01') - interval 1 second, 1, 0 )) as sumary1, 
            sum(if(workorder_end_time between concat(date_format(now() - interval 1 month,'%Y-%m'), '-01 00:00:00') and concat(date_format(now(),'%Y-%m'),'-01') - interval 1 second, 1, 0 )) as sumary2,
            sum(if(workorder_end_time between concat(date_format(now() - interval 2 month,'%Y-%m'), '-01 00:00:00') and concat(date_format(now() - interval 1 month,'%Y-%m'),'-01') - interval 1 second, 1, 0 )) as sumary3
            from correct_logs cl join correct_logs_info cll on cl.log_id=cll.log_id 
            group by cl.work_line;";

        $sql_scripts_number = "select 
            date_format(cl.workorder_end_time,'%Y-%m') monthday,
            sum(if(cll.scripts_number>=10,1,0)) gt10,
            sum(if(cll.scripts_number>=100,1,0)) gt100,
            sum(if(cll.scripts_number>=1000,1,0)) gt1000,
            sum(if(cll.scripts_number>=3000,1,0)) gt3000,
            sum(if(cll.scripts_number>=5000,1,0)) gt5000,
            sum(if(cll.scripts_number>=10000,1,0)) gt10000
            from correct_logs cl join correct_logs_info cll on cl.log_id=cll.log_id 
            group by monthday desc;";

        $sql_influences_number = "select 
            date_format(cl.workorder_end_time,'%Y-%m') monthday,
            sum(if(cll.influences_number>=5000,1,0)) gt5k,
            sum(if(cll.influences_number>=10000,1,0)) gt10k,
            sum(if(cll.influences_number>=50000,1,0)) gt50k,
            sum(if(cll.influences_number>=100000,1,0)) gt100k,
            sum(if(cll.influences_number>=500000,1,0)) gt500k,
            sum(if(cll.influences_number>=1000000,1,0)) gt1000k,
            sum(if(cll.influences_number>=5000000,1,0)) gt5000k
            from correct_logs cl join correct_logs_info cll on cl.log_id=cll.log_id 
            group by monthday desc;";

        $connection = Yii::$app->db;

        $data['database'] = $connection->createCommand($sql_database)->queryAll();
        $data['workline'] = $connection->createCommand($sql_workline)->queryAll();
        $data['scripts'] = $connection->createCommand($sql_scripts_number)->queryAll();
        $data['influences'] = $connection->createCommand($sql_influences_number)->queryAll();
        //var_dump($data);exit;

        return $this->render('logview', $data);
    }

    public function actionAllowSelfhelp(){
        $status = $_GET['status'];
        $data = SelfHelp::find()->select(['status'])->asArray()->one();
        if( $status == $data['status'] ){
            $model = SelfHelp::find()->where(['status' => $status])->one();
            $model->status = ( $data['status'] == '1' ) ? '0' : '1';
            $model->update();
            Output::success("修改成功");
        }else{
            Output::error("当前状态和存储状态不一致！");
        }
    }
}
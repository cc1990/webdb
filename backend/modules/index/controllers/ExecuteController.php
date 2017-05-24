<?php 
namespace backend\modules\index\controllers;

use Yii;

use backend\controllers\SiteController;

use backend\modules\projects\models\Projects;
use backend\modules\projects\models\ProjectsStatusLogs;
use backend\modules\users\models\Users;

use common\models\Servers;
use common\models\ExecuteLogs;
use common\models\ExecuteDeploymentLogs;

use vendor\twl\tools\utils\Output;

/**
* 
*/
class ExecuteController extends SiteController
{
    public function actionIndex()
    {
        //获取服务器列表
        $Servers = new servers();
        $server_list = $Servers::find()->where(['status' => 1])->orderBy('server_id')->asArray()->all();
        $default_host = \Yii::$app->session->get('default_host');
        if(!empty($default_host)){
            foreach($server_list as $key=>$val){
                if($default_host != $val['ip'])
                    unset($server_list[$key]);
            }
            foreach($this->servers['privilege'] as $key=>$val){
                if($default_host != $key){
                    unset($this->servers['privilege'][$key]);
                }
            }
        }

        //传给视图
        $data['server_list'] = $server_list;
        $data['to_server_list'] = $this->getServerList();
        $data['server_ids'] = $this->servers['server_ids'];
        $data['db_name_array'] = json_encode($this->servers['db_name_array']);

        $user_name = Yii::$app->users->identity->username;
        $project_list = Projects::find()->select(['pro_id', 'name'])->where(['status' => 1, 'owner' => $user_name])->asArray()->all();

        $data['project_list'] = $project_list;

        return $this->render('index', $data);
    }


    public function actionGetScriptList()
    {
        @$project_id = rtrim($_REQUEST['Project']);
        @$server_id = rtrim($_REQUEST['server_id']);
        @$database = $_REQUEST['database'];
        @$action = rtrim($_REQUEST['action']);
        @$start_time = rtrim($_REQUEST['start_time']);
        @$end_time = rtrim($_REQUEST['end_time']);
        @$type = rtrim($_REQUEST['type']);

        $user_id = Yii::$app->users->identity->id;

        $logs = new ExecuteLogs;

        $user_id = Yii::$app->users->identity->id;
        $result = array();
        
        if( empty( $project_id ) ){
            Output::error("项目必选！");
        }
        if( empty( $server_id ) ){
            Output::error("环境必选！");
        }

        if( $action == '2' ){
            $action_type = 'DML';
        }elseif( $action == '3' ){
            $action_type = 'DDL';
        }

        $request = $logs::find()->select(['log_id','script','notes', 'host', 'database', 'pro_id', 'user_id', 'created_date'])->where(['server_id' => $server_id, 'pro_id' => $project_id]);

        //$database = implode(",", $database);
        if( !empty( $database ) ){
            $request->andWhere(['in', 'database', $database]);
        }else{
            Output::error("请选择数据库！");
        }
        if( $type == 1 || $type == 2 ){ //正式数据、历史数据
            $request->andWhere(['user_id' => $user_id]);

            if( $action != '1' ){
                $request->andWhere(['sqloperation' => $action_type]);
            }
            if( !empty($start_time) ){
                $request->andWhere(['>=', 'created_date', $start_time]);
            }
            if( !empty($end_time) ){
                $request->andWhere(['<=', 'created_date', $end_time]);
            }
            $is_formal = ($type == 1) ? 1 : 0;
            $request->andWhere(['is_formal' => "$is_formal"]);

        }else if( $type == 3 ){
            $request->andWhere(['is_formal' => '1']);
        }else{
            Output::error("非法请求！");
        }
        //var_dump($request);exit;
        $result = $request->asArray()->all();

        foreach ($result as $key => $value) {
            $pro_id = $value['pro_id'];
            $user_id = $value['user_id'];
            $project = Projects::find()->select(['name'])->where(['pro_id' => $pro_id])->asArray()->one();
            $user = Users::find()->select(['username', 'chinesename'])->where(['id' => $user_id])->asArray()->one();
            $result[$key]['project_name'] = $project['name'];
            $result[$key]['username'] = $user['chinesename'] ? $user['chinesename']: $user['username'];
        }

        echo json_encode(array('code' => 1, 'content' => $result, 'database' => implode(",", $database)));exit;

        //Output::success("获取脚本列表成功", $result);
    }

    public function actionChangeScriptStatus()
    {
        $user_id = Yii::$app->users->identity->id;
        $log_id = intval($_GET['log_id']);
        if( $log_id < 1 ){
            Output::error("非法脚本日志ID！");
        }

        $model = ExecuteLogs::findOne( ['user_id' => $user_id, 'log_id' => $log_id] );
        $logs_data = $model;
        if( empty( $logs_data ) ){
            Output::error("未找到对应的脚本日志！");
        }

        $is_formal = $logs_data['is_formal'];

        $model->is_formal = ( $is_formal == '1' ) ? '0' : '1';
        $model->save();
        Output::success("变更脚本日志状态成功");
    }

    /**
     * 页面获取服务器环境的项目
     * @return [type] [description]
     */
    public function actionGetProjects()
    {
        $server_id = rtrim($_REQUEST['server_id']);
        
        $list = $this->getProjects( $server_id );

        Output::success("获取项目列表成功", $list['list']);
    }

    public function actionExecute()
    {
        @$from_project = rtrim($_REQUEST['from_project']);
        @$from_server_id = rtrim($_REQUEST['from_server_id']);
        @$from_database = $_REQUEST['from_database'];
        @$to_project = rtrim($_REQUEST['to_project']);
        @$to_server_id = rtrim($_REQUEST['to_server_id']);

        if( empty( $from_project ) || empty( $from_server_id ) || empty( $from_database ) || empty( $to_project ) || empty( $to_server_id ) ){
            Output::error("缺少参数");
        }

        if( $from_server_id == $to_server_id ){
            Output::error("来源服务器和目标服务器不能相同");
        }

        $list = $this->getProjects( $from_server_id );
        if( empty( $list['list_'][$from_project] ) ){
            Output::error("来源项目不在服务器环境中");
        }

        if( substr( $from_database, -1, 1 ) == ',' ){
            $from_database = substr( $from_database, 0, -1 );
        }

        $from_database_ = explode(",", $from_database);

        $request = ExecuteLogs::find()->select(['log_id','script','notes', 'host', 'database', 'pro_id', 'user_id', 'created_date'])->where(['server_id' => $from_server_id, 'pro_id' => $from_project, 'is_formal' => '1']);

        $request->andWhere(['in', 'database', $from_database_]);

        $result = $request->asArray()->all();
        if( empty( $result ) ){
            Output::error("正式脚本为空！");
        }

        $to_server_ip = $this->getServerIp( $to_server_id );
        $to_project_name = $this->getProjectName( $to_project );
        $environment = $this->getServerEnvironment( $to_server_id );
        
        //获取执行过的脚本
        $from_logs = $this->getHistoryScripts( $from_server_id, $from_project, $to_server_id, $to_project );

        $db_name = "";
        foreach ($result as $key => $value) {
            $log_id = $value['log_id'];
            if( in_array( $log_id, $from_logs ) ){ //如果已存在执行过的脚本记录，则跳过
                continue;
            }

            if( $db_name == "" || $db_name != $value['database'] ){
                $executeConnection = $this->connectDb( $to_server_ip, $value['database'] );
            }

            $db_name = $value['database'];
            $sql = $value['script'];
            try {
                $command = $executeConnection->createCommand($sql);
                $excute_result = $command->execute();
                $excute_num = $excute_result > 0 ? $excute_result:0;
            } catch (\Exception $e) {
                echo json_encode(array( 'code' => 2, 'msg' => $e->getMessage(), 'log_id' => $log_id ));exit;
            }


            if (preg_match(Yii::$app->params['regexp']['dml_ddl_sql'],$sql)) {
                $excute_result = 0;
                $sqlstatus = 0;
                $sqlresult = '执行成功';
            } else {
                $rows = count($excute_result);
                if($rows == 0){
                    $sqlstatus = 1;
                    $sqlresult = '没有记录.';
                } else {
                    $sqlstatus = 0;
                    $sqlresult = '记录条数为:' . $rows . '行.';
                }
            }

            $SaveSQL = new ExecuteLogs;
            $SaveSQL->notes = $value['notes'];

            $action = strtolower(strtok( rtrim($sql), ' ' ));
            if ( $action == 'insert' || $action == 'update' || $action == 'delete' ) {
                $SaveSQL->sqloperation = 'dml';
            } else if( $action == 'create' || $action == 'alter' || $action == 'drop' || $action == 'truncate' ) {
                $SaveSQL->sqloperation = 'ddl';
            }

            $SaveSQL->is_formal = 1; //是否是正式脚本
            $SaveSQL->user_id = Yii::$app->users->identity->id;
            $SaveSQL->host = $to_server_ip;
            $SaveSQL->database = $db_name;
            $SaveSQL->script = $sql;
            $SaveSQL->result = $sqlresult;
            $SaveSQL->status = $sqlstatus;
            $SaveSQL->pro_id = $to_project;
            $SaveSQL->project_name = $to_project_name;
            $SaveSQL->server_id = $to_server_id;
            $SaveSQL->environment = $environment ? $environment : 'dev';
            $result = $SaveSQL->save();

            if( $result !== true ){
                echo json_encode(array( 'code' => 2, 'msg' => '写入执行脚本日志失败', 'log_id' => $log_id ));exit;
            }
            $new_log_id = $SaveSQL->getPrimaryKey();

            $this->setExecuteDeploymentLog( $log_id, $new_log_id, $from_server_id, $from_project, $to_server_id, $to_project );

        }
        Output::success("一键执行成功！");
    }

    /**
     * 插入执行脚本部署的日志
     * @param [type] $from_log_id    [description]
     * @param [type] $new_log_id     [description]
     * @param [type] $from_server_id [description]
     * @param [type] $from_pro_id    [description]
     * @param [type] $to_server_id   [description]
     * @param [type] $to_pro_id      [description]
     * @param string $action         [description]
     */
    public function setExecuteDeploymentLog( $from_log_id, $new_log_id, $from_server_id, $from_pro_id, $to_server_id, $to_pro_id, $action = 'execute' )
    {
        $execute_model = new ExecuteDeploymentLogs();
        $execute_model->from_log_id = $from_log_id;
        $execute_model->new_log_id = $new_log_id;
        $execute_model->from_server_id = $from_server_id;
        $execute_model->from_pro_id = $from_pro_id;
        $execute_model->to_server_id = $to_server_id;
        $execute_model->to_pro_id = $to_pro_id;
        $execute_model->action = $action;
        $execute_model->username = Yii::$app->users->identity->username;

        $execute_model->save();
    }

    /**
     * 获取项目列表
     * @param  [type] $server_id [description]
     * @return [type]            [description]
     */
    public function getProjects( $server_id )
    {
        $server_data = Servers::findOne($server_id);
        if( empty( $server_data ) ){
            Output::error("服务器ID不能为空");
        }

        $environment = $server_data['environment'];
        if( empty( $environment ) ){
            Output::error("该服务器未标记环境");
        }

        @$data = ProjectsStatusLogs::find()->select(['projects.pro_id', 'projects.name'])->joinWith(['projects'])->where(['environment' => $environment])->groupBy(['projects_status_logs.pro_id'])->asArray()->all();
        
        $list_data = $list = $list_ = array();
        if( !empty( $data ) ){
            foreach ($data as $key => $value) {
                $list[$key]['pro_id'] = $value['pro_id'];
                $list[$key]['name'] = $value['name'];

                $list_[$value['pro_id']] = $value['name'];
            }
        }
        $list_data['list'] = $list;
        $list_data['list_'] = $list_;

        return $list_data;
    }


    /**
     * 获取非线上和预发的服务器
     * @return [type] [description]
     */
    public function getServerList()
    {
        return Servers::find()->select(['server_id', 'ip', 'name'])->where(['status' => 1])->andWhere(['!=', 'environment', 'pre'])->andWhere(['!=', 'environment', 'pro'])->asArray()->all();
    }

    /**
     * 获取执行的脚本日志
     * @param  [type] $from_server_id [description]
     * @param  [type] $from_pro_id    [description]
     * @param  [type] $to_server_id   [description]
     * @param  [type] $to_pro_id      [description]
     * @return [type]                 [description]
     */
    public function getHistoryScripts( $from_server_id, $from_pro_id, $to_server_id, $to_pro_id )
    {
        return ExecuteDeploymentLogs::find()->select(['from_log_id'])->where(['from_server_id' => $from_pro_id, 'from_pro_id' => $from_pro_id, 'to_server_id' => $to_server_id, 'to_pro_id' => $to_pro_id, 'action' => 'execute'])->asArray()->all();
    }

    /**
     * 根据服务器ID获取服务器IP
     * @param  [type] $server_id [description]
     * @return [type]            [description]
     */
    public function getServerIp( $server_id )
    {
        $data = Servers::findOne( $server_id );
        return $data['ip'];
    }

    /**
     * 根据服务器ID获取服务器环境
     * @param  [type] $server_id [description]
     * @return [type]            [description]
     */
    public function getServerEnvironment( $server_id )
    {
        $data = Servers::findOne( $server_id );
        return $data['environment'];
    }


    /**
     * 获取项目名称
     * @param  [type] $to_project [description]
     * @return [type]             [description]
     */
    public function getProjectName( $to_project )
    {
        $data = Projects::findOne( $to_project );
        return $data['name'];
    }

    /**
     * 链接数据库
     * @param  [String] $server_ip [数据库IP]
     * @param  [String] $db_name   [数据库名]
     * @return [type]            [description]
     */
    private function connectDb( $server_ip, $db_name ){
        //组合数据库配置
        $connect_config['dsn'] = "mysql:host=$server_ip;dbname=$db_name";
        $connect_config['username'] = Yii::$app->params['MARKET_USER'];
        $connect_config['password'] = Yii::$app->params['MARKET_PASSWD'];
        $connect_config['charset'] = Yii::$app->params['MARKET_CHARSET'];
        //数据库连接对象
        $executeConnection = new \yii\db\Connection((Object)$connect_config);
        return $executeConnection;
    }
}
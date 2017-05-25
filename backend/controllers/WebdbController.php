<?php 
/**
 * Copyright 2016 webdb.qccr.com
 */
namespace backend\controllers;

use Yii;
use yii\web\Controller;

use common\models\QueryLogs;
use common\models\ExecuteLogs;
use common\models\Users;
use common\models\ApiUsersConfig;

use backend\modules\projects\models\Projects;
use backend\modules\projects\models\ProjectsStatusLogs;

use backend\modules\correct\models\Log;
use backend\modules\correct\models\LogInfo;

use backend\server\DbServer;
use backend\server\RedisServerWeb;

use yii\httpclient\Client;
use yii\base\Exception;

/**
* webdb数据库管理工具快速执行SQL的接口
* @author Cc
* @version V2.0
*/
class WebdbController extends Controller
{
    const SHARDING_SERVICE = 'http://dbservice.cc1990.com';//分库分表接口地址
    const CRYW_API = 'http://cryw.cc1990.com/releasemanage/api/'; //运维平台API接口地址
    const SUPERCONFIG = 'http://superconfig.cc1990.com/appConfigDetail/queryOnlineProject.json'; //超人配置中心请求应用对应的实际环境配置

    public function init()
    {
        error_reporting(0);
        header("Content-type:text/html;charset=utf-8");
        $this->enableCsrfValidation = false;
    }

    public function actionIndex()
    {
        $code = 110;
        $this->echoOut($code, $this->codeToMsg()["$code"]);
    }

    public function actionGetDbInfo()
    {
        @$project = $_REQUEST['project'];
        @$environment = $_REQUEST['environment'];
        @$db_name = $_REQUEST['db_name'];
        @$tb_name = $_REQUEST['tb_name'];

        if( REDIS_STATUS == 0 ){ //如果redis服务器正常
            $redis = new RedisServerWeb();
        }else{ //否则调用本地缓存
            $redis = new DbServer();
        }

        if( empty( $environment ) || empty( $project ) ){ //如果环境为空
            $code = 110;
            $this->echoOut($code, $this->codeToMsg()["$code"]);
        }

        //根据项目、环境获取对应的数据库服务器IP
        $request = $this->getServerIp( $project, $environment );

        if( is_array( $request ) ){
            $this->echoOut($request['code'], $request['msg']);
        }else{
            $server_ip = $request;
            if( empty( $server_ip ) ){ //获取项目环境的数据库配置信息为空
                $this->echoOut(303, $this->codeToMsg()["303"]);
            }
        }

        $data = '';
        if( empty( $db_name ) ){ //如果库名为空，则显示全部库名
            $data = $redis->hmget( $server_ip, "databases" );
        }elseif( !empty( $db_name ) && empty( $tb_name ) ){  //如果库名不为空，表名为空，则返回该库下的全部表名
            if( in_array( $db_name, Yii::$app->params['sharding_database'] ) ){
                /*$sharding_project = $this->getShardingProject( $environment, $db_name );
                if( isset( $sharding_project['error'] ) ){
                    $this->echoOut( 900, $sharding_project['error'] );
                }

                $project = $sharding_project['project'];*/
                $url = self::SHARDING_SERVICE . "/db/rule.json?projectName={$project}&environment={$environment}&database={$db_name}";
                $request = $this->http_get( $url);

                $data_array = [];

                $content = json_decode( $request );
                if( $content->success != true ){
                    $this->echoOut( 900, "获取分库分表列表信息失败！" );
                }
                $content_data = json_decode( $content->data );

                if( !empty( $content_data ) ){
                    $tbInfoList = $content_data->tbInfoList; // 表信息
                    $tbName = $tbInfoList[0]->tbName;//主表
                    $tbName_array = explode("#", $tbName);
                    $data_array[] = $tbName_array[0];

                    $detailTBName = $tbInfoList[0]->detailTBName;
                    foreach (explode(",", $detailTBName) as $key => $value) {
                        $value_ = explode("#", $value);
                        $data_array[] = $value_[0];
                    }
                }
                $data = implode(",", $data_array) . ",";
            }
            $data .= $redis->hmget( $server_ip, $db_name, "tables" );
        }elseif( !empty( $db_name ) && !empty( $tb_name ) ){  //如果库名不为空，表名不为空，则返回该库该表下的全部字段

            if( in_array( $db_name, Yii::$app->params['sharding_database'] ) ){
                $sql = "select * from {$tb_name} limit 1";
                $execute_request = $this->execute_sharding( $environment, $db_name, $project, $sql );

                if( $execute_request['code'] == 0 ){
                    $list = $execute_request['data']->list;
                    if( count( $list ) > 1 ){
                        $data = implode(",", $list[0]);
                    }
                }else{
                    $this->echoOut( 900, $execute_request['msg'] );
                }
            }else{
                $data = $redis->hmget( $server_ip, $db_name, $tb_name );
            }
            
        }else{  //否则非法请求
            $code = 401;
            $this->echoOut($code, $this->codeToMsg()["$code"]);
        }

        if( !empty( $data ) ){
            $value_array = explode(",", $data);
            foreach ( $value_array as $key => $value) {
                if(preg_match('/[ordercenter_|membercenter_]{1}[0-9]+$/i',$value)){
                    unset($value_array[$key]);
                }
            }
            $data = implode(",", $value_array);
        }

        $this->echoOut(0, $this->codeToMsg()["0"], $data);
    }

    /**
     * 获取分库分表中该环境下使用该库的项目
     * @param  [type] $environment [description]
     * @param  [type] $db_name     [description]
     * @return [type]              [description]
     */
    public function getShardingProject( $environment, $db_name ){
        $url = self::SHARDING_SERVICE . "/db/list.json";
        $request = $this->http_get( $url);

        $info = [];

        $content = json_decode( $request );
        if( $content->success != true ){
            $info['error'] = "获取分库分表列表信息失败！";
            return $info;
        }
        $content_data = $content->data;

        if( !empty( $content_data ) && is_array( $content_data ) ){

            foreach ($content_data as $key => $value) {
                if( $environment == $value->environment && in_array( $db_name, $value->database ) ){
                    $info['project'] = $value->projectName;
                    return $info;
                }
            }
        }
        $info['error'] = "未获取到该环境下使用{$db_name}数据库的项目！";
        return $info;
    }

    /**
     * 修改项目状态
     * 运维平台在变更项目状态时调用此接口，从而变更项目状态信息
     * @return [type] [description]
     */
    public function actionChangeProjectStatus()
    {
        $pro_id = trim( $_REQUEST['id'] );
        $project_name = trim( $_REQUEST['project_name'] );
        $status = trim( $_REQUEST['status'] );
        $project_owner = trim( $_REQUEST['project_owner'] );

        if( empty( $pro_id ) || empty( $project_name ) || empty( $status ) || empty( $project_owner ) ){
            $code = 110;
            $this->echoOut($code, $this->codeToMsg()["$code"]);
        }

        $environment =Yii::$app->params['cryw_project_status']["$status"];

        $projects_status = ProjectsStatusLogs::find()->where(['pro_id' => $pro_id, 'environment' => $environment])->asArray()->one();
        
        if( empty( $projects_status ) ){
            $projects_status_model = new ProjectsStatusLogs();
            $projects_status_model->pro_id = $pro_id;
            $projects_status_model->environment = $environment ? $environment : '';
            $projects_status_model->owner = $project_owner;
            $projects_status_model->insert();
        }
        
        
        $projects = Projects::findOne($pro_id);
        if( empty( $projects ) ){
            $projects = new Projects();
            $projects->pro_id = $pro_id;
            $projects->name = $project_name;
        }
        $projects->owner = $project_owner;
        $projects->save();

        $code = 0;
        $this->echoOut($code, $this->codeToMsg()["$code"]);

    }
    
    /**
     * 快速执行SQL接口
     * @return [type] [description]
     */
    public function actionExecute()
    {

        $environment = trim( $_REQUEST['environment'] );//环境
        $db_name = trim( $_REQUEST['database'] ); //数据库名
        $project = trim( $_REQUEST['project'] ); //项目名称
        $sql = trim( $_REQUEST['sql'] ); //SQL 语句
        $note = trim( $_REQUEST['note'] ); //SQL注释
        $md5_key = trim( $_REQUEST['md5_key'] ); //SQL注释
//        $environment = "dev";//环境
//        $db_name = "market"; //数据库名
//        $project = "P_6461"; //项目名称
//        $sql = "select name,temp_id,instruction,use_type,command_type,total,amount,status,channel_id,create_person,update_person,platform,single_account_num,single_device_num,type,cycle from redbag where id='12' limit 10;"; //SQL 语句
//        $note = "123123123123123"; //SQL注释
//        $md5_key = "19fe5fbc09688a8316f40bee9e63c8e4"; //SQL注释

        $start_time = explode(' ',microtime());

        //检查是否缺少参数或参数不正确
        $code = $this->checkParam( $environment, $db_name, $project, $sql, $note, $md5_key );
        if( $code != 0 ){
            $data['code'] = $code;
            $this->echoOut($code, $this->codeToMsg()["$code"]);
        }

        //验证来源请求是否合法
        $result = $this->verifyIp( $md5_key );
        if( $result === false ){
            $code = 401;
            $this->echoOut($code, $this->codeToMsg()["$code"]);
        }else{
            $user_id = $result;
        }

        //判断是否是DML类型SQL语句，当前只支持DML类型SQL
        if( !$this->sqlIsDml( $sql ) ){
            $this->echoOut(204, $this->codeToMsg()["204"]);
        }

        //验证SQL合法性
        $sql_verify = $this->sqlVerify( $db_name, $sql );
        if( $sql_verify !== true ){
            $msg = $this->codeToMsg()["202"] . "：" . $sql_verify;
            $this->echoOut(202, $msg);
        }

        //检查SQL语法是否正确
        $sql_is_true = $this->checkSqlIsTrue( $sql );
        if( $sql_is_true !== true ){
            if( stristr( $sql_is_true, "1064 You have an error in your SQL syntax" ) ){
                $this->echoOut(202, $this->codeToMsg()["202"]);
            }
        }

        $action = strtolower(strtok( rtrim( $sql ), ' ' ));
        //判断是否是通用库还是分库分表下执行
        $is_in_sharding = $this->isInShardingList( $environment, $db_name, $project );
        if( $is_in_sharding === true || is_array( $is_in_sharding ) ){ //分库分表下执行
            if( $is_in_sharding === true ){
                $request = $this->execute_sharding( $environment, $db_name, $project, $sql );
                
            }else{//真实配置信息
                $config_environment = $is_in_sharding['environment'];
                $config_db_name = $is_in_sharding['appName'];
                $config_project = $is_in_sharding['projectName'];
                $request = $this->execute_sharding( $config_environment, $config_db_name, $config_project, $sql );
            }

            if( $request['code'] != 0 ){
                $this->echoOut($request['code'], $request['msg']);
            }else{
                $excute_result = $request['data']->list;
                $excute_num = $request['data']->count; //操作数量

                if( $excute_num > 1 ){ //count值等于1时，list的值是字段名称
                    $ex = 0;
                    $key_array = array();
                    $excute_result_array = array();
                    foreach ($excute_result as $ex_key => $ex_value) {
                        if( $ex_key == 0 ){
                            $title_excute_array = $ex_value;
                        }else{
                            foreach ($ex_value as $k_key => $k_value) {
                                $excute_result_array[$ex][$title_excute_array[$k_key]] = $k_value;
                            }
                            $ex++;
                        }
                    }
                    if( $excute_result_array ){
                        $excute_result = $excute_result_array;
                    }
                }else{
                    $excute_result = array();
                }
                if( $action == 'select' ){
                    //在select查询的结果集中，第一行是数据库字段名称，所以查询出的结果显示的行数应当-1.
                    $excute_num = $excute_num-1 ;
                }
            }
        }else if ( $is_in_sharding === false ) { //通用库下执行
            $request = $this->getServerIp( $project, $environment );
            if( is_array( $request ) ){
                $this->echoOut($request['code'], $request['msg']);
            }else{
                $server_ip = $request;
                if( empty( $server_ip ) ){ //获取项目环境的数据库配置信息为空
                    $this->echoOut(303, $this->codeToMsg()["303"]);
                }

                $executeConnection = $this->connectDb( $server_ip, $db_name );

                try {
                    $command = $executeConnection->createCommand($sql);
                    if( $action == 'select' ){
                        $excute_result = $command->queryAll();
                        $excute_num = count( $excute_result );
                    }else{
                        $excute_result = $command->execute();
                        $excute_num = $excute_result > 0 ? $excute_result : 0;
                    }
                    
                } catch (\Exception $e) {
                    $eMessage = mb_convert_encoding($e->getMessage(), "UTF-8", "GBK"); 
                    $msg = $this->codeToMsg()["206"] . "：" . $eMessage;
                    $this->echoOut( 206, $msg );
                }
            }
        } else {
            $code = $is_in_sharding;
            $this->echoOut($code, $this->codeToMsg()["$code"]);
        }

        $end_time = explode(' ',microtime());
        $thistime = $end_time[0]+$end_time[1]-($start_time[0]+$start_time[1]);
        $thistime = round($thistime,3);

        $data_array['sql'] = $sql;
        $data_array['count'] = $excute_num;
        $data_array['time'] = $thistime . "s";
        $data_array['list'] = $excute_result;
        

        $sqlresult = '记录条数为:' . $excute_num . '行';
        
        if( $action == 'select' ){
            $SaveSQL = new QueryLogs;
        }else{
            $SaveSQL = new ExecuteLogs;
            $SaveSQL->notes = $note;
            $data_array['list'] = null;
        }
        $SaveSQL->user_id = $user_id;
        $SaveSQL->host = ( $is_in_sharding === false ) ? $server_ip : '';
        $SaveSQL->database = $db_name;
        $SaveSQL->script = $sql;
        $SaveSQL->result = $sqlresult;
        $SaveSQL->status = 0;
        $SaveSQL->project_name = $project;
        $SaveSQL->environment = $environment;
        $SaveSQL->action = 'api';

        $result = $SaveSQL->save();

        $this->echoOut(0, $this->codeToMsg()["0"], $data_array);
    }

    /**
     * 订正日志推送
     * 运维平台上订正工单完成之后将订正工单的数据推送到WEBDB上
     * @return [type] [description]
     */
    public function actionCorrectLog()
    {
        @$workorder_no = rtrim($_REQUEST['workorder_no']);
        @$workorder_user = rtrim($_REQUEST['workorder_user']);
        @$workorder_time = rtrim($_REQUEST['workorder_create_time']);
        @$workorder_title = rtrim($_REQUEST['workorder_title']);
        @$workorder_reason = rtrim($_REQUEST['workorder_reason']);
        @$workorder_dbname = rtrim($_REQUEST['workorder_dbname']);
        @$workorder_sql_checker = rtrim($_REQUEST['workorder_sql_checker']);
        @$workorder_type = rtrim($_REQUEST['workorder_type']);
        @$workorder_dba = rtrim($_REQUEST['workorder_dba']);
        @$workorder_end_time = rtrim($_REQUEST['workorder_end_time']);
        @$workorder_source = rtrim($_REQUEST['workorder_source']);

        if( empty( $_REQUEST ) || !is_array( $_REQUEST ) ){
            $code = 111;
            $this->echoOut($code, $this->codeToMsg()["$code"]);
        }

        foreach ($_REQUEST as $key => $value) {
            if( empty( rtrim( $value ) ) ){
                $code = 111;
                $this->echoOut($code, $this->codeToMsg()["$code"]);
            }
        }

        $log_data = Log::find()->where(['workorder_no' => $workorder_no])->one();
        if( empty( $log_data ) ){
            $log_model = new Log();
            $log_model->workorder_no = $workorder_no;

            $log_model->workorder_user = $workorder_user;
            $log_model->workorder_time = $workorder_time;
            $log_model->workorder_title = $workorder_title;
            $log_model->workorder_reason = $workorder_reason;
            $log_model->workorder_sql_checker = $workorder_sql_checker;
            $log_model->workorder_type = $workorder_type;
            $log_model->workorder_end_time = $workorder_end_time;
            $log_model->workorder_dba = $workorder_dba;
            $log_model->source = $workorder_source ? $workorder_source : 'handwork';

            //$workorder_dbname值格式为192.168.5.122:upkeep:common_dev;192.168.70.8:otter:pre;192.168.69.150:oms:online
            $workorder_dbname_ = explode(";", $workorder_dbname);
            $db_names = array();
            foreach ($workorder_dbname_ as $key => $value){
                $value_ = explode(":", $value);
                $db_names[] = $value_[1];
            }

            $log_model->db_names = implode(",", $db_names);

            if( $log_model->insert() ){
                $log_id = $log_model->log_id;
            }else{
                $code = 201;
                $this->echoOut($code, $this->codeToMsg()["$code"]);
            }

            
            foreach ($workorder_dbname_ as $key => $value) {
                if( empty( $value ) ) continue;

                $value_ = explode(":", $value);

                $info_model = new LogInfo();
                $info_model->log_id = $log_id;
                $info_model->server_ip = $value_[0];
                $info_model->db_name = $value_[1];
                $info_model->insert();
            }
        }

        $code = 0;
        $this->echoOut($code, $this->codeToMsg()["$code"]);
    }

    /**
     * 输出结果集
     * @param  [type] $code   [description]
     * @param  [type] $msg    [description]
     * @param  [type] $result [description]
     * @return [type]         [description]
     */
    private function echoOut( $code, $msg, $result = array() ){
        //echo $code;exit;
        $data = array(
            'code' => $code,
            'msg' => $msg,
            'data' => $result,
        );

        echo json_encode($data);exit;
    }

    /**
     * 通用库执行
     * @param  [type] $environment [description]
     * @param  [type] $db_name     [description]
     * @param  [type] $project     [description]
     * @param  [type] $sql         [description]
     * @return [type]              [description]
     */
    private function execute_sharding( $environment, $db_name, $project, $sql )
    {
        //DML语句不允许带库名，所以需要过滤掉库名
        $sql = str_replace("`", "", $sql);
        $sql = str_replace($db_name.'.', "", $sql);

        preg_match("/^(delete|insert|update|select)\s+/", strtolower( $sql ), $pre);

        //DML执行
        $post['projectName'] = $project;
        $post['environment'] = $environment;
        $post['database'] = $db_name;
        $post['oper'] = $pre[1];
        $post['sql'] = $sql;

        $url = self::SHARDING_SERVICE . "/db/oper.json";
        $request = $this->http_post( $url, $post ); 
        $content = json_decode($request);

        if ($content->success && $content->failed == false) {
            $result['code'] = 0;
            $result['data'] = $content->data;
        } else {
            $result['code'] = 206;
            $result['msg'] = $this->codeToMsg()["206"] . "：" .$content->statusText;
        }
        return $result;
    }

    /**
     * [execute_common description]
     * @param  [type] $server_ip [description]
     * @param  [type] $db_name   [description]
     * @param  [type] $sql       [description]
     * @return [type]            [description]
     */
    private function execute_common($server_ip, $db_name, $sql)
    {
        $executeConnection = $this->connectDb( $server_ip, $db_name );
        $command = $executeConnection->createCommand($sql);
        try {
            $excute_result = $command->execute();
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 根据项目名称和环境获取环境配置
     * @param  [type] $project     [description]
     * @param  [type] $environment [description]
     * @return [type]              [description]
     */
    private function getServerIp( $project, $environment )
    {
        $url = self::CRYW_API . "get_environment_info/?project_name=" . $project . "&env=" . $environment;
        $request = $this->http_get( $url);
        $content = json_decode( $request );
        if( $content->code == 0 ){
            $info = $content->result;
            if( empty( $info ) ){
                $result['code'] = 302;
                $result['msg'] = $this->codeToMsg()["302"];
            }else{
                return $info->mysql;
            }

        }elseif (condition) {
            $result['code'] = 302;
            $result['msg'] = $this->codeToMsg()["302"] . "：" .$content->message;
        }
        return $result;
    }

    /**
     * 验证来源合法性
     * @param  [type] $md5_key [description]
     * @return [type]          [description]
     */
    private function verifyIp( $md5_key )
    {
        $userIP = Yii::$app->request->userIP;
        $api_config_data = ApiUsersConfig::find()->select(['username', 'from_ip'])->where(['md5_key' => $md5_key])->asArray()->one();
        if( empty( $api_config_data ) ){
            return false;
        }else{
            $from_ip = $api_config_data['from_ip'];
            if( empty( $from_ip ) ){
                return false;
            }else{
                $from_ip_array = explode(",", $from_ip);
                if( !in_array( $userIP, $from_ip_array ) ){
                    return false;
                }
            }
        }

        $username = $api_config_data['username'];
        if( empty( $username ) ){
            return false;
        }

        $users_data = Users::find()->select(['id'])->where(['username' => $username])->asArray()->one();
        return $users_data['id'];
    }

    /**
     * 检查SQL是否是DML类型语句
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function sqlIsDml( $sql )
    {
        if (preg_match("/^(?:delete|insert|update|select){1}?\s/i", $sql)) {
            return true;
        }else{
            return false;
        }
    }

    /**
     * 判断环境、项目、数据库是否在分库分表规则中
     * 根据  环境、项目、数据库是否在分库分表列表中，如果存在，则在分库分表下执行，如果不存在，则在通用库下执行
     * @param  [type]  $environment [description]
     * @param  [type]  $db_name     [description]
     * @param  [type]  $project     [description]
     * @return boolean              [description]
     */
    private function isInShardingList( $environment, $db_name, $project )
    {
        $url = self::SHARDING_SERVICE . "/db/list.json";
        $request = $this->http_get( $url);

        $content = json_decode( $request );
        if( $content->success != true ){
            return 301;
        }
        $content_data = $content->data;

        if( !empty( $content_data ) && is_array( $content_data ) ){
            foreach ($content_data as $key => $value) {
                if( $project == $value->projectName && $environment == $value->environment && in_array( $db_name, $value->database ) ){
                    return true;
                }
            }

            //判断该项目环境对应配置中心中实际的环境配置信息
            $data_array = array( array('appName' => $db_name, 'environment' => $environment, 'projectName' => $project) );
            $superconfig_url = self::SUPERCONFIG . "?coordinates=".json_encode($data_array);
            $superconfig_request = json_decode( $this->http_get($superconfig_url) );

            if( $superconfig_request->success == true ){ //如果请求成功
                $superconfig_data = $superconfig_request->data;

                if( !empty( $superconfig_data ) ){ //如果返回的值不为空
                    $appName = $superconfig_data[0]->appName;  //模块名，也就是数据库名
                    $projectName = $superconfig_data[0]->projectName;
                    $environment = $superconfig_data[0]->environment;

                    foreach ($content_data as $key => $value) {
                        if( $projectName == $value->projectName && $environment == $value->environment && in_array( $appName, $value->database ) ){
                            return [
                                'appName' => $appName,
                                'projectName' => $projectName,
                                'environment' => $environment,
                            ];
                        }
                    }
                }
            }
            return false;
        }else{//如果结果集为空，或者不是数组，则返回false，即为通用库环境
            return false;
        }
    }

    /**
     * 检查SQL语法是否正确
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function checkSqlIsTrue( $sql ){
        $server_ip = "127.0.0.1";
        $db_name = 'webdb';
        $executeConnection = $this->connectDb( $server_ip, $db_name );
        try {
            $command = $executeConnection->createCommand($sql);
            $excute_result = $command->execute();
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        
    }

    /**
     * 验证SQL合法性
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function sqlVerify( $db_name, $sql ){
        //过滤某些不允许的操作
        if( preg_match('/^select\s+/', strtolower($sql)) ){
            if(!preg_match('/\s+limit\s+/i', strtolower($sql))){
                return 'select语句必须加limit限制！';
            }
        }

        //灰度与线上的数据查询增加对敏感金额数据的统计限制 ----------S-----------
        if( $db_name == 'oms' ){
            $preg = Yii::$app->params['regexp']['oms_limit_money'];
            if( preg_match( $preg, strtolower($sql) ) ){
                return '敏感金额数据的统计已做限制！';
            }
        }

        if( $db_name == 'ordercenter' ){
            $preg = Yii::$app->params['regexp']['ordercenter_limit_money'];
            if( preg_match( $preg, strtolower($sql) ) ){
                return '敏感金额数据的统计已做限制！';
            }
        }
        
        //防呆限制
        if( preg_match(Yii::$app->params['regexp']['delete_limit']['condition'], strtolower($sql)) ){ // 如果能够匹配到delete
            //if( !preg_match(Yii::$app->params['regexp']['delete_limit']['limit'], strtolower($sql) ) ){ //匹配delete语句后面是否带有where条件
            if( !preg_match('/\s+where\s+/', strtolower($sql) ) ){ //匹配delete语句后面是否带有where条件
                return 'delete语句必须带有where条件！';
            }
        }
        if( preg_match(Yii::$app->params['regexp']['update_limit']['condition'], strtolower($sql)) ){ // 如果能够匹配到delete
            //if( !preg_match(Yii::$app->params['regexp']['update_limit']['limit'], strtolower($sql) ) ){ //匹配delete语句后面是否带有where条件
            if( !preg_match('/\s+where\s+/', strtolower($sql) ) ){ //匹配delete语句后面是否带有where条件
                return 'update语句必须带有where条件！';
            }
        }
        return true;
    }

    /**
     * 检查参数信息
     * @param  [type] $environment [description]
     * @param  [type] $db_name   [description]
     * @param  [type] $project   [description]
     * @param  [type] $sql       [description]
     * @return [type]            [description]
     */
    public function checkParam( $environment, $db_name, $project, $sql, $note, $md5_key ){
        $action = strtolower(strtok( rtrim( $sql ), ' ' ));
        if( empty( $environment ) ){ //环境为空
            $code = 101;
        }else if ( $environment != 'dev' && $environment != 'dev_trunk' && $environment != 'test' && $environment != 'test_trunk') { //环境值只包含dev、 dev_trunk、 test、 test_trunk、 pre、 pro
            $code = 109;
        }else if( empty( $db_name ) ){
            $code = 102;
        }else if( empty( $project ) ){
            $code = 103;
        }else if( empty( $sql ) ){
            $code = 104;
        }else if( empty( $md5_key ) ){
            $code = 106;
        }else if( $action != 'select' ){
            if( empty( $note ) ){
                $code = 105;
            }else{
                $start_char = substr($note, 0, 1);
                if ($start_char !== '#' || strlen( $note ) <= 20 ) {
                    $code = 107;
                }
            }
        }else{
            $code = 0;
        }
        
        return $code;
    }

    /**
     * 异常信息返回值
     * @return [type] [description]
     */
    public function codeToMsg(){
        return array(
            '0' => '执行成功',

            '101' => '环境不能为空',
            '102' => '数据库名称不能为空',
            '103' => '项目名称不能为空',
            '104' => 'SQL语句不能为空',
            '105' => 'SQL注释不能为空',
            '106' => 'md5值不能为空',
            '107' => 'SQL注释请以#开始，并且超过20个英文字符以上',
            '109' => '环境参数不正确',
            '110' => '缺少参数',
            '111' => '参数不能为空',

            '201' => '数据库链接失败',
            '202' => 'SQL语句语法有误',
            '203' => '数据库名不存在',
            '204' => '只支持DML类型SQL语句',
            '206' => '错误信息',

            '301' => '分库分表接口异常',
            '302' => '获取当前项目环境配置接口异常',
            '303' => '当前项目环境配置数据库为空',

            '401' => '非法请求',
            '402' => '未配置',
            '900' => '其他异常',
        );
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

    /**
     * HTTP  GET请求
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    private function http_get( $url )
    {
        $client = new Client();
        $result = $client->createRequest()
                ->setMethod('get')
                ->setUrl($url)
                ->setHeaders(['content-type' => 'application/json'])
                ->send()->getContent();

        return $result;
    }

    /**
     * HTTP  POST请求
     * @param  [type] $url  [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private function http_post( $url, $data )
    {
        $client = new Client();
        $request = $client->createRequest()
                ->setMethod('post')
                ->setHeaders(['content-type' => 'application/json'])
                ->setUrl( $url );
        if ( is_array($data) ) {
            $request->setData( $data );
        } else {
            $request->setContent( $data );
        }
        $result = $request->send()->getContent();
        return $result;
    }
}
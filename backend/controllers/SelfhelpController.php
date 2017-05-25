<?php 
namespace backend\controllers;

use Yii;
use backend\controllers\BaseController;

use backend\modules\correct\models\SelfHelp as SelfHelpConfig;
use common\models\SelfHelp;

use backend\server\TechApiServer;
use backend\server\SqlParserServer;
use backend\server\fixedsql\FixedSqlParserServer;
use backend\server\fixedsql\FixedBack;

use yii\base\Exception;

class SelfhelpController extends BaseController
{
    const SHARDING_SERVICE = 'http://dbservice.cc1990.com';//分库分表接口地址

    const SHARDING_DATABASE = ['membercenter', 'ordercenter']; //分库分表

    const BACKUP_SHELL_PATH = '/data/scripts/'; //备份脚本目录
    const CORRECT_BACKUP_LOG_PATH = '/data/self_correct_dump/'; //自助订正备份文件目录

    public function init()
    {
        error_reporting(0);
        header("Content-type:text/html;charset=utf-8");
        $this->enableCsrfValidation = false;
    }

    /**
    * 操作之前的校验
    * @return [type] [description]
    */
    public function beforeAction( $action )
    {
        $action_id = $action->id;
        $action_array = ['correct', 'correct-backup', 'correct-execute', 'release-backup', 'release-execute'];
        if( !in_array( $action_id, $action_array ) ){
            return parent::beforeAction($action);
        }

        //判断是否允许自助
        $self_help = SelfHelpConfig::find()->select(['status'])->asArray()->one();
        if( $self_help['status'] != '1' ){ //已关闭自助功能
            $this->echoOut('1');
        }

        @$techapi_signature = $_REQUEST['techapi_signature'];
        @$techapi_timestamp = $_REQUEST['techapi_timestamp'];

        if( empty( $techapi_signature ) || empty( $techapi_timestamp ) ){
            $this->echoOut('201');
        }

        $tech_api_server = new TechApiServer();
        //校验请求是否合法
        $request = $tech_api_server->checkTechapiSignature( $techapi_signature, $techapi_timestamp);

        if( !$request ){
            $this->echoOut('101');
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $code = '201';
        $this->echoOut($code);
    }

    public function actionTest()
    {
        $data = $this->getShardingRule("P_6472", 'pro', 'membercenter');
        var_dump($data);
    }

    /**
     * 通用库下的SQL解析树
     * @return [type] [description]
     */
    public function actionCommonSqlParser()
    {
        if( empty( $_POST['data'] ) ){ //如果参数为空，返回错误信息
            $this->echoOut("201");
        }

        $sql_list = json_decode($_POST['data'], true);// {"env_name": "pre", "db_name": "test", "server_ip": "192.168.3.121", "sql": "update test set name='cc' where user_id=3;delete from test t where t.user_id=3;"}


        @$environment = trim( $sql_list['env_name'] );
        @$server_ip = trim( $sql_list['server_ip'] );
        @$db_name = trim( $sql_list['db_name'] );
        @$sql_info = trim( $sql_list['sql'] );

        if( empty($environment) || empty( $db_name ) || empty( $server_ip ) || empty( $sql_info ) ){
            $this->echoOut("201"); // 如果环境和库名为空，则返回缺少参数错误信息
        }

        $sqlParser = new SqlParserServer();
        $separate_sql = $sqlParser->separateSql( $sql_info );
        if( isset( $separate_sql['error'] ) ){ // 解析失败
            $this->echoOut("201", $separate_sql['error']);
        }
        $sqlinfoA = $separate_sql[0];//取第一条SQL

        //判断注释内容是否是SQL语句，如果是则跳出执行
        $preg = "/#(.*)(" . Yii::$app->params['regexp']['rule_key_words'] . ")/";
        if( preg_match($preg, strtolower($sqlinfoA)) ){
            $this->echoOut("201", "");
        }


        $sqlinfoA = trim($sqlinfoA) . ";#";

        preg_match_all('/(?:delete|update|insert|select)[\s\S]*?(?:;#)/i',strtolower( $sqlinfoA ), $sql_array);
        if (!empty($sql_array[0][0])){
            $sql = $sql_array[0][0];
            $sql = substr(rtrim($sql), 0, -1);
            $sql_data['sql'] = $sql;
        }else{
            if ( substr($sqlinfoA, 0, 1) == ';' ) {
                $sql_data['sql'] = substr($sqlinfoA, 1, -1);
            }else{
                $sql_data['sql'] = substr($sqlinfoA, 0, -1);
            }
            
            $sql_data['warning'] = "非DML语句不做SQL语法检测";
            $this->echoOut("0", "", $sql_data);
        }

        //检测SQL语法是否正确
        $sql_true = $this->checkSqlIsTrue( $sql );
        if( $sql_true !== true ){
            if( stristr( $sql_true, "1064 You have an error in your SQL syntax" ) ){
                $sql_data['sql'] = $sql;
                $sql_data['error'] = "SQL语句语法有误，请检查！";
                $this->echoOut("0", "", $sql_data);
            }
        }

        //语法解析树
        $sql_data = $sqlParser->sqlParserTree( $sql );

        if( $sql_data['table_number'] == 1 && !empty( $sql_data['table_name'] ) && !empty( $sql_data['where'] ) ){
            $sql_review = "select count(*) from " . $sql_data['table_name'] . " where " . $sql_data['where'];
            $connect = $this->connectDb($server_ip, $db_name);
            try {
                $result = $connect->createCommand($sql_review)->queryAll();
            } catch (\Exception $e) {
                $error = $e->getMessage();
                if( stristr( $error, "1146 Table" ) ){
                    $sql_data['error'] = "SQL语句语法有误，表不存在！";
                }else{
                    $sql_data['error'] = "SQL执行失败：". $e->getMessage();
                }
                
                $this->echoOut("0", "", $sql_data);
            }
            
            $influences_number = $result[0]["count(*)"];
            $sql_data['influences_number'] = (int)$influences_number;
        }else{
            $sql_data['influences_number'] = 0;
        }

        $this->echoOut("0", "", $sql_data);
    }

    /**
     * 提供分库分表的SQL解析接口
     * @return [type] [description]
     */
    public function actionShardingSqlParser()
    {
        if( empty( $_POST['data'] ) ){ //如果参数为空，返回错误信息
            $this->echoOut("201");
        }

        $sql_list = json_decode($_POST['data'], true);// {"env_name": "pre", "db_name": "test", "sql_info": "update test set name='cc' where user_id=3;delete from test t where t.user_id=3;"}


        @$environment = trim( $sql_list['env_name'] );
        @$db_name = trim( $sql_list['db_name'] );

        if( empty($environment) || empty( $db_name ) ){
            $this->echoOut("201"); // 如果环境和库名为空，则返回缺少参数错误信息
        }

        if( !in_array( $db_name, self::SHARDING_DATABASE ) ){
            $this->echoOut("202", "该库不在分库分表规则中"); 
        }

        //获取环境、库名对应的项目名
        $projectRequest = $this->getShardingProject( $environment, $db_name );
        if( !empty($projectRequest['error']) ){
            $this->echoOut('202', $projectRequest['error']);
        }
        $project = $projectRequest['project'];

        @$sql_info = trim( $sql_list['sql_info'] );

        if( !empty( $sql_info ) ){ //SQL为空
            $sqlParser = new SqlParserServer();
            $separate_sql = $sqlParser->separateSql( $sql_info );
            if( isset( $separate_sql['error'] ) ){ // 解析失败
                $this->echoOut("201", $separate_sql['error']);
            }

            $sql_result = []; $sql_number = 0;
            foreach ($separate_sql as $key => $sqlinfoA) {
                $sql_data = [];
                //判断注释内容是否是SQL语句，如果是则跳出执行
                $preg = "/#(.*)(" . Yii::$app->params['regexp']['rule_key_words'] . ")/";
                if( preg_match($preg, strtolower($sqlinfoA)) ){
                    continue;
                }

                $sql_number ++; //SQL条数+1

                $sqlinfoA = trim($sqlinfoA) . ";#";

                preg_match_all('/(?:delete|update|insert|select)[\s\S]*?(?:;#)/i',strtolower( $sqlinfoA ), $sql_array);
                if (!empty($sql_array[0][0])){
                    $sql = $sql_array[0][0];
                    $sql = substr(rtrim($sql), 0, -1);
                }else{
                    if ( substr($sqlinfoA, 0, 1) == ';' ) {
                        $sql_data['sql'] = substr($sqlinfoA, 1, -1);
                    }else{
                        $sql_data['sql'] = substr($sqlinfoA, 0, -1);
                    }
                    
                    $sql_data['warning'] = "非DML语句不做SQL语法检测";
                    $sql_data_array[] = $sql_data;
                    continue;
                }

                //检测SQL语法是否正确
                $sql_true = $this->checkSqlIsTrue( $sql );
                if( $sql_true !== true ){
                    if( stristr( $sql_true, "1064 You have an error in your SQL syntax" ) ){
                        $sql_data['sql'] = $sql;
                        $sql_data['error'] = "SQL语句语法有误，请检查！";
                        $sql_data_array[] = $sql_data;
                        continue;
                    }
                }

                //语法解析树
                $sql_data = $sqlParser->sqlParserTree( $sql );

                if( $sql_data['table_number'] == 1 && !empty( $sql_data['table_name'] ) && !empty( $sql_data['where'] ) ){
                    $sql_range_request = $this->computeSqlRange( $project, $environment, $db_name, $sql_data['table_name'], $sql_data['where'], $sql_data['oper'], $sql_data['set_column'] );
                    if( isset( $sql_range_request['error'] ) ){
                        if( $error_info = stristr($sql_range_request['error'], 'MySQLSyntaxErrorException:') ){
                            $sql_data['error'] = str_replace("MySQLSyntaxErrorException: ", "", $error_info);
                        }else{
                            $sql_data['error'] = $sql_range_request['error'];
                        }
                        
                    }else{
                        $sql_data['influences_number'] = $sql_range_request['data'];
                    }
                }

                $sql_data_array[] = $sql_data;
            }

            $sql_result['sql_number'] = $sql_number;
            $sql_result['list'] = $sql_data_array;

            $this->echoOut("0", "", $sql_result);
        }else{
            $this->echoOut("201", "SQL语句为空，无法解析");
        }

    }

    /**
     * 自助订正功能（包含备份和执行）
     * @return [type] [description]
     */
    public function actionCorrect()
    {
        $file_content = json_decode( file_get_contents('php://input'), true );

        $workorder_no = $file_content['workorder_no']; //工单流水号
        $workorder_user = $file_content['workorder_user']; //工单创建人
        $params_list = $file_content['sql_list']; //SQL信息

        $sql_result = [];
        foreach($params_list as $key => $item) {

            $sql = trim($item['SQL']);
            $affected_rows = $item['Affected_rows'];//受影响行数
            $database_array = explode(":", $item['json_database']); //json_database的格式为 "192.168.70.100:ordercenter:online",
            $server_ip = $database_array[0];
            $db_name = $db_name = $database_array[1];
            $environment = $database_array[2];
            //需要将online转成pro
            if( $environment == 'online' ) $environment = 'pro';

            $sql_info = $item['json_sql'];

            //语法解析
            $sqlParser = new SqlParserServer();
            /*$separate_sql = $sqlParser->separateSql( $sql_info );
            if( isset( $separate_sql['error'] ) ){ // 解析失败
                $this->echoOut("201", "第". ($key+1) ."个库下的SQL有误，" . $separate_sql['error']);
                break;
            }*/

            //判断库名是否是分库分表
            if( in_array( $db_name, self::SHARDING_DATABASE ) ){
                //获取项目名
                $project_request = $this->getShardingProject( $environment, $db_name );
                if( isset( $project_request['error'] ) ){
                    $this->echoOut("201", "第". ($key+1) ."条SQL有误，" . $project_request['error']);
                    break;
                }

                $project = $project_request['project'];

                //获取分库分表的规则
                $rule = $this->getShardingRule($project_request['project'], $environment, $db_name);
                if( isset( $rule['error'] ) ){
                    $this->echoOut("201", "第". ($key+1) ."条SQL有误，" . $rule['error']);
                    break;
                }              
            }

            $sql_list_array = []; //记录单条SQL的执行情况
            $sql_list_array['sql'] = $sql;
            $sql_list_array['success'] = false;

            $status = true;//备份或执行成功

            //获取SQL的解析树
            $sql_data = $sqlParser->sqlParserTree( $sql );

            if( $sql_data['table_number'] != 1 || empty( $sql_data['table_name'] ) || empty( $sql_data['where'] ) ){
                $this->echoOut("301", "第". ($key+1) ."条SQL有误，不符合自助规则！");
            }

            //判断表名中是否包含库名
            $tb_name_array = explode(".", $sql_data['table_name']);
            $tb_name = count( $tb_name_array ) == 1 ? $sql_data['table_name'] : end($tb_name_array);

            //where条件
            $where = $sql_data['where'];

            $model = new SelfHelp();

            $model->workorder_no = $workorder_no;
            $model->workorder_user = $workorder_user;
            $model->environment = $environment;
            $model->db_name = $db_name;
            $model->tb_name = $tb_name;
            $model->sql = $sql;
            $model->where = $where;
            $model->server_ip = $server_ip;
            $model->type = in_array( $db_name, self::SHARDING_DATABASE ) ? 'sharding' : 'common'; //SQL执行环境，common、通用库；sharding、分库分表

            /**
             * 检测受影响行数
             *
             * 1、获取受影响行数
             * 2、判断影响行数和SQL审核平台提供的影响行数是否一致
             */
            
            //1、获取受影响行数
            
            if( in_array( $db_name, self::SHARDING_DATABASE ) ){
                $sql_range = $this->computeSqlRange( $project, $environment, $db_name, $tb_name, $where );
            }else{
                $sql_review = "select count(*) from " . $tb_name . " where " . $where;
                $connect = $this->connectDb($server_ip, $db_name);
                try {
                    $result = $connect->createCommand($sql_review)->queryAll();
                    $influences_number = $result[0]["count(*)"];
                    $sql_range['data'] = (int)$influences_number;
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    if( stristr( $error, "1146 Table" ) ){
                        $sql_range['error'] = "SQL语句语法有误，表不存在！";
                    }else{
                        $sql_range['error'] = "SQL执行失败：". mb_convert_encoding($error, "UTF-8", "GB2312");
                    }
                }
            }
            
            if( isset( $sql_range['error'] ) ){
                $status = false;
                $model->backup_status = '1';//未备份
                $model->backup_note = $sql_range['error'];

                if( $error_info = stristr($sql_range['error'], 'MySQLSyntaxErrorException:') ){
                    $model->backup_note = $sql_list_array['error'] = str_replace("MySQLSyntaxErrorException: ", "", $error_info);
                }else{
                    $model->backup_note = $sql_list_array['error'] = $sql_range['error'];
                }
            }else{
                $sql_range_number = $sql_range['data'];

                //2、判断影响行数和SQL审核平台提供的影响行数是否一致
                if ( $affected_rows != $sql_range_number ) {
                    $status = false;
                    $model->backup_status = '1';//未备份
                    $model->backup_note = "SQL脚本影响行数两次检测不一致";

                    $sql_list_array['error'] = "SQL脚本影响行数两次检测不一致";
                }
            }

            //开始执行备份操作
            if( $status == true ){
                $backup_result = [];
                if( in_array( $db_name, self::SHARDING_DATABASE ) ){
                    //执行分库分表的备份
                    $backup_result = $this->_correctShardingBackup( $workorder_no, $server_ip, $db_name, $tb_name, $where, $project, $environment, $rule );
                }else{
                    //执行通用库的备份
                    $backup_result = $this->_correctCommonBackup( $workorder_no, $server_ip, $db_name, $tb_name, $where );
                }

                if( isset( $backup_result['error'] ) ){  //备份失败
                    $status = false;
                    $model->backup_status = '2';//备份失败
                    $model->backup_note = $backup_result['error'];

                    $sql_list_array['error'] = $backup_result['error'];
                }else{
                    $model->backup_status = '3';//备份成功
                    $model->backup_note = $backup_result['msg'];
                }

                $model->backup_time = date("Y-m-d H:i:s"); //备份时间
            }

            if( $status == false ){ //备份失败
                $model->execute_status = '1';//未执行
            }else{

                //开始执行脚本的操作
                $sqlinfo['server_ip'] = $server_ip;
                $sqlinfo['environment'] = $environment;
                $sqlinfo['db_name'] = $db_name;
                $sqlinfo['sql'] = $sql;
                $sqlinfo['type'] = in_array( $db_name, self::SHARDING_DATABASE ) ? 'sharding' : 'common';

                $execute_result = $this->_correctExecute( $sqlinfo );

                if( isset( $execute_result['error'] ) ){
                    $status = false;
                    $model->execute_status = '2';//执行失败
                    $model->execute_note = $execute_result['error'];

                    $sql_list_array['error'] = $execute_result['error'];
                }else{
                    $model->execute_status = '3';//执行成功
                    $model->execute_note = '执行脚本成功';//执行成功

                    $sql_list_array['msg'] = '执行脚本成功';
                    $sql_list_array['success'] = true;
                }

                $model->execute_time = date("Y-m-d H:i:s");
            }
            $model->workorder_type = 'correct';
            $model->insert();

            $sql_result[] = $sql_list_array;

            if( $status == false ) break; //如果备份失败，则直接终止备份程序
        }

        $code = $status ? '0' : '302';

        $this->echoOut($code, '', $sql_result);
    }

    /**
     * 自助订正的数据备份功能
     * @return [type] [description]
	 * 
     */
    public function actionCorrectBackup()
    {
        /**
         * 获取的数据格式
         * {"sql_list": [{"json_database": "192.168.70.100:ordercenter:online",
   "json_sql": "update test set name='cc' where user_id=3;delete from prdercenter.user_info where id=107001272;"},
  {"json_database": "192.168.70.15:crm:online",
   "json_sql": "update test set name='cc' where user_id=3;delete from prdercenter.user_info where id=107001272;"}],
 "workorder_no": "xxx",
 "workorder_user": "01892"}
         * @var [type]
         */

        $file_content = json_decode( file_get_contents('php://input'), true );

        $workorder_no = $file_content['workorder_no']; //工单流水号
        $workorder_user = $file_content['workorder_user']; //工单创建人
		$params_list = $file_content['sql_list']; //SQL信息
    
        $sql_list = [];
		foreach($params_list as $key => $item) {
            //初始化SQL数组
            $sql_array = [];

            $database_array = explode(":", $item['json_database']); //json_database的格式为 "192.168.70.100:ordercenter:online",
            $sql_array['server_ip'] = $database_array[0];
            $sql_array['db_name'] = $db_name = $database_array[1];
            $environment = $database_array[2];
            //需要将online转成pro
            if( $environment == 'online' ) $environment = 'pro';
            $sql_array['environment'] = $environment;

			$sql_info = $item['json_sql'];

            //语法解析
            $sqlParser = new SqlParserServer();
            $separate_sql = $sqlParser->separateSql( $sql_info );
            if( isset( $separate_sql['error'] ) ){ // 解析失败
                $this->echoOut("201", "第". ($key+1) ."个库下的SQL有误，" . $separate_sql['error']);
                break;
            }

            //判断库名是否是分库分表
            if( in_array( $db_name, self::SHARDING_DATABASE ) ){
                //获取项目名
                $project_request = $this->getShardingProject( $environment, $db_name );
                if( isset( $project_request['error'] ) ){
                    $this->echoOut("201", "第". ($key+1) ."个库选择的环境有误，" . $separate_sql['error']);
                    break;
                }

                $sql_array['project'] = $project_request['project'];

                //获取分库分表的规则
                $rule = $this->getShardingRule($project_request['project'], $environment, $db_name);
                if( isset( $rule['error'] ) ){
                    $this->echoOut("201", "第". ($key+1) ."个库选择的环境有误，" . $rule['error']);
                    break;
                }else{
                    $sql_array['rule'] = $rule;
                }                
            }

            $sql_list_array = [];
            foreach ($separate_sql as $sqlinfoA) {
                //判断注释内容是否是SQL语句，如果是则跳出执行
                $preg = "/#(.*)(" . Yii::$app->params['regexp']['rule_key_words'] . ")/";
                if( preg_match($preg, strtolower($sqlinfoA)) ){
                    continue;
                }

                $sqlinfoA = trim($sqlinfoA) . ";#";

                preg_match_all('/(?:delete|update|insert|select)[\s\S]*?(?:;#)/i',strtolower( $sqlinfoA ), $sql_info_array);
                if (!empty($sql_info_array[0][0])){
                    $sql = $sql_info_array[0][0];
                    $sql = substr(rtrim($sql), 0, -1);

                    $sql_list_array[] = $sql;
                }
            }
            $sql_array['sql_list'] = $sql_list_array;

            $sql_list[] = $sql_array;
		}

        //开始执行备份
        $backup_result = $this->_correctBackup( $workorder_no, $workorder_user, $sql_list );

        $code = $backup_result['success'] ? '0' : '301';

        $this->echoOut($code, '', $backup_result['list']);
    }

    /**
     * 定制工单备份功能
     * @param  [type] $workorder_no   [description]
     * @param  [type] $workorder_user [description]
     * @param  [type] $sql_list       [description]
     * @return [type]                 [description]
     */
    private function _correctBackup( $workorder_no, $workorder_user, $sql_list )
    {
        if ( empty( $sql_list ) || !is_array( $sql_list ) ) {
            $this->echoOut("301", "备份失败：未检测到SQL语句");
        }

        $backup_status = true;//备份成功

        //语法解析
        $sqlParser = new SqlParserServer();


        $backup_info = [];
        foreach ($sql_list as $key => $sql_info) {
            $server_ip = $sql_info['server_ip'];
            $db_name = $sql_info['db_name'];
            $environment = $sql_info['environment'];
            $sql_list_array = $sql_info['sql_list'];

            //判断库名是否是分库分表的库
            if( in_array( $db_name, self::SHARDING_DATABASE ) ){
                $project = $sql_info['project']; //项目名
                $rule = $sql_info['rule'];//该项目、环境、库的分库分表规则
            }

            foreach ($sql_list_array as $sk => $sql) {
                $backup_list = [];

                $backup_list['sql'] = $sql; //获取到SQL语句

                //获取SQL的解析树
                $sql_data = $sqlParser->sqlParserTree( $sql );

                //判断表名中是否包含库名
                $tb_name_array = explode(".", $sql_data['table_name']);
                $tb_name = count( $tb_name_array ) == 1 ? $sql_data['table_name'] : end($tb_name_array);

                //where条件
                $where = $sql_data['where'];

                if( in_array( $db_name, self::SHARDING_DATABASE ) ){
                    //执行分库分表的备份
                    $backup_result = $this->_correctShardingBackup( $workorder_no, $server_ip, $db_name, $tb_name, $where, $project, $environment, $rule );
                }else{
                    //执行通用库的备份
                    $backup_result = $this->_correctCommonBackup( $workorder_no, $server_ip, $db_name, $tb_name, $where );
                }

                if( isset( $backup_result['error'] ) ){
                    $backup_status = false;
                    $backup_list['error'] = $backup_result['error'];
                }else{
                    $backup_list['msg'] = $backup_result['msg'];
                }

                $backup_info[] = $backup_list;

                if( $backup_status == false ){
                    $status = '2';//备份失败
                    $note = $backup_list['error'];
                }else{
                    $status = '3';//备份成功
                    $note = $backup_list['msg'];
                }

                $sqlinfo['server_ip'] = $server_ip;
                $sqlinfo['environment'] = $environment;
                $sqlinfo['db_name'] = $db_name;
                $sqlinfo['tb_name'] = $tb_name;
                $sqlinfo['sql'] = $sql;
                $sqlinfo['type'] = in_array( $db_name, self::SHARDING_DATABASE ) ? 'sharding' : 'common';
                $sqlinfo['where'] = $where;

                //记录执行的脚本
                $this->insertScript( $workorder_no, $status, $note, $workorder_user, 'correct', $sqlinfo );

                if( $backup_status == false ) break; //如果备份失败，则直接终止备份程序
            }

            if( $backup_status == false ) break; //如果备份失败，则直接终止备份程序
        }
        
        $info['success'] = $backup_status; //备份状态
        $info['list'] = $backup_info;

        return $info;
    }

    /**
     * 通用库下的备份
     * @param  [type] $workorder_no [description]
     * @param  [type] $server_ip    [description]
     * @param  [type] $db_name      [description]
     * @param  [type] $tb_name      [description]
     * @param  [type] $where        [description]
     * @return [type]               [description]
     */
    private function _correctCommonBackup( $workorder_no, $server_ip, $db_name, $tb_name, $where )
    {
        //备份脚本
        $backup_shell = self::BACKUP_SHELL_PATH . "self_correct_dump_common.sh";

        //备份参数
        $backup_param = "{$workorder_no} {$server_ip} {$db_name} {$tb_name} \"{$where}\"";

        $result['server_ip'] = $server_ip;
        $result['db_name'] = $db_name;
        $result['tb_name'] = $tb_name;

        if( is_file( $backup_shell ) ){
            $shell_path = "/bin/sh " . $backup_shell . " " . $backup_param;
            try {
                //执行备份
                system($shell_path, $code);
            } catch ( \Exception $e) {
                $result['error'] = "备份失败：" . $e->getMessage();
            }
            if( $code == 100 ){
                $result['msg'] = "备份成功！";
            }elseif( $code == 103 ){
                $result['error'] = "备份失败：参数不足";
            }else{
                //获取错误日志内容
                $error_log = self::CORRECT_BACKUP_LOG_PATH . "{$workorder_no}/error.log";
                $error = $this->http_get( $error_log );
                $result['error'] = "备份失败：" . $error;
            }
        }else{
            $result['error'] = "备份失败：未知的脚本路径";
        }

        return $result;
    }

    /**
     * 分库分表环境的备份
     * @param  [type] $workorder_no [description]
     * @param  [type] $server_ip    [description]
     * @param  [type] $db_name      [description]
     * @param  [type] $tb_name      [description]
     * @param  [type] $where        [description]
     * @param  [type] $project      [description]
     * @param  [type] $environment  [description]
     * @param  [type] $rule         [分库分表规则]
     * @return [type]               [description]
     */
    private function _correctShardingBackup( $workorder_no, $server_ip, $db_name, $tb_name, $where, $project, $environment, $rule )
    {
        $result = [];
        $tBKey_value = ''; 
        $is_sharding_table = 'N'; //是否是分片表  Y-分片表、N-通用库下表
        foreach ($rule['tblist'] as $rk => $rv) {

            if( $tb_name == $rv['tbName'] ){ //如果是在分库分表的分片表中
                if( stristr($where, $rv['tBKey'] . " = ") ){ //如果where条件中包含对应的分片符，且等于某个数值
                    @$tBKey_value = explode(" ", substr( $where, strlen( $rv['tBKey'] . " = " ) ) )[0];
                }
                $is_sharding_table = "Y";
            }
        }
        if( empty( $tBKey_value ) && $is_sharding_table == "Y" ){
            $result['error'] = "备份失败：未获取到分片符的值";
        }else{
            $backup_shell = self::BACKUP_SHELL_PATH . "self_correct_dump_sharding.sh";

            if( is_file( $backup_shell ) ){
                $backup_param = "{$workorder_no} {$server_ip} {$db_name} {$tb_name} '{$tBKey_value}' \"{$where}\" {$is_sharding_table}";

                $result['server_ip'] = $server_ip;
                $result['db_name'] = $db_name;
                $result['tb_name'] = $tb_name;

                $shell_path = "/bin/sh " . $backup_shell . " " . $backup_param;
                try {
                    system($shell_path, $code);
                } catch ( \Exception $e) {
                    $result['error'] = "备份失败：" . $e->getMessage();
                }
                if( $code == 100 ){
                    $result['msg'] = "备份成功！";
                }elseif( $code == 103 ){
                    $result['error'] = "备份失败：参数不足";
                }else{
                    $error_log = self::CORRECT_BACKUP_LOG_PATH . "{$workorder_no}/error.log";
                    $error = $this->http_get( $error_log );
                    $result['error'] = "备份失败：" . $error;
                }
            }else{
                $result['error'] = "备份失败：未知的脚本路径";
            }
        }
        return $result;
    }

    /**
     * 自助订正的脚本执行接口
     * @return [type] [description]
     */
    public function actionCorrectExecute()
    {
        $workorder_no = $REQUEST['workorder_no'];
        $sql_data = SelfHelp::find()->where(['workorder_no' => $workorder_no])->asArray()->all();

        $status = true;
        $data = [];
        if( !empty( $sql_data ) ){
            foreach ($sql_data as $key => $value) {
                if( $status == true ){
                    $correct_info = $this->_correctExecute( $value );
                    if( $correct_info == true ){
                        $execute_status = '3';
                        $msg = "执行成功";
                    }else{
                        $execute_status = '2';
                        $msg = "订正失败：" . $correct_info['error'];
                        $status = false;
                    }

                    //修改脚本的执行状态信息
                    $model = SelfHelp::findOne($value['id']);
                    $model->execute_status = $execute_status;
                    $model->execute_note = $msg;
                    $model->save();

                }else{
                    $msg = "未备份";
                }
                $data[$key]['environment'] = $value['environment'];
                $data[$key]['db_name'] = $value['db_name'];
                $data[$key]['sql'] = $value['sql'];
                $data[$key]['msg'] = $msg;
            }

            if( $status == true ){
                $this->echoOut('0');
            }else{
                $this->echoOut('301', "执行失败", $data);
            }
            
        }else{
            $this->echoOut('301', "执行失败：未查询出对应的订正脚本");
        }
    }

    /**
     * 执行订正脚本
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    private function _correctExecute( $value )
    {
        $info = $post = [];
        $url = self::SHARDING_SERVICE;

        $db_name = $value['db_name'];
        $sql = trim($value['sql']);

        if( $value['type'] == 'common' ){  //通用库下在执行订正脚本
            $server_ip = $value['server_ip'];

            $connect = $this->connectDb( $server_ip, $db_name );
            try {
                $command = $connect->createCommand($sql)->execute();
                return true;
            } catch (\Exception $e) {
                $info['error'] = $e->getMessage();
                return $info;
            }

        }elseif( $value['type'] == 'sharding' ){  //分库分表下执行订正脚本
            $oper = explode(" ", $sql)[0];
            $environment = $value['environment'];

            //获取分库分表中该环境使用该库的项目
            $project_info = $this->getShardingProject($environment, $db_name);
            if( !empty($project_info['error']) ){ //获取不到项目名
                $info['error'] = $project_info['error'];
                return $info;
            }else{
                $project = $project_info['project'];
            }

            $post['projectName'] = $project;
            $post['environment'] = $environment;
            $post['database'] = $db_name;
            $post['oper'] = $oper;
            $post['sql'] = $sql;
            
            //分库分表环境下执行订正脚本
            $sharding_request = $this->sharding_execute_dml($post);
            if( !empty( $sharding_request['error'] ) ){
                if ( $error_info = stristr( $sharding_request['error'], "{$sql}]" ) ) {
                    $info['error'] = "分库分表下执行失败：".str_replace("{$sql}]", "", $error_info);
                }else{
                    $info['error'] = "分库分表下执行失败：".$sharding_request['error'];
                }
                
                return $info;
            }

            return true;
        }

        $info['error'] = "SQL脚本未分类";
        return $info;
    }


    /**
     * 项目自助发布的数据备份功能
     * @return [type] [description]
     */
    public function actionReleaseBackup()
    {
        $this->echoOut('0');
    }

    /**
     * 项目自助发布的脚本执行接口
	 * @return [type] [description]
     */
    public function actionReleaseExecute()
	{
        $this->echoOut('0');
    }

    /**
     * 状态码和返回的信息
     * @return [type] [description]
     */
    public function codeToMsg()
    {
        return [
            '0' => "执行成功",
            '1' => "自助功能已关闭",

            '101' => "签名错误，请求不合法",

            '201' => "缺少参数",
			'202' => "其他错误",

            '301' => "备份失败",
            '302' => "执行失败",
            
			'500' => 'sql解析失败'
        ];
    }

    /**
     * 新增自助功能中的脚本
     * @param  [type] $workorder_no   [description]
     * @param  [type] $status         [description]
     * @param  [type] $note           [description]
     * @param  string $workorder_user [description]
     * @param  [type] $workorder_type [description]
     * @param  array  $sqlinfo        [description]
     * @return [type]                 [description]
     */
    private function insertScript( $workorder_no, $status, $note, $workorder_user, $workorder_type, $sqlinfo )
    {
        $model = new SelfHelp();

        $model->workorder_no = $workorder_no;
        $model->workorder_user = $workorder_user;
        $model->environment = $sqlinfo['environment'];
        $model->db_name = $sqlinfo['db_name'];
        $model->tb_name = $sqlinfo['tb_name'];
        $model->sql = $sqlinfo['sql'];
        $model->where = $sqlinfo['where'];
        $model->server_ip = $sqlinfo['server_ip'];
        $model->type = $sqlinfo['type']; //SQL执行环境，common、通用库；sharding、分库分表
        $model->backup_status = $status;
        $model->backup_note = $note;
        $model->backup_time = date("Y-m-d H:i:s");
        $model->workorder_type = $workorder_type;

        $model->insert();
        
    }

    /**
     * 输出结果集
     * @param  [type] $code   [description]
     * @param  [type] $msg [description]
     * @param  [type] $data [description]
     * @return [type]         [description]
     */
    public function echoOut( $code, $msg = '', $data = array() ){
        $msg = !empty( $msg ) ? $msg : $this->codeToMsg()["$code"];
        $data = array(
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        );

        echo json_encode($data);exit;
    }


    /**
     * 分库分表下执行DML操作
     * @param  [Array] $data [description]
     * @return [Array]       [description]
     */
    public function sharding_execute_dml( $data )
    {
        //ini_set('memory_limit','640M');
        $url = self::SHARDING_SERVICE . "/db/oper.json";
        $request = $this->http_post( $url, $data );
        $content = json_decode($request);

        if ($content->success && $content->failed == false) {
            $result['data'] = $content->data;
        } else {
            $result['error'] = $content->statusText;
        }
        return $result;
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
     * 根据项目、环境、库名获取分库分表信息
     * @param  [type] $project     [description]
     * @param  [type] $environment [description]
     * @param  [type] $database    [description]
     * @return [type]              [description]
     */
    public function getShardingRule( $project, $environment, $database )
    {
        $url = self::SHARDING_SERVICE . "/db/rule.json?projectName={$project}&environment={$environment}&database={$database}";
        $request = $this->http_get( $url);

        $info = [];

        $content = json_decode( $request );
        if( $content->success != true ){
            $info['error'] = "获取分库分表列表信息失败！";
            return $info;
        }
        $content_data = json_decode( $content->data );

        if( !empty( $content_data ) ){

            $tbInfoList = $content_data->tbInfoList; // 表信息
            $dbInfoList = $content_data->dbInfoList; // 库信息

            foreach ($dbInfoList as $k => $vo) {
                $data['dblist'][$k]['dbBeginIndex'] = $vo->dbBeginIndex;
                $data['dblist'][$k]['dbEndIndex'] = $vo->dbEndIndex;
                $data['dblist'][$k]['masterIP'] = $vo->masterIP;
                $data['dblist'][$k]['slaveIP'] = $vo->slaveIP;
            }

            $dbEnd = end( $dbInfoList ); // 获取最后一个库信息
            $dbEndIndex = $dbEnd->dbEndIndex;  //获取最后一个库下标
            $dbNum = $dbEndIndex+1; //分库规则

            $i = 0;
            foreach ($tbInfoList as $key => $value) {
                $tbName = $value->tbName;
                $tbName_array = explode('#', $tbName);
                $data['tblist'][$i]['tbName'] = $tbName_array[0];
                $data['tblist'][$i]['dBKey'] = @$tbName_array[1] ? $tbName_array[1] : $value->shardingDBKey;
                $data['tblist'][$i]['tBKey'] = @$tbName_array[2] ? $tbName_array[2] : $value->shardingTBKey;
                $data['tblist'][$i]['tbAmountPerDB'] = $value->tbAmountPerDB;
                $data['tblist'][$i]['dbIndex'] = $dbNum;
                $data['tblist'][$i]['type'] = 'master';

                $detailTBName = $value->detailTBName; // 从表信息
                if ( !empty( $detailTBName ) ) {
                    $d_tbname = explode(",", $detailTBName);
                    foreach ($d_tbname as $kd => $vd) {
                        $i++;
                        $d_tbname_ = explode("#", $vd);
                        $data['tblist'][$i]['tbName'] = $d_tbname_[0];
                        $data['tblist'][$i]['dBKey'] = @$d_tbname_[1] ? $d_tbname_[1] : $value->shardingDBKey;
                        $data['tblist'][$i]['tBKey'] = @$d_tbname_[2] ? $d_tbname_[2] : $value->shardingTBKey;
                        $data['tblist'][$i]['tbAmountPerDB'] = $value->tbAmountPerDB;
                        $data['tblist'][$i]['dbIndex'] = $dbNum;
                        $data['tblist'][$i]['type'] = 'slave';
                    }
                    $i++;
                }else {
                    $i++;
                }
                
            }

            $data['commonDBInfo']['dbName'] = $content_data->commonDBInfo->dbName;
            $data['commonDBInfo']['masterIP'] = $content_data->commonDBInfo->masterIP;
            return $data;
        }
        $info['error'] = "未获取到该环境下使用{$database}数据库的项目！";
        return $info;
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
     * 计算SQL影响行数
     * @param  [type] $project     [description]
     * @param  [type] $environment [description]
     * @param  [type] $database    [description]
     * @param  [type] $table       [description]
     * @param  [type] $where       [description]
     * @param  [type] $oper        [description]
     * @param  [type] $set_column  [description]
     * @return [type]              [description]
     */
    public function computeSqlRange( $project, $environment, $database, $table, $where, $oper, $set_column )
    {
        $table_array = explode(".", str_replace("`", "", $table));
        $tb_name = count($table_array) == 1 ? $table_array[0] : end($table_array);
        if ( $oper == 'update' ) { //update语句必须叫上count字段，用于判断字段是否存在
            if( !empty( $set_column ) ){
                $select_column_array = [];
                $set_column_array = explode(",", $set_column);
                foreach ($set_column_array as $key => $value) {
                    $select_column_array[]= "count(ifnull(".$value.",1))";
                }
                $select_column = implode(", ", $select_column_array);
            }else{
                $select_column = "count(*)";
            }
            $sql_review = "select {$select_column} from " . $tb_name . " where " . $where;
        } else {
            $sql_review = "select count(*) from " . $tb_name . " where " . $where;
        }
        
        
        $sql_post['projectName'] = $project;
        $sql_post['environment'] = $environment;
        $sql_post['database'] = $database;
        $sql_post['oper'] = "select";
        $sql_post['sql'] = $sql_review;

        $url = self::SHARDING_SERVICE . "/db/oper.json";
        $request = $this->http_post( $url, $sql_post );
        $content = json_decode($request);

        if ($content->success && $content->failed == false) {
            $request_exec_list = $content->data->list;
            @$exec_count = $request_exec_list[1][0];
            $result['data'] = $exec_count;
        } else {
            $result['error'] = $content->statusText;
        }

        return $result;
    }
}

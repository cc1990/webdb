<?php 
namespace backend\modules\index\controllers;

use backend\server\ShardingServer;
use Yii;
use backend\controllers\SiteController;
use common\models\Servers;
use common\models\ExecuteLogs;
use common\models\QueryLogs;
use common\models\AuthItemServers;

use backend\modules\operat\models\Select;
use backend\modules\operat\models\SelectWhite;
use backend\modules\operat\models\Authorize;

use backend\server\SqlParserServer;
use yii\base\Exception;
use yii\httpclient\Client;
use vendor\twl\tools\utils\Output;
use vendor\phpoffice\phpexcel\Classes;

/**
 * 分库分表操作
 */
class ShardingController extends SiteController
{
    //分库分表接口地址
    const host = "http://dbservice.cc1990.com";
    const shell_ddl_common = "/bin/sh /data/scripts/opt_ddl_common.sh";
    const shell_ddl_sharding = "/bin/sh /data/scripts/opt_ddl_sharding.sh";
    const config = "http://dbservice.cc1990.com/db/rule.json?projectName={@project}&environment={@environment}&database={@database}";

    public function actionIndex()
    {
        //服务器环境查询条数限制
        @$environment_rule = Select::find()->select(['dev', 'dev_trunk', 'test', 'test_trunk', 'pro', 'pre'])->asArray()->one();
        $data['environment_rule'] = json_encode($environment_rule);

        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(Yii::$app->users->identity->id);
        $data['is_administrator'] = !empty( $roles['Administrator'] ) ? true : false;

        return $this->render('index', $data);
    }

    /**
     * Description 获取数据库信息
    */
    public function actionGetConfig($projectName,$environment,$database)
    {
        //获取配置中心数据库配置信息
        $url = str_replace(['{@project}','{@environment}','{@database}'],[$projectName,$environment,$database],self::config);
        $result = $this->get($url);
        $result = json_decode($result,true);
        $result['data'] = json_decode($result['data'],true);
//        $result = ['data'=>['dbInfoList'=>[['masterIP'=>'192.168.5.122','dbName'=>'memberCenter']]]];

        //实例化分库分表服务，获取库表信息
        $return  = ['status'=>0,'msg'=>'系统维护中'];
        try {
            $shardingServer = new ShardingServer($result["data"]);
            $return['data']['common'] = $shardingServer->getTables();
            $return['data']['sharding'] = $shardingServer->getShardingTables();
            $return['status'] = 1;
            $return['msg'] = '数据获取成功';
            return json_encode($return);
        }catch(Exception $e){
            $return['msg'] = $e->getMessage();
            return json_encode($return);
        }
    }

    /**
     * Description 获取表信息
    */
    public function actionGetTableInfo()
    {
        $projectName = $_REQUEST['projectName'];
        $environment = $_REQUEST['environment'];
        $db_name = $_REQUEST['db_name'];
        $tb_name = $_REQUEST['tb_name'];
        $type = $_REQUEST['type'];

        //获取配置中心数据库配置信息
        $url = str_replace(['{@project}','{@environment}','{@database}'],[$projectName,$environment,$db_name],self::config);
        $result = $this->get($url);
        $result = json_decode($result,true);
        $result['data'] = json_decode($result['data'],true);
//        $result = ['data'=>['dbInfoList'=>[['masterIP'=>'192.168.5.122','dbName'=>'memberCenter']]]];

        $list[0]["name"] = "数据库";
        $list[0]["value"] = $db_name;
        $list[1]["name"] = "表名";
        $list[1]["value"] = $tb_name;

        $info_rule = array(
            'Rows' => '行数',
            'Engine' => '存储引擎',
            'Row_format' => '行格式',
            'Auto_increment' => '自动递增数值',
            'Comment' => '注释',
            'Create_time' => '创建时间',
            'Collation' => '校验规则',
        );

        $return = ["status"=>0,'msg'=>'系统维护中'];
        try{
            $shardingServer = new ShardingServer($result["data"]);
            if($type == "sharding"){
                $return["data"] = $shardingServer->getShardingTableInfo($tb_name,$info_rule,$list);
            }else{
                $return["data"] = $shardingServer->getTableInfo($tb_name,$info_rule,$list);
            }
            $return["status"] = 1;
            $return["msg"] = "获取成功";
            return json_encode($return);
        }catch(\Exception $e){
            $return['msg'] = $e->getMessage();
            return json_encode($return);
        }
    }


    public function actionExecute()
    {
        @$db_name = rtrim($_REQUEST['DBName']);
        @$sqlinfo_all = rtrim($_REQUEST['sqlinfo']);
        //@$is_limit = rtrim($_REQUEST['is_limit']);
        @$is_limit = 1;//强制设置查询条数限制

        @$project = !empty($_REQUEST['Project'])?rtrim($_REQUEST['Project']):0;
        @$batch = rtrim($_REQUEST['batch']);
        @$batch_notes = rtrim($_REQUEST['batch_notes']);
        @$environment = rtrim($_REQUEST['environment']);

        //根据服务器环境读取查询语句的执行条数
        @$environment_rule = Select::find()->asArray()->one();
        //$nums = $environment_rule[$environment] ? $environment_rule[$environment] : '500';

        $sqlParser = new SqlParserServer();
        $nums = $sqlParser->getSelectWhite( $db_name, $environment );


        if(empty($db_name))
            Output::error('请选择目标数据库！');
        if(empty($project))
            Output::error('请选择项目！');
        if(empty($environment) || !in_array( $environment, $this->getRoleEnvironment() ))
            Output::error('请选择环境！');

        //获取当前用户所对应的分库分表环境下SQL操作权限DDM、DML、DQL
        $operations = $this->getRoleOperations( $environment );

        //将SQL语句分割成SQL数组
        $sql_list = $sqlParser->separateSql( $sqlinfo_all );
        if( isset( $sql_list['error'] ) ){
            Output::error( $sql_list['error'] );
        }

        $contents = array();
        //开始循环执行sql
        foreach ($sql_list as $sql_key => $sqlinfoA) {
            if (!empty($nextadd)) {
                $sqlinfoA = $nextadd . $sqlinfoA;
                $nextadd = '';
            }
            //echo $key.':'.$sqlinfoA;
            $sqlinfoA .= "\r\n";
            $notes = '';

            $rule_key_words = Yii::$app->params['regexp']['rule_key_words'];
            //把SQL分割出来
            $rule = "(?:$rule_key_words)";

            preg_match_all("/#[\s\S]*?(?=\r\n(?:\s|\r\n)*" . $rule . ")/i", $sqlinfoA, $preg_notes);
            if (!empty($preg_notes[0][0])) {
                //print_r($notes);exit;
                $notes = $preg_notes[0][0];
                $notes = str_replace("\r\n", '', $notes);
                //判断注释是否20个字以上
                if (strlen($notes) <= 20) {
                    $contents[$sql_key]['sql'] = $sqlinfoA;
                    $contents[$sql_key]['msg'] = '注释长度必须20个英文字符以上';
                    continue;
                }
            } else {
                if (preg_match("/^;(?:\n|\s)*?(?:delete|insert|update|drop|create|alter|rename|truncate|optimize|analyze){1}?\s/i", $sqlinfoA)) {
                    if (1 == $batch) {
                        $notes = $batch_notes;
                    } else {
                        $contents[$sql_key]['sql'] = $sqlinfoA;
                        $contents[$sql_key]['msg'] = 'DDL与DML语句必须添加8个字符以上注释，格式：# 注释1234';
                        continue;
                    }

                }
            }

            if (!empty($notes) && 1 != $batch) {
                preg_match_all("/#[\s\S]*/i", $sqlinfoA, $sqlinfoA2);
                $sqlinfoA = $sqlinfoA2[0][0];
                $sqlinfoA = substr_replace($sqlinfoA, '', 0, strlen($notes) - 1);
            }

            //判断注释内容是否是SQL语句，如果是则跳出执行
            $preg = "/#(.*)(" . Yii::$app->params['regexp']['rule_key_words'] . ")/";
            if( preg_match($preg, strtolower( $sqlinfoA) ) ){
                continue;
            }

            if ($sqlinfoA == ";\r\n") {
                $nextadd = $notes;
                continue;
            }

            $sqlinfoA = trim($sqlinfoA);
            $sqlinfoA .= ";#";

            preg_match_all("/(?:select|delete|insert|update|drop|create|alter|rename|truncate|explain|optimize|show|analyze|desc|describe)[\s\S]*?(?:;#)/i",
                $sqlinfoA, $sql);
            if (!empty($sql[0][0])){
                $sqlinfoA = $sql[0][0];
            }else{
                $sqlinfoA = substr(rtrim($sqlinfoA), 0, -2);
                $nextadd = $sqlinfoA;
                continue;
            }

            //获取SQL类型和首个关键词
            $sql_info = $sqlParser->getSqlType( $sqlinfoA );
            if( $sql_info['sql_type'] == 'other' ){
                $contents[$key]['sql'] = $sqlinfoA;
                $contents[$key]['msg'] = '不支持的SQL类型';
                continue;
            }

            $sql_type = $sql_info['sql_type'];
            $sql_action = $sql_info['sql_action'];

            if (empty($sqlinfoA) || preg_match('/^\/\//i', $sqlinfoA) || preg_match('/^#/i', $sqlinfoA)) {
                continue;
            }

            if (preg_match('/^show\s+/i', rtrim($sqlinfoA)) || preg_match('/^desc\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^explain\s+/i', rtrim($sqlinfoA))
                || preg_match('/^insert\s+/i', rtrim($sqlinfoA)) || preg_match('/^update\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^delete\s+/i', rtrim($sqlinfoA))
                || preg_match('/^create\s+/i', rtrim($sqlinfoA)) || preg_match('/^drop\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^alter\s+/i', rtrim($sqlinfoA))
            ) {
                if (strrpos(rtrim($sqlinfoA), ';') == (strlen(rtrim($sqlinfoA)) - 2)) {
                    $sqlinfoA = substr(rtrim($sqlinfoA), 0, -2);
                }
            } else {
                if (strrpos(rtrim($sqlinfoA), ';') == (strlen(rtrim($sqlinfoA)) - 2)) {
                    $sqlinfoA = substr(rtrim($sqlinfoA), 0, -2);
                }
            }

            if (!preg_match('/^show\s+/i', rtrim($sqlinfoA)) || !preg_match('/^desc\s+/i',
                    rtrim($sqlinfoA)) || !preg_match('/^explain\s+/i', rtrim($sqlinfoA))
                || preg_match('/^insert\s+/i', rtrim($sqlinfoA)) || preg_match('/^update\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^delete\s+/i', rtrim($sqlinfoA))
                || preg_match('/^create\s+/i', rtrim($sqlinfoA)) || preg_match('/^drop\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^alter\s+/i', rtrim($sqlinfoA))
            ) {
                if (strrpos(rtrim($sqlinfoA), ';') == (strlen(rtrim($sqlinfoA)))) {
                    $sqlinfoA = substr(rtrim($sqlinfoA), 0, -1);
                }
            }
            $sqlinfo = $sqlinfoA;

            //检测SQL防呆限制、脱敏
            $sql_rule = $sqlParser->checkSqlRule( $sqlinfo, $db_name );
            if( isset( $sql_rule['error'] ) ){
                $contents[$sql_key]['sql'] = $sqlinfoA;
                $contents[$sql_key]['msg'] = $sql_rule['error'];
                continue;
            }

            //检查SQL语句语法是否正确
            $check_result = $this->checkSqlIsTrue( $sqlinfo );
            if( $check_result !== true ){
                if( stristr( $check_result, "1064 You have an error in your SQL syntax" ) ){
                    $contents[$sql_key]['sql'] = $sqlinfo;
                    $contents[$sql_key]['msg'] = "输入的SQL语句语法有误，请检查！";
                    continue;
                }
                
            }

            $sql_change = 0;//SQL重组 0、无重组，1、有重组
            //如果是select查询，则检查limit查询条数
            if( $sql_action == 'select' ){
                
                if( preg_match('/(?!^);\s*/', $sqlinfoA) || preg_match('/(?!^)#\s*limit/', $sqlinfoA) ){
                    $contents[$sql_key]['sql'] = $sqlinfoA;
                    $contents[$sql_key]['msg'] = 'SQL语法错误';
                    continue;
                }

                //此处判断线上环境的数据库表是否在查询条数限制的白名单里
                preg_match_all('/^select\s+((?!where).|\n)*\s+from\s+([a-zA-A0-9_.`]+)\s*/', strtolower($sqlinfo), $table_preg);
                @$table_name = end($table_preg)[0];

                //输入的SQL语句中表名之前可能加有库名
                @$sql_table = explode(".", $table_name);
                if ( count( $sql_table ) > 1 ) {
                    @$table_name_all = str_replace("`", "", $sql_table[0]) . "." . $sql_table[1];//替换库中的反引号`
                }else{
                    @$table_name_all = $db_name . "." . $table_name;//获取带库名的表名，比如 membercenter.user_info
                }
                
                $white_list = str_replace("\r", "", $environment_rule['white_list']);
                $white_list = str_replace("\n", "", $white_list);
                $white_list = str_replace(" ", "", $white_list);

                @$white_list_array = explode(",", $white_list);
                if ( $environment == 'pro' && in_array( $table_name_all, $white_list_array ) ) {
                    //线上环境，并且在白名单列表中
                    $nums = (int)$environment_rule['white_list_num'] ? $environment_rule['white_list_num'] : $nums;
                }

                $limit_num = $nums;

                $sql_limit = $sqlParser->getSelectLimit( $sqlinfoA, $nums );
                if( $sql_limit === false ){
                    $sqlinfoA .= ' limit 0, '.$nums;
                }else if( is_string( $sql_limit ) ){ //返回重新组装的SQL
                    $execute_sql = $sql_limit;
                    $sql_change = 1;
                }

            }

            //开始计时
            $start_time = explode(' ',microtime());

            $action = strtolower(strtok( rtrim($sqlinfo), ' ' ));
            $is_query = ($sql_action == 'select') ? 1 : 0;
            if (preg_match("/^(?:delete|insert|update|select){1}?\s/i", $sqlinfoA)) {
                if( $action == 'select' && !in_array('DQL', $operations) && $this->checkAuthorize( $environment, $db_name, "DQL" ) == false ){
                    $contents[$sql_key]['sql'] = $sqlinfoA;
                    $contents[$sql_key]['msg'] = '暂无DQL执行权限操作！';
                    continue;
                }elseif( ($action == 'insert' || $action == 'update' || $action == 'delete') && !in_array('DML', $operations ) && $this->checkAuthorize( $environment, $db_name, "DML" ) == false){
                    $contents[$sql_key]['sql'] = $sqlinfoA;
                    $contents[$sql_key]['msg'] = '暂无DML执行权限操作！';
                    continue;
                }

                $check_result = $this->checkSqlIsTrue( $sqlinfo );
                if( $check_result !== true ){
                    if( stristr( $check_result, "1064 You have an error in your SQL syntax" ) ){
                        $contents[$sql_key]['msg'] = "输入的SQL语句语法有误，请检查！";
                        continue;
                    }
                    
                }

                if( $sql_change == 1 ){
                    $request_sqlinfo = str_replace($db_name.'.', "", $execute_sql);
                }else{
                    $request_sqlinfo = str_replace($db_name.'.', "", $sqlinfo);
                }

                //DML语句不允许带库名，所以需要过滤掉库名
                $request_sqlinfo = str_replace("`", "", $request_sqlinfo);
                //DML执行
                $post['projectName'] = $project;
                $post['environment'] = $environment;
                $post['database'] = $db_name;
                $post['oper'] = $action;

                //select 查询语句不支持关键词大写
                $post['sql'] = ($action == 'select') ? strtolower($request_sqlinfo) : $request_sqlinfo;

                //Output::error($post['sql']);exit;
                $request = $this->execute_dml( $post );
                if( isset( $request['error'] ) ){
                    $contents[$sql_key]['sql'] = $sqlinfoA;
                    $contents[$sql_key]['msg'] = $request['error'];
                    continue;
                }
                
                $request_list = $request['data']->list;
                $excute_num = $request['data']->count;
                if ( !empty($request_list) ) {
                    $excute_result = array_slice( $request_list, 0, ($limit_num+1) );
                    if( $action == 'select' ){ //对select的结果重新组合，以字段->值的形式
                        if( $excute_num > 1 ){ //count值等于1时，list的值是字段名称
                            $ex = 0;
                            $key_array = array();
                            $excute_result_array = array();
                            foreach ($excute_result as $ex_key => $ex_value) {
                                if( $ex_key == 0 ){
                                    $title_excute_array = $ex_value;
                                }else{
                                    foreach ($ex_value as $k_key => $k_value) {
                                        $excute_result_array[$ex][$title_excute_array[$k_key]] = htmlspecialchars($k_value);
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
                    }
                } else {
                    $excute_result = 0;
                }
                //Output::error($excute_result_array[0]['audit_note']);
                $sqlstatus = 0;
                
                if ( $action == 'select' ) {
                    //在select查询的结果集中，第一行是数据库字段名称，所以查询出的结果显示的行数应当-1.
                    $excute_num = ($excute_num >= $limit_num) ? $limit_num : $excute_num-1 ;
                }
                $sqlresult = '记录条数为:' . $excute_num . '行';

            }elseif (preg_match("/^(?:create|alter|drop){1}?\s/i", $sqlinfoA)){
                if( !in_array('DDL', $operations ) && $this->checkAuthorize( $environment, $db_name, "DDL" ) == false ){
                    $contents[$sql_key]['sql'] = $sqlinfoA;
                    $contents[$sql_key]['msg'] = '暂无DDL执行权限操作！';
                    continue;
                }
                //DDL执行
                //获取分库分表规则
                $post['projectName'] = $project;
                $post['environment'] = $environment;
                $post['database'] = $db_name;
                $post['sql'] = $sqlinfo;

                //判断SQL语句中的表名是否在分库分表规则中
                $in_sharding = $this->tableIsInShardingDbList( $project, $environment, $db_name, $sqlinfo );
                if( isset( $in_sharding['error'] ) ){
                    $contents[$sql_key]['sql'] = $sqlinfo;
                    $contents[$sql_key]['msg'] = $in_sharding['error'];
                    continue;
                }

                if ( $in_sharding == true ) { //分库分表下操作
                    $sharding_rule = $this->getShardingRule($post);

                    if( isset( $sharding_rule['error'] ) ){
                        $contents[$sql_key]['sql'] = $sqlinfo;
                        $contents[$sql_key]['msg'] = $sharding_rule['error'];
                        continue;
                    }
                    @$tbInfo = $sharding_rule['tbInfo'];
                    @$tbAmountPerDB = $tbInfo->tbAmountPerDB;//每个库的表数

                    //$tpl = "membercenter \"192.168.5.139:3306:0:0:15#192.168.5.139:3306:1:16:31#192.168.5.139:3306:2:32:47#192.168.5.139:3306:3:48:63" "alter table wp_test add c int;\"";
                    $dbInfoList = $sharding_rule['dbInfoList']; // 分库规则
                    $tpl = "";
                    foreach ($dbInfoList as $key => $value) {
                        $masterIP = $value->masterIP;
                        $master_ip = explode(":", $masterIP);
                        //判断IP是否带有端口号，如果没有，则默认3306
                        @$mysql_port = $master_ip[1] ? $master_ip[1] : '3306';

                        $i = $value->dbBeginIndex;//分库分表库下标起始键
                        $j = $value->dbEndIndex;//分库分表库下标结束键

                        for ($i; $i <= $j ; $i++) { 
                            $tpl .= $master_ip[0] . ":" . $mysql_port . ":" . $i . ":" . $i*$tbAmountPerDB . ":" . (($i+1)*$tbAmountPerDB-1) . "#";
                        }
                    }

                    $rule = substr( $tpl, 0, -1 );

                    //执行ddl脚本
                    $post['dbName'] = $db_name;
                    $post['dbNum'] = $j;
                    $post['tableNum'] = ($j+1) * $tbAmountPerDB;
                    $post['rule'] = $rule;
                    
                    $request = $this->execute_ddl_sharding( $post );
                    if ( isset( $request['error'] ) ) {
                        $contents[$sql_key]['sql'] = $sqlinfo;
                        $contents[$sql_key]['msg'] = $request['error'];
                        continue;
                    } else {
                        $excute_result = 0;
                        $sqlstatus = 0;
                        $sqlresult = '执行成功';
                    }
                } else {//通用库下操作
                    //Output::error($db_name);
                    $commonDBInfo = $this->getCommonInfo( $project, $environment, $db_name );
                    if( isset( $commonDBInfo['error'] ) ){
                        $contents[$sql_key]['sql'] = $sqlinfo;
                        $contents[$sql_key]['msg'] = $commonDBInfo['error'];
                        continue;
                    }

                    $request = $this->execute_ddl_common( $commonDBInfo['masterIP'], $db_name, $sqlinfo );
                    if ( isset( $request['error'] ) ) {
                        $contents[$sql_key]['sql'] = $sqlinfo;
                        $contents[$sql_key]['msg'] = $request['error'];
                        continue;
                    } else {
                        $excute_result = 0;
                        $sqlstatus = 0;
                        $sqlresult = '执行成功';
                    }
                }
                $excute_num = 0;
            }elseif( preg_match("/\s?show\s+tables\s?/", strtolower($sqlinfoA) ) ){
                if( !in_array('DQL', $operations ) && $this->checkAuthorize( $environment, $db_name, "DQL" ) == false ){
                    $contents[$sql_key]['sql'] = $sqlinfoA;
                    $contents[$sql_key]['msg'] = '暂无DQL执行权限操作！';
                    continue;
                }
                $post['projectName'] = $project;
                $post['environment'] = $environment;
                $post['database'] = $db_name;
                $request = $this->getDbList( $post );
                if ( isset( $request['error'] ) ) {
                    $contents[$sql_key]['sql'] = $sqlinfo;
                    $contents[$sql_key]['msg'] = $request['error'];
                    continue;
                }
                //$dblist_array[0]['Tables_in_' . $db_name] = 'Tables_in_' . $db_name;
                $dblist = $request['list'];
                foreach ($dblist as $key => $value) {
                    $dblist_array[$key]['Tables_in_' . $db_name] = $value;
                }
                $is_query = 1;
                $sqlstatus = 0;
                $excute_result = $dblist_array;
                $excute_num = count( $dblist );
                $sqlresult = '记录条数为:' . $excute_num . '行.';
            }else{
                $contents[$sql_key]['sql'] = $sqlinfo;
                $contents[$sql_key]['msg'] = "非DDL和DML类型语句不可执行";
                continue;
            }

            //计算执行时间
            $end_time = explode(' ',microtime());
            $thistime = $end_time[0]+$end_time[1]-($start_time[0]+$start_time[1]);
            $thistime = round($thistime,3);
            
            if ($is_query == 0) {
                $msg = $notes . '<br>' . $sqlinfo .$sqlresult. '&nbsp;&nbsp;&nbsp;';
            } else {
                $msg = $sqlinfo .$sqlresult. '&nbsp;&nbsp;&nbsp;' ;
            }
            if ($is_query == 0) {
                $msg = "执行成功，受影响：[".$excute_num."]行，耗时：[" . $thistime . "s]";
            } else {
                $msg = "执行成功，当前返回：[".$excute_num."]行，耗时：[" . $thistime . "s]";
                if( $sql_change == 1 ){
                    $msg .= '，本次最多只能查询' . $nums . '条数据，如需查询更多，请联系DBA授权！';
                }
            }

            if (1 == $is_query) {
                $SaveSQL = new QueryLogs;
            } else {
                $SaveSQL = new ExecuteLogs;
                $SaveSQL->notes = $notes;

                if ( $action == 'insert' || $action == 'update' || $action == 'delete' ) {
                    $SaveSQL->sqloperation = 'dml';
                } else if( $action == 'create' || $action == 'alter' || $action == 'drop' || $action == 'truncate' ) {
                    $SaveSQL->sqloperation = 'ddl';
                }
            }
            $SaveSQL->user_id = Yii::$app->users->identity->id;
            //$SaveSQL->host = $server_ip;
            $SaveSQL->database = $db_name;
            $SaveSQL->script = $sqlinfo;
            $SaveSQL->result = $sqlresult;
            $SaveSQL->status = $sqlstatus;
            $SaveSQL->project_name = $project;
            $SaveSQL->environment = $environment;
            //$SaveSQL->server_id = $server_id;
            $result = $SaveSQL->save();
//            var_dump($result);

            if($result !==true){
                $contents[$sql_key]['sql'] = $sqlinfo;
                $contents[$sql_key]['msg'] = "数据库操作失败";
                continue;
            }

            if ($excute_result !== true) {
                $contents[$sql_key]['sql'] = $sqlinfo;
                $contents[$sql_key]['msg'] = $msg;
                $contents[$sql_key]['excute_result'] = $excute_result;
            }
        }
        Output::success('操作成功',$contents);
    }

    /**
     * 查询结果导出，只支持select查询导出
     * @return [type] [description]
     */
    public function actionSqlExport(){
	    set_time_limit(300);
		if( $this->is_Administrator() == false ){
			Output::error('暂无权限操作');
		}

        @$db_name = rtrim($_REQUEST['DBName']);
        @$sqlinfo_all = rtrim($_REQUEST['sqlinfo']);

        @$project = !empty($_REQUEST['Project'])?rtrim($_REQUEST['Project']):0;
        @$environment = rtrim($_REQUEST['environment']);

        if(empty($db_name))
            Output::error('请选择目标数据库！');
        if(empty($project))
            Output::error('请选择项目！');
        if(empty($environment) || !in_array( $environment, $this->getRoleEnvironment() ))
            Output::error('请选择环境！');


        //过滤某些不允许的操作
        if (preg_match('/databases/i', $sqlinfo_all)
            || preg_match('/sleep/i', $sqlinfo_all)
            || preg_match('/\s+count.*\s+user+\s.*/i', $sqlinfo_all)
            || preg_match('/\s+count.*\s+user_info+\s.*/i', $sqlinfo_all)
            || preg_match('/\s+count.*\s+qccr\.user+\s.*/i',$sqlinfo_all)
            || preg_match('/\s+count.*\s+membercenter\.user_info+\s.*/i', $sqlinfo_all)
            || preg_match('/.*--.*/i', $sqlinfo_all)
            || preg_match('/\s+limit\s+/', $sqlinfo_all)
        ) {
            Output::error('输入内容不合法请检查！');
        }

        //灰度与线上的数据查询增加对敏感金额数据的统计限制 ----------S-----------
        if( $db_name == 'oms' ){
            $preg = "/(\s|,)+(count|sum|avg)\(\s*(suggest_price|evaluate)\s*\)\s+\w+\s+(realtime_inventory|opening_inventory)\s*/";
            if( preg_match( $preg, $sqlinfo_all ) ){
                Output::error('敏感金额数据的统计已做限制！');
            }
        }

        if( $db_name == 'ordercenter' ){
            $preg = "/(\s|,)+(count|sum)\(\s*(market_cost|original_cost|sale_costreal_cost|market_cost|original_cost|sale_cost|coupon_apportion|market_cost|orig_cost|real_cost|sprice|signed_sprice|award_sprice|store_award_sprice|coupon_apportion|original_cost|sale_cost)\s*\)\s+\w+\s+(orders|order_goods|order_server|goods_sku_order)\s*/";
            if( preg_match( $preg, $sqlinfo_all ) ){
                Output::error('敏感金额数据的统计已做限制！');
            }
        }
        //-----------E-------------
        

        if( preg_match( '/^select\s+([\s\S]*)\s+(avg|AVG)\s*/', strtolower($sqlinfo_all) ) ){
            Output::error('查询语句不能使用求平均值AVG函数！');
        }

        if( preg_match( '/\s+union\s+/', strtolower($sqlinfo_all) ) ){
            Output::error('不能使用union查询！');
        }

        $end_char = substr($sqlinfo_all, -1);
        if ($end_char !== ';') {
            Output::error('完整的sql结尾必须加上;');
        }
        $sqlinfo_all = ';' . $sqlinfo_all . '#';
        $rule_key_words = 'select|delete|insert|update|drop|create|alter|rename|truncate|explain|optimize|show|analyze|desc|describe';
        //第一次分割规则，连同注释和SQL一起
        $rule = "(?:#|$rule_key_words)";
        //第二次分割，把SQL分割出来
        $rule2 = "(?:$rule_key_words)";

        preg_match_all("/;((?:\s|\r\n)*" . $rule . ")[\s\S]*?(?=;(?:\s|\r\n)*" . $rule . ")/i", $sqlinfo_all, $info);
        $info = $info[0];
        $nextadd = '';

        if( empty( $info ) ){
            Output::error('注释格式不正确，请以#开始！');
        }
        //判断是否适合查询条件
        foreach ($info as $key => $sqlinfoA) {
            $sqlinfo = str_replace("\r\n", " ", strtolower($sqlinfoA));
            $sqlinfo = str_replace(";", "", $sqlinfo);
            $action = strtok( rtrim($sqlinfo), ' ' );

            if( $action != "select" )
                Output::error("只能执行DQL类型语句，并且不能做注释");

        }

        $contents = array();
        foreach ($info as $sql_key => $sqlinfo) {
            $sqlinfo = str_replace("\r\n", " ", $sqlinfo);
            $sqlinfo = str_replace(";", " ", $sqlinfo);
            $sqlinfo = str_replace($db_name.'.', "", trim( $sqlinfo ));  //DML语句不允许带库名，所以需要过滤掉库名
            $sqlinfo = strtolower($sqlinfo) . " limit 10000";

            //Output::error($sqlinfo);
            //DML执行
            $post['projectName'] = $project;
            $post['environment'] = $environment;
            $post['database'] = $db_name;
            $post['oper'] = 'select';
            $post['sql'] = $sqlinfo;

            //Output::error($post['sql']);
            $request = $this->execute_dml( $post );
            if( isset( $request['error'] ) ){
                Output::error($request['error']);
            }
            $list = $request['data']->list;
            if ( empty( $list ) ) {
                $number  = 0;
            }else{
                $excute_num = $request['data']->count;
                //在select查询的结果集中，第一行是数据库字段名称，所以查询出的结果显示的行数应当-1.
                $number = $excute_num-1;

                preg_match_all('/^select\s+((?!where).)*\s+from\s+([a-zA-A0-9_.`]+)\s*/', $sqlinfo, $db_table_name);
                $db_table_name_array = explode(".", end($db_table_name)[0]);
                if ( count( $db_table_name_array ) > 1 ) {
                    $tbName = $db_table_name_array[1];
                } else {
                    $tbName = end($db_table_name)[0];
                }

                $this->execute_export($tbName, $request['data']->list, $environment);
            }

            $excute_result = 0;
            $sqlstatus = 0;
            $sqlresult = '&nbsp;&nbsp;&nbsp;记录条数为:' . $number  . '行.';

            $contents[$sql_key]['msg'] = $sqlinfo . $sqlresult;
            $contents[$sql_key]['sqlresult'] = $sqlresult;

            $contents[$sql_key]['excute_result'] = $excute_result;
            
        }
        Output::success("操作成功", $contents);
    }

    /**
     * 执行导出脚本
     * @param  [String] $tbName [表名]
     * @param  [Array] $data [数据]
     * @return [type]       [description]
     */
    public function execute_export( $tbName, $data, $environment = 'dev' ){
        /*$shell_path = "/data/scripts/select_export/export.php";
        if ( !file_exists( $shell_path ) ) 
            Output::error('脚本不存在');*/

        $count = count( $data );
        $title_array = $data[0]; //获取表头
        $tiaoshu = 10000;//每次导出的数据为一万条
        $num = ceil(($count-1)/$tiaoshu); 

        for ($i=0; $i < $num; $i++) { 
            $list = array_slice( $data, $tiaoshu*$i+1, $tiaoshu );
            $key = $i+1;
            $this->export_to_excel( $tbName . "_" .$key, $title_array, $list, $environment );
            
            /*$title = json_encode( $title_array );
            $content = json_encode( $list );
            $shell = "/usr/local/php/bin/php $shell_path $tbName($key) $title $content";
            $shell = "/usr/local/php/bin/php " . $shell_path . " " . $tbName . "(" .$key . ") " . $title . " " . $content;
            //echo $shell;exit;
            if ( function_exists('pcntl_fork') ) {
                $pid = pcntl_fork();  //产生子进程，而且从当前行之下开试运行代码，而且不继承父进程的数据信息 
                if ( $pid == 0 ) {
                    exec($shell);
                    //pcntl_exec("/usr/local/php/bin/php", array($shell_path, $tbName . "($key)", $title, $content));
                }
            } else {
                Output::error('该服务器不支持pcntl');
            }*/
        }
        return true;
    }

    /**
     * 导出成excel
     * @param  [String] $tbName [表名]
     * @param  [Array] $title  [表头]
     * @param  [Array] $list   [内容]
     * @param  [String] $environment   [环境]
     * @return [type]         [description]
     */
    public function export_to_excel( $tbName, $title, $list, $environment = 'dev' )
    {
        $objExcel = new \PHPExcel();

        $objExcel->getProperties()->setCreator("office 2003 excel");
        $objExcel->getProperties()->setLastModifiedBy("office 2003 excel");
        $objExcel->getProperties()->setTitle("Office 2003 XLS Test Document");
        $objExcel->getProperties()->setSubject("Office 2003 XLS Test Document");
        $objExcel->getProperties()->setDescription("Test document for Office 2003 XLS, generated using PHP classes.");
        $objExcel->getProperties()->setKeywords("office 2003 openxml php");
        $objExcel->getProperties()->setCategory("Test result file");
        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();

        /*$table_name = $argv[1]; //表名
        $title_array = json_decode($argv[2]);  // 表内容标题
        $list_array = json_decode($argv[3]);  // 表内容*/

        $objActSheet->setTitle($tbName);//设置当前sheet

        $excel_key = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ", "BA", "BB", "BC", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BK", "BL", "BM", "BN", "BO", "BP", "BQ", "BR", "BS", "BT", "BU", "BV", "BW", "BX", "BY", "BZ", "CA", "CB", "CC", "CD", "CE", "CF", "CG", "CH", "CI", "CJ", "CK", "CL", "CM", "CN", "CO", "CP", "CQ", "CR", "CS", "CT", "CU", "CV", "CW", "CX", "CY", "CZ");


        if( !empty( $title ) ){
            for ($i=0; $i < count($title); $i++) { 
                $objActSheet->setCellValue($excel_key[$i] . "1", $title[$i]);//设置表标题
            }
        }

        if ( !empty( $list ) ) {
            $j = 2;
            foreach ($list as $key => $value) {
                foreach ($value as $k => $v) {
                    if ( is_numeric( $v ) && strlen( $v ) > 11 ) {
                        $objActSheet->setCellValueExplicit($excel_key[$k] . $j, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
                    }else{
                        $objActSheet->setCellValue($excel_key[$k] . $j, $v);
                    }
                }
                $j++;
            }
        }

        // 设置页方向和规模
        $objActSheet->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $objActSheet->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

        //生成EXCEL文档
        $username = Yii::$app->users->identity->username;
        $data_time = date("YmdHis");
        $excelName = $username . "_" . $data_time . "_" . $environment . "_" . $tbName;
        header('Content-Type: application/vnd.ms-excel');
        header('Cache-Control: max-age=0');
        header( 'Content-Disposition: attachment; filename='.iconv("utf-8", "GBK", $excelName).'.xls');
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
        $objWriter->save("/data/scripts/select_export/download/".iconv("utf-8", "GBK", $excelName).'.xls');
    }

    /**
     * 根据服务器IP获取项目列表
     * @return [JSON]     [description]
     */
    public function actionGetProjectList()
    {
        $result = $this->getShardingList();
        
        $content = json_decode($result);
        @$content_data = $content->data;

        $environment_array = array(
            'dev' => '开发',
            'test' => '测试',
            'test_trunk' => '测试主干',
            'pre' => '预发布',
            'pro' => '线上',
            'dev_trunk' => '研发主干',
        );

        $i = $j = 0;
        $name_array = array();
        $environment_ = array();
        $role = $this->getRoleEnvironment();

        $info = array('project_list' => '', 'data' => '', 'environ_list' => '');
        
        if( !empty( $content_data ) && is_array( $content_data ) ){
            $project = array();$environ_list = array();
            foreach ($content_data as $key => $value) {
                $projectName = $value->projectName;
                $environment = $value->environment;
                if ( in_array( $environment, $role ) ) {
                    $project[$projectName][$environment] = $value->database;
                    $environ_list[$environment][$projectName] = $value->database;
                }
            }
            //此处需要对项目下的环境重新排序，为了防止误操作，将开发环境Dev放在首位
            foreach ($project as $key => $value) {
                $data[$i]['name'] = $key;
                foreach ($value as $k => $v) {
                    if( in_array( $k, $role ) ){
                        if( $k == 'dev' && $j != 0 ){
                            //将排在第一的数据移到当前索引下
                            $data[$i]['environment'][$j] = $data[$i]['environment'][0];

                            //将当前数据键值改成0
                            $data[$i]['environment'][0]['name'] = $k;
                            $data[$i]['environment'][0]['title'] = $environment_array[$k];
                            $data[$i]['environment'][0]['database'] = $v;
                        }else{
                            $data[$i]['environment'][$j]['name'] = $k;
                            $data[$i]['environment'][$j]['title'] = $environment_array[$k];
                            $data[$i]['environment'][$j]['database'] = $v;
                        }
                        $j++;
                    }else{
                        continue;
                    }
                }
                $i++;
                $j=0;
            }
            $info['project_list'] = $project;
            $info['data'] = $data;
            $info['environ_list'] = $environ_list;
            echo json_encode($info);
        }else{
            echo json_encode('');
        }
    }

    /**
     * 检测SQL的合法性和SQL执行影响范围
     * @return [type] [description]
     */
    public function actionSqlVerify(){
        @$db_name = rtrim($_REQUEST['DBName']);
        @$sqlinfo_all = rtrim($_REQUEST['sqlinfo']);
        //@$is_limit = rtrim($_REQUEST['is_limit']);
        @$is_limit = 1;//强制设置查询条数限制

        @$project = !empty($_REQUEST['Project'])?rtrim($_REQUEST['Project']):0;
        @$batch = rtrim($_REQUEST['batch']);
        @$batch_notes = rtrim($_REQUEST['batch_notes']);
        @$environment = rtrim($_REQUEST['environment']);


        if(empty($db_name))
            Output::error('请选择目标数据库！');
        if(empty($project))
            Output::error('请选择项目！');
        if(empty($environment) || !in_array( $environment, $this->getRoleEnvironment() ))
            Output::error('请选择环境！');

        $sqlParser = new SqlParserServer();

        //将SQL语句分割成SQL数组
        $sql_list = $sqlParser->separateSql( $sqlinfo_all );
        if( isset( $sql_list['error'] ) ){
            Output::error( $sql_list['error'] );
        }

        $contents = array();
        //开始循环执行sql
        foreach ($sql_list as $sql_key => $sqlinfoA) {
            if (!empty($nextadd)) {
                $sqlinfoA = $nextadd . $sqlinfoA;
                $nextadd = '';
            }
            //echo $key.':'.$sqlinfoA;
            $sqlinfoA .= "\r\n";
            $notes = '';

            $rule_key_words = Yii::$app->params['regexp']['rule_key_words'];
            //把SQL分割出来
            $rule = "(?:$rule_key_words)";

            preg_match_all("/#[\s\S]*?(?=\r\n(?:\s|\r\n)*" . $rule . ")/i", $sqlinfoA, $preg_notes);
            if (!empty($preg_notes[0][0])) {
                //print_r($notes);exit;
                $notes = $preg_notes[0][0];
                $notes = str_replace("\r\n", '', $notes);
                //判断注释是否20个字以上
                if (strlen($notes) <= 20) {
                    $contents[$sql_key]['sql'] = $sqlinfoA;
                    $contents[$sql_key]['msg'] = '注释长度必须20个英文字符以上';
                    continue;
                }
            } else {
                if (preg_match("/^;(?:\n|\s)*?(?:delete|insert|update|drop|create|alter|rename|truncate|optimize|analyze){1}?\s/i", $sqlinfoA)) {
                    if (1 == $batch) {
                        $notes = $batch_notes;
                    } else {
                        $contents[$sql_key]['sql'] = $sqlinfoA;
                        $contents[$sql_key]['msg'] = 'DDL与DML语句必须添加8个字符以上注释，格式：# 注释1234';
                        continue;
                    }

                }
            }

            if (!empty($notes) && 1 != $batch) {
                preg_match_all("/#[\s\S]*/i", $sqlinfoA, $sqlinfoA2);
                $sqlinfoA = $sqlinfoA2[0][0];
                $sqlinfoA = substr_replace($sqlinfoA, '', 0, strlen($notes) - 1);
            }

            //判断注释内容是否是SQL语句，如果是则跳出执行
            $preg = "/#(.*)(" . Yii::$app->params['regexp']['rule_key_words'] . ")/";
            if( preg_match($preg, $sqlinfoA) ){
                continue;
            }

            if ($sqlinfoA == ";\r\n") {
                $nextadd = $notes;
                continue;
            }

            $sqlinfoA = trim($sqlinfoA);
            $sqlinfoA .= ";#";

            preg_match_all("/(?:select|delete|insert|update|drop|create|alter|rename|truncate|explain|optimize|show|analyze|desc|describe)[\s\S]*?(?:;#)/i",
                $sqlinfoA, $sql);
            if (!empty($sql[0][0])){
                $sqlinfoA = $sql[0][0];
            }else{
                $sqlinfoA = substr(rtrim($sqlinfoA), 0, -2);
                $nextadd = $sqlinfoA;
                //echo $nextadd;
                continue;
            }
            if (preg_match("/^(?:delete|insert|update){1}?\s/i", $sqlinfoA)) {
                $sql_type = 'DML';
            }
            if (preg_match("/^(drop|create|alter|rename|truncate|optimize|analyze){1}?\s/i", $sqlinfoA)) {
                $sql_type = 'DDL';
            }

            if (empty($sqlinfoA) || preg_match('/^\/\//i', $sqlinfoA) || preg_match('/^#/i', $sqlinfoA)) {
                continue;
            }

            if (preg_match('/^show\s+/i', rtrim($sqlinfoA)) || preg_match('/^desc\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^explain\s+/i', rtrim($sqlinfoA))
                || preg_match('/^insert\s+/i', rtrim($sqlinfoA)) || preg_match('/^update\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^delete\s+/i', rtrim($sqlinfoA))
                || preg_match('/^create\s+/i', rtrim($sqlinfoA)) || preg_match('/^drop\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^alter\s+/i', rtrim($sqlinfoA))
            ) {
                if (strrpos(rtrim($sqlinfoA), ';') == (strlen(rtrim($sqlinfoA)) - 2)) {
                    $sqlinfoA = substr(rtrim($sqlinfoA), 0, -2);
                }
            } else {
                if (strrpos(rtrim($sqlinfoA), ';') == (strlen(rtrim($sqlinfoA)) - 2)) {
                    $sqlinfoA = substr(rtrim($sqlinfoA), 0, -2);
                }
            }

            if (!preg_match('/^show\s+/i', rtrim($sqlinfoA)) || !preg_match('/^desc\s+/i',
                    rtrim($sqlinfoA)) || !preg_match('/^explain\s+/i', rtrim($sqlinfoA))
                || preg_match('/^insert\s+/i', rtrim($sqlinfoA)) || preg_match('/^update\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^delete\s+/i', rtrim($sqlinfoA))
                || preg_match('/^create\s+/i', rtrim($sqlinfoA)) || preg_match('/^drop\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^alter\s+/i', rtrim($sqlinfoA))
            ) {
                if (strrpos(rtrim($sqlinfoA), ';') == (strlen(rtrim($sqlinfoA)))) {
                    $sqlinfoA = substr(rtrim($sqlinfoA), 0, -1);
                }
            }
            $sqlinfo = $sqlinfoA;

            $contents[$sql_key]['sql'] = $sqlinfo;

            $check_result = $this->checkSqlIsTrue( $sqlinfo );
            if( $check_result !== true ){
                if( stristr( $check_result, "1064 You have an error in your SQL syntax" ) ){
                    $contents[$sql_key]['msg'] = "输入的SQL语句语法有误，请检查！";
                    continue;
                }
                
            }

            //开始检测SQL
            //只检测update和delete语句的影响范围
            if( preg_match('/^update\s+/i', rtrim($sqlinfoA)) ){
                //正则匹配update语句的表名、修改值、where条件
                preg_match_all('/^update\s+([a-zA-Z0-9.`_]+)\s+set\s+((?!where).)*\s+where\s+([\s\S]+)/', strtolower($sqlinfo), $sql_preg);
                @$table_name = $sql_preg[1][0];  //表名
                @$sql_where = $sql_preg[3][0];  //SQL条件
                
                $sql_rule = $sqlParser->checkSqlRule( $sqlinfoA, $db_name );
                if( isset( $sql_rule['error'] ) ){
                    $contents[$sql_key]['msg'] = $sql_rule['error'];
                    continue;
                }

                $table_name_array = explode('.', $table_name);
                if( !empty( $table_name_array[1] ) ){//判断是否带有库名
                    $contents[$sql_key]['msg'] = "SQL语句不能带有库名！";
                    continue;
                }

                $msg = $this->computeSqlRange( $project, $environment, $db_name, $table_name, $sql_where );
                $contents[$sql_key]['msg'] = $msg;

            }elseif( preg_match('/^delete\s+/i', rtrim($sqlinfoA)) ){
                //正则匹配delete语句的表名、修改值、where条件
                preg_match_all('/^delete\s+from\s+([a-zA-Z0-9.`_]+)\s+where\s+([\s\S]+)/', strtolower($sqlinfo), $sql_preg);
                @$table_name = $sql_preg[1][0];  //表名
                @$sql_where = $sql_preg[2][0];  //SQL条件

                $sql_rule = $sqlParser->checkSqlRule( $sqlinfoA, $db_name );
                if( isset( $sql_rule['error'] ) ){
                    $contents[$sql_key]['msg'] = $sql_rule['error'];
                    continue;
                }

                $table_name_array = explode('.', $table_name);
                if( !empty( $table_name_array[1] ) ){//判断是否带有库名
                    $contents[$sql_key]['msg'] = "SQL语句不能带有库名！";
                    continue;
                }

                $msg = $this->computeSqlRange( $project, $environment, $db_name, $table_name, $sql_where );
                $contents[$sql_key]['msg'] = $msg;

            }else{
                $contents[$sql_key]['msg'] = '非update和delete语句暂不检测受影响范围';
            }
        }
        Output::success('操作成功',$contents);

    }

    /**
     * 计算SQL执行受影响范围
     * @param  [type] $project     [description]
     * @param  [type] $environment [description]
     * @param  [type] $db_name     [description]
     * @param  [type] $table_name  [description]
     * @param  [type] $sql_where   [description]
     * @return [type]              [description]
     */
    public function computeSqlRange( $project, $environment, $db_name, $table_name, $sql_where ){
        //分库分表执行SQL参数
        $execinfo['projectName'] = $project;
        $execinfo['environment'] = $environment;
        $execinfo['database'] = $db_name;
        $execinfo['oper'] = "select";
        $execinfo['sql'] = "select count(*) from " . $table_name . " where " . $sql_where;//带where条件查询行数

        $request_exec = $this->execute_dml( $execinfo );
        if( isset( $request_exec['error'] ) ){
            return $request_exec['error'];
        }
        //获取受影响行数
        $request_exec_list = $request_exec['data']->list;
        @$exec_count = $request_exec_list[1][0];
        $msg = "受影响行数：【".$exec_count."】";

        $execinfo['sql'] = "select count(*) from " . $table_name;//查询所有数据总行
        $request_all = $this->execute_dml( $execinfo );
        if( isset( $request_all['error'] ) ){
            return $request_all['error'];
        }
        $request_all_list = $request_all['data']->list;
        @$all_count = $request_all_list[1][0]; //获取所有行数
        $msg .= "，总行数：【".$all_count."】，影响数百分比为：";

        if( $all_count == 0){
            $msg .= "0%";
        }else{
            $shang = number_format($exec_count/$all_count,4);
            $percentage = $shang * 100;
            if( $shang > 0.3 ){
                $msg .= "<font color='red'>$percentage%</font>";
            }else{
                $msg .= $percentage."%";
            }
        }
        return $msg;
    }

    /**
     * 测试方法
     * @return [type] [description]
     */
    public function actionTest()
    {
        //var_dump($this->getRoleOperations());exit;
        $post['projectName'] = "P_6172";
        $post['environment'] = "dev";
        $post['database'] = "membercenter";
        $post['oper'] = 'select';
        $post['sql'] = "select count(*) from user_info where id = '348236'";

        //Output::error($post['sql']);
        $request = $this->execute_dml( $post );
        //$list = $request['data']->list;
        //$list_ = array_slice( $list, 0, 11 );
        //$content = $this->execute_export("user_info", $request['data']->list);
        var_dump($request);
        exit;

        var_dump( $this->getCommonInfo('6084', 'dev', 'membercenter') );exit;

        $data['projectName'] = '6084';
        $data['environment'] = 'dev';
        $data['database'] = 'membercenter';
        var_dump($this->getDbList( $data ));exit;

        $roles = $this->getRoleEnvironment();
        var_dump( $roles );exit;
    }

    /**
     * DML操作
     * @param  [Array] $data [description]
     * @return [Array]       [description]
     */
    public function execute_dml( $data )
    {
        ini_set('memory_limit','640M');
        $url = self::host . "/db/oper.json";
        $request = $this->post( $url, $data );
        //$url .= "?projectName=".$data['projectName']."&environment=".$data['environment']."&database=".$data['database']."&oper=".$data['oper']."&sql=".urlencode($data['sql']);

        //$request = $this->get( $url );
        
        $content = json_decode($request);

        if ($content->success && $content->failed == false) {
            $result['data'] = $content->data;
        } else {
            $result['error'] = $content->statusText;
        }
        return $result;
    }

    /**d
     * DDL操作执行分库分表脚本
     * @param  [Array] $data [description]
     * @return [Array]       [description]
     */
    public function execute_ddl_sharding( $data )
    {
        $data['sql'] = str_replace("\r\n", " ", $data['sql']);
        $data['sql'] = str_replace("`", "", $data['sql']);
        
        $params = $data['dbName'] . " \"" . $data['rule'] . "\" \"" . $data['sql'] . "\"";
        $shell_path = self::shell_ddl_sharding . " " . $params;

        try {
            system($shell_path, $code);
        } catch ( \Exception $e) {
            $result['error'] = "脚本执行错误：" . $e->getMessage();
        }
        //Output::error($shell_path);
        if ( $code == 100 ) {
            $result['data'] = '执行成功';
        } elseif ( $code == 101 ) {
            $error_log = "/data/sharding/".$data['dbName']."/log/opt_ddl_sharding.err";
            $error = $this->get( $error_log );
            $result['error'] = $data['sql'] . "执行错误：" . $error;
        } elseif ( $code == 102 ) {
            $result['error'] = "脚本执行错误：" . $data['sql'] ." 不允许的操作";
        } elseif ( $code == 103 ) {
            $result['error'] = "脚本执行错误：缺少对应的参数";
        } else {
            $result['error'] = "脚本执行错误：该脚本不可被执行";
        }
        
        return $result;
    }

    /**
     * 执行DDL通用库脚本
     * @param  [type] $ip     [description]
     * @param  [type] $dbName [description]
     * @param  [type] $sql    [description]
     * @return [type]         [description]
     */
    public function execute_ddl_common( $ip, $dbName, $sql )
    {
        $sql = str_replace("`", "", $sql); //过滤反引号，反引号在shell脚本中特殊存在
        $ip_array = explode(":", $ip);
        $port = empty( $ip_array[1] ) ? '3306' : $ip_array[1];
        $params = $dbName . " " . $ip_array[0] . ":" . $port . " \"" . $sql . ";\"";

        $shell_path = self::shell_ddl_common . " " . $params;

        system($shell_path, $code);
        if ( $code == 100 ) {
            $result['data'] = '执行成功';
        } elseif ( $code == 101 ) {
            $error_log = "/data/sharding/".$dbName."/log/opt_ddl_common.err";
            $error = $this->get( $error_log );
            $result['error'] = $sql . "执行错误：" . $error;
        } elseif ( $code == 102 ) {
            $result['error'] = "脚本执行错误：" . $sql ." 不允许的操作";
        } elseif ( $code == 103 ) {
            $result['error'] = "脚本执行错误：缺少对应的参数";
        } else {
            $result['error'] = "脚本执行错误：该脚本不可被执行";
        }
        
        return $result;
    }

    /**
     * 获取分库分表规则
     * @param  [type] $ip   [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function getShardingRule( $data )
    {
        $url = self::host . "/db/rule.json";
        $url .= "?projectName=".$data['projectName']."&environment=".$data['environment']."&database=".$data['database'];
        $request = $this->get( $url);
        $content = json_decode( $request );

        $sql = str_replace("\r\n", " ", $data['sql']);
        preg_match_all( "/^(create|alter|drop)\s+(table|view){1}\s+(if\s+not\s+exists|if\s+exists)?\s?([0-9a-zA-Z_.`]+)/", strtolower($sql), $tb_name_array );
        //$action = strtok( rtrim($data['sql']), ' ' );
        //var_dump($tb_name_array);exit;
        @$sql_table_name = str_replace("`", "",end( $tb_name_array )[0]);
        //输入的SQL语句中表名之前可能加有库名
        @$sql_table = explode(".", $sql_table_name);
        if ( count( $sql_table ) > 1 ) {
            @$sql_db_name = $sql_table[0];
            @$sql_table_name = $sql_table[1];
        }

        if ( !empty( $sql_db_name ) && $sql_db_name != $data['database'] ) {
            $result['error'] = "SQL语句中输入的库名必须和选择的库名一致！";
        }else {
            //判断获取分库分表规则是否返回成功
            if( $content->success && $content->failed == false ){
                $result_data = json_decode($content->data);
                //var_dump($result_data);
                if ( !empty( $result_data ) ) { // 判断data值是否为空
                    $tbInfoList = $result_data->tbInfoList; // 获取表列表信息

                    if ( !empty( $tbInfoList ) ) {
                        foreach ($tbInfoList as $key => $value) {
                            $tbName = $value->tbName; //主表名
                            $tb_name = explode("#", $tbName);

                            $detailTBName = $value->detailTBName; // 从表
                            $d_tbname_array = array(); // 从表数组
                            if ( !empty( $detailTBName ) ) {
                                $d_tbname = explode(",", $detailTBName);
                                foreach ($d_tbname as $k => $v) {
                                    $d_tbname_ = explode("#", $v);
                                    $d_tbname_array[] = $d_tbname_[0];
                                }
                            }
                            
                            if ( $tb_name[0] == $sql_table_name || in_array( $sql_table_name, $d_tbname_array ) ) {
                                $result['tbInfo'] = $value;
                                $rule_s = 1;
                                break;
                            } else {
                                $rule_s = 0;
                            }
                        }
                        if ( $rule_s == 1 ) {
                            $result['dbInfoList'] = $result_data->dbInfoList;
                        } else {
                            $result['error'] = "表". $sql_table_name. "不在分库分表规则配置文件中，请先配置分库分表规则";
                        }
                        
                    } else {
                        $result['error'] = "分库分表接口返回数据为空！";
                    }
                } else {
                    $result['error'] = "分库分表接口返回数据为空！";
                }
            }else{
                $result['error'] = $content->statusText;
            }
        }
        return $result;
    }

    /**
     * 获取分库分表规则中所有的表名
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function getDbList( $data )
    {
        $url = self::host . "/db/rule.json";
        $url .= "?projectName=".$data['projectName']."&environment=".$data['environment']."&database=".$data['database'];
        $request = $this->get( $url);
        $content = json_decode( $request );

        $dblist = array();

        if( $content->success && $content->failed == false ){
            $result_data = json_decode($content->data);
            //var_dump($result_data);
            if ( !empty( $result_data ) ) {
                $tbInfoList = $result_data->tbInfoList; // 获取表列表信息

                if ( !empty( $tbInfoList ) ) {
                    foreach ($tbInfoList as $key => $value) {
                        $tbName = $value->tbName; //主表名
                        $tb_name = explode("#", $tbName);
                        if ( !in_array( $tb_name[0], $dblist ) ) {
                            $dblist[] = $tb_name[0];
                        }

                        $detailTBName = $value->detailTBName; // 从表
                        if( !empty( $detailTBName ) ){
                            $d_tbname = explode(",", $detailTBName);
                            foreach ($d_tbname as $k => $v) {
                                $d_tbname_ = explode("#", $v);
                                if ( !in_array( $d_tbname_[0], $dblist ) ) {
                                    $dblist[] = $d_tbname_[0];
                                }
                            }
                        }
                    }
                    $result['list'] = $dblist;
                } else {
                    $result['error'] = "分库分表接口返回数据为空！";
                }
            } else {
                $result['error'] = "分库分表接口返回数据为空！";
            }
        }else{
            $result['error'] = $content->statusText;
        }
        return $result;
    }

    /**
     * 验证前端表单内容的合法性
     * @param  [type] $db_name     [数据库名]
     * @param  [type] $environment [description]
     * @param  [type] $sqlinfo_all [description]
     * @param  [type] $batch       [description]
     * @param  [type] $batch_notes [description]
     * @return [type]              [description]
     */
    public function check_sql( $db_name, $environment, $sqlinfo_all, $batch, $batch_notes){

        //过滤某些不允许的操作
        if (preg_match('/databases/i', strtolower($sqlinfo_all))
            || preg_match('/sleep/i', strtolower($sqlinfo_all))
            || preg_match('/\s+count.*\s+user\s*/i', strtolower($sqlinfo_all))
            || preg_match('/\s+count\(.*\s+user_info\s*/i', strtolower($sqlinfo_all))
            || preg_match('/\s+count\(.*\s+qccr\.user\s*/i',strtolower($sqlinfo_all))
            || preg_match('/\s+count\(.*\s+membercenter\.user_info\s*/i', strtolower($sqlinfo_all))
            || preg_match('/.*--.*/i', strtolower($sqlinfo_all))
            || preg_match('/\s+limit\s+/', strtolower($sqlinfo_all))
        ) {
            Output::error('输入内容不合法请检查！');
        }

        //灰度与线上的数据查询增加对敏感金额数据的统计限制 ----------S-----------
        if( $db_name == 'oms' ){
            $preg = "/\s+(count|sum|avg)\(\s*(suggest_price|evaluate)\s*\)\s+\w+\s+(realtime_inventory|opening_inventory)\s*/";
            if( preg_match( $preg, strtolower($sqlinfo_all) ) ){
                Output::error('敏感金额数据的统计已做限制！');
            }
        }

        if( $db_name == 'ordercenter' ){
            $preg = "/\s+(count|sum)\(\s*(market_cost|original_cost|sale_costreal_cost|market_cost|original_cost|sale_cost|coupon_apportion|market_cost|orig_cost|real_cost|sprice|signed_sprice|award_sprice|store_award_sprice|coupon_apportion|original_cost|sale_cost)\s*\)\s+\w+\s+(orders|order_goods|order_server|goods_sku_order)\s*/";
            if( preg_match( $preg, strtolower($sqlinfo_all) ) ){
                Output::error('敏感金额数据的统计已做限制！');
            }
        }
        //-----------E-------------
        //
        //防呆限制
        if( preg_match('/\s*delete\s+/', strtolower($sqlinfo_all)) ){ // 如果能够匹配到delete
            if( !preg_match( '/\s*delete\s+([\s\S]*)\s+where\s+\w+([\s\S]*)/', strtolower($sqlinfo_all) ) ){ //匹配delete语句后面是否带有where条件
                Output::error('delete语句必须带有where条件！');
            }
        }
        if( preg_match('/\s*update\s+/', strtolower($sqlinfo_all)) ){ // 如果能够匹配到delete
            if( !preg_match( '/\s*update\s+([\s\S]*)\s+where\s+\w+([\s\S]*)/', strtolower($sqlinfo_all) ) ){ //匹配delete语句后面是否带有where条件
                Output::error('update语句必须带有where条件！');
            }
        }

        //操作限制
        if( preg_match( '/\s*alter\s+([\s\S]*)\s+(drop|change)\s*/', strtolower($sqlinfo_all) ) ){
            //Output::error('不能删除字段和修改字段名！');
        }

        if( preg_match( '/\s*select\s+([\s\S]*)\s+(avg|AVG)\s*/', strtolower($sqlinfo_all) ) ){
            Output::error('查询语句不能使用求平均值AVG函数！');
        }

        if( preg_match( '/\s+union\s+/', strtolower($sqlinfo_all) ) ){
            Output::error('不能使用union查询！');
        }

        $end_char = substr($sqlinfo_all, -1);
        if ($end_char !== ';') {
            Output::error('请输入完整的sql，并以英文分号;结尾！');
        }
        $sqlinfo_all = ';' . $sqlinfo_all . '#';
        $rule_key_words = 'select|delete|insert|update|drop|create|alter|rename|truncate|explain|optimize|show|analyze|desc|describe';
        //第一次分割规则，连同注释和SQL一起
        $rule = "(?:#|$rule_key_words)";
        //第二次分割，把SQL分割出来
        $rule2 = "(?:$rule_key_words)";

        preg_match_all("/;((?:\s|\r\n)*" . $rule . ")[\s\S]*?(?=;(?:\s|\r\n)*" . $rule . ")/i", $sqlinfo_all, $info);
        $info = $info[0];
        $nextadd = '';

        if( empty( $info ) ){
            Output::error('注释格式不正确，请以#开始！');
        }

        //有批量注释的情况
        if(1 == $batch){
            
            if(empty($batch_notes))
                Output::error('请输入批量注释内容或取消批量注释！');
            $batch_first_char = substr($batch_notes,0,1);
            //var_dump($batch_first_char);exit;
            if ($batch_first_char !== '#') {
                Output::error('请在注释前加上#');
            }
            //检测是否为同一类型关键字操作
            $keywordB = '';
            foreach ($info as $key => $sqlinfoB) {
                $sqlinfoB .= "\r\n";
                $notesB = '';
                if (preg_match("/#.*?(?:\r\n)/i", $sqlinfoB) == 1) {
                    preg_match_all("/#[\s\S]*?(?=\r\n(?:\s|\r\n)*" . $rule2 . ")/i", $sqlinfoB, $notesB);
                    //print_r($notes);exit;
                    $notesB = @$notesB[0][0];
                    $notesB = str_replace("\r\n", '', $notesB);
                }
                //有注释的则跳过
                if (!empty($notesB)) {
                    continue;
                }else{
                    //如果为insert，update，delete，alter中一种则进行判断是否穿插使用
                    if (preg_match_all("/insert|update|delete|alter?\s/i",$sqlinfoB,$keyword)) {
                        if($keywordB == ''){
                            $keywordB = $keyword[0][0];
                        }elseif(!empty($keywordB) && $keywordB != $keyword[0][0]){
                            Output::error('请对同一类型的操作进行批量注释！只能为insert,update,delete,alter其中一种');
                        }
                    //如果没有，则报错，提示需要单独使用批量功能
                    }else{
                        Output::error('当前只支持insert,update,delete,alter操作进行批量注释！');
                    }
                }
            }
        }
        return $info;
    }


    public function get( $url )
    {
        $client = new Client();
        $result = $client->createRequest()
                ->setMethod('get')
                ->setUrl($url)
                ->setHeaders(['content-type' => 'application/json'])
                ->send()->getContent();

        return $result;
    }

    public function post( $url, $data )
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

    /**
     * 获取该用户角色所对应的服务器的环境environment
     * @return [type] [description]
     */
    public function getRoleEnvironment()
    {
        $auth = Yii::$app->authManager;
        $role = $auth->getRolesByUser(Yii::$app->users->identity->id);

        $roles = array();
        if ( !empty( $role ) ) {
            foreach ($role as $key => $value) {
                $server = AuthItemServers::findByItemName( $value->name );
                $environment = $server->environment;
                if( !empty($environment) ){
                    $environment_array = explode(',', $environment);
                    foreach ($environment_array as $v) {
                        if ( !in_array( $v, $roles ) ) {
                            $roles[] = $v;
                        }
                    }
                    
                }
            }
        }
        
        return $roles;
    }

    /**
     * 获取用户角色所对应的分库分表的SQL操作权限
     * @return [type] [description]
     */
    public function getRoleOperations( $environment )
    {
        $auth = Yii::$app->authManager;
        $role = $auth->getRolesByUser(Yii::$app->users->identity->id);

        $roles = array();
        if ( !empty( $role ) ) {
            foreach ($role as $key => $value) {
                $server = AuthItemServers::findByItemName( $value->name );
                $sharding_operations = $server->sharding_operations;
                $sharding_environment = $server->environment;
                $sharding_environment_array = explode(',', $sharding_environment);

                //如果环境不为空并且当前环境在环境集中
                if( !empty( $sharding_environment ) && in_array( $environment, $sharding_environment_array ) && !empty($sharding_operations) ){
                    $sharding_operations_array = explode(',', $sharding_operations);
                    foreach ($sharding_operations_array as $v) {
                        if ( !in_array( $v, $roles ) ) {
                            $roles[] = $v;
                        }
                    }
                }
            }
        }
        
        return $roles;
    }

    /**
     * 判断是否SQL语句中的表是否在分库分表规则中
     * @param  [type]  $project     [项目名称]
     * @param  [type]  $environment [环境]
     * @param  [type]  $dbName      [数据库名]
     * @param  [type]  $sql         [SQL语句]
     * @return boolean              [description]
     */
    public function tableIsInShardingDbList( $project, $environment, $dbName, $sql ){
        $data['projectName'] = $project;
        $data['environment'] = $environment;
        $data['database'] = $dbName;

        $request = $this->getDbList($data);
        if ( isset( $request['error'] ) ) {
            return $result['error'] = $request['error'];
        }else{
            $dblist = $request['list'];
            @$sql = str_replace("\r\n", " ", $sql);
            
            preg_match_all( "/^(create|alter|drop)\s+(table|view){1}\s+(if\s+not\s+exists|if\s+exists)?\s?([0-9a-zA-Z_.`]+)/", strtolower($sql), $tb_name_array );

            @$sql_table_name = str_replace("`", "", end( $tb_name_array )[0]);
            //输入的SQL语句中表名之前可能加有库名
            @$sql_table = explode(".", $sql_table_name);
            @$tbName = $sql_table[1] ? $sql_table[1] : $sql_table[0];

            if ( in_array( $tbName, $dblist ) ) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 获取分库分表下公共库信息
     * @param  [type] $project     [description]
     * @param  [type] $environment [description]
     * @param  [type] $dbName      [description]
     * @return [type]              [description]
     */
    public function getCommonInfo( $project, $environment, $dbName ){
        $url = self::host . "/db/rule.json";
        $url .= "?projectName=".$project."&environment=".$environment."&database=".$dbName;
        $request = $this->get( $url);
        $content = json_decode( $request );

        //判断获取分库分表规则是否返回成功
        if( $content->success && $content->failed == false ){
            $result_data = json_decode($content->data);
            //var_dump($result_data);exit;
            if ( !empty( $result_data ) ) {
                $commonDBInfo = $result_data->commonDBInfo;
                if ( !empty( $commonDBInfo ) ) {
                    $result['masterIP'] = $commonDBInfo->masterIP;
                } else {
                    $result['error'] = "分库分表接口返回公共库数据为空！";
                }
            } else {
                $result['error'] = "分库分表接口返回数据为空！";
            }
        }
        return $result;
    }

    /**
     * 获取分库分表信息
     * @return [type] [description]
     */
    public function getShardingList(){
        // $result = Yii::$app->redis->get("tools:sharding_list");
        // if( empty( $result ) ){
        //     $url = self::host . "/db/list.json";
        //     $result = $this->get( $url );
        //     Yii::$app->redis->set("tools:sharding_list", $result);
        // }
        $url = self::host . "/db/list.json";
        $result = $this->get( $url );
        return $result;
    }

    /**
     * 更新分库分表缓存
     * @return [type] [description]
     */
    public function actionUpdateShardingRedis(){
        $url = self::host . "/db/list.json";
        $result = $this->get( $url );
        Yii::$app->redis->set("tools:sharding_list", $result);
    }
	
	/**
     * 检查当前账户是否有Administrator角色
     * @return [type] [description]
     */
	public function is_Administrator()
	{
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(Yii::$app->users->identity->id);
        if( empty( $roles['Administrator'] ) ){
            return false;
        }
		return true;
	}


    public function connectDb(){
        //组合数据库配置
        //$connect_config['dsn'] = "mysql:host=localhost;dbname=tools";
        $connect_config['dsn'] = "mysql:host=127.0.0.1;dbname=webdb";
        $connect_config['username'] = Yii::$app->params['MARKET_USER'];
        $connect_config['password'] = Yii::$app->params['MARKET_PASSWD'];
        $connect_config['charset'] = Yii::$app->params['MARKET_CHARSET'];

        //数据库连接对象
        $executeConnection = new \yii\db\Connection((Object)$connect_config);
        return $executeConnection;
    }

    public function checkSqlIsTrue( $sql ){
        $executeConnection = $this->connectDb();
        $command = $executeConnection->createCommand($sql);
        try {
            if(preg_match('/^select\s+/i', rtrim($sql))){
                $excute_result = $command->queryAll();
            }else{
                $excute_result = $command->execute();
            }
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        
    }

    /**
     * 检查临时授权
     * @param  [type] $environment [环境]
     * @param  [type] $database  [数据库名]
     * @param  [type] $type      [SQL类型]
     * @return [boolean]         [false 未匹配到对应的授权，true 通过授权]
     */
    public function checkAuthorize( $environment, $database, $type ){
        $username = Yii::$app->users->identity->username;
        $time = date("Y-m-d H");
        $authorize = Authorize::find()->where(['environment' => $environment, 'username' => $username, 'type' => 'sharding'])->andWhere(['>=', 'stop_time', $time])->asArray()->all();

        if( !empty( $authorize ) ){ //如果没有查询到授权列表
            foreach ($authorize as $key => $value) {
                $db_name = $value['db_name'];
                $sqloperation = $value['sqloperation'];

                if( !empty( $db_name ) ){ //如果临时授权信息中数据库名称不为空，则继续往下判断

                    if( in_array( $database, explode(",", $db_name) ) ){ //如果操作的数据在临时授权信息中，则继续往下判断

                        if ( !empty( $sqloperation ) ) { //如果临时授权信息中SQL类型不为空，则继续往下判断

                            if( in_array( $type, explode(",", $sqloperation) ) ){  //如果操作的SQL类型在临时授权信息中，则继续往下判断
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }
}
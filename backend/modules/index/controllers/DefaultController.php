<?php
namespace backend\modules\index\controllers;

use backend\server\RedisServerWeb;
use backend\server\RedisBaseServer;
use backend\server\SqlParserServer;
use backend\server\DbServer;

use backend\modules\projects\models\Projects;
use common\models\AuthItemServersDbs;
use Yii;
use backend\controllers\SiteController;
//服务器模型
use common\models\Servers;
//DML及DDL模型
use common\models\ExecuteLogs;
//查询模型
use common\models\QueryLogs;

use backend\modules\operat\models\Select;
use backend\modules\operat\models\SelectWhite;
use backend\modules\operat\models\Authorize;

use backend\modules\logs\models\Version;
use backend\modules\logs\models\DdlConfigs;

use vendor\twl\tools\utils\Output;
use vendor\twl\api\jobCenter\JobCenter;
use yii\base\Exception;
use yii\db\Query;

class DefaultController extends SiteController
{

    public function actionHome()
    {
        return $this->render('home');
    }

    /**
     * 后台首页，数据库操作页面
     * @return string
     */
    public function actionIndex()
    {
        //获取服务器列表
        $server_list = Servers::find()->where(['status' => 1])->orderBy('server_id')->asArray()->all();
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
        
        foreach($server_list as $value){
            $return[$value['server_id']] = $value;
        }
        $server_list = $return;unset($return);

        $project_list = Projects::find()->where(['status' => 1])->orderBy('pro_id')->asArray()->all();
        $default_pro_id = \Yii::$app->session->get('default_pro_id');
        if(!empty($default_pro_id)){
            foreach($project_list as $key=>$val){
                if($default_pro_id != $val['pro_id'])
                    unset($project_list[$key]);
            }
        }
        //传给视图
        $data['project_list'] = $project_list;
        $data['allPrivilege'] = $this->servers;
        $server_list_new = array();
        foreach($server_list as $key=>$value){
            if(isset($this->servers['privilege'][$value['ip']])){
                $server_list_new[$value['ip']] = $value;
            }
        }
        $data['server_list'] = $server_list_new;

        //服务器环境查询条数限制
        @$environment_rule = Select::find()->select(['dev', 'dev_trunk', 'test', 'test_trunk', 'pro', 'pre'])->asArray()->one();
        $data['environment_rule'] = json_encode($environment_rule);

        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(Yii::$app->users->identity->id);
        $data['is_administrator'] = !empty( $roles['Administrator'] ) ? true : false;

        $data['version'] = Version::find()->select(['version_title', 'version_log'])->orderBy('id desc')->asArray()->one();
        
        return $this->render('index',$data);
    }
    

    /**
     * 执行SQL
     */
    public function actionExecute()
    {
        //获取数据
        @$db_name = rtrim($_REQUEST['DBName']);
        @$server_ip = rtrim($_REQUEST['DBHost']);
        @$sqlinfo = rtrim($_REQUEST['sqlinfo']);
        //@$is_limit = rtrim($_REQUEST['is_limit']);
        @$is_limit = 1;//强制设置查询条数限制

        @$pro_id = !empty($_REQUEST['project'])?rtrim($_REQUEST['project']):0;
        @$batch = rtrim($_REQUEST['batch']);
        @$batch_notes = rtrim($_REQUEST['batch_notes']);
        @$is_formal = rtrim($_REQUEST['is_formal']);

        //查询条数限制
        @$environment_rule = Select::find()->asArray()->one();
        

        if(empty($server_ip))
            Output::error('请选择目标服务器！');
        if(empty($db_name))
            Output::error('请选择目标数据库！');
        
        if( REDIS_STATUS == 0 ){
            $redis = new RedisServerWeb();
        }
        $dbserver = new DbServer();

        $sqlParser = new SqlParserServer();
        //获取服务器下所有的库名
        $db_list = isset($this->servers['privilege'][$server_ip]) ? array_keys($this->servers['privilege'][$server_ip]) : [];

        //获取服务器信息
        $server = $this->check_server( $server_ip, $db_name );
        $server_id = $server['server_id'];

        //组合检测非法操作数据库正则
        $db_regex = '/';
        $db_keyword = array('from','into','update','on','table');

        foreach($db_list as $val){
                foreach($db_keyword as $db_val){
                    $db_regex .= "($db_val(?:\s|\r\n)+{$val}\.)|";
                }
        }
        $db_regex = substr($db_regex, 0, strlen($db_regex) - 1) . '/i';

        //根据服务器环境读取默认查询语句的执行条数
        $environment = !empty($server['environment']) ? $server['environment'] : 'dev';
        $nums = $sqlParser->getSelectWhite( $db_name, $environment );

        if(empty($pro_id) && $environment != "pro" ){
            Output::error('请选择项目！');
        }else if( $environment == "pro" ){
            $pro_id = 1;
            $project = 'pro_project';
        }else{
            $project_info = Projects::find()->select(['name'])->where(['pro_id' => $pro_id])->asArray()->one();
            $project = $project_info['name'];
        }

        //数据库连接对象
        $executeConnection = $sqlParser->connectDb( $server_ip, $db_name );

        //将SQL语句分割成SQL数组
        $sql_list = $sqlParser->separateSql( $sqlinfo );
        if( isset( $sql_list['error'] ) ){
            Output::error( $sql_list['error'] );
        }

        //判断批量注释是否合法
        if( $batch == 1 ){
            $check_batch = $sqlParser->checkBatchNote( $sql_list, $batch_notes );
            if( isset( $check_batch['error'] ) ){
                Output::error( $check_batch['error'] );
            }
        }

        //开始循环执行sql
        $contents = array();
        $ddl_num = 0; //计算DDL执行条数
        foreach ($sql_list as $key => $sqlinfoA) {
            if (!empty($nextadd)) {
                $sqlinfoA = $nextadd . $sqlinfoA;
                $nextadd = '';
            }
            
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
                    $contents[$key]['sql'] = $sqlinfoA;
                    $contents[$key]['msg'] = '注释长度必须20个英文字符以上';
                    continue;
                }
            } else {
                if (preg_match(Yii::$app->params['regexp']['note_same'], $sqlinfoA)) {
                    if (1 == $batch) {
                        $notes = $batch_notes;
                    } else {
                        $contents[$key]['sql'] = $sqlinfoA;
                        $contents[$key]['msg'] = 'DDL与DML语句必须添加8个字符以上注释，格式：# 注释1234';
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
            if( preg_match($preg, strtolower($sqlinfoA)) ){
                continue;
            }

            if ($sqlinfoA == ";\r\n") {
                $nextadd = $notes;
                continue;
            }

            $sqlinfoA = trim($sqlinfoA);
            $sqlinfoA .= ";#";

            preg_match_all(Yii::$app->params['regexp']['sqlinfoA_sql'],$sqlinfoA, $sql);
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

            if (empty($sqlinfoA) || preg_match('/^\/\//i', $sqlinfoA) || preg_match('/^#/i', $sqlinfoA)) {
                continue;
            }

            $sql_type = $sql_info['sql_type'];
            $sql_action = $sql_info['sql_action'];

            //if( !in_array($environment, array('pre', 'pro')) && $sql_type == 'DQL' ){
                //非线上环境，并且SQL类型为DQL时跳过权限
            //}else{
                if( substr($sqlinfoA, -1) == '#'){
                    $sqlinfoA_bak = substr( $sqlinfoA , 0, -1);
                }else{
                    $sqlinfoA_bak = $sqlinfoA;
                }
                //获取SQL语句中的表名
                $db_table = $sqlParser->getSqlTable($sqlinfoA_bak);
                if( !empty( $db_table ) ){ //如果返回的有表名
                    foreach ($db_table as $dbk => $dbv) {
                        $db_table_name_ = explode(".", $dbv);
                        $db_name_ = !empty($db_table_name_[1]) ? $db_table_name_[0] : $db_name;
                        $table_name_ = !empty($db_table_name_[1]) ? $db_table_name_[1] : $db_table_name_[0];
                        $sql_execute_result = $this->checkTableAuthorize( $db_name_, $table_name_, $server_ip, $sql_type );

                        if( !$sql_execute_result ){
                            $contents[$key]['sql'] = $sqlinfoA;
                            $contents[$key]['msg'] = '你没有'.$db_name_.'数据库中'.$table_name_.'表的'.$sql_type.'操作权限！请联系管理员授权';
                            break;
                        }
                    }
                    if( !$sql_execute_result ){
                        continue;
                    }
                }

                if(@empty($sql_type) || !in_array($sql_type,$this->servers['operations'])){

                    if( $this->checkAuthorize( $server_id, $db_name, $sql_type ) == false ){ //检查临时授权
                        $contents[$key]['sql'] = $sqlinfoA;
                        $contents[$key]['msg'] = '你没有数据库'.$db_name.'的'.$sql_type.'操作权限！请联系管理员解决';
                        continue;
                    }
                }
                
            //}

            $label_sql_operation = false;
            foreach(Yii::$app->params['regexp']['sql_operation'] as $val_sql_opration){
                if(preg_match('/^show\s+/i', rtrim($sqlinfoA))) {
                    $label_sql_operation = true;
                    break;
                }
            }
            if ($label_sql_operation) {
                if (strrpos(rtrim($sqlinfoA), ';') == (strlen(rtrim($sqlinfoA)) - 2)) {
                    $sqlinfoA = substr(rtrim($sqlinfoA), 0, -2);
                }
            } else {
                if (strrpos(rtrim($sqlinfoA), ';') == (strlen(rtrim($sqlinfoA)) - 2)) {
                    $sqlinfoA = substr(rtrim($sqlinfoA), 0, -2);
                }
            }

            if ($label_sql_operation) {
                if (strrpos(rtrim($sqlinfoA), ';') == (strlen(rtrim($sqlinfoA)))) {
                    $sqlinfoA = substr(rtrim($sqlinfoA), 0, -1);
                }
            }
            //检测SQL防呆限制、脱敏
            $sql_rule = $sqlParser->checkSqlRule( $sqlinfoA, $db_name );
            if( isset( $sql_rule['error'] ) ){
                $contents[$key]['sql'] = $sqlinfoA;
                $contents[$key]['msg'] = $sql_rule['error'];
                continue;
            }

            $sql_change = 0;//SQL重组 0、无重组，1、有重组
            //如果是select查询，则检查limit查询条数
            if( $sql_action == 'select' ){

                //此处判断线上环境的数据库表是否在查询条数限制的白名单里
                preg_match_all(Yii::$app->params['regexp']['get_select_table'], strtolower($sqlinfoA), $table_preg);
                @$table_name = end($table_preg)[0];

                //输入的SQL语句中表名之前可能加有库名
                @$sql_table = explode(".", $table_name);
                if ( count( $sql_table ) > 1 ) {
                    @$table_name_all = str_replace("`", "", $sql_table[0]) . "." . $sql_table[1];//替换库中的反引号`
                }else{
                    @$table_name_all = $db_name . "." . $table_name;//获取带库名的表名，比如 membercenter.user_info
                }

                //获取查询条数限制的白名单
                
                $white_list = str_replace("\r", "", $environment_rule['white_list']);
                $white_list = str_replace("\n", "", $white_list);
                $white_list = str_replace(" ", "", $white_list);
                
                @$white_list_array = explode(",", $white_list);
                if ( $environment == 'pro' && in_array( $table_name_all, $white_list_array ) ) {
                    //线上环境，并且在白名单列表中
                    $nums = (int)$environment_rule['white_list_num'] ? $environment_rule['white_list_num'] : $nums;
                }


                $sql_limit = $sqlParser->getSelectLimit( $sqlinfoA, $nums );
                if( $sql_limit === false ){
                    $sqlinfoA .= ' limit 0, '.$nums;
                }else if( is_string( $sql_limit ) ){ //返回重新组装的SQL
                    $execute_sql = $sql_limit;
                    $sql_change = 1;
                }
            }

            $sqlinfo = $sqlinfoA;

            //过滤某些没有操作的数据库名+.的操作
//            if (!preg_match($db_regex, $sqlinfo)) {
//                $contents[$key]['sql'] = $sqlinfo;
//                $contents[$key]['msg'] = "没有此数据库的操作权限！";
//                continue;
//            }

            try {
                $start_time = explode(' ',microtime());
                if( $sql_change == 1 ){
                    $command = $executeConnection->createCommand($execute_sql);
                }else{
                    $command = $executeConnection->createCommand($sqlinfo);
                }
                
                if( $sql_type == 'DQL' ){
                    $is_query = 1;
                    $excute_result_list = $command->queryAll();
                    //Output::error($excute_result_list);
                    $excute_result = array();
                    foreach ($excute_result_list as $er_key => $er_value) {
                        foreach ($er_value as $er_k => $er_v) {
                            $excute_result[$er_key][$er_k] = htmlspecialchars( $er_v );
                        }
                        
                    }
                }else{
                    $excute_result = $command->execute();
                    $excute_num = $excute_result > 0 ? $excute_result:0;
                    $is_query = 0;
                }
                //计算执行时间
                $end_time = explode(' ',microtime());
                $thistime = $end_time[0]+$end_time[1]-($start_time[0]+$start_time[1]);
                $thistime = round($thistime,3);
            } catch (\Exception $e) {
                $contents[$key]['sql'] = $sqlinfo;
                $contents[$key]['msg'] = "数据库操作失败: 请检查sql语句:".$e->getMessage();
                continue;
            }


            if (preg_match(Yii::$app->params['regexp']['dml_ddl_sql'],$sqlinfoA)) {
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

            if( $environment == 'pre' && $sql_type == 'DDL' ){
                preg_match_all( "/^(create|alter|drop)\s+(table|view){1}\s+(if\s+not\s+exists|if\s+exists)?\s?([0-9a-zA-Z_.`]+)/", strtolower($sqlinfo), $tb_name_array );

                @$sql_table_name = str_replace("`", "", end( $tb_name_array )[0]);
                //输入的SQL语句中表名之前可能加有库名
                @$sql_table = explode(".", $sql_table_name);
                @$database = $sql_table[1] ? $sql_table[0] : $db_name;
                @$tbName = $sql_table[1] ? $sql_table[1] : $sql_table[0];

                if( $this->isInDdlConfig( $database, $tbName ) ){
                    $ddl_num++; //执行成功后DDL执行条数+1
                }
            }

            if( $sql_action == 'create' || $sql_action == 'drop' ){ //更新redis缓存
                $conn = $this->connectDb($server_ip, $db_name);
                $result = $conn->createCommand("show tables;")->queryAll();
                $tb_list = [];
                foreach($result as $value){
                    $tb_list[] = $value['Tables_in_'.$db_name];
                }
                if( REDIS_STATUS == 0 ){
                    $redis->hmset($server_ip, $db_name, 'tables', implode(",", $tb_list));
                }
                $dbserver->hmset($server_ip, $db_name, 'tables', implode(",", $tb_list));
            }


            if ($is_query == 0) {
                $msg = "执行成功，受影响：[".$excute_num."]行，耗时：[" . $thistime . "s]";
            } else {
                $msg = "执行成功，当前返回：[".$rows."]行，耗时：[" . $thistime . "s]";
                if( $sql_change == 1 ){
                    $msg .= '，本次最多只能查询' . $nums . '条数据，如需查询更多，请联系DBA授权！';
                }
            }


            if (1 == $is_query) {
                $SaveSQL = new QueryLogs;
            } else {
                $SaveSQL = new ExecuteLogs;
                $SaveSQL->notes = $notes;

                $action = strtolower(strtok( rtrim($sqlinfo), ' ' ));
                if ( $action == 'insert' || $action == 'update' || $action == 'delete' ) {
                    $SaveSQL->sqloperation = 'dml';
                } else if( $action == 'create' || $action == 'alter' || $action == 'drop' || $action == 'truncate' ) {
                    $SaveSQL->sqloperation = 'ddl';
                }

                $SaveSQL->is_formal = $is_formal ? 1 : 0; //是否是正式脚本
            }
            $SaveSQL->user_id = Yii::$app->users->identity->id;
            $SaveSQL->host = $server_ip;
            $SaveSQL->database = $db_name;
            $SaveSQL->script = $sqlinfo;
            $SaveSQL->result = $sqlresult;
            $SaveSQL->status = $sqlstatus;
            $SaveSQL->pro_id = $pro_id;
            $SaveSQL->project_name = $project;
            $SaveSQL->server_id = $server_id;
            $SaveSQL->environment = $environment ? $environment : 'dev';
            $result = $SaveSQL->save();

            if($result !==true){
                $contents[$key]['sql'] = $sqlinfo;
                $contents[$key]['msg'] = '数据库操作失败';
                continue;
            }

            if ($excute_result !== true) {
                $contents[$key]['sql'] = $sqlinfo;
                $contents[$key]['msg'] = $msg;
                $contents[$key]['excute_result'] = $excute_result;
            }

        }

        //预发环境执行了DDL操作时，消息发送给大数据组
        if( $environment == 'pre' && $ddl_num > 0 ){
            $ddl_msg = "DBA在 " . date("Y-m-d H:i:s") . " 执行DDL操作，共计 " . $ddl_num . " 条，请登录WEBDB平台查看";
            $this->sendDdlMsg( $ddl_msg );
        }
        Output::success('操作成功',$contents);
    }

    /**
     * 导出操作记录
     * @return string
     */
    public function actionExport()
    {

        //$request = Yii::$app->request;
        @$action=rtrim($_REQUEST['action']);
        //导出执行
        if ($action > 0) {
            $user_id = Yii::$app->users->identity->id;
            @$server_id=rtrim($_REQUEST['server_id']);
            @$database=rtrim($_REQUEST['database']);
            @$startTime=trim($_REQUEST['startTime']);
            @$endTime=trim($_REQUEST['endTime']);
            @$pro_id=trim($_REQUEST['pro_id']);
            //检测权限
            $this->check_server_permission($server_id,$database);
            switch ($action){
                case '1':
                    $table = 'query_logs';
                    $file_name = 'QUERY_'.date('YmdHis');
                    $where = '';
                    break;
                case '2':
                    $table = 'execute_logs';
                    $file_name = 'DML_'.date('YmdHis');
                    $where = " AND (script COLLATE utf8_general_ci LIKE 'insert%' OR  script COLLATE utf8_general_ci LIKE 'update%' OR script COLLATE utf8_general_ci LIKE 'delete%')";
                    break;
                case '3':
                    $table = 'execute_logs';
                    $file_name = 'DDL_'.date('YmdHis');
                    $where = " AND (script COLLATE utf8_general_ci NOT LIKE 'insert%' AND script COLLATE utf8_general_ci NOT LIKE 'update%' AND script COLLATE utf8_general_ci NOT LIKE 'delete%')";
                    break;
                default:
                    Output::error('非法操作',3);
            }

            if($startTime){
                $time1 = strtotime($startTime);
                $where .= " AND (created_date >= '".$startTime."')";
            }
            if($endTime) {
                $time2=strtotime($endTime);
                if($startTime && $time2 < $time1){
                    Output::error('开始时间不能大于结束时间，请重新选择',3);
                }
                $where.= " AND (created_date <= '".$endTime."')";
            }


            //获取可操作数据库列表
            $Connection = \Yii::$app->db;
            $user_name =Yii::$app->users->identity->username;



            if (!file_exists('./downloads'))
                mkdir('./downloads');
            $folder = './downloads/'.$file_name.'_'.uniqid();
            mkdir ($folder);
            $command = $Connection->createCommand("SELECT * FROM $table WHERE `user_id` = '$user_id' AND `server_id` = '$server_id' AND `database` = '$database' AND `pro_id` = '$pro_id' $where");
            $result2 = $command->queryAll();
            if(count($result2) == 0)
                Output::error('没有数据可导出',3);
            if(!$result2)
                Output::error('数据库操作异常，请联系管理员',3);
            $content = '';
            foreach($result2 as $row2){
                if(!empty($row2['notes']))
                    $content .= $row2['notes']."\r\n".stripslashes($row2['script']).';'."\r\n\r\n";
                else
                    $content .= stripslashes($row2['script']).';'."\r\n\r\n";
            }
            $content = file_put_contents($folder.'/'.$user_id.'_'.$user_name.'_'.$file_name.'.txt',$content);//得到文件执行的结果


            //获取列表
            $datalist=$this->list_dir($folder);
            $filename = $folder.".zip"; //最终生成的文件名（含路径）
            if(!file_exists($filename)){
                //重新生成文件
                $zip = new \ZipArchive();//使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
                if ($zip->open($filename, \ZIPARCHIVE::CREATE)!==TRUE) {
                    Output::error('无法打开文件，或者文件创建失败',3);
                }
                foreach( $datalist as $val){
                    if(file_exists($val)){
                        $zip->addFile( $val, basename($val));//第二个参数是放在压缩包中的文件名称
                    }
                }
                $zip->close();//关闭
            }
            foreach( $datalist as $val){
                //删除文件
                unlink($val);
            }
            //删除文件夹ss
            rmdir($folder);
            if(!file_exists($filename)){
                Output::error('无法找到文件',3); //即使创建，仍有可能失败。。。。
            }

            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header('Content-disposition: attachment; filename='.basename($filename)); //文件名
            header("Content-Type: application/zip"); //zip格式的
            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
            header('Content-Length: '. filesize($filename)); //告诉浏览器，文件大小
            @readfile($filename);
            //删除压缩文件
            unlink($filename);
        }else{
            //获取服务器列表
            $Servers = new servers();
            $server_list = $Servers::find()->where(['status' => 1])->orderBy('server_id')->asArray()->all();
            $default_host = \Yii::$app->session->get('default_host');
            if(!empty($default_host)){
                foreach($server_list as $key=>$val){
                    if($default_host != $val['ip'])
                        unset($server_list[$key]);
                }
            }

            $project_list = Projects::find()->where(['status' => 1])->orderBy('pro_id')->asArray()->all();
            $default_pro_id = \Yii::$app->session->get('default_pro_id');
            if(!empty($default_pro_id)){
                foreach($project_list as $key=>$val){
                    if($default_pro_id != $val['pro_id'])
                        unset($project_list[$key]);
                }
            }
            //传给视图
            $data['server_list'] = $server_list;
            $data['project_list'] = $project_list;
            $data['server_ids'] = $this->servers['server_ids'];
            //$data['user_dbs'] = $this->servers['db_names'];
            $data['user_dbs'] = array();
            $data['servers'] = $this->servers;
            $data['db_name_array'] = json_encode($this->servers['db_name_array']);

            return $this->render('export',$data);
        }
    }


    /**
     * 获取文件夹下所有文件
     * @param string $dir
     * @return type
     */
    public function list_dir($dir){
        $dir = $dir.'/';
        $result = array();
        if (is_dir($dir)){
            $file_dir = scandir($dir);
            foreach($file_dir as $file){
                if ($file == '.' || $file == '..'){
                    continue;
                }
                elseif (is_dir($dir.$file)){
                    $result = array_merge($result, $this->list_dir($dir.$file.'/'));
                }
                else{
                    array_push($result, $dir.$file);
                }
            }
        }
        return $result;
    }

    /**
     * 获取数据库下的所有表
     * @return [type] [description]
     */
    public function actionGetTableList(){

        $tb_list = array();
        $server_ip = $_REQUEST['server_ip'];
        $db_name = $_REQUEST['db_name'];
        @$pri_tb_list = $this->servers['privilege'][$server_ip][$db_name];

        $redis = new RedisBaseServer();
        $redis_tb = $redis->hget($server_ip . "-" . $db_name, 'tables');
        @$redis_tb_ = !empty( $redis_tb ) ? explode(",", $redis_tb) : array();
        if( is_array( $pri_tb_list ) && is_array( $redis_tb_ ) ){
            $tb_list = array_values( array_intersect($pri_tb_list, $redis_tb_) );
        }elseif( is_array( $pri_tb_list ) ){
            $tb_list = $pri_tb_list;
        }elseif( is_array( $redis_tb_ ) ){
            $tb_list = $redis_tb_;
        }
        echo json_encode( $tb_list );
    }

    /**
     * 获取表信息
     * @return [type] [description]
     */
    public function actionGetTableInfo(){
        $server_ip = $_REQUEST['server_ip'];
        $db_name = $_REQUEST['db_name'];
        $tb_name = $_REQUEST['tb_name'];
        $this->check_server( $server_ip, $db_name );

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

        $executeConnection = $this->connectDb( $server_ip, $db_name );
        try {
            $command = $executeConnection->createCommand('show create table ' . $tb_name . ";");
            $result = $command->queryAll();
            $create_sql = $result[0]['Create Table'];//获取创建表的SQL语句

            $table_info = strstr($create_sql, "ENGINE=");

            $command = $executeConnection->createCommand("show table status where name = '" . $tb_name . "'");
            $table_status = $command->queryAll();
            $table_status_ = $table_status[0];
            //var_dump($table_status_);exit;

            $i = 2;
            foreach ($info_rule as $key => $value) {
                $list[$i]['name'] = $value;
                $list[$i]['value'] = $table_status_[$key];
                $i++;
            }

            $list[$i]["name"] = "选项";
            $list[$i]["value"] = $table_info;

            $info['create_sql'] = $create_sql;
            $info['list'] = $list;
            Output::success("查询成功", $info);
        } catch (\Exception $e) {
            Output::error("数据库连接失败:\r\n".$e->getMessage());
        }

    }

    /**
     * 发送消息
     * 此处用于DBA在预发或线上环境执行DDL操作时，给大数据平台组成员发送推送消息
     * @param  [type] $msg [description]
     * @return [type]      [description]
     */
    public function sendDdlMsg( $msg )
    {
        $touser = "02209|01835|02616|01502|01503|01475|01781|02725|02443";
        $jobCenter = new JobCenter();
        $request = $jobCenter->sendTextDingTalk($touser, '', $msg);
        //var_dump($request);exit;
    }

    /**
     * 判断执行SQL 的数据库和表是否在DDL规则中
     * @param  [type]  $database  [description]
     * @param  [type]  $tablename [description]
     * @return boolean            [description]
     */
    public function isInDdlConfig( $database, $tablename ){
        $ddlConfig = new DdlConfigs();
        $rule = $ddlConfig->getRule();
        //如果DDL配置规则为空
        if( empty( $rule ) ){
            return false;
        }

        //如果数据库不在规则中
        if( empty( $rule[$database] ) ){
            return false;
        }

        if( !in_array( $tablename, $rule[$database] ) ){
            return false;
        }

        return true;
    }

    /**
     * 检查服务器是否连通
     * @return [type] [description]
     */
    public function actionPing()
    {  
        $server_ip = $_REQUEST['server_ip'];
        $status = -1;  
        if (strcasecmp(PHP_OS, 'WINNT') === 0) {  
            // Windows 服务器下  
            $pingresult = exec("ping -n 1 {$server_ip}", $outcome, $status);  
        } elseif (strcasecmp(PHP_OS, 'Linux') === 0) {
            // Linux 服务器下  
            $pingresult = exec("nc -n -w1 $server_ip 3306", $outcome, $status);
        }
        if($status == 0) {
            $db_list = $this->_filterDb($server_ip);
        }else{
            $db_list = 'all';
        }
        echo json_encode(['status'=>$status,'db_list'=>$db_list]);
    }
    /**
     * 检测SQL的合法性和SQL执行影响范围
     * @return [type] [description]
     */
    public function actionSqlVerify(){
        //获取数据
        @$db_name = rtrim($_REQUEST['DBName']);
        @$server_ip = rtrim($_REQUEST['DBHost']);
        @$sqlinfo_all = rtrim($_REQUEST['sqlinfo']);
        //@$is_limit = rtrim($_REQUEST['is_limit']);
        @$is_limit = 1;//强制设置查询条数限制

        @$pro_id = !empty($_REQUEST['project'])?rtrim($_REQUEST['project']):0;
        @$batch = rtrim($_REQUEST['batch']);
        @$batch_notes = trim($_REQUEST['batch_notes']);

        //查询条数限制
        @$environment_rule = Select::find()->asArray()->one();

        if(empty($server_ip))
            Output::error('请选择目标服务器！');
        if(empty($db_name))
            Output::error('请选择目标数据库！');
        
        $sqlParser = new SqlParserServer();

        $server = $this->check_server( $server_ip, $db_name );
        $server_id = $server['server_id'];

        //根据服务器环境读取查询语句的执行条数
        $environment = $server['environment'];
        $nums = $sqlParser->getSelectWhite( $environment );

        if(empty($pro_id) && $environment != "pro" ){
            Output::error('请选择项目！');
        }else if( $environment == "pro" ){
            $pro_id = 1;
        }


        //数据库连接对象
        $executeConnection = $sqlParser->connectDb( $server_ip, $db_name );

        //将SQL语句分割成SQL数组
        $sql_list = $sqlParser->separateSql( $sqlinfo_all );
        if( isset( $sql_list['error'] ) ){
            Output::error( $sql_list['error'] );
        }

        //判断批量注释是否合法
        if( $batch == 1 ){
            $check_batch = $sqlParser->checkBatchNote( $sql_list, $batch_notes );
            if( isset( $check_batch['error'] ) ){
                Output::error( $check_batch['error'] );
            }
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

            //获取SQL类型和首个关键词
            $sql_info = $sqlParser->getSqlType( $sqlinfoA );
            if( $sql_info['sql_type'] == 'other' ){
                $contents[$sql_key]['sql'] = $sqlinfoA;
                $contents[$sql_key]['msg'] = '不支持的SQL类型';
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
                //@$set_value = $sql_preg[2][0];  //set值
                @$sql_where = $sql_preg[3][0];  //SQL条件
                if( empty( $table_name ) || empty($sql_where) ){
                    $contents[$sql_key]['msg'] = 'SQL语句不合法或者请添加where条件！';
                    continue;
                }

                $msg = $this->computeSqlRange( $server_ip, $db_name, $table_name, $sql_where );
                $contents[$sql_key]['msg'] = $msg;

            }elseif( preg_match('/^delete\s+/i', rtrim($sqlinfoA)) ){
                //正则匹配delete语句的表名、修改值、where条件
                preg_match_all('/^delete\s+from\s+([a-zA-Z0-9.`_]+)\s+where\s+([\s\S]+)/', strtolower($sqlinfo), $sql_preg);
                @$table_name = $sql_preg[1][0];  //表名
                @$sql_where = $sql_preg[2][0];  //SQL条件
                if( empty( $table_name ) || empty($sql_where) ){
                    $contents[$sql_key]['msg'] = 'SQL语句不合法或者请添加where条件！';
                    continue;
                }

                $msg = $this->computeSqlRange( $server_ip, $db_name, $table_name, $sql_where );
                $contents[$sql_key]['msg'] = $msg;

            }else{
                $contents[$sql_key]['msg'] = '非update和delete语句暂不检测受影响范围';
            }
        }
        Output::success('操作成功',$contents);

    }

    /**
     * 计算SQL执行受影响范围
     * @param  [type] $server_ip     [description]
     * @param  [type] $db_name     [description]
     * @param  [type] $table_name  [description]
     * @param  [type] $sql_where   [description]
     * @return [type]              [description]
     */
    public function computeSqlRange( $server_ip, $db_name, $table_name, $sql_where ){
        $executeConnection = $this->connectDb( $server_ip, $db_name );

        $exec_sql = "select count(*) from " . $table_name . " where " . $sql_where;//带where条件查询行数
        $request_exec = $this->execute_select_sql( $executeConnection, $exec_sql );
        if( isset( $request_exec['error'] ) ){
            return $request_exec['error'];
        }

        //获取受影响行数
        $exec_count = $request_exec['number'];
        $msg = "受影响行数：【".$exec_count."】";

        $all_sql = "select count(*) from " . $table_name;//查询所有数据总行
        $request_all = $this->execute_select_sql( $executeConnection, $all_sql );
        if( isset( $request_all['error'] ) ){
            return $request_all['error'];
        }
        $all_count = $request_all['number'];
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
     * 执行查询的SQL
     * @param  [type] $executeConnection [description]
     * @param  [type] $sqlinfo           [description]
     * @return [type]                    [description]
     */
    public function execute_select_sql( $executeConnection, $sqlinfo ){
        try {
            $command = $executeConnection->createCommand($sqlinfo);
            $excute_result = $command->queryAll();
            $info['number'] = $excute_result[0]['count(*)'];
        } catch (\Exception $e) {
            $info['error'] = $e->getMessage();
        }
        return $info;
    }

    /**
     * 检查临时授权
     * @param  [type] $server_id [服务器ID]
     * @param  [type] $database  [数据库名]
     * @param  [type] $type      [SQL类型]
     * @return [boolean]         [false 未匹配到对应的授权，true 通过授权]
     */
    public function checkAuthorize( $server_id, $database, $type ){
        $username = Yii::$app->users->identity->username;
        $time = date("Y-m-d H");
        $authorize = Authorize::find()->where(['server_id' => $server_id, 'username' => $username, 'type' => 'common'])->andWhere(['>=', 'stop_time', $time])->asArray()->all();

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

    /**
     * 检测表是否有权限执行
     * @param  [type] $db_name   [description]
     * @param  [type] $table     [description]
     * @param  [type] $server_ip [description]
     * @param  [type] $type      [SQL类型]
     * @return [boole]            [description]
     */
    public function checkTableAuthorize( $db_name, $table, $server_ip, $type )
    {
        
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(Yii::$app->users->identity->id);
        $role = array_keys($roles);

        $query = new Query;
        $query  ->select(['auth_item_servers_dbs.privilege'])  
                ->from('auth_item_servers_dbs')
                ->leftJoin('auth_item_servers', 'auth_item_servers.item_name = auth_item_servers_dbs.item_server_name')
                ->where(['auth_item_servers_dbs.server_ip' => $server_ip])->andWhere(['in', 'auth_item_servers.item_name', $role])->andWhere(['like', 'auth_item_servers.sql_operations', $type]); 
        $command = $query->createCommand();
        $data = $command->queryAll();

        $result = false;
        if( !empty( $data ) ){
            foreach ($data as $key => $value) {
                $privilege = json_decode($value['privilege'], true);
                if( $privilege == 'all' ){
                    $result = true;
                    break;
                }elseif( empty( $privilege ) ){
                    continue;
                }else{
                    @$tables = $privilege[$db_name];
                    if( !empty($tables) && ( ( is_array( $tables ) && in_array( $table, $tables ) ) || $tables == 'all' ) ){
                        $result = true;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 获取服务器信息，并检查是否有权限对服务器、数据库操作
     * @param  [type] $server_ip [description]
     * @param  [type] $db_name   [description]
     * @return [type]            [description]
     */
    public function check_server( $server_ip, $db_name ){
        $server = Servers::find()->where(['ip'=>$server_ip])->one();
        $this->check_server_permission($server_ip,$db_name);
        return $server;
    }

    /**
     * 连接数据库
     * @param  [type] $server_ip [description]
     * @param  [type] $db_name   [description]
     * @return [type]            [description]
     */
    public function connectDb( $server_ip, $db_name ){
        //组合数据库配置
        $connect_config['dsn'] = "mysql:host=$server_ip;dbname=$db_name";
        $connect_config['username'] = Yii::$app->params['MARKET_USER'];
        $connect_config['password'] = Yii::$app->params['MARKET_PASSWD'];
        $connect_config['charset'] = Yii::$app->params['MARKET_CHARSET'];

        //数据库连接对象
        $executeConnection = new \yii\db\Connection((Object)$connect_config);
        return $executeConnection;
    }


    public function checkSqlIsTrue( $sql ){
        $executeConnection = $this->connectDb("127.0.0.1", "webdb");
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
     * @description 获取项目列表
    */
    public function actionGetProject($search)
    {
        $search = trim($search);
        $project_list = Projects::find()->where(['status' => 1])->andWhere(['like','name',$search])->orderBy('pro_id')->asArray()->all();
        $default_pro_id = \Yii::$app->session->get('default_pro_id');
        if(!empty($default_pro_id)){
            foreach($project_list as $key=>$val){
                if($default_pro_id != $val['pro_id'])
                    unset($project_list[$key]);
            }
        }
        return json_encode(['data'=>$project_list,'search'=>$search]);
    }

    /**
     * @description 数据库过滤
     * @param $db_list 数据库列表
     * @project_info 项目详情
    */
    private function _filterDb($server_ip)
    {
        try {
            $conn = $this->connectDb($server_ip, 'mysql');
            $result = $conn->createCommand("show databases;")->queryAll();
            $db_list = [];
            foreach($result as $value){
                $db_list[current($value)] = 1;
            }
            return $db_list;
        }catch(\Exception $e){
            return 'all';
        }
    }

}
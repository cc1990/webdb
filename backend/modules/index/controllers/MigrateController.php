<?php 
namespace backend\modules\index\controllers;

use Yii;
use backend\controllers\SiteController;

use common\models\AuthItemServers;

use yii\httpclient\Client;
use vendor\twl\tools\utils\Output;
use yii\helpers\Url;

/**
 * 数据迁移类
 */
class MigrateController extends SiteController
{
    //分库分表接口地址
    const host = "http://dbservice.qccrnb.com";
    const shell_common = "/data/scripts/migrateData_common.sh";
    const shell_sharding = "/data/scripts/migrateData_sharding.sh";
    const shell_common_create = "/data/scripts/migrateData_common_createTable.sh";
    
    public function actionIndex()
    {
        $request = $this->getProjectList();
        $data['project_list'] = json_encode($request['project_list']);
        $data['project_data'] = json_encode($request['data']);
        return $this->render("index", $data);
    }

    public function actionExecute()
    {
        @$type = rtrim( $_POST['type'] );
        $action_type = 'migrate';
        if ( $type == 'common' ) {
            $this->common_to_sharding( $action_type );
        }elseif ( $type == 'sharding' ) {
            $this->sharding_to_sharding();
        }else {
            Output::error("请选择迁移方式！");
        }
    }

    public function actionCreateTable()
    {
        @$type = rtrim( $_POST['type'] );
        $action_type = 'create';
        if ( $type == 'common' ) {
            $this->common_to_sharding( $action_type );
        }else{
            Output::error("只支持通用库到分库分表的表结构创建！");
        }
    }

    /**
     * 根据服务器IP获取项目列表
     * @param  [type] $ip [description]
     * @return [JSON]     [description]
     */
    public function getProjectList()
    {
        //获取分库分表规则
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
        $project = array();
        $info = array('project_list' => '', 'data' => '');

        //获取当前用户所授权的环境
        $role = $this->getRoleEnvironment();
        if( !empty( $content_data ) && is_array( $content_data ) ){
            foreach ($content_data as $key => $value) {
                $data_environ = array();
                $projectName = $value->projectName;
                $environment = $value->environment;
                if ( in_array( $environment, $role ) ) {
                    $project[$projectName][$environment] = $value->database;
                }
            }


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
        }
        return $info;
    }

    /**
     * 通用库到分库分表
     * @return [type] [description]
     */
    public function common_to_sharding( $action_type = 'migrate' )
    {
        @$server_ip = rtrim($_POST['from_ip']);
        @$project = rtrim( $_POST['project'] );
        @$environment = rtrim( $_POST['environment'] );
        @$dbname = rtrim( $_POST['DBName'] );
        @$db_name = rtrim( $_POST['db_name'] );
        $db_name_ = !empty( $db_name ) ? $db_name : $dbname;
        @$tbname = $_POST['tbname'];
        @$status = $_POST['create_tb'] ? 'y' : 'n';
        if ( empty( $server_ip ) ) 
            Output::error("请输入正确的IP地址！");

        $reg = '/^([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])$/';
        if ( @preg_match( $reg, $server_ip ) === false ) {
            Output::error("请输入正确的IP地址！");
        }
        if ( empty( $project ) ) 
            Output::error("请输入项目！");
        if ( empty( $environment ) ) 
            Output::error("请选择环境！");
        if ( empty( $dbname ) ) 
            Output::error("请选择数据库！");
        if ( empty( $tbname ) ) 
            Output::error("请选择表！");

        $post['project'] = $project;
        $post['environment'] = $environment;
        $post['database'] = $dbname;

        $request = $this->getRule( $post );
        if( isset( $request['error'] ) )
            Output::error($request['error']);
        //$param = "membercenter 192.168.5.122 \"user_info:id:5##192.168.5.122:3306:0:0:1#192.168.5.122:3306:1:2:4###user_gather_info:id:5##192.168.5.122:3306:1:0:1#192.168.5.122:3306:2:2:3#192.168.5.122:3306:3:4:4\" y";
        
        if ( is_array( $request ) ) {
            $result_data = $request['data'];

            foreach ($result_data['tblist'] as $key => $value) {
                $param = $dbname . " " . $server_ip . '"';

                if ( in_array( $value['tbName'], $tbname ) ) {
                    $tbAmountPerDB = $value['tbAmountPerDB'];
                    $param .= $value['tbName'] . ":" . $value['tBKey'] . ":";
                    $tpl = "";
                    foreach ($result_data['dblist'] as $k => $v) {
                        $masterIP = $v['masterIP'];
                        $master_ip = explode(":", $masterIP);
                        //判断IP是否带有端口号，如果没有，则默认3306
                        @$mysql_port = $master_ip[1] ? $master_ip[1] : '3306';

                        $i = $v['dbBeginIndex'];//分库分表库下标起始键
                        $j = $v['dbEndIndex'];//分库分表库下标结束键

                        for ($i; $i <= $j; $i++) { 
                            $tpl .= $master_ip[0] . ':' . $mysql_port . ':' . $i . ":" . ($i*$tbAmountPerDB) . ":" . (($i+1)*$tbAmountPerDB-1) . '#';
                        }
                        $tpl_ = substr( $tpl, 0, -1 );
                    }
                    $param .= ($j+1)*$tbAmountPerDB . "##" . $tpl_ . '"' . $status;
                    $table_param = $value['tbName'] . ":" . $value['tBKey'] . ":".($j+1)*$tbAmountPerDB . "##" . $tpl_;

                    if ( function_exists('pcntl_fork') ) {
                        $pid = pcntl_fork();  //产生子进程，而且从当前行之下开试运行代码，而且不继承父进程的数据信息 
                        if( $pid == -1 ){
                            Output::error('子进程创建失败');
                        }else if( $pid ){

                        }else{
                            if( $action_type == 'migrate' ){
                                pcntl_exec("/bin/sh", array(self::shell_common, $db_name_, $dbname, $server_ip, $table_param, $status));
                            }else{
                                pcntl_exec("/bin/sh", array(self::shell_common_create, $db_name_, $dbname, $server_ip, $table_param));
                            }
                        }
                    } else {
                        Output::error('该服务器不支持pcntl');
                    }
                }
            }
            $param_ = substr($param, 0, -3);
            $param_ .= "\" " . $status;
        }else{
            Output::error('分库分表规则返回为空！');
        }
        if( $action_type == 'migrate' ){
            Output::success('数据迁移中，请查看日志！');
        }else{
            Output::success('表结构正在创建中！');
        }
    }

    /**
     * 分库分表到分库分表
     * @return [type] [description]
     */
    public function sharding_to_sharding()
    {
        @$from_project = rtrim($_POST['from_project']);
        @$from_environment = rtrim($_POST['from_environment']);
        @$from_dbname = rtrim($_POST['from_DBName']);
        @$to_project = rtrim($_POST['to_project']);
        @$to_environment = rtrim($_POST['to_environment']);
        @$to_dbname = rtrim($_POST['to_DBName']);
        @$tbname = $_POST['tbname'];

        if( empty( $from_project ) )
            Output::error("请选择来源项目");
        if( empty( $from_environment ) )
            Output::error("请选择来源环境");
        if( empty( $from_dbname ) )
            Output::error("请选择来源数据库");
        if( empty( $to_project ) )
            Output::error("请选择目标项目");
        if( empty( $to_environment ) )
            Output::error("请选择目标环境");        
        if( empty( $to_dbname ) )
            Output::error("请选择目标数据库");        
        if( empty( $tbname ) )
            Output::error("请选择来源表");
        if( $from_dbname != $to_dbname )
            Output::error("来源数据库和目标数据库必须相同！");

        $post['project'] = $from_project;
        $post['environment'] = $from_environment;
        $post['database'] = $from_dbname;

        $post_['project'] = $to_project;
        $post_['environment'] = $to_environment;
        $post_['database'] = $to_dbname;
        $request_ = $this->getRule( $post_ );
        if( isset( $request_['error'] ) )
            Output::error($request_['error']);

        $dblist = $request_['data']['dblist'];
        foreach ($dblist as $key => $value) {
            $i = $value['dbBeginIndex'];//分库分表库下标起始键
            $j = $value['dbEndIndex'];//分库分表库下标结束键
            $masterIP = $value['masterIP'];
            $master_ip = explode(":", $masterIP);
                        //判断IP是否带有端口号，如果没有，则默认3306
            @$mysql_port = $master_ip[1] ? $master_ip[1] : '3306';
            $db_ip = $master_ip[0] . ":" . $mysql_port;
            for ($i; $i <= $j; $i++) {
                $db_list[$i] = $db_ip;
            }
        }

        if ( empty( $db_list ) ) {
            Output::error("目标分库分表规则还未配置");
        }

        $request = $this->getRule( $post );
        if( isset( $request['error'] ) )
            Output::error($request['error']);

        //$tpl = "/migrateData_shading_new.sh membercenter \"user_info:192.168.5.122:3306:0:0:1:192.168.5.123:3306#user_info:192.168.5.122:3306:1:2:4:192.168.5.123:3306\"";
        
        $result_q = false;//脚本执行结果
        if ( is_array( $request ) ) {
            $result_data = $request['data'];

            foreach ($result_data['tblist'] as $key => $value) {
                $param = $from_dbname . " \"";
                $table_param = '';
                if ( in_array( $value['tbName'], $tbname ) ) {
                    $tbAmountPerDB = $value['tbAmountPerDB'];
                    $tpl = "";
                    foreach ($result_data['dblist'] as $k => $v) {
                        $masterIP = $v['masterIP'];
                        $master_ip = explode(":", $masterIP);
                        //判断IP是否带有端口号，如果没有，则默认3306
                        @$mysql_port = $master_ip[1] ? $master_ip[1] : '3306';

                        $i = $v['dbBeginIndex'];//分库分表库下标起始键
                        $j = $v['dbEndIndex'];//分库分表库下标结束键

                        for ($i; $i <= $j; $i++) { 
                            $param .= $value['tbName'] . ":" . $master_ip[0] . ':' . $mysql_port . ':' . $i . ":" . ($i*$tbAmountPerDB) . ":" . (($i+1)*$tbAmountPerDB-1) . ':' . $db_list[$i] . "#";
                            $table_param .= $value['tbName'] . ":" . $master_ip[0] . ':' . $mysql_port . ':' . $i . ":" . ($i*$tbAmountPerDB) . ":" . (($i+1)*$tbAmountPerDB-1) . ':' . $db_list[$i] . "#";
                        }
                        
                    }
                    $tpl = substr( $param, 0, -1 );
                    $table_param_ = substr( $table_param, 0, -1 );
                    $tpl .= "\"";
                    
                    if ( function_exists('pcntl_fork') ) {
                        $pid = pcntl_fork();  //产生子进程，而且从当前行之下开试运行代码，而且不继承父进程的数据信息 
                        if ( $pid == 0 ) {
                            pcntl_exec("/bin/sh", array(self::shell_sharding, $from_dbname, $table_param_));
                        }
                    } else {
                        Output::error('该服务器不支持pcntl');
                    }
                }
            }
            Output::success('数据迁移中，请查看日志！');
        } else {
            Output::error('分库分表规则返回为空！');
        }
    }

    public function actionGetRuleList()
    {
        @$data['project'] = rtrim( $_POST['project'] );
        @$data['environment'] = rtrim( $_POST['environment'] );
        @$data['database'] = rtrim( $_POST['dbname'] );
        
        $result = $this->getRule( $data );
        echo json_encode( $result );
    }

    /**
     * 获取执行日志
     * @return [type] [description]
     */
    public function actionGetExecuteLog(){
        $dbname = $_GET['database'];
        $type = $_GET['type'];
        $tbname = $_GET['tbname'];
        if( !empty( $dbname ) && !empty($type) && !empty( $tbname ) ){
            $logs_path = "/data/sharding/" . $dbname . "/log/" . $type . "/";
            if ( is_dir( $logs_path ) ) {
                $log = $logs_path . $tbname . '.log';
                if ( is_file( $log ) ) {
                    @$content = file_get_contents( $log );
                    $msg = str_replace('\n', '<\br>', $content);
                }else{
                    $msg = "未能读取到日志信息";
                }
                $result = $msg;
            } else {
                $result = "路径不存在！";
            }
        }else{
            $result = "缺少参数！";
        }
        echo "<pre>$result</pre>";
        //echo json_encode($result);
    }

    /**
     * 获取执行日志
     * @return [type] [description]
     */
    public function actionGetCreateLog(){
        $dbname = $_GET['database'];
        $tbname = $_GET['tbname'];
        if( !empty( $dbname ) && !empty( $tbname ) ){
            $logs_path = "/data/sharding/" . $dbname . "/log/common_createTable/";
            if ( is_dir( $logs_path ) ) {
                $log = $logs_path . $tbname . '.log';
                if ( is_file( $log ) ) {
                    @$content = file_get_contents( $log );
                    $msg = str_replace('\n', '<\br>', $content);
                }else{
                    $msg = "未能读取到日志信息";
                }
                $result = $msg;
            } else {
                $result = "路径不存在！";
            }
        }else{
            $result = "缺少参数！";
        }
        echo "<pre>$result</pre>";
        //echo json_encode($result);
    }

    /**
     * 获取分库分表规则
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function getRule( $post )
    {
        $url = self::host . "/db/rule.json";
        $url .= "?projectName=".$post['project']."&environment=".$post['environment']."&database=".$post['database'];
        $request = $this->get( $url);
        $content = json_decode( $request );
        if( $content->success && $content->failed == false ){
            $result_data = json_decode($content->data);
            if ( !empty( $result_data ) ) {
                //var_dump($result_data);exit;
                $tbInfoList = $result_data->tbInfoList; // 表信息
                $dbInfoList = $result_data->dbInfoList; // 库信息

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

                $data['commonDBInfo']['dbName'] = $result_data->commonDBInfo->dbName;
                $data['commonDBInfo']['masterIP'] = $result_data->commonDBInfo->masterIP;
                $result['data'] = $data;
            } else {
                $result['error'] = '分库分表规则返回为空！';
            }
            
        }else{
            $result['error'] = $content->statusText;
        } 
        //var_dump($result);exit;
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

    public function fork(){
        if ( function_exists('pcntl_fork') ) {
            $wait = FALSE; //是否等待进程结束
            $iniNum = 10; //进程总数
            $pids = array(); //进程PID数组
            echo 'Starting------------' . date("Y-m-d H:i:s");

            for ($i=0; $i < $iniNum; $i++) {
                $pids[$i] = pcntl_fork();  //产生子进程，而且从当前行之下开试运行代码，而且不继承父进程的数据信息 
                if ( !$pids[$i] ) {
                    $str = "";
                    sleep(5+$i);
                    for ($j=0; $j < $i; $j++) { 
                        $str .= "*";
                    }
                    echo "$i -> " . time() . " $str n";
                    exit;
                }
            }
            echo 'End------------' . date("Y-m-d H:i:s");
        } else {
            echo '该服务器不支持pcntl';
        }
        
    }

    /**
     * 获取分库分表信息
     * @return [type] [description]
     */
    public function getShardingList(){
        /*$result = Yii::$app->redis->get("tools:sharding_list");
        if( empty( $result ) ){
            $url = self::host . "/db/list.json";
            $result = $this->get( $url );
            Yii::$app->redis->set("tools:sharding_list", $result);
        }*/
        $url = self::host . "/db/list.json";
        $result = $this->get( $url );
        return $result;
    }
}

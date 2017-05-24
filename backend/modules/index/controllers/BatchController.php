<?php 

namespace backend\modules\index\controllers;

use backend\modules\projects\models\Projects;
use backend\controllers\SiteController;
use backend\modules\users\models\Users;
use backend\modules\index\models\Deployment;

use yii;
use common\models\Servers;

use common\models\QueryLogs;
use common\models\ExecuteLogs;

use vendor\twl\tools\utils\Output;


class BatchController extends SiteController
{

    /**
     * 批量执行SQL
     * @return [type] [description]
     */
    public function actionIndex()
    {
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

        $url = "http://cryw.qccr.com/releasemanage/ajax/get_project_list/"; //获取项目列表
        $request = json_decode(file_get_contents($url));
        $project = $request->result;

        foreach ($project as $key => $value) {
            $project_list[$key]['pro_id'] = $value->id;
            $project_list[$key]['name'] = $value->project_name;

            if ( $value->status == '10' ) {
                $project_list[$key]['status'] = 'dev';
            } elseif( $value->status == '15' ) {
                $project_list[$key]['status'] = 'dev_trunk';
            } elseif( $value->status == '20' ) {
                $project_list[$key]['status'] = 'test';
            } elseif( $value->status == '30' ) {
                $project_list[$key]['status'] = 'test_trunk';
            } elseif( $value->status == '35' ) {
                $project_list[$key]['status'] = 'pre';
            } elseif( $value->status == '99' ) {
                $project_list[$key]['status'] = 'pro';
            } else{
                unset($project_list[$key]);
            }
        }

        //$project_list = Projects::find()->where(['status' => 1])->orderBy('pro_id desc')->asArray()->all();
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

        $data['db_name_array'] = json_encode($this->servers['db_name_array']);

        $data['user_dbs'] = array();
        //数组返回以server_id为key，db_name组成的数组为value
        //var_dump($this->servers['db_name_array']);exit;
        //            var_dump(\Yii::$app->session->get('default_host_id'));exit;
        if(!empty($this->servers['db_name_array'][\Yii::$app->session->get('default_host_id')]))
            $data['user_dbs'] = $this->servers['db_name_array'][\Yii::$app->session->get('default_host_id')];
        return $this->render('index', $data);
    }

    /**
     * 批量执行SQL语句
     * @return [type] [description]
     */
    public function actionExecute()
    {
        @$db_name = rtrim($_REQUEST['DBName']);
        @$server_ip = rtrim($_REQUEST['DBHost']);
        @$sqlinfo_all = rtrim($_REQUEST['sqlinfo']);
        @$pro_id = rtrim($_REQUEST['Project']);

        
        //$sql_content = $_REQUEST['sql_content'];
        //$sqlinfo_all = implode('', $sql_content);


        if(empty($server_ip))
            Output::error('请选择目标服务器！');
        if(empty($db_name))
            Output::error('请选择目标数据库！');


        //获取服务器详情
        $Servers = new servers();
        $server = $Servers::find()->where(['ip' => $server_ip,'status' => 1])->asArray()->one();

        if(empty($server)){
            Output::error('IP为'.$server_ip.'的服务器不存在！');
        }
        //服务器id
        $server_id = $server['server_id'];

        //检测操作权限
        $this->check_server_permission($server_id,$db_name);

        //组合数据库配置
        $connect_config['dsn'] = "mysql:host=$server_ip;dbname=$db_name";
        $connect_config['username'] = Yii::$app->params['MARKET_USER'];
        $connect_config['password'] = Yii::$app->params['MARKET_PASSWD'];

        //数据库连接对象
        $executeConnection = new \yii\db\Connection((Object)$connect_config);

        //获取可操作数据库列表
        $user_dbs = $this->servers['db_name_array'][$server_id];
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

        //获取全部数据库名
        try {
            $command = $executeConnection->createCommand('show databases');
            $result = $command->queryAll();
        } catch (\Exception $e) {
            Output::error("数据库连接失败:\r\n".$e->getMessage());
        }

        //组合检测非法操作数据库正则
        $db_regex = '/';
        $db_keyword = array('from','into','update','on','table');
        //var_dump($result);exit;
        foreach($result as $val)
        if (in_array($val['Database'], $user_dbs) == false) {
            //$db_regex .= "({$val['Database']}\.)|";
            foreach($db_keyword as $db_val){
                $db_regex .= "($db_val(?:\s|\r\n)+{$val['Database']}\.)|";
            }
        }
        $db_regex = substr($db_regex, 0, strlen($db_regex) - 1) . '/i';

        //灰度与线上的数据查询增加对敏感金额数据的统计限制 ----------S-----------
        if( $db_name == 'oms' ){
            $preg = "/\s+(count|sum|avg)\(\s*(suggest_price|evaluate)\s*\)\s+\w+\s+(realtime_inventory|opening_inventory)\s*/";
            if( preg_match( $preg, $sqlinfo_all ) ){
                Output::error('敏感金额数据的统计已做限制！');
            }
        }

        if( $db_name == 'ordercenter' ){
            $preg = "/\s+(count|sum)\(\s*(market_cost|original_cost|sale_costreal_cost|market_cost|original_cost|sale_cost|coupon_apportion|market_cost|orig_cost|real_cost|sprice|signed_sprice|award_sprice|store_award_sprice|coupon_apportion|original_cost|sale_cost)\s*\)\s+\w+\s+(orders|order_goods|order_server|goods_sku_order)\s*/";
            if( preg_match( $preg, $sqlinfo_all ) ){
                Output::error('敏感金额数据的统计已做限制！');
            }
        }
        //-----------E-------------
        //
        //防呆限制
        if( preg_match('/^delete\s+/', $sqlinfo_all) ){ // 如果能够匹配到delete
            if( !preg_match( '/^delete\s+([\s\S]*)\s+where\s+\w+([\s\S]*)/', $sqlinfo_all ) ){ //匹配delete语句后面是否带有where条件
                Output::error('delete语句必须带有where条件！');
            }
        }

        //操作限制
        if( preg_match( '/^alter\s+([\s\S]*)\s+(drop|change)\s*/', $sqlinfo_all ) ){
            Output::error('不能删除字段和修改字段名！');
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

        //有批量注释的情况
        $batch_notes = '';
        
        //开始循环执行sql
        foreach ($info as $key => $sqlinfoA) {
            if (!empty($nextadd)) {
                $sqlinfoA = $nextadd . $sqlinfoA;
                $nextadd = '';
            }
            //echo $key.':'.$sqlinfoA;
            $sqlinfoA .= "\r\n";
            $notes = '';
            //Output::error('<pre>'.$sqlinfoA.'</pre>');
            preg_match_all("/#[\s\S]*?(?=\r\n(?:\s|\r\n)*" . $rule2 . ")/i", $sqlinfoA, $preg_notes);
            if (!empty($preg_notes[0][0])) {
                //print_r($notes);exit;
                $notes = $preg_notes[0][0];
                $notes = str_replace("\r\n", '', $notes);
                //判断注释是否20个字以上
                if (strlen($notes) <= 20) {
                    Output::error('注释长度必须20个英文字符以上');
                }
            } else {
//                echo $sqlinfoA;
//                preg_match_all("/^;?(?:delete|insert|update|drop|create|alter|rename|truncate|optimize|analyze){1}?\s/i", $sqlinfoA,$a);
//                var_dump($a);exit;
                if (preg_match("/^;(?:\n|\s)*?(?:delete|insert|update|drop|create|alter|rename|truncate|optimize|analyze){1}?\s/i", $sqlinfoA)) {
                    if (1 == $batch) {
                        $notes = $batch_notes;
                    } else {
                        Output::error('DDL与DML语句必须添加8个字符以上注释，格式：# 注释1234');
                    }
                }
            }
            if (!empty($notes)) {
                preg_match_all("/#[\s\S]*/i", $sqlinfoA, $sqlinfoA2);
                $sqlinfoA = $sqlinfoA2[0][0];
                $sqlinfoA = substr_replace($sqlinfoA, '', 0, strlen($notes) - 1);
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
            if(!empty($sql_type) && !empty($this->servers['sql_operations'][$server_id.'_'.$db_name]) && !in_array($sql_type,$this->servers['sql_operations'][$server_id.'_'.$db_name])){
                Output::error('你没有此数据库的'.$sql_type.'操作权限！请联系管理员解决');
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

            //过滤某些没有操作的数据库名+.的操作
            if (preg_match($db_regex, $sqlinfo)) {
                Output::error('没有此数据库的操作权限！');
            }

            //if(1 == $is_limit && preg_match('/select/i', $sqlinfo))
                //$sqlinfo .= ' limit '.$nums;
            try {
                //echo $sqlinfo;exit;
                $command = $executeConnection->createCommand($sqlinfo);
                if(preg_match('/^show\s+/i', rtrim($sqlinfoA)) || preg_match('/^desc\s+/i',
                        rtrim($sqlinfoA)) || preg_match('/^select\s+/i', rtrim($sqlinfoA))){
                    $is_query = 1;
                    $excute_result = $command->queryAll();
                }else{
                    $excute_result = $command->execute();
                    $excute_num = $excute_result > 0 ? $excute_result:0;
                    $is_query = 0;
                    //var_dump($excute_result);exit;
                }
            } catch (\Exception $e) {
                print "数据库操作失败: 请检查sql语句:\r\n".$e->getMessage();
                exit();
            }

//            $rows = 0;
//            if ($excute_result !== 0) {
//
//            }
            if (preg_match('/^insert\s+/i', rtrim($sqlinfoA)) || preg_match('/^update\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^delete\s+/i', rtrim($sqlinfoA))
                || preg_match('/^create\s+/i', rtrim($sqlinfoA)) || preg_match('/^drop\s+/i',
                    rtrim($sqlinfoA)) || preg_match('/^alter\s+/i', rtrim($sqlinfoA))
            ) {
                $excute_result = 0;
                $sqlstatus = 0;
                $sqlresult = '&nbsp;&nbsp;&nbsp;执行成功';
            } else {
                $rows = count($excute_result);
                if($rows == 0){
                    $sqlstatus = 1;
                    $sqlresult = '&nbsp;&nbsp;&nbsp;没有记录.';
                } else {
                    $sqlstatus = 0;
                    $sqlresult = '&nbsp;&nbsp;&nbsp;记录条数为:' . $rows . '行.';
                }
            }


            if ($is_query == 0) {
                $msg = $notes . '<br>' . $sqlinfo .$sqlresult. '&nbsp;&nbsp;&nbsp;<br><font size="3" color="#dc143c">受影响行:'.$excute_num.'</font>';
            } else {
                $msg = $sqlinfo .$sqlresult. '&nbsp;&nbsp;&nbsp;' ;
            }


            if (1 == $is_query) {
                $SaveSQL = new QueryLogs;
            } else {
                $SaveSQL = new Deployment;
                $SaveSQL->notes = $notes;
                $SaveSQL->action = "batch";
            }
            $SaveSQL->user_id = Yii::$app->users->identity->id;
            $SaveSQL->host = $server_ip;
            $SaveSQL->database = $db_name;
            $SaveSQL->script = $sqlinfo;
            $SaveSQL->result = $sqlresult;
            $SaveSQL->status = $sqlstatus;
            //$SaveSQL->pro_id = $pro_id;
            $SaveSQL->server_id = $server_id;
            $result = $SaveSQL->save();
//            var_dump($result);

            if($result !==true){
//                            var_dump($SaveSQL->errors);exit;
//                Output::error('数据库操作失败');
                Output::error($notes);
            }

            if ($excute_result !== true) {

                $contents[$key]['msg'] = $msg;
                $contents[$key]['sqlresult'] = $sqlresult;

                $contents[$key]['excute_result'] = $excute_result;
            }



        }
        Output::success('操作成功',$contents);

    }

    /**
     * 根据服务器ID获取该服务器执行的SQL日志；
     * @param  [int] $server_id 服务器ID
     * @param  [String] $dbname 数据库名称
     * @param  [String] $project_id 项目ID
     * @return [Json]            [description]
     */
    public function actionGetSqlList( $server_id, $project_id )
    {
        $logs = new ExecuteLogs;

        $user_id = Yii::$app->users->identity->id;
        $result = array();
        
        if( empty($server_id) || empty( $project_id ) ){
            echo json_encode($result);exit;
        }
        $project_id_array = explode(",", $project_id);
        $result = $logs::find()->select(['log_id','script','notes', 'database', 'pro_id', 'user_id', 'created_date'])->where(['server_id' => $server_id, 'pro_id' => $project_id_array])->asArray()->all();

        foreach ($result as $key => $value) {
            $pro_id = $value['pro_id'];
            $user_id = $value['user_id'];
            $project = Projects::find()->select(['name'])->where(['pro_id' => $pro_id])->asArray()->one();
            $user = Users::find()->select(['username', 'chinesename'])->where(['id' => $user_id])->asArray()->one();
            $result[$key]['project_name'] = $project['name'];
            $result[$key]['username'] = $user['chinesename'] ? $user['chinesename']: $user['username'];
        }
        echo json_encode($result);
    }

}



 ?>
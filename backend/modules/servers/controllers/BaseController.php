<?php

namespace backend\modules\servers\controllers;

use backend\modules\servers\models\Rule;
use Yii;
use backend\modules\servers\models\Servers;
use backend\controllers\SiteController;
use yii\data\Pagination;
use yii\widgets\LinkPager;

/**
 * @description 基础类
 */
class BaseController extends SiteController
{

    /**
     * @description 获取服务器列表
     */
    protected function _getServerList()
    {
        $server_list = Servers::find()->where(['status' => 1])->orderBy('server_id')->asArray()->all();
        $default_host = \Yii::$app->session->get('default_host');
        if(!empty($default_host)){
            foreach($server_list as $key=>$val){
                if($default_host != $val['ip'])
                    unset($server_list[$key]);
            }
        }
        $return = [];
        foreach($server_list as $value){
            if(in_array($value['environment'],['test_trunk','pre','pro'])){
                continue;
            }
            $return[$value['server_id']] = $value;
        }
        return $return;
    }

    /**
     * @description 获取数据库存在的表数量
     */
    protected function _getTableNum($server_ip,$db_name_array)
    {
        $conn = $this->_connectDb($server_ip);
        $sql = "show tables;";
        foreach($db_name_array as $val) {
            if(in_array($val,Yii::$app->params['filter_databases']))
                continue;
            $conn->createCommand("use {$val}")->execute();
            $command = $conn->createCommand($sql);
            $result = $command->queryAll();
            $data[] = ['db_name'=>$val,'table_num'=>count($result)];
        }
        return $data;
    }

    /**
     * 连接数据库
     * @param  string 服务器地址
     * @param  string $db_name
     * @return [type]
     */
    protected function _connectDb($server_ip,$db_name = 'mysql'){
        //组合数据库配置
        $db_name = $db_name == '*' ? 'mysql' :  $db_name;
        $server_ip_arr = explode(":",$server_ip);
        $ip = $server_ip_arr[0];
        $port = isset($server_ip_arr[1]) ? $server_ip_arr[1] : 3306;
        $connect_config['dsn'] = "mysql:host={$ip};port={$port};dbname={$db_name}";
        $connect_config['username'] = Yii::$app->params['ADMIN_USER'];
        $connect_config['password'] = Yii::$app->params['ADMIN_PASSWD'];
        $connect_config['charset'] = Yii::$app->params['MARKET_CHARSET'];

        //数据库连接对象
        $executeConnection = new \yii\db\Connection((Object)$connect_config);
        return $executeConnection;
    }

    /**
     * @description 获取用户权限
     * @param
     */
    protected function _getPrivilege($ip,$host,$user,$database,$table = '*',$type = 1)
    {
        $conn = $this->_connectDb($ip, $database == '*' ? 'mysql':$database);
        $sql = "show grants for '{$user}'@'{$host}';";
        $result = $conn->createCommand($sql)->queryAll();
        foreach($result as $value){
            if(current($value) == ''){
                $privilege[] = [];
            }else{
                preg_match(Yii::$app->params['regexp']['privilege_grant'],current($value),$grant_arr);
                $grant_to = explode(".",str_replace('`','',$grant_arr[2]));
                if($database == '*' && $table == '*'){  //对用户操作
                    if($grant_to[0] == '*' && $grant_to[1] == '*'){
                        if($grant_arr[1] == 'ALL PRIVILEGES'){
                            $privilege[] = array_keys(Yii::$app->params['database_privilege_list']);
                        }else {
                            $grant_privilege = explode(",", $grant_arr[1]);
                            $privilege[] = array_map('trim', $grant_privilege);
                        }
                    }
                }elseif($database != '*' && $table == '*'){ //对数据库操作
                    if(($grant_to[0] == '*' && $grant_to[1] == '*') || ($grant_to[0] == $database && $grant_to[1] == '*')){
                        if($grant_arr[1] == 'ALL PRIVILEGES'){
                            $privilege[] = array_keys(Yii::$app->params['database_privilege_list']);
                        }else {
                            $grant_privilege = explode(",", $grant_arr[1]);
                            $privilege[] = array_map('trim', $grant_privilege);
                        }
                    }
                }elseif($database != '*' && $table != '*'){ //对表操作
                    if(($grant_to[0] == '*' && $grant_to[1] == '*') || ($grant_to[0] == $database && $grant_to[1] == '*') || ($grant_to[0] == $database && $grant_to[1] = $table)){
                        if($grant_arr[1] == 'ALL PRIVILEGES'){
                            $privilege[] = array_keys(Yii::$app->params['database_privilege_list']);
                        }else {
                            $grant_privilege = explode(",", $grant_arr[1]);
                            $privilege[] = array_map('trim', $grant_privilege);
                        }
                    }
                }
            }
        }

        $privilege_return = [];
        foreach($privilege as $value){
            $privilege_return = array_merge($privilege_return,$value);
        }
        $privilege_return = array_unique($privilege_return);
        $privilege = [];
        switch($type){
            case 1 :    $privilege_list = Yii::$app->params['database_privilege_list']; break;
            case 2 :    $privilege_list = Yii::$app->params['table_privilege_list']; break;
            case 3 :    $privilege_list = Yii::$app->params['privilege_list']; break;
        }
        foreach($privilege_list as $key=>$value){
            $privilege[$key] = [$value,in_array($key,$privilege_return)?1:0];
        }
        return $privilege;
    }

    /**
     * @description 获取表信息
    */
    protected function _getTableInfo($server_ip,$db_name,$tb_name,$executeConnection)
    {
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
            return $info;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @description 获取用户列表
    */
    protected function _getUserList($ip,$page,$pageSize)
    {
        $conn = $this->_connectDb($ip,'mysql');
        $start = ($page-1)*$pageSize;
        $user_list = $conn->createCommand("select Host,User from user order by User limit {$start},$pageSize;")->queryAll();
        return $user_list;
    }

    /**
     * @description 获取用户总数
    */
    protected function _getUserTotal($ip)
    {
        $conn = $this->_connectDb($ip,'mysql');
        $count = $conn->createCommand("select count(*) from user;")->queryAll();
        return current($count[0]);
    }

    /**
     * @description 判断用户是否存在
    */
    protected function _isExistsPrivilege($ip,$host,$user,$database='*',$table = '*'){
        $conn = $this->_connectDb($ip,$database == '*' ? 'mysql' : $database);
        $result = $conn->createCommand("show grants for '{$user}'@'{$host}'")->queryAll();
        $database = $database == '*' ? $database : "`{$database}`";
        $table = $table == '*' ? $table : "`{$table}`";
        foreach($result as $value){
            if(strpos(current($value),"{$database}.{$table}") == true){
                return true;
            }
        }
        return false;
    }

    /**
     * @description 分页类
     * @param
     * @return pageHtml 分页html
    */
//    protected function _getPageHtml($totalCount,$page,$pageSize)
//    {
//        $pageClass = new Pagination(['totalCount'=>$totalCount,'pageSize' => $pageSize]);
//
//        $firstPageLabel = $page > 1?false:'首页';
//        $prevPageLabel = $firstPageLabel?false:'上一页';
//        $lastPageLabel = (int)$totalCount/$pageSize > (int)$page?false:'尾页';
//        $nextPageLabel = $lastPageLabel?false:'下一页';
//        $pageHtml = LinkPager::widget([
//            'pagination' => $pageClass,
//            'firstPageLabel' =>$firstPageLabel,
//            'nextPageLabel' => $nextPageLabel,
//            'prevPageLabel' => $prevPageLabel,
//            'lastPageLabel' => $lastPageLabel
//        ]);
//        return $pageHtml;
//    }

    /**
     * @description 获取用户规则列表
     * @param server_id 服务器id
     * @return rule_list 规则列表
    */
    protected function _getRuleList($server_id)
    {
        $ruleModel = new Rule();
        $where['server_id'] = $server_id;
        $where['status'] = 1;
        $result = $ruleModel->find()->where($where)->all();
        $ruleList = [];
        foreach($result as $key=>$value){
            $ruleList[$value->user.'@'.$value->host] = true;
        }
        return $ruleList;
    }

    /**
     * @description 验证用户的合法性
     * @param $user 用户
    */
    protected function _verifyUser($user)
    {
        foreach(Yii::$app->params['create_user_filter_keyword'] as $value){
            if(strpos($user,$value) !== false){
                return $value;
            }
        }
        return false;
    }

}

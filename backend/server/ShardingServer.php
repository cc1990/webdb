<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/2/27
 * Time: 10:36
 */

namespace backend\server;

use yii\base\Exception;

class ShardingServer
{
    private $_config_info;
    private $serverIp;
    private $username = 'php';
    private $password = 'phpmysqldb2016';
    private $db_name;
    private $conn;

    /**
     * Description 初始化相关信息
    */
    public function __construct($config_info)
    {
        $this->_config_info = $config_info;
        if(empty($config_info['dbInfoList'][0]['masterIP']))   throw new Exception("该环境服务器不存在!");
        $this->serverIp = $config_info['dbInfoList'][0]['masterIP'];
        $this->db_name = empty($config_info['dbInfoList'][0]['dbName']) ? 'mysql' : $config_info['dbInfoList'][0]['dbName'];
        $this->conn = $this->_connDb();
    }

    /**
     * Description 获取数据库下的所有通用表
    */
    public function getTables($databases = null)
    {
        $databases = empty($databases) ? $this->db_name : $databases;
        $this->conn->createCommand("use {$databases};")->execute();
        $result = $this->conn->createCommand("show tables")->queryAll();
        $tables = [];
        foreach($result as $value){
            $tables[] = current($value);
        }
        return $tables;
    }

    /**
     * Description 获取分库分表表
    */
    public function getShardingTables()
    {
        $databases = $this->getDatabases();
        $result = $tables = [];
        foreach($databases as $database){
            $this->conn->createCommand("use $database")->execute();
            $result = array_merge($result,$this->conn->createCommand("show tables;")->queryAll());
        }
        foreach($result as $value){
            $table = preg_replace('/_[0-9]+/i','',current($value));
            $tables[] = $table;
        }
        return array_unique($tables);
    }

    /**
     * Description 获取所有分库分表数据库
    */
    public function getDatabases($database = null)
    {
        $database = empty($database) ? $this->db_name : $database;
//        $result = $this->conn->createCommand("show databases like '%{$database}_%'")->queryAll();
//        $databases = [];
//        foreach($result as $value){
//            $databases[] = current($value);
//        }
        $databases = [];
        for($i = $this->_config_info['dbInfoList'][0]['dbBeginIndex']; $i <= $this->_config_info['dbInfoList'][0]['dbEndIndex']; $i++){
            $databases[] = $database."_".$i;
        }
        return $databases;
    }

    /**
     * Description 获取表格信息
    */
    public function getTableInfo($tb_name,$info_rule,$list,$database = null)
    {
        $database = empty($database) ? $this->db_name : $database;
        $this->conn->createCommand("use {$database}")->execute();
        $command = $this->conn->createCommand('show create table ' . $tb_name . ";");
        $result = $command->queryAll();
        $create_sql = $result[0]['Create Table'];//获取创建表的SQL语句

        $table_info = strstr($create_sql, "ENGINE=");

        $command = $this->conn->createCommand("show table status where name = '" . $tb_name . "'");
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
    }

    /**
     * Description 获取分库分表的表格信息
    */
    public function getShardingTableInfo($tb_name,$info_rule,$list)
    {
        $tableInfo = $this->_getSharding($tb_name);
        if(empty($tableInfo))   throw new Exception("该分库分表不存在!");
        return $this->getTableInfo($tableInfo['tb_name'],$info_rule,$list,$tableInfo['db_name']);
    }

    /**
     * Description 随机获取数据库和表
    */
    private function _getSharding($tb_name)
    {
//        $databases = $this->conn->createCommand("show databases like '%{$this->db_name}_%'")->queryAll();
//        $return = [];
//        $this->conn->createCommand("use {$this->db_name}_0");
//        $tables = $this->conn->createCommand("show ")
//        foreach($databases as $value){
//            $database = current($value);
//            $this->conn->createCommand("use {$database}")->execute();
//            $tables = $this->conn->createCommand("show tables like '%{$tb_name}_%'")->queryAll();
//            if(!empty($tables)){
//                $return['db_name'] = $database;
//                $return['tb_name'] = current($tables[0]);
//            }
//        }
        $return['db_name'] = $this->db_name . "_0";
        $return['tb_name'] = $tb_name . "_0";
        return $return;
    }

    /**
     * Description 连接数据库
    */
    private function _connDb()
    {
        $connect_config['dsn'] = "mysql:host={$this->serverIp};dbname={$this->db_name}";
        $connect_config['username'] = $this->username;
        $connect_config['password'] = $this->password;
        $connect_config['charset'] = "utf8";

        //数据库连接对象
        $executeConnection = new \yii\db\Connection((Object)$connect_config);
        return $executeConnection;
    }
}
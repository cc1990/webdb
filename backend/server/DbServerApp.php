<?php 
/**
 * 
 */
namespace app\backend\server;

use Yii;

class DbServerApp
{
    public $tableName = "redis_cache";

    public function hget($server_ip, $dbname = 'databases')
    {
        if( $dbname != 'databases' ){
            $sql = "show tables";
            $conn = $this->connectDb($server_ip, $dbname);
        }else{
            $sql = "show databases";
            $conn = $this->connectDb($server_ip);
        }

        $command = $conn->createCommand($sql);
        try {
            $excute_result = $command->queryAll();
            $list = [];
            if( $dbname != 'databases' ){
                foreach($result as $value){
                    $list[] = $value['Tables_in_'.$dbname];
                }
            }else{
                foreach($result as $value){
                    $list[] = $value['Database'];
                }
            }
            $result = implode(",", $list);
            
        } catch (\Exception $e) {
            $result = '';
        }
        return $result;
    }



    public function hmset($ip,$database,$table,$column = '')
    {
        $connection = Yii::$app->db;
        $column = is_array($column) ? implode(",",$column) : $column;
        if($database == "databases"){
            $table = is_array($table) ? implode(",",$table) : $table;
            if($table != $this->hmget($ip,$database)){
                $sql = "delete from ". $this->tableName . " where server_ip = '" . $ip . "' and type = 'databases'";
                $connection->createCommand($sql)->execute();
                $sql = "insert into ". $this->tableName . " (server_ip, `values`, type) values ('" . $ip . "', '" . $table . "', 'databases')";
                $connection->createCommand($sql)->execute();
            }
        }elseif($table == "tables" && $database != 'databases'){
            if($column != $this->hmget($ip,$database,$table)) {
                $sql = "delete from ". $this->tableName . " where server_ip = '" . $ip . "' and db_name = '" . $database . "' and type = 'tables'";
                $connection->createCommand($sql)->execute();
                $sql = "insert into ". $this->tableName . " (server_ip, db_name, `values`, type) values ('" . $ip . "', '" . $database . "', '" . $column . "', 'tables')";
                $connection->createCommand($sql)->execute();
            }
        }else{
            if($column != $this->hmget($ip,$database,$table)) {
                $sql = "delete from ". $this->tableName . " where server_ip = '" . $ip . "' and db_name = '" . $database . "' and tb_name = '" . $table . "' and type = 'columns'";
                $connection->createCommand($sql)->execute();
                $sql = "insert into ". $this->tableName . " (server_ip, db_name, tb_name, `values`, type) values ('" . $ip . "', '" . $database . "', '" . $table . "', '" . $column . "', 'columns')";
                $connection->createCommand($sql)->execute();
            }
        }
    }

    /**
     * Description 获取hash值
    */
    public function hmget($ip,$database,$table = '')
    {

        if($database == "databases"){
            $sql = "select `values` from ". $this->tableName . " where server_ip = '" . $ip . "' and type = 'databases'";
        }elseif($table == "tables"){
            $sql = "select `values` from ". $this->tableName . " where server_ip = '" . $ip . "' and db_name = '" . $database . "' and type = 'tables'";
        }else{
            $sql = "select `values` from ". $this->tableName . " where server_ip = '" . $ip . "' and db_name = '" . $database . "' and tb_name = '" . $table . "' and type = 'columns'";
        }

        $values = [];

        $connection = Yii::$app->db;
        $data = $connection->createCommand($sql)->queryAll();
        if( !empty( $data ) ){
            foreach ($data as $key => $value) {
                @$values[] = $value['values'];
            }
        }
        return implode(",", $values);
    }

    /**
     * 连接数据库
     * @param  [type] $server_ip [description]
     * @param  [type] $db_name   [description]
     * @return [type]            [description]
     */
    public function connectDb( $server_ip, $db_name = 'mysql' ){
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
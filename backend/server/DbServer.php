<?php 
/**
 * 
 */
namespace backend\server;

use Yii;

class DbServer
{
    public $tableName = "redis_cache";

    public function hget($key, $value = '')
    {
        $key_ = explode("-", $key);

        if( count($key_) > 1 ){
            $server_ip = $key_[0];
            $db_name = $key_[1];
        }else{
            $server_ip = $key;
        }

        if( count($key_) > 1 && ( empty( $value ) || $value == 'tables' ) ){
            $sql = "select `values` from ". $this->tableName . " where server_ip = '" . $server_ip . "' and db_name = '" . $db_name . "' and type = 'tables'";
        }elseif( count($key_) > 1 && !empty( $value ) && $value != 'tables' ){
            $sql = "select `values` from ". $this->tableName . " where server_ip = '" . $server_ip . "' and db_name = '" . $db_name . "' and tb_name = '" . $value . "' and type = 'columns'";
        }else{
            $sql = "select `values` from ". $this->tableName . " where server_ip = '" . $server_ip . "' and type = 'databases'";
        }

        $values = "";

        $connection = Yii::$app->db;
        $data = $connection->createCommand($sql)->queryAll();
        if( !empty( $data ) ){
            $values = $data[0]['values'];
        }
        return $values;
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

        $values = "";

        $connection = Yii::$app->db;
        $data = $connection->createCommand($sql)->queryAll();
        if( !empty( $data ) ){
            $values = $data[0]['values'];
        }
        return $values;
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
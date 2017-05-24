<?php
/**
 * Created by PhpStorm.
 * User: gaochaolyf
 * Date: 16/7/1
 * Time: 下午1:51
 */
namespace app\backend\server;

use Yii;

class RedisServer
{
    private $_redis;

    function __construct($redis = null)
    {
        $this->_redis = $redis ? $redis : Yii::$app->redis;
    }

    /**
     * Description 设置redis值
    */
    public function set($key, $value, $is_array = 0, $expire = 0)
    {
        if ($is_array) {
            $data = $this->_redis->SET($key, json_encode($value, JSON_UNESCAPED_UNICODE));
        } else {
            $data = $this->_redis->SET($key, $value);
        }

        if ($expire) {
            $this->_redis->EXPIRE($key, $expire);
        }

        return $data;
    }

    /**
     * Description 获取redis值
    */
    public function get($key, $is_decode = 0)
    {
        $data = array();
        $redis_data = $this->_redis->GET($key);
        if ($redis_data) {
            if ($is_decode) {
                $data = @json_decode($redis_data, true);
            } else {
                $data = $redis_data;
            }
        }

        return $data;
    }

    /**
     * Description hash设置值
    */
    public function hmset($ip,$database,$table,$column = '')
    {
        $column = is_array($column) ? implode(",",$column) : $column;
        if($database == "databases"){
            $table = is_array($table) ? implode(",",$table) : $table;
            if($table != $this->hmget($ip,$database)){
                $this->_redis->hdel($ip,$database);
                $this->_redis->hmset($ip,$database,$table);
            }
        }elseif($table == "tables" && $database != 'databases'){
            if($column != $this->hmget($ip,$database,$table)) {
                $this->_redis->hdel($ip."-".$database,$table);
                $this->_redis->hmset($ip . '-' . $database, $table, $column);
            }
        }else{
            if($column != $this->hmget($ip,$database,$table)) {
                $this->_redis->hdel($ip."-".$database,$table);
                $this->_redis->hmset($ip . '-' . $database, $table, $column);
            }
        }
    }

    /**
     * Description 获取hash值
    */
    public function hmget($ip,$database,$table = '')
    {
        if($database == "databases"){
            $data = $this->_redis->hmget($ip,$database);
        }elseif($table == "tables"){
            $data = $this->_redis->hmget($ip . "-" .$database,$table);
        }else{
            $data = $this->_redis->hmget($ip . "-" .$database,$table);
        }
        return $data[0];
    }

    /**
     * Description 获取所有hash
    */
    public function hgetall($host,$database)
    {
        return $this->_redis->hgetall($host . "-" . $database);
    }
}
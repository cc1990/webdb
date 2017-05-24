<?php
/**
 * Created by PhpStorm.
 * User: gaochaolyf
 * Date: 16/7/1
 * Time: 下午1:51
 */
namespace backend\server;

use Yii;

use backend\server\DbServer;

class RedisBaseServer
{
    private $_redis;

    function __construct($redis = null)
    {
        if( REDIS_STATUS == 0 ){
            $this->_redis = $redis ? $redis : Yii::$app->redis;
        }else{
            $this->_redis = new DbServer();
        }
    }

    /**
     * Description 设置redis值
    */
    public function set($key, $value, $is_array = 0, $expire = 0)
    {
        if($this->redisStatus ==1 ){
            return true;
        }
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
    public function hset($name,$sub,$value,$delete = false)
    {
        if($delete){
            $this->_redis->hdel($name,$sub);
        }

        return $this->_redis->hset($name, $sub, $value);

    }

    /**
     * Description 获取hash值
    */
    public function hget($name,$sub = false)
    {
        if(empty($sub)){
            return $this->_redis->hgetall($name);
        }else{
            return $this->_redis->hget($name,$sub);
        }
    }

    /**
     * Description 获取所有hash
    */
    public function hgetall($name)
    {
        return $this->_redis->hgetall($name);
    }
}
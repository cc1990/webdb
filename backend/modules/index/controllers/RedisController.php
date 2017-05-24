<?php 

namespace backend\modules\index\controllers;

use backend\server\RedisServerWeb;
use Yii;
use yii\web\Controller;


class RedisController extends Controller
{
    private $redisServer = null;

    /**
     * Description 初始化redis
    */
    public function init()
    {
        parent::init();
        $this->redisServer = new RedisServerWeb();
    }

    /**
     * Description 获取联想关键字
    */
    public function actionGetKeyword($host,$database,$table)
    {
        $return[$host] = [];
        $databases = explode(",",$this->redisServer->hmget($host,"databases"));
        $return[$host]["databases"] = $databases;
        $return[$host][$database] = $this->_parse($this->redisServer->hgetall($host,$database));
        $return[$host][$database]["tables"] = explode(",",$this->redisServer->hmget($host,$database,"tables"));
        return json_encode($return);
    }

    private function _parse($singleDatabase)
    {
        $database = [];
        foreach($singleDatabase as $key=>$value){
            if($key%2 == 0) continue;
            $database[$singleDatabase[$key-1]] = explode(",",$value);
        }
        unset($database['tables']);
        return $database;
    }
}
?>
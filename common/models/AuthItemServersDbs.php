<?php

namespace common\models;

use backend\server\RedisBaseServer;
use backend\server\DbServer;
use Yii;

/**
 * This is the model class for table "auth_item_servers".
 *
 * @property string $item_name
 * @property string $server_ids
 * @property string $db_names
 */
class AuthItemServersDbs extends \yii\db\ActiveRecord
{
    public $redisServer;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auth_item_servers_dbs';
    }

    /**
     * Description 更新角色对应服务器的数据库权限
    */
    public function saveData($name,$privilegeList)
    {
        //清空历史权限
        $this->deleteAll(['item_server_name'=>$name]);

        $this->item_server_name = $name;
        foreach($privilegeList as $server_ip=>$value){
            $this->isNewRecord = true;
            $this->server_ip = $server_ip;
            $this->privilege = json_encode($value);
            $this->insert();
        }
    }

    /**
     * Descritpion 获取角色权限
    */
    public function getPrivilege($name)
    {
        $privilegeList = [];
        if(empty($name))    return $privilegeList;
        $result = $this->findAll(['item_server_name'=>$name]);
        foreach($result as $value) {
            $privilegeList[$value['server_ip']] = json_decode($value['privilege'], true);
        }
        return $privilegeList;
    }

    /**
     * Description 获取角色权限
    */
    public function getPrivilegeDetail($privilege)
    {
        $privilegeList = [];
        foreach($privilege as $server_ip=>$databases){
            $privilegeList[$server_ip] = $databases;
            $serverInfo = $this->_getRedisPrivilege($server_ip);
            if($privilegeList[$server_ip] == 'all'){
                $privilegeList[$server_ip] = $serverInfo;
            }else{
                foreach($privilegeList[$server_ip] as $key2=>$value2){
                    if($value2 == 'all'){
                        $privilegeList[$server_ip][$key2] = isset($serverInfo[$key2]) ? $serverInfo[$key2] : [];
                    }
                }
            }
        }

        return $privilegeList;
    }

    /**
     * Description redis中获取数据
    */
    private function _getRedisPrivilege($server_ip)
    {
        if(empty($this->redisServer)){
            $this->redisServer = new RedisBaseServer();
        }
        $databases = $this->redisServer->hget($server_ip,"databases");
        $data = [];
        foreach(explode(',',$databases) as $value){
            if(preg_match('/[ordercenter_|membercenter_]{1}[0-9]+$/i',$value)){
                continue;
            }
            $data[$value] = explode(',',$this->redisServer->hget($server_ip."-".$value,"tables"));
        }
        return $data;
    }
}

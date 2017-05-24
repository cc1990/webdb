<?php

namespace johnitvn\rbacplus\models;

use Yii;
use yii\base\Exception;
use yii\rbac\Item;
use common\models\AuthItemServers;
use backend\server\RedisBaseServer;
use common\models\AuthItemServersDbs;

/**
 * @author John Martin <john.itvn@gmail.com>
 * @since 1.0.0
 */
class Role extends AuthItem {

    public $permissions = [];
//    public $servers = [];
//    public $dbs = '';
    public $sqloperations = '';
    public $environment = '';
    public $sharding_operations = '';
    public $serversPrivilege = [];

    public function init() {
        parent::init();
        if (!$this->isNewRecord) {
            $permissions = [];
            foreach (static::getPermistions($this->item->name) as $permission) {
                $permissions[] = $permission->name;
            }
            $this->permissions = $permissions;
            $servers = AuthItemServers::findByItemName($this->item->name);
            if(!empty($servers)){
                $server_ids = explode(",",$servers->server_ids);
//                $this->servers = $server_ids;
//                $this->dbs = $this->getDbs($servers->item_name);
                $this->serversPrivilege = $this->getPrivilege($servers->item_name);
                $this->sqloperations = $servers->sql_operations;
                $this->sharding_operations = $servers->sharding_operations;
                $this->environment = $servers->environment;
//                foreach($this->sqloperations as $key=>$val){
//                    if(strpos($servers->sql_operations,$key) !== false){
//                        $this->sqloperations[$key]['is_checked'] = 1;
//                    }
//                }
            }
        }
    }

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios['default'][] = 'permissions';
        $scenarios['default'][] = 'servers';
//        $scenarios['default'][] = 'dbs';
        $scenarios['default'][] = 'sqloperations';
        $scenarios['default'][] = 'sharding_operations';
        $scenarios['default'][] = 'environment';
        return $scenarios;
    }

    protected function getType() {
        return Item::TYPE_ROLE;
    }

    public function afterSave($insert,$changedAttributes) {
        $authManager = Yii::$app->authManager;
        $role = $authManager->getRole($this->item->name);
        if (!$insert) {
            $authManager->removeChildren($role);
        }
        if ($this->permissions != null && is_array($this->permissions)) {
            foreach ($this->permissions as $permissionName) {
                $permistion = $authManager->getPermission($permissionName);
                $authManager->addChild($role, $permistion);
            }
        }
    }

    /**
     * Description 删除角色
    */
    public function deleteRole(){
        try {
            $transaction = Yii::$app->getDb()->beginTransaction();
            $this->delete();

        }catch (Exception $e){
            $transaction->rollBack();
            throw $e;
        }
    }

    public function attributeLabels() {
        $labels = parent::attributeLabels();
        $labels['name'] = Yii::t('rbac', 'Role name');
        $labels['permissions'] = Yii::t('rbac', 'Permissions');
        $labels['servers'] = Yii::t('rbac', 'servers');
//        $labels['dbs'] = Yii::t('rbac', 'dbs');
        $labels['sqloperations'] = Yii::t('rbac', 'sqloperations');
        $labels['sharding_operations'] = Yii::t('rbac', 'sharding_operations');
        $labels['environment'] = Yii::t('rbac', 'environment');
        return $labels;
    }

    public static function find($name) {
        $authManager = Yii::$app->authManager;
        $item = $authManager->getRole($name);
        return new self($item);
    }

    public static function getPermistions($name) {
        $authManager = Yii::$app->authManager;
        return $authManager->getPermissionsByRole($name);
    }

    /**
     * Description redis中获取db权限
    */
    public function getDbs($item_name)
    {
        if( REDIS_STATUS == '0' ){
            $redisServer = new RedisBaseServer();
            $privilege = $redisServer->hgetall("privilege"."-".$item_name);
        }else{
            $privilege = '';
        }

        if(empty($privilege)){
            return $this->getDbsFromMysql($item_name);
        }else{
            $return = [];
            foreach($privilege as $key=>$val){
                if($key%2 == 0){
                    $return[$privilege[$key]] = json_decode($privilege[$key+1],true);
                }
            }
        }
        return $return;
    }

    /**
     * Description 从数据库中获取权限数据
    */
    private function getDbsFromMysql($item_name)
    {
        $serversDbs = new AuthItemServersDbs();
        $serverDbsList = $serversDbs->findAll(["item_server_name"=>$item_name]);
        $return = [];
        foreach($serverDbsList as $value){
            $return[$value->server_ip] = json_encode($value->privilege,true);
        }
        return $return;
    }

    /**
     * Description 获取角色权限
    */
    public function getPrivilege($name)
    {
        if(empty($name))    return [];
        $authItemServersDbsModel = new AuthItemServersDbs();
        $result = $authItemServersDbsModel->findAll(['item_server_name'=>$name]);
        $existsPrivilegeList = $privilegeList = [];
        foreach($result as $value){
            $existsPrivilegeList[$value['server_ip']] = json_decode($value['privilege'],true);
        }
        foreach($existsPrivilegeList as $serverIp=>$databases){
            if(!empty($existsPrivilegeList[$serverIp]) && $existsPrivilegeList[$serverIp] == 'all'){
                $privilegeList[$serverIp] = 'all';
            }else{
                foreach($this->_getPrivilegeByServerIp($serverIp) as $database=>$tables){
                    $privilegeList[$serverIp][$database] = [];
                    if(!empty($existsPrivilegeList[$serverIp][$database]) && $existsPrivilegeList[$serverIp][$database] == 'all'){
                        $privilegeList[$serverIp][$database]['_checked'] = true;
                    }
                    foreach($tables as $table){
                        if(empty($table))   continue;
                        if(!empty($existsPrivilegeList[$serverIp][$database]) && $existsPrivilegeList[$serverIp][$database] == 'all'){
                            $privilegeList[$serverIp][$database][$table] = true;
                        }else {
                            $privilegeList[$serverIp][$database][$table] = empty($existsPrivilegeList[$serverIp][$database]) ? false : in_array($table, $existsPrivilegeList[$serverIp][$database]);
                        }
                    }
                }
            }
        }
        return $privilegeList;
    }

    private function _getPrivilegeByServerIp($server_ip){
        $redisServer = new RedisBaseServer();
        $databases = $redisServer->hget($server_ip,"databases");
        $data = [];
        foreach(explode(',',$databases) as $value){
            if(preg_match('/[ordercenter_|membercenter_]{1}[0-9]+$/i',$value)){
                continue;
            }
            $data[$value] = explode(',',$redisServer->hget($server_ip."-".$value,"tables"));
        }
        return $data;
    }

}

<?php

namespace johnitvn\rbacplus\models;

use Yii;
use yii\rbac\Item;
use common\models\AuthItemServers;

/**
 * @author John Martin <john.itvn@gmail.com>
 * @since 1.0.0
 */
class Role extends AuthItem {

    public $permissions = [];
    public $servers = [];
    public $dbs = '';
    public $sqloperations = '';
    public $environment = '';

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
                $this->servers = $server_ids;
                $this->dbs = $servers->db_names;
                $this->sqloperations = $servers->sql_operations;
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
        $scenarios['default'][] = 'dbs';
        $scenarios['default'][] = 'sqloperations';
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

    public function attributeLabels() {
        $labels = parent::attributeLabels();
        $labels['name'] = Yii::t('rbac', 'Role name');
        $labels['permissions'] = Yii::t('rbac', 'Permissions');
        $labels['servers'] = Yii::t('rbac', 'servers');
        $labels['dbs'] = Yii::t('rbac', 'dbs');
        $labels['sqloperations'] = Yii::t('rbac', 'sqloperations');
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
}

<?php

namespace common\models;

use johnitvn\rbacplus\models\AuthItemChild;
use johnitvn\rbacplus\models\Role;
use Yii;
use yii\base\Exception;

/**
 * This is the model class for table "auth_item_servers".
 *
 * @property string $item_name
 * @property string $server_ids
 * @property string $db_names
 */
class AuthItemServers extends \yii\db\ActiveRecord
{
    public $rolePrivilege = [];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auth_item_servers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['item_name'], 'required'],
            [['server_ids', 'db_names', 'sql_operations', 'environment', 'sharding_operations'], 'string'],
            [['item_name'], 'string', 'max' => 64]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'item_name' => 'Item Name',
            'server_ids' => 'Server Ids',
            'db_names' => 'Db Names',
            'environment' => 'Environment',
            'sql_operations' => 'SQL Operations',
        ];
    }

    public static function findByItemName($item_name) {
        $auth_item_servers = AuthItemServers::find()->where(array('item_name' => $item_name))->one();
        if ($auth_item_servers) {
            return new static($auth_item_servers);
        }

        return null;
    }

    public function deleteByItemName($item_name) {
        $auth_item_servers = AuthItemServers::find()->where(array('item_name' => $item_name))->one();
        if ($auth_item_servers) {
            return $auth_item_servers;
        }

        return null;
    }

    public static function findServers($roles) {
        $privilege = $operations = $sharding_operations = $environment = [];
        $authItemServersDbsModel = new AuthItemServersDbs();
        if(!empty($roles)) {
            foreach ($roles as $role) {
                //获取角色的数据库操作权限
                $privilege = empty($privilege) ? $authItemServersDbsModel->getPrivilege($role->name) : AuthItemServers::_merge_privilege($privilege,$authItemServersDbsModel->getPrivilege($role->name));

                $serversModel = new AuthItemServers();
                $serverInfo = $serversModel->findOne($role->name)->toArray();
                $operations = array_merge($operations,explode(",",$serverInfo['sql_operations']));
                $sharding_operations = array_merge($operations,explode(",",$serverInfo['sharding_operations']));
                $environment = array_merge($environment,explode(",",$serverInfo['environment']));
            }
        }
        $return['operations'] = array_filter(array_unique($operations));
        $return['sharding_operations'] = array_filter(array_unique($sharding_operations));
        $return['environment'] = array_filter(array_unique($environment));
        $return['privilege'] = $authItemServersDbsModel->getPrivilegeDetail($privilege);
        $return['server_ids'] = [];
        $return['db_name_array'] = [];
        $serverModel = new Servers();
        $result = $serverModel->find()->asArray()->all();
        $serverList = [];
        foreach ($result as $key=>$value) {
            $serverList[$value['ip']] = $value['server_id'];
        }
        foreach($return['privilege'] as $server_ip=>$databases){
            if( empty( $serverList[$server_ip] ) ){  continue; }

            @$return['server_ids'][] = $serverList[$server_ip];
            foreach($databases as $database=>$tables){
                @$return['db_name_array'][$serverList[$server_ip]][] = $database;
            }
        }

        return $return;
    }

    /**
     * Description 权限合并
    */
    public static function _merge_privilege($privilege1,$privilege2){
        $privilege = [];
        if(empty($privilege1)) return $privilege2;
        if(empty($privilege2))  return $privilege1;

        foreach($privilege1 as $server_ip=>$databases){
            if($databases == 'all' || (isset($privilege2[$server_ip]) && $privilege2[$server_ip] == 'all')) {
                $privilege[$server_ip] = $databases;
                if(isset($privilege2[$server_ip]))    unset($privilege2[$server_ip]);
                continue;
            }

            foreach($databases as $database=>$tables){
                if($tables == 'all' || (isset($privilege2[$server_ip][$database]) && $privilege2[$server_ip][$database] == 'all')){
                    $privilege[$server_ip][$database] = 'all';
                    if(isset($privilege2[$server_ip][$database]))   unset($privilege2[$server_ip][$database]);
                    continue;
                }else{
                    if(isset($privilege2[$server_ip][$database])){
                        $privilege[$server_ip][$database] = array_unique(array_merge($tables,$privilege2[$server_ip][$database]));
                        unset($privilege[$server_ip][$database]);
                    }else{
                        $privilege[$server_ip][$database] = $database;
                    }
                }
            }

            if(isset($privilege2[$server_ip])){
                $privilege[$server_ip] = array_merge($privilege[$server_ip],$privilege2[$server_ip]);
            }
            unset($privilege2[$server_ip]);
        }
        $privilege = array_merge($privilege,$privilege2);
        return $privilege;
    }



    /**
     * Description 修改权限
     */
    public function modifyPrivilege($name,$data,$type = 'update')
    {
        try {
            $transaction = $this->getDb()->beginTransaction();
            if($type == 'update') {
                $model = $this->findOne($name);
            }else{
                $model = $this;
                $model->item_name = $name;
                $model->isNewRecord = true;
            }
//            if (empty($model)) throw new Exception("该角色不存在");

            $model->sql_operations = isset($data['sqloperations']) ? implode(",", $data['sqloperations']) : '';
            $model->environment = isset($data['environment']) ? implode(",", $data['environment']) : '';
            $model->sharding_operations = isset($data['sqlshardingoperations']) ? implode(",", $data['sqlshardingoperations']) : '';
            $model->save();

            //页面访问权限列表添加
            $authItemChildModel = new AuthItemChild();
            $authItemChildModel->saveData($name,isset($data['permissions']) ? $data['permissions'] : '');

            //服务器数据库权限访问列表添加
            $authItemServersDbsModel = new AuthItemServersDbs();
            $authItemServersDbsModel->saveData($name,isset($data['servers']) ? $data['servers'] : []);

            $transaction->commit();
        }catch (Exception $e){
            $transaction->rollBack();
            throw $e;
        }
    }
}

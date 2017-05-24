<?php
/**
 * Created by PhpStorm.
 * User: 童旭华
 * Date: 2016/10/31
 * Time: 10:12
 */
namespace backend\modules\servers\controllers;

use Yii;

/**
 * @description 服务器php用户权限控制
*/
class PrivilegeController extends BaseController
{
    /**
     * @description 用户权限列表
    */
    public function actionIndex()
    {
        $ip = Yii::$app->request->getQueryParam('ip',0);
        $server_list = $this->_getServerList();

        return $this->render('/privilege/index',[
            'server_list'   => $server_list,
            'db_name_array' => $this->servers['db_name_array'],
            'ip'    =>  $ip
        ]);
    }

    /**
     * @description 获取数据库信息
    */
    public function actionGetDbInfo($serverId,$ip)
    {
        $return = ['status'=>0,'data'=>[],'msg'=>'系统错误'];
        if(empty($serverId) || empty($ip)){
            $return['msg'] = '请选择服务器!';
            return  json_encode($return);
        }

        if(isset($this->servers['db_name_array'][$serverId])){
            $return['status'] = 1;
            $return['msg'] = '获取成功';
            $return['data'] = $this->_getTableNum($ip,$this->servers['db_name_array'][$serverId]);
            return json_encode($return);
        }else{
            $return['msg'] = '操作服务器不存在或没有权限，请联系管理员';
            return  json_encode($return);
        }
    }

    /**
     * @description 获取用户或数据库或表的操作权限
    */
    public function actionGetPrivilege($host,$user,$ip,$database,$type = 1,$table = '*')
    {
        $return = ['status'=>1,'msg'=>'系统错误'];
        try{
            if($type == 1){
                $privilege = $this->_getPrivilege($ip,$host,$user,$database,$table,$type);
            }elseif($type == 2){
                $privilege = $this->_getPrivilege($ip,$host,$user,$database,$table,$type);
            }elseif($type == 3){
                $privilege = $this->_getPrivilege($ip,$host,$user,$database,$table,$type);
            }else{
                throw new \Exception("操作类型有误，请重新操作");
            }
            $return['msg'] = '权限获取成功';
            $return['privilege'] = $privilege;
        }catch(\Exception $e){
            $return['status'] = 0;
            $return['msg'] = $e->getMessage();
        }
        return json_encode($return);
    }

    /**
     * @description 保存修改
    */
    public function actionSave()
    {
        $ip = Yii::$app->getRequest()->post('ip');
        $database = Yii::$app->getRequest()->post('database');
        $table = Yii::$app->getRequest()->post('table','*');
        $privilege = Yii::$app->getRequest()->post('privilege');
        $type = Yii::$app->getRequest()->post('type',1);
        $host = Yii::$app->getRequest()->post('host');
        $user = Yii::$app->getRequest()->post('user');
        $return = ['status'=>0,'msg'=>'系统错误'];
        if(empty($ip)){
            $return['msg'] = '请选择服务器';
        }
        if(empty($database)){
            $return['msg'] = '请选择数据库';
        }
        $conn = $this->_connectDb($ip,$database);
        $privilege = explode(";",$privilege);
        foreach($privilege as $value){
            $sub_privilege_arr = explode("=>",$value);
            if(isset($sub_privilege_arr[1]) && $sub_privilege_arr[1] == 'true'){
                $privilege_str[] = $sub_privilege_arr[0];
            }
        }
        if(isset($privilege_str)) {
            $privilege_str = implode(",", $privilege_str);
            $sql = "grant {$privilege_str} on {$database}.{$table} to '{$user}'@'{$host}'";
        }else{
            $sql = 0;
        }
        try {
            $exists = $this->_isExistsPrivilege($ip,$host,$user,$database,$table);
            if($exists) {
                $conn->createCommand("revoke all privileges on {$database}.{$table} from '{$user}'@'{$host}'")->execute();
            }
            if($sql !== 0) {
                $conn->createCommand($sql)->execute();
            }
            $return['status'] = 1;
            $return['msg'] = "服务器:{$ip} '{$user}'@'{$host}' 权限修改成功";
            return json_encode($return);
        }catch(\Exception $e){
            $return['msg'] = $e->getMessage();
            return json_encode($return);
        }
    }

    /**
     * @description 权限获取
    */
    public function actionGet($ip,$host,$user)
    {
        $return = ['status' => 0,'msg' => '系统维护中'];
        if(empty($ip) || empty($host) || empty($user)){
            $return['msg'] = '参数传递错误';
            return json_encode($return);
        }
        try{
            $conn = $this->_connectDb($ip);
            $result = $conn->createCommand("show grants for '{$user}'@'{$host}'")->queryAll();
            $return['status'] = 1;
            $return['data'] = $result;
            $return['msg'] = '数据获取成功';
            return json_encode($return);
        }catch(\Exception $e){
            $return['msg'] = $e->getMessage();
            return json_encode($return);
        }
    }
}
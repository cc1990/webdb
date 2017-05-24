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
 * @description 用户信息
*/
class UserController extends BaseController
{
    /**
     * @description 获取用户表列表
    */
    public function actionGetUserList($server_id,$ip)
    {
        $return = ['status'=>0,'msg'=>'系统维护中'];
        try {
            $page = Yii::$app->request->getQueryParam("page",1);
            $pageSize = Yii::$app->request->getQueryParam("per-page",20);
            $user_list = $this->_getUserList($ip,$page,$pageSize);
            $return['status'] = 1;
            $return['msg'] = '用户列表获取成功';
            $return['data'] = $user_list;
            $return['pageHtml'] = $this->_getPageHtml($this->_getUserTotal($ip),$page,$pageSize);
            $rule_list = $this->_getRuleList($server_id);
            $return['rule_list'] = $rule_list;
            return json_encode($return);
        }catch(\Exception $e){
            $return['msg'] = $e->getMessage();
            return json_encode($return);
        }
    }

    /**
     * @description 用户修改
    */
    public function actionModify($ip,$user,$host,$password,$oldUser,$oldHost)
    {
        $return = ['status' => 0 ,'msg' => '系统维护中'];
        if(empty($ip) || empty($user) || empty($host) || empty($oldHost) || empty($oldUser)){
            $return['msg'] = '参数传递错误，不可为空';
            return json_encode($return);
        }
        if($str = $this->_verifyUser($user)){
            $return['msg'] = "您的用户名存在非法字符:{$str},修改失败";
            return json_encode($return);
        }

        try{
            $conn = $this->_connectDb($ip);
            if($user != $oldUser || $host != $oldHost){ //修改用户名或主机
                $conn->createCommand("rename user '{$oldUser}'@'{$oldHost}' to '{$user}'@'{$host}'")->execute();
            }
            if(!empty($password)) {
                $conn->createCommand("set password for '{$user}'@'{$host}' = password('{$password}')")->execute();
            }
            $return['status'] = 1;
            $return['msg'] = "修改老用户{$oldUser}成功";
            return json_encode($return);
        }catch (\Exception $e){
            $return['msg'] = $e->getMessage();
            return json_encode($return);
        }
    }

    /**
     * @description 用户添加
    */
    public function actionAdd($ip,$user,$host,$password)
    {
        $return = ['status' => 0 ,'msg' => '系统维护中'];
        if(empty($ip) || empty($user) || empty($host) || empty($password)){
            $return['msg'] = '参数传递错误，不可为空';
            return json_encode($return);
        }
        if($str = $this->_verifyUser($user)){
            $return['msg'] = "您的用户名存在非法字符:{$str},添加失败";
            return json_encode($return);
        }

        try{
            $conn = $this->_connectDb($ip);
            $conn->createCommand("create user '{$user}'@'{$host}' identified by '{$password}'")->execute();
            $return['status'] = 1;
            $return['msg'] = "用户{$user}添加成功";
            return json_encode($return);
        }catch (\Exception $e){
            $return['msg'] = $e->getMessage();
            return json_encode($return);
        }
    }

    /**
     * @description 用户删除
    */
    public function actionDelete($ip,$user,$host)
    {
        $return = ['status' => 0 ,'msg' => '系统维护中'];
        if(empty($ip) || empty($user) || empty($host)){
            $return['msg'] = '参数传递错误，不可为空';
            return json_encode($return);
        }

        try{
            $conn = $this->_connectDb($ip);
            $conn->createCommand("drop user '{$user}'@'{$host}'")->execute();
            $return['status'] = 1;
            $return['msg'] = "用户{$user}删除成功";
            return json_encode($return);
        }catch (\Exception $e){
            $return['msg'] = $e->getMessage();
            return json_encode($return);
        }
    }
}
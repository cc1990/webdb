<?php
/**
 * Created by PhpStorm.
 * User: 童旭华
 * Date: 2016/10/31
 * Time: 10:12
 */
namespace backend\modules\servers\controllers;

use Yii;
use backend\modules\servers\models\Rule;

/**
 * @description 用户规则
*/
class RuleController extends BaseController
{
    private $ruleModel = '';
    private $_return = [];
    /**
     * @description 初始化配置
    */
    public function beforeAction($action)
    {
        $this->ruleModel = new Rule();
        $this->_return = ['status'=>0 ,'msg'=>'系统维护中'];
        return parent::beforeAction($action);
    }

    /**
     * @description 首页
     * @param server_id 服务器ID
    */
    public function actionIndex()
    {
        $server_id = Yii::$app->request->getQueryParam("server_id",current($this->servers['server_ids']));
        $rule_list = $this->ruleModel->find()->where(['server_id'=>$server_id])->all();

        return $this->render('/rule/index',[
            'server_id' =>  $server_id,
            'rule_list' =>  $rule_list,
            'server_list'   =>  $this->_getServerList()
        ]);
    }

    /**
     * @description 新增规则
    */
    public function actionAdd($user,$server_id,$host)
    {
        $where['user'] = $user;
        $where['server_id'] = $server_id;
        $where['host'] = $host;
        $userInfo = $this->ruleModel->findOne($where);
        if($userInfo){
            $this->_return['msg'] = '该规则已存在,不可重复提交';
            return json_encode($this->_return);
        }else{
            $this->ruleModel->user = $user;
            $this->ruleModel->host = $host;
            $this->ruleModel->server_id = $server_id;
            $this->ruleModel->status = 1;
            $this->ruleModel->create_time = $this->ruleModel->update_time = time();
            $this->ruleModel->save();
            $this->_return['status'] = 1;
            $this->_return['msg'] = '规则添加成功';
            return json_encode($this->_return);
        }
    }

    /**
     * @description 删除规则
    */
    public function actionDelete($id)
    {
        try{
            $where['id'] = $id;
            $this->ruleModel->findOne($where)->delete();
            $this->_return['status'] = 1;
            $this->_return['msg'] = '删除成功';
            return json_encode($this->_return);
        }catch(\Exception $e){
            $this->_return['msg'] = $e->getMessage();
            return json_encode($this->_return);
        }
    }

    /**
     * @description 删除规则
     */
    public function actionStatus($id,$status)
    {
        try{
            $ruleInfo = $this->ruleModel->findOne(['id'=>$id])->toArray();
            $ruleOne = $this->ruleModel->findOne($id);
            $ruleOne->status = $ruleInfo['status'] ? 0 : 1;
            $ruleOne->save();
            $this->_return['status'] = 1;
            $this->_return['msg'] = '删除成功';
            $this->_return['data'] = $ruleInfo['status'] ? "off" : "on";
            return json_encode($this->_return);
        }catch(\Exception $e){
            $this->_return['msg'] = $e->getMessage();
            return json_encode($this->_return);
        }
    }
}
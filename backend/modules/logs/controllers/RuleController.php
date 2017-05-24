<?php 
namespace backend\modules\logs\controllers;

use Yii;
use backend\controllers\SiteController;
use backend\modules\logs\models\DdlConfigs;

/**
*  DDL相关规则配置
*/
class RuleController extends SiteController
{
    private $_ddl_configs_Model = '';
    private $_return;

    /**
     * @description 初始化函数
    */
    public function beforeAction($action)
    {
        $this->_return = ["status" => 0,"msg"=>"系统维护中"];
        $this->_ddl_configs_Model = new DdlConfigs();
        parent::beforeAction($action);
        return true;
    }

    /**
     * @description 查看DDL列表
    */
    public function actionIndex()
    {
        $page = Yii::$app->request->getQueryParam("page",1);
        $pageSize = Yii::$app->request->getQueryParam("per-page",20);
        $where = [];
        $andWhere = ['and'];
        if(Yii::$app->request->getQueryParam("database",0)) {
            $andWhere[] = ['like','database',Yii::$app->request->getQueryParam("database", 0)];
        }
        if(Yii::$app->request->getQueryParam("table",0)) {
            $andWhere[] = ['like','table',Yii::$app->request->getQueryParam("table", 0)];
        }
        if(Yii::$app->request->getQueryParam("status",'') !== '') {
            $where['status'] = Yii::$app->request->getQueryParam("status", 0);
        }
        $totalCount = $this->_ddl_configs_Model->find()->where($where)->andFilterWhere($andWhere)->count();
        $rule_list = $this->_ddl_configs_Model->find()->where($where)->andFilterWhere($andWhere)->orderBy(['id'=>SORT_DESC])->limit($pageSize)->offset(($page-1)*$pageSize)->all();
        return $this->render("index",[
            'rule_list'  =>  $rule_list,
            'pageHtml'  =>  $this->_getPageHtml($totalCount,$page,$pageSize),
            'search' => Yii::$app->request->getQueryParams()
        ]);
    }

    /**
     * @description 规则添加
    */
    public function actionCreate($database,$table)
    {
        $where['database'] = $database;
        $where['table'] = $table;
        $ruleInfo = $this->_ddl_configs_Model->findOne($where);
        if($ruleInfo){
            $this->_return['msg'] = '该规则已存在,不可重复提交';
            return json_encode($this->_return);
        }else{
            $this->_ddl_configs_Model->database = $database;
            $this->_ddl_configs_Model->table = $table;
            $this->_ddl_configs_Model->status = 1;
            $this->_ddl_configs_Model->create_time = $this->_ddl_configs_Model->update_time = time();
            $this->_ddl_configs_Model->save();
            $this->_return['status'] = 1;
            $this->_return['msg'] = '规则添加成功';
            return json_encode($this->_return);
        }
    }

    /**
     * @description 规则状态变更
     */
    public function actionStatus($id,$status)
    {
        try{
            $ruleInfo = $this->_ddl_configs_Model->findOne(['id'=>$id])->toArray();
            $ruleOne = $this->_ddl_configs_Model->findOne($id);
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

    /**
     * @description 删除规则
     */
    public function actionDelete($id)
    {
        try{
            $where['id'] = $id;
            $this->_ddl_configs_Model->findOne($where)->delete();
            $this->_return['status'] = 1;
            $this->_return['msg'] = '删除成功';
            return json_encode($this->_return);
        }catch(\Exception $e){
            $this->_return['msg'] = $e->getMessage();
            return json_encode($this->_return);
        }
    }

    /**
     * @description 编辑规则
    */
    public function actionUpdate($id,$database,$table)
    {
        if(empty($id) || empty($database) || empty($table)){
            $this->_return['msg'] = '参数传递错误';
            return json_encode($this->_return);
        }

        $where['id'] = $id;
        $ruleOne = $this->_ddl_configs_Model->findOne($where);
        if(empty($ruleOne)){
            $this->_return['msg'] = "数据不存在";
            return json_encode($this->_return);
        }else{
            $ruleOne->database = $database;
            $ruleOne->table = $table;
            $ruleOne->save();
            $this->_return['status'] = 1;
            $this->_return['msg'] = "编辑成功";
            return json_encode($this->_return);
        }
    }
}
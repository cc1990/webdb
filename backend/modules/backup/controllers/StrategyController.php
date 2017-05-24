<?php
/**
 * Description 策略控制器，用于策略的增删改查
 * User: 童旭华
 * Date: 2017/1/16
 * Time: 10:42
 */
namespace backend\modules\backup\controllers;

use Yii;
use backend\controllers\SiteController;
use backend\modules\backup\models\Strategy;
use backend\modules\backup\models\StrategyContent;
use backend\modules\backup\models\BackupShServers;
use backend\modules\backup\models\HostStrategy;

/**
 * Description 策略控制器
 * Author   童旭华
*/
class StrategyController extends SiteController
{
    private $_return = [];
    private $_page = 1;
    private $_pageSize = 20;
    private $_strategyModel;
    private $_strategyContentModel;

    /**
     * Description 数据初始化
    */
    public function init()
    {
        parent::init();
        $this->_return = [
            'status'    =>  0,
            'msg'       =>  '系统维护中'
        ];
        $this->_strategyModel = new Strategy();
        $this->_strategyContentModel = new StrategyContent();
    }

    /**
     * Description 策略展示页
    */
    public function actionIndex()
    {
        $page = Yii::$app->request->getQueryParam('page',$this->_page);
        $pageSize = Yii::$app->request->getQueryParam('per-page',$this->_pageSize);
        $where['status'] = [0,1];
        if(Yii::$app->request->getQueryParam('status','') !== '') $where['status'] = Yii::$app->request->getQueryParam('status',false);
        if(Yii::$app->request->getQueryParam('name',false))   $where = ["and",$where,["like","name",Yii::$app->request->getQueryParam("name",false)]];
        $strategyList = $this->_strategyModel->getAll($where,['id','name','status'],$page,$pageSize);
        $count = $this->_strategyModel->getCount($where);
        $pageHtml = $this->_getPageHtml($count,$page,$pageSize);
        return $this->render('index',[
            'strategyList'  =>  $strategyList,
            'pageHtml'  =>  $pageHtml,
            'search'    =>  Yii::$app->request->getQueryParams()
        ]);
    }

    /**
     * @description 规则状态变更
     */
    public function actionStatus($id,$status)
    {
        try{
            $backupStrategyOne = $this->_strategyModel->findOne($id);
            $backupStrategyOne->status = $status;
            $backupStrategyOne->save();
            $this->_return['status'] = 1;
            $this->_return['msg'] = '删除成功';
            $this->_return['data'] = $status ? "on" : "off";
            return json_encode($this->_return);
        }catch(\Exception $e){
            $this->_return['msg'] = $e->getMessage();
            return json_encode($this->_return);
        }
    }

    /**
     * Description 新增策略
    */
    public function actionAdd()
    {
        if(Yii::$app->request->isPost){
            if(empty(Yii::$app->request->post("name"))) {$this->_return['msg'] = '备份策略名称不可为空!';return json_encode($this->_return);}
            $transaction = $this->_strategyModel->getDb()->beginTransaction();
            try {
                $this->_strategyModel->name = Yii::$app->request->post("name");
                $this->_strategyModel->status = 1;
                $this->_strategyModel->create_time = time();
                $this->_strategyModel->create_person = Yii::$app->users->identity->username;
                $this->_strategyModel->insert();
                $id = Yii::$app->getDb()->getLastInsertID();
                $this->_strategyContentModel->deleteAll(['strategy_id' => $id]);
                foreach(Yii::$app->request->post('strategy_content') as $value){
                    $strategyContentModel = new StrategyContent();
                    $strategyContentModel->strategy_id = $id;
                    $strategyContentModel->type = $value['type'];
                    $strategyContentModel->cycle = $value['cycle'];
                    $strategyContentModel->retention_time = $value['retention_time'];
                    $strategyContentModel->save();
                }
                $transaction->commit();
                $this->_return['status'] = 1;
                $this->_return['msg'] = '策略添加成功';
                return json_encode($this->_return);
            }catch(\Exception $e){
                $transaction->rollback();
                $this->_return['msg'] = $e->getMessage();
                return json_encode($this->_return);
            }
        }else {
            return $this->render('add');
        }
    }

    /**
     * Description 修改策略
    */
    public function actionUpdate()
    {
        if(Yii::$app->request->isPost){
            $id = Yii::$app->request->post('id');
            if(empty(Yii::$app->request->post("name"))) {$this->_return['msg'] = '备份策略名称不可为空!';return json_encode($this->_return);}
            $transaction = $this->_strategyModel->getDb()->beginTransaction();
            try {
                $strategyInfo = $this->_strategyModel->findOne($id);
                $strategyInfo->name = Yii::$app->request->post("name");
                $strategyInfo->status = 1;
                $strategyInfo->create_time = time();
                $strategyInfo->create_person = Yii::$app->users->identity->username;
                $strategyInfo->save();
                $this->_strategyContentModel->deleteAll(['strategy_id' => $id]);
                foreach(Yii::$app->request->post('strategy_content') as $value){
                    $strategyContentModel = new StrategyContent();
                    $strategyContentModel->strategy_id = $id;
                    $strategyContentModel->type = $value['type'];
                    $strategyContentModel->cycle = $value['cycle'];
                    $strategyContentModel->retention_time = $value['retention_time'];
                    $strategyContentModel->save();
                }
                $transaction->commit();
                $this->_return['status'] = 1;
                $this->_return['msg'] = '策略更新成功';
                return json_encode($this->_return);
            }catch(\Exception $e){
                $transaction->rollback();
                $this->_return['msg'] = $e->getMessage();
                return json_encode($this->_return);
            }
        }else{
            $id = Yii::$app->request->getQueryParams('id',0);
            $strategyInfo = $this->_strategyModel->findOne($id)->toArray();
            $where['strategy_id'] = $id;
            $strategyContentList = $this->_strategyContentModel->find()->where($where)->asArray()->all();
            return $this->render('update',[
                'id'    =>  $id,
                'strategyInfo'  =>  $strategyInfo,
                'strategyContentList'   =>  $strategyContentList
            ]);
        }
    }

    /**
     * Description 删除策略
    */
    public function actionDelete($id)
    {
        try{
            $strategyInfo = $this->_strategyModel->findOne($id);
            $strategyInfo->status = 2;
            $strategyInfo->save();
            $this->_return['status'] = 1;
            $this->_return['msg'] = '删除成功';
        }catch(\Exception $e){
            $this->_return['msg'] = $e->getMessage();
        }
        return json_encode($this->_return);
    }

    /**
     * Description 关联备份策略
     */
    public function actionRelation($strategy_id)
    {
        $hostStrategyModel = new HostStrategy();
        $hostList = Yii::$app->request->getQueryParam('hostList');
        $transaction = $hostStrategyModel->getDb()->beginTransaction();
        try{
            $hostStrategyModel->deleteAll(['strategy_id'=>$strategy_id]);
            foreach($hostList as $value){
                $hostStrategyModel = new HostStrategy();
                $hostStrategyModel->strategy_id = $strategy_id;
                $hostStrategyModel->host_id = $value;
                $hostStrategyModel->save();
            }
            $transaction->commit();
            $this->_return['status'] = 1;
            $this->_return['msg'] = '关联成功';
        }catch(\Exception $e){
            $transaction->rollBack();
            $this->_return['msg'] = $e->getMessage();
        }
        return json_encode($this->_return);
    }

    /**
     * Description 获取所有备份策略
     */
    public function actionGetAll($strategy_id)
    {
        $hostModel = new BackupShServers();
        $hostStrategyModel = new HostStrategy();
        $where['status'] = [0,1];
        $hostList = $hostModel->find()->where($where)->asArray()->all();

        $whereRelation['strategy_id'] = $strategy_id;
        $hostStrategyList = $hostStrategyModel->find()->where($whereRelation)->asArray()->all();
        $hostRelation = [];
        foreach($hostStrategyList as $value){$hostRelation[] = $value['host_id'];}

        $host = [];
        foreach($hostList as $value){
            if(in_array($value['id'],$hostRelation)){
                $host[$value['id']] = [['serverIp'=>$value['serverIp'],'serverName'=>$value['serverName']],true];
            }else{
                $host[$value['id']] = [['serverIp'=>$value['serverIp'],'serverName'=>$value['serverName']],false];
            }
        }
        $this->_return['status'] = 1;
        $this->_return['data'] = $host;
        return json_encode($this->_return);
    }
}
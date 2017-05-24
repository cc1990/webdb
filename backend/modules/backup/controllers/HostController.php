<?php

namespace backend\modules\backup\controllers;

use Yii;
use backend\controllers\SiteController;
use backend\modules\backup\models\BackupShServers;
use backend\modules\backup\models\HostStrategy;
use backend\modules\backup\models\Strategy;

/**
 * Description 备份主机列表，展示主机的相关操作
 */
class HostController extends SiteController
{
    private $_backupShServers;
    private $_page = 1;
    private $_pageSize = 20;
    private $_return;

    /**
     * Description 初始化函数
    */
    public function init()
    {
        parent::init();
        $this->_backupShServers = new BackupShServers();
        $this->_return = [
            "status"    =>  0,
            "msg"       =>  '系统维护中'
        ];
    }

    /**
     * Description 展示备份主机列表
     * @return mixed
     */
    public function actionIndex()
    {
        $page = Yii::$app->request->getQueryParam("page",$this->_page);
        $pageSize = Yii::$app->request->getQueryParam("per-page",$this->_pageSize);
        $where['status'] = [0,1];
        if(Yii::$app->request->getQueryParam("serverIp",false)) $where = ["and",$where,["like","serverIp",Yii::$app->request->getQueryParam("serverIp",false)]];
        if(Yii::$app->request->getQueryParam("serverName",false)) $where = ["and",$where,["like","serverName",Yii::$app->request->getQueryParam("serverName",false)]];
        if(Yii::$app->request->getQueryParam("status",false)) $where = ["and",$where,["like","status",Yii::$app->request->getQueryParam("status",false)]];
        $serverList = $this->_backupShServers->getAll($where,["id","serverIp","serverName","disk","scriptFile","backupPath","logPath","archiveIp","archivePath","status"],$page,$pageSize);
        return $this->render("index",[
            "serverList"    =>  $serverList,
            "search"        =>  array_merge(Yii::$app->request->getQueryParams(),["page"=>$page,"pageSize"=>$pageSize]),
            "pageHtml"      =>  $this->_getPageHtml($this->_backupShServers->getCount($where),$page,$pageSize)
        ]);
    }

    /**
     * Description 更新备份主机列表
    */
    public function actionUpdate($backupHost)
    {
        $backupHost = array_filter(array_unique(explode("-",$backupHost)));
        try {
            $this->_backupShServers->updateAll(["status"=>2]);
            foreach($backupHost as $value){
                $backupShServers = new BackupShServers();
                $info = explode("|",$value);
                $serverInfo = $backupShServers->findOne(['serverIp'=>$info[0]]);
                if(!empty($serverInfo)) {
                    $backupShServers = $backupShServers->findOne($serverInfo['id']);
                }
                $backupShServers->serverIp = $info[0];
                $backupShServers->serverName = $info[1];
                $backupShServers->backupPath = $info[2];
                $backupShServers->status = 1;
                $backupShServers->create_time = $this->_backupShServers->update_time = time();
                $backupShServers->create_person = $this->_backupShServers->update_person = Yii::$app->users->identity->username;
                $backupShServers->save();
            }
            $this->_return['status'] = 1;
            $this->_return['msg'] = '需备份主机更新成功';
            return json_encode($this->_return);
        }catch (\Exception $e){
            $this->_return['msg'] = $e->getMessage();
            return json_encode($this->_return);
        }
    }

    /**
     * Description 规则状态变更
     */
    public function actionStatus($id,$status)
    {
        try{
            $backupServersOne = $this->_backupShServers->findOne($id);
            $backupServersOne->status = $status;
            $backupServersOne->save();
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
     * Description 关联备份策略
    */
    public function actionRelation($host_id)
    {
        $hostStrategyModel = new HostStrategy();
        $strategyList = Yii::$app->request->getQueryParam('strategyList');
        $transaction = $hostStrategyModel->getDb()->beginTransaction();
        try{
            $hostStrategyModel->deleteAll(['host_id'=>$host_id]);
            foreach($strategyList as $value){
                $hostStrategyModel = new HostStrategy();
                $hostStrategyModel->host_id = $host_id;
                $hostStrategyModel->strategy_id = $value;
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
    public function actionGetAll($host_id)
    {
        $strategyModel = new Strategy();
        $hostStrategyModel = new HostStrategy();
        $where['status'] = [0,1];
        $strategyList = $strategyModel->find()->where($where)->asArray()->all();

        $whereRelation['host_id'] = $host_id;
        $hostStrategyList = $hostStrategyModel->find()->where($whereRelation)->asArray()->all();
        $strategyRelation = [];
        foreach($hostStrategyList as $value){$strategyRelation[] = $value['strategy_id'];}

        foreach($strategyList as $value){
            if(in_array($value['id'],$strategyRelation)){
                $strategy[$value['id']] = [['name'=>$value['name']],true];
            }else{
                $strategy[$value['id']] = [['name'=>$value['name']],false];
            }
        }
        $this->_return['status'] = 1;
        $this->_return['data'] = $strategy;
        return json_encode($this->_return);
    }

    /**
     * Description 更新主机属性
    */
    public function actionSave()
    {
        $request = Yii::$app->request->getQueryParams();
        if(empty($request['id'])){
            $this->_return['msg'] = '请选择更新主机!';
            return json_encode($this->_return);
        }
        $hostInfo = $this->_backupShServers->findOne($request['id']);
        if(empty($hostInfo)){
            $this->_return['msg'] = '该主机不存在!';
            return json_encode($this->_return);
        }
        $hostInfo->disk = $request['disk'];
        $hostInfo->serverName = $request['serverName'];
        $hostInfo->scriptFile = $request['scriptFile'];
        $hostInfo->logPath = $request['logPath'];
        $hostInfo->backupPath = $request['backupPath'];
        $hostInfo->archiveIp = $request['archiveIp'];
        $hostInfo->archivePath = $request['archivePath'];
        $hostInfo->save();
        $this->_return['status'] = 1;
        $this->_return['msg'] = '更新成功!';
        return json_encode($this->_return);
    }

    /**
     * Description 获取主机列表
    */
    public function actionGet()
    {
        $hostList = [
            ['serverIp'=>'192.168.3.236','serverName'=>'3_236','backupDir'=>'/data/db_backup/current_backup/'],
            ['serverIp'=>'192.168.5.122','serverName'=>'5_122','backupDir'=>'/data/db_backup/current_backup/'],
            ['serverIp'=>'192.168.5.123','serverName'=>'5_123','backupDir'=>'/data/db_backup/current_backup/'],
            ['serverIp'=>'192.168.5.139','serverName'=>'5_139','backupDir'=>'/data/db_backup/current_backup/'],
            ['serverIp'=>'192.168.69.114','serverName'=>'69_114','backupDir'=>'/data2/db_backup/current_backup/'],
            ['serverIp'=>'192.168.69.152','serverName'=>'69_152','backupDir'=>'/data/db_backup/current_backup/'],
            ['serverIp'=>'192.168.70.100','serverName'=>'70_100','backupDir'=>'/data/db_backup/current_backup/'],
            ['serverIp'=>'192.168.70.19','serverName'=>'70_19','backupDir'=>'/data/db_backup/current_backup/'],
            ['serverIp'=>'192.168.70.8','serverName'=>'70_8','backupDir'=>'/data/db_backup/current_backup/'],
            ['serverIp'=>'192.168.5.70','serverName'=>'5_70','backupDir'=>'/data/db_backup/current_backup/'],
        ];
        $this->_return['status'] = 1;
        $this->_return['msg'] = '主机获取成功';
        $this->_return['data'] = $hostList;
        return json_encode($this->_return);
    }
}

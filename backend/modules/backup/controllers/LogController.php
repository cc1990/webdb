<?php

namespace backend\modules\backup\controllers;

use Yii;
use backend\controllers\SiteController;
use backend\modules\backup\models\Logs;

/**
 * Description 备份主机列表，展示主机的相关操作
 */
class LogController extends SiteController
{
    private $_logsModel;
    private $_page = 1;
    private $_pageSize = 20;

    /**
     * Description 初始化函数
    */
    public function init()
    {
        parent::init();
        $this->_logsModel = new Logs();
    }

    /**
     * Description 展示备份主机列表
     * @return mixed
     */
    public function actionIndex()
    {
        $page = Yii::$app->request->getQueryParam("page",$this->_page);
        $pageSize = Yii::$app->request->getQueryParam("per-page",$this->_pageSize);
        $where = [];
        if(Yii::$app->request->getQueryParam("server_ip",false)) $where['server_ip'] = Yii::$app->request->getQueryParam("server_ip",false);
//        if(Yii::$app->request->getQueryParam("archive_ip",false)) $where = ["and",$where,["like","archive_ip",Yii::$app->request->getQueryParam("archive_ip",false)]];
        if(Yii::$app->request->getQueryParam("status",false)) $where['status'] = Yii::$app->request->getQueryParam("status",false);
        if(Yii::$app->request->getQueryParam("type",false)) $where['type'] = Yii::$app->request->getQueryParam("type",false);
        $logList = $this->_logsModel->getAll($where,[],$page,$pageSize,'start_time desc');

        $serverList = $this->_logsModel->getServerList();
        return $this->render("index",[
            "logList"    =>  $logList,
            "serverList"    =>  $serverList,
            "search"        =>  array_merge(Yii::$app->request->getQueryParams(),["page"=>$page,"pageSize"=>$pageSize]),
            "pageHtml"      =>  $this->_getPageHtml($this->_logsModel->getListCount($where),$page,$pageSize)
        ]);
    }
}

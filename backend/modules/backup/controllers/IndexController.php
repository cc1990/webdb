<?php

namespace backend\modules\backup\controllers;

use backend\modules\backup\models\BackupShServers;
use Yii;
use backend\controllers\SiteController;
use backend\server\SshServer;
use backend\server\LogServer;
use backend\modules\backup\models\Logs;

/**
 * Description 备份
 */
class IndexController extends SiteController
{
    private $logsModel;
    private $serversModel;
    private $_page = 1;
    private $_pageSize = 20;

    public function init()
    {
        parent::init();
        $this->logsModel = new Logs();
        $this->serversModel = new BackupShServers();
    }

    /**
     * Description 首页
     * @return mixed
     */
    public function actionIndex()
    {
        set_time_limit(0);
        $serverList = $this->serversModel->find()->where(['status'=>1])->asArray()->all();

        $page = Yii::$app->request->getQueryParam('page',$this->_page);
        $pageSize = Yii::$app->request->getQueryParam('per-page',$this->_pageSize);
        $where['backup_sh_servers.status'] = 1;
        $update = true;
        if(Yii::$app->request->getQueryParam("server_ip",false)){
            $where['server_ip'] = ['like',Yii::$app->request->getQueryParam("server_ip",false)];
            $update = false;
        }
        if(Yii::$app->request->getQueryParam("serverName",false)){
            $where['serverName'] = ['like',Yii::$app->request->getQueryParam("serverName",false)];
            $update = false;
        }
        if(Yii::$app->request->getQueryParam("archive_ip",false)) {
            $where['archive_ip'] = ['like', Yii::$app->request->getQueryParam("archive_ip", false)];
            $update = false;
        }
        if($update) $this->_updateLog($serverList); //更新日志
//        if(Yii::$app->request->getQueryParam("status",false)) $where['tmp.status'] = Yii::$app->request->getQueryParam("status",false);

        $logList = $this->logsModel->getControlList($where,$page,$pageSize);
        $count = $this->logsModel->getCount($where);
        return $this->render("index",[
            'logList'   =>  $logList,
            'pageHtml'  =>  $this->_getPageHtml($count,$page,$pageSize),
            'search'    =>  Yii::$app->request->getQueryParams()
        ]);
    }

    /**
     * Description 更新当前备份记录
    */
    private function _updateLog($serverList)
    {
        foreach($serverList as $server){
            try {
                $logsServer = new LogServer(new SshServer($server['serverIp']));
                $logsServer->run();
            }catch(\Exception $e){
                continue;
            }
        }
    }
}

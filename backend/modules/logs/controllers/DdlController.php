<?php 
namespace backend\modules\logs\controllers;

use backend\modules\logs\models\DdlConfigs;
use backend\modules\logs\models\ExecuteLogs;
use Yii;
use backend\controllers\SiteController;

/**
*  DDL日志管理
*/
class DdlController extends SiteController
{
    private $_executeLogsModel = '';
    /**
     * @description 初始化函数
    */
    public function beforeAction($action)
    {
        $this->_executeLogsModel = new ExecuteLogs();
        parent::beforeAction($action);
        return true;
    }

    /**
     * @description 查看DDL列表
    */
    public function actionIndex()
    {
        $where['sqloperation'] = 'ddl';
        $where['environment'] = 'pre';
        if(Yii::$app->request->getQueryParam("host",0)) {
            $where['host'] = Yii::$app->request->getQueryParam("host", 0);
        }
        if(Yii::$app->request->getQueryParam("username",0)) {
            $where['username'] = Yii::$app->request->getQueryParam("username", 0);
        }
        if(Yii::$app->request->getQueryParam("database",0)) {
            $where['database'] = Yii::$app->request->getQueryParam("database", 0);
        }
        if(Yii::$app->request->getQueryParam("project_name",0)) {
            $where['project_name'] = Yii::$app->request->getQueryParam("project_name", 0);
        }
        if(Yii::$app->request->getQueryParam("table",0)) {
            $where['script'] = Yii::$app->request->getQueryParam("table", 0);
        }

        //数据过滤
        $ruleModel = new DdlConfigs();
        $rule = $ruleModel->getRule();

        $totalCount = $this->_executeLogsModel->getCount($where,$rule);
        $ddl_list = $this->_executeLogsModel->getList($where,$rule,Yii::$app->request->getQueryParam("limit",20),Yii::$app->request->getQueryParam("page",1));

        return $this->render("index",[
            'ddl_list'  =>  $ddl_list,
            'pageHtml'  =>  $this->_getPageHtml(current($totalCount[0]),Yii::$app->request->getQueryParam("limit",20),Yii::$app->request->getQueryParam("page",1)),
            'search'    =>  Yii::$app->request->getQueryParams(),
        ]);
    }
}
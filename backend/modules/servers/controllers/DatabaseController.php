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
 * @description 数据库查看
*/
class DatabaseController extends BaseController
{
    /**
     * @description 获取数据库列表
    */
    public function actionIndex($ip,$user,$host,$server_id)
    {
        $page = Yii::$app->request->getQueryParam("page",1);
        $pageSize = Yii::$app->request->getQueryParam("pageSize",20);
        $conn = $this->_connectDb($ip,'mysql');
        $result = $conn->createCommand("show databases")->queryAll();
        $database_arr = [];
        foreach ($result as $value) {
            $database_arr[] = current($value);
        }
        $database_list = $this->_getTableNum($ip,$database_arr);
        $pageHtml = $this->_getPageHtml(count($database_list),$page,$pageSize);
        $database_list = array_slice($database_list,($page-1)*$pageSize,$pageSize);

        return $this->render('/database/index',[
            'database_list'   => $database_list,
            'info'  =>  ['ip'=>$ip,'user'=>$user,'host'=>$host,'server_id'=>$server_id],
            'pageHtml'  =>  $pageHtml,
            'rule_list' =>  $this->_getRuleList($server_id)
        ]);
    }
}
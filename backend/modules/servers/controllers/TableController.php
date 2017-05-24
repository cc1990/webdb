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
 * @description 表格信息
*/
class TableController extends BaseController
{
    /**
     * @description 获取用户表列表
    */
    public function actionIndex($ip,$host,$user,$database,$server_id)
    {
		$page = Yii::$app->request->getQueryParam("page",1);
        $pageSize = Yii::$app->request->getQueryParam("per-page",20);
        $conn = $this->_connectDb($ip,$database);
        $result = $conn->createCommand("show tables")->queryAll();
		$pageHtml = $this->_getPageHtml(count($result),$page,$pageSize);
		$result = array_slice($result,($page-1)*$pageSize,$pageSize);
        foreach($result as $key=>$value){
            $table_info = $this->_getTableInfo($ip,$database,current($value),$conn);
            $table_list[] = [
                'db_name'  =>  $table_info['list'][0]['value'],
                'tb_name'  =>  $table_info['list'][1]['value'],
                'num'  =>  $table_info['list'][2]['value'],
                'create_time'  =>  $table_info['list'][7]['value'],
                'engine'  =>  $table_info['list'][3]['value'],
                'auto_increment'  =>  $table_info['list'][5]['value'],
            ];
        }

        return $this->render('/table/index',[
            'table_list'   => $table_list,
            'info'  =>  ['ip'=>$ip,'database'=>$database,'user'=>$user,'host'=>$host,'server_id'=>$server_id],
            'pageHtml'  =>	$pageHtml,
            'rule_list' =>  $this->_getRuleList($server_id)
        ]);
    }
}
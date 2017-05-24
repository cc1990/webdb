<?php 

namespace backend\modules\operat\controllers;


use Yii;
use backend\controllers\SiteController;

//服务器模型
use common\models\Servers;
use common\models\AuthItemServersDbs;

use vendor\twl\tools\utils\Output;

use backend\server\RedisServerWeb;
use backend\server\DbServer;

/**
* 手动更新缓存
*/
class CacheController extends SiteController
{
    public function actionIndex()
    {
        //获取服务器列表
        $server_list = Servers::find()->where(['status' => 1])->orderBy('server_id')->asArray()->all();
        $default_host = \Yii::$app->session->get('default_host');
        if(!empty($default_host)){
            foreach($server_list as $key=>$val){
                if($default_host != $val['ip'])
                    unset($server_list[$key]);
            }
            foreach($this->servers['privilege'] as $key=>$val){
                if($default_host != $key){
                    unset($this->servers['privilege'][$key]);
                }
            }
        }

        $server_list_new = array();
        foreach($server_list as $key=>$value){
            if(isset($this->servers['privilege'][$value['ip']])){
                $server_list_new[$value['ip']] = $value;
            }
        }
        $data['server_list'] = $server_list_new;

        return $this->render('index', $data);
    }

    public function actionUpdate()
    {
        $data = Yii::$app->request->post();
        $server_ip = $data['server_ip'];
        @$db_name = $data['db_name'];

        $redis = new RedisServerWeb();
        $dbserver = new DbServer();
        if( !empty( $db_name ) && is_array( $db_name ) ){
            foreach ($db_name as $key => $db) {

                $conn = $this->connectDb($server_ip, $db);
                $result = $conn->createCommand("show tables;")->queryAll();
                $tb_list = [];
                foreach($result as $value){
                    $tb_list[] = $value['Tables_in_'.$db];
                }

                $dbserver->hmset($server_ip, $db, 'tables', implode(",", $tb_list));
                $redis->hmset($server_ip, $db, 'tables', implode(",", $tb_list));
            }
        }elseif( empty( $db_name ) ){
            $result = $this->getDb( $server_ip );
            if( isset( $result['error'] ) ){
                Output::error("更新失败");
            }else{
                $db_name_ = implode(",", $result['list']);
                $dbserver->hmset($server_ip, "databases", $db_name_);
                $redis->hmset($server_ip, "databases", $db_name_);
            }
            
        }
        Output::success("更新缓存成功");
    }

    public function actionGetDb()
    {
        $server_ip = trim( $_GET['server_ip'] );
        $result = $this->getDb( $server_ip );
        if( isset( $result['error'] ) ){
            Output::error( $result['error'] );
        }else{
            Output::success("查询成功", $result['list']);
        }
    }

    public function getDb( $server_ip )
    {
        if( !empty( $server_ip ) ){
            try {
                $conn = $this->connectDb($server_ip, 'mysql');
                $result = $conn->createCommand("show databases;")->queryAll();
                $db_list = [];
                foreach($result as $value){
                    $db_list[] = $value['Database'];
                }
                $info['list'] = $db_list;
            }catch(\Exception $e){
                $info['error'] = "数据库操作失败:".$e->getMessage();
            }
        }else{
            $info['error'] = "服务器IP不能为空";
        }

        return $info;
    }

    /**
     * 连接数据库
     * @param  [type] $server_ip [description]
     * @param  [type] $db_name   [description]
     * @return [type]            [description]
     */
    public function connectDb( $server_ip, $db_name ){
        //组合数据库配置
        $connect_config['dsn'] = "mysql:host=$server_ip;dbname=$db_name";
        $connect_config['username'] = Yii::$app->params['MARKET_USER'];
        $connect_config['password'] = Yii::$app->params['MARKET_PASSWD'];
        $connect_config['charset'] = Yii::$app->params['MARKET_CHARSET'];

        //数据库连接对象
        $executeConnection = new \yii\db\Connection((Object)$connect_config);
        return $executeConnection;
    }
}
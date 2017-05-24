<?php 
namespace backend\modules\index\controllers;

use Yii;
use backend\controllers\SiteController;

use common\models\Servers;
use common\models\AuthItemServers;

use backend\modules\index\models\Createdb;
use backend\modules\index\models\ServerDbs;
use backend\modules\index\models\CreatedbSearch;

use vendor\twl\tools\utils\Output;
use yii\base\Exception;

/**
* 
*/
class CreatedbController extends SiteController
{
    public function actionIndex()
    {
        $searchModel = new CreatedbSearch();
        $searchModel->scenario = 'search';
        $dataProvider = $searchModel->search( Yii::$app->request->queryParams );
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionCreate()
    {
        $model = new Createdb();
        $model->scenario = 'create';

        if ( $model->load( Yii::$app->request->post() ) && $model->validate() ) {
            $server_id = $_POST['server_id'];
            $server_data = Servers::find()->select('ip')->where(['in', 'server_id', $server_id])->asArray()->all();
            $server_ip_arr = array();
            foreach ($server_data as $key => $value) {
                $server_ip_arr[] = $value['ip'];
            }

            $model->server_ip = implode(",", $server_ip_arr);
            $model->server_id = implode(",", $server_id);
            $model->status = 1;

            //完成建库的添加
            $model->insert();
            return $this->redirect('index');
        }else{
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
            return $this->render('create', ['model' => $model, 'server_list' => $server_list]);
        }
    }

    public function actionUpdate( $id )
    {
        $model = Createdb::findOne($id);
        $model->scenario = 'update';

        if( empty( $model->id ) ){
            Output::error("未查询到该信息！");
        }

        $status = $model->status;
        if( $status == 1 ){
            $result = $this->nextstep1( $model );

        }elseif( $status ==2 ){
            $result = $this->nextstep2( $model );
        }elseif( $status ==3 ){
            $result = $this->nextstep3( $model );
        }elseif( $status ==4 ){
            $result = $this->nextstep4( $model );
        }elseif( $status == 5 ){
            $result = $this->nextstep5( $model );
        }elseif( $status == 6 ){
            $result = $this->nextstep6( $model );
        }

        if( isset( $result['error'] ) ){
            Output::error($result['error']);
        }else{
            Output::success('执行成功');
        }
    }

    public function actionDelete( $id )
    {
        $model = Createdb::find($id);
        $model->delete();
    }

    /**
     * 第一次执行下一步，做环境授权
     * @param  [type] $model [description]
     * @return [type]        [description]
     */
    private function nextstep1( $model )
    {
        $info = array();
        $server_id = $model->server_id;
        $db_name = $model->db_name;
        $server_list = Servers::find()->where(['in', 'server_id', $server_id])->asArray()->all();
        if( !empty( $server_list ) ){
            $execute = true;
            foreach ($server_list as $key => $value) {
                $environment = $value['environment'];
                $server_ip = $value['ip'];
                $executeConnection = $this->connectDb( $server_ip );

                $grant_sql = $this->grant_sql( $environment, $db_name );
                if( !empty( $grant_sql ) ){
                    foreach ($grant_sql as $k => $sql) {
                        try{
                            $command = $executeConnection->createCommand($sql);
                            $command->execute();
                            $command = $executeConnection->createCommand("flush privileges");
                            $command->execute();
                        }catch( \Exception $e ){
                            $info['error'] = "授权失败，授权SQL：" . $sql . "，错误信息：".$e->getMessage();
                            $execute = false;
                            break;
                        }
                    }
                }
                if( $execute == false ){
                    break;
                }
            }
            if( $execute ){
                $model->status = 2;
                $model->save();
            }
        }

        return $info;
    }

    /**
     * 第二次执行下一步，做角色授权
     * @param  [type] $model   [description]
     * @param  [type] $db_name [description]
     * @return [type]          [description]
     */
    public function nextstep2( $model )
    {
        $info = array();
        $server_id = $model->server_id;
        $role_array = array('dev_op', 'dev_query', 'offline_db_dml', 'offline_db_ddl', 'trunk_dml', 'trunk_ddl', 'yunwei', '管理员');
        $db_name = $model->db_name;
        $server_list = Servers::find()->where(['in', 'server_id', $server_id])->asArray()->all();
        if( !empty( $server_list ) ){
            $execute = true;
            foreach ($server_list as $key => $value) {
                $server_ip = $value['ip'];
                foreach ($role_array as $k => $role) {
                    $server_dbs_model_ = ServerDbs::find()->where(['item_server_name' =>$role, 'server_ip' => $server_ip ])->one();
                    if( !empty( $server_dbs_model ) && !empty($privilege_json = $server_dbs_model->privilege) ){
                        $privilege = json_decode($privilege_json);
                        if( $privilege != 'all' ){
                            $privilege[] = array("$db_name" => 'all');
                            $server_dbs_model_->privilege = json_encode( $privilege );
                        }
                    }
                }
            }
            $new_role = array('gray_', 'online_');

            foreach ($new_role as $k => $o) {
                $role_name = $o.$db_name;
                $item_server_model = AuthItemServers::find()->where(['item_name' => $role_name])->one();
                if( empty( $item_server_model ) ){
                    $item_server_model->item_name = $role_name;
                    $item_server_model->sql_operations = "DQL";
                    $item_server_model->insert();
                }
            }
        }

        $model->status = 3;
        $model->save();

        return $info;
    }

    /**
     * 第三次执行下一步，做域名解析
     * @param  [type] $model   [description]
     * @param  [type] $db_name [description]
     * @return [type]          [description]
     */
    public function nextstep3( $model )
    {
        $info = array();

        $model->status = 4;
        $model->save();

        return $info;
    }

    /**
     * 第四次执行下一步，配置iptables
     * @param  [type] $model   [description]
     * @param  [type] $db_name [description]
     * @return [type]          [description]
     */
    public function nextstep4( $model )
    {
        $info = array();

        $model->status = 5;
        $model->save();
        
        return $info;
    }

    /**
     * 第五次执行下一步，配置独立
     * @param  [type] $model   [description]
     * @param  [type] $db_name [description]
     * @return [type]          [description]
     */
    public function nextstep5( $model )
    {
        $info = array();

        if( $model->is_independent_db == 1 ){
            $model->status = 6;
        }else{
            $model->status = 0;
        }
        
        $model->save();
        
        return $info;
    }

    /**
     * 最后一步
     * @param  [type] $model   [description]
     * @param  [type] $db_name [description]
     * @return [type]          [description]
     */
    public function nextstep6( $model )
    {
        $info = array();

        $model->status = 0;
        $model->save();
        
        return $info;
    }

    /**
     * 第二步授权的SQL语句
     * @param  [type] $environment [description]
     * @param  [type] $db_name     [description]
     * @return [type]              [description]
     */
    private function grant_sql( $environment, $db_name ){
        $grant_sql = array();

        if( empty( $environment ) || empty( $db_name ) ){
            return $grant_sql;
        }

        $grant_sql = array(
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'toowell'@'172.30.%'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'toowell'@'192.168.0.%'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'toowell'@'192.168.1.%'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'toowell'@'192.168.2.%'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'toowell'@'192.168.3.%'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'toowell'@'192.168.4.%'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'toowell'@'192.168.5.%'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'toowell'@'192.168.6.%'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'toowell'@'192.168.7.%'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'otter_op'@'192.168.0.4'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'otter_op'@'192.168.0.96'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'otter_op'@'192.168.5.63'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'otter_op'@'192.168.5.90'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'atp'@'192.168.0.70'",
            "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'atp'@'172.30.220.151'",
        );

        if( $environment == 'dev' ){
            $grant_sql[] = "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'toowell'@'%'";
        }elseif( $environment == 'test' ){
            $grant_sql[] = "GRANT SELECT ON " . $db_name . ".* TO 'toowell'@'%'";
        }else if( $environment == 'test_trunk' ){
            $grant_sql[] = "GRANT SELECT ON " . $db_name . ".* TO 'toowell'@'%'";
            $grant_sql[] = "GRANT SELECT, INSERT, UPDATE, DELETE ON " . $db_name . ".* TO 'toowell_kf'@'%';";
        }

        return $grant_sql;
    }

    public function connectDb( $server_ip, $db_name = 'mysql' ){
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
<?php 
namespace backend\modules\operat\controllers;

use Yii;
use backend\controllers\SiteController;

use backend\modules\operat\models\Authorize;
use backend\modules\operat\models\AuthorizeSearch;

use common\models\Servers;

/**
* 
*/
class AuthorizeController extends SiteController
{
    
    public function actionIndex()
    {
        $searchModel = new AuthorizeSearch();
        $dataProvider = $searchModel->search( Yii::$app->request->queryParams );
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
            ]
        );
    }

    public function actionCreate()
    {
        $model = new Authorize();

        if( $model->load( Yii::$app->request->post() ) && $model->validate() ){
            if( $model->type == 'sharding' ){
                $model->server_id = 0;
                $model->server_ip = '';
            }else{
                $model->environment = '';
                $server = Servers::findOne($model->server_id);
                $model->server_ip = $server['ip'];
            }
            $model->sqloperation = implode(",", $model->sqloperation);

            if( $model->save() ){
                return $this->redirect('index');
            }
        }

        //获取服务器列表
        $Servers = new Servers();
        $server_list = $Servers::find()->select(['server_id', 'ip', 'name'])->where(['status' => 1])->orderBy('server_id')->asArray()->all();
        $default_host = \Yii::$app->session->get('default_host');
        
        if(!empty($default_host)){
            foreach($server_list as $key=>$val){
                $server_array[$val['server_id']] = $val['ip'];
                if($default_host != $val['ip'])
                    unset($server_list[$key]);
            }
        }
        return $this->render('create', [
            'model' => $model,
            'server_list' => $server_list
        ]);
        
    }

    public function actionUpdate( $id )
    {
        $model = Authorize::findOne( $id );
        if( $model->load( Yii::$app->request->post() ) && $model->validate() ){
            if( $model->type == 'sharding' ){
                $model->server_id = 0;
                $model->server_ip = '';
            }else{
                $model->environment = '';
                $server = Servers::findOne($model->server_id);
                $model->server_ip = $server['ip'];
            }
            $model->sqloperation = implode(",", $model->sqloperation);

            if( $model->save() ){
                return $this->redirect('index');
            }
        }

        //获取服务器列表
        $Servers = new Servers();
        $server_list = $Servers::find()->select(['server_id', 'ip', 'name'])->where(['status' => 1])->orderBy('server_id')->asArray()->all();
        $default_host = \Yii::$app->session->get('default_host');
        
        if(!empty($default_host)){
            foreach($server_list as $key=>$val){
                $server_array[$val['server_id']] = $val['ip'];
                if($default_host != $val['ip'])
                    unset($server_list[$key]);
            }
        }
        return $this->render('update', [
            'model' => $model,
            'server_list' => $server_list
        ]);
    }

    public function actionDelete( $id )
    {
        $model = Authorize::findOne( $id );
        $model->delete();
        return $this->redirect('index');
    }
}
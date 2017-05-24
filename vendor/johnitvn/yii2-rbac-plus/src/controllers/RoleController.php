<?php

namespace johnitvn\rbacplus\controllers;

use backend\server\RedisBaseServer;
use common\models\AuthItemServers;
use common\models\AuthItemServersDbs;
use common\models\Servers;
use Yii;
use yii\base\Exception;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\Html;
use johnitvn\rbacplus\models\Role;
use johnitvn\rbacplus\models\RoleSearch;

use backend\controllers\SiteController;


/**
 * RoleController is controller for manager role
 * @author John Martin <john.itvn@gmail.com>
 * @since 1.0.0
 */
class RoleController extends SiteController {
    private $return = ['status'=>0,'msg'=>'系统维护中'];

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['get'],
                    'bulk-delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Role models.
     * @return mixed
     */
    public function actionIndex() {

        $searchModel = new RoleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Role model.
     * @param string $name
     * @return mixed
     */
    public function actionView($name) {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => $name,
                'content' => $this->renderPartial('view', [
                    'model' => $this->findModel($name),
                ]),
                'footer' => Html::button(Yii::t('rbac', 'Close'), ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                Html::a(Yii::t('rbac', 'Edit'), ['update', 'name' => $name], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $this->servers,
            ]);
        }
    }

    /**
     * Creates a new Role model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $request = Yii::$app->request;
        $model = new Role(null);
//        var_dump($model);exit;
        if ($request->isAjax) {
            /*
             *   Process for ajax request
             */

            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => Yii::t('rbac', "Create new {0}", ["Role"]),
                    'content' => $this->renderPartial('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button(Yii::t('rbac', 'Close'), ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::button(Yii::t('rbac', 'Save'), ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else {
                try {
                    $authItemServersModel = new AuthItemServers();
                    $roleModel = new Role(null);
                    $roleModel->createRole($request->post());
                    $authItemServersModel->modifyPrivilege($request->post('name'), $request->post(), 'create');
                    $this->return['status'] = 1;
                    $this->return['msg'] = '添加成功';
                    return json_encode($this->return);
                }catch (Exception $e){
                    $this->return['msg'] = $e->getMessage();
                    return json_encode($this->return);
                }
            }
        } else {
            /*
             *   Process for non-ajax request
             */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'name' => $model->name]);
            } else {
                return $this->render('create', [
                            'model' => $model,
                ]);
            }
        }
    }

    /**
     * Updates an existing Role model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param string $name
     * @return mixed
     */
    public function actionUpdate($name) {
        $request = Yii::$app->request;
        $model = $this->findModel($name);


        if ($request->isAjax) {
            /*
             *   Process for ajax request
             */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => Yii::t('rbac', "Update {0}", ['"' . $name . '" Role']),
                    'content' => $this->renderPartial('update', [
                        'model' => $this->findModel($name),
                    ]),
                    'footer' => Html::button(Yii::t('rbac', 'Close'), ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::button(Yii::t('rbac', 'Save'), ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else {
                try {
                    $authItemServersModel = new AuthItemServers();
                    $authItemServersModel->modifyPrivilege($name, $request->post());
                    $this->return['status'] = 1;
                    $this->return['msg'] = '更新成功';

                    //$model->description = $request->post('description');
                    //$model->save();

                    return json_encode($this->return);
                }catch (Exception $e){
                    $this->return['msg'] = $e->getMessage();
                    return json_encode($this->return);
                }
            }
        } else {
            /*
             *   Process for non-ajax request
             */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'name' => $model->name]);
            } else {
                return $this->render('update', [
                            'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing Role model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $name
     * @return mixed
     */
    public function actionDelete($name) {
        $this->findModel($name)->delete();
        return $this->redirect(['index']);
    }

    /**
     * Finds the Role model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $name
     * @return Role the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($name) {
        if (($model = Role::find($name)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('rbac', 'The requested page does not exist.'));
        }
    }

    /**
     * Description 获取指定服务器的数据库详情
    */
    public function actionGetDbs($server_ip,$name = null)
    {
        $redisServer = new RedisBaseServer();
        $databases = $redisServer->hget($server_ip,"databases");
        $data = [];
        foreach(explode(',',$databases) as $value){
            if(preg_match('/[ordercenter_|membercenter_]{1}[0-9]+$/i',$value)){
                continue;
            }
            $data[$value] = explode(',',$redisServer->hget($server_ip."-".$value,"tables"));
        }
        return json_encode($data);
    }

    /**
     * Description 更新数据库权限
    */
    public function actionUpgrade()
    {
        exit;
        try {
            $authItemServersModel = new AuthItemServers();
            $authItemServersDbsModel = new AuthItemServersDbs();
            $serversModel = new Servers();
            $result = $serversModel->find()->asArray()->all();
            $serverList = [];
            foreach ($result as $value) {
                $serverList[$value['server_id']] = $value['ip'];
            }
            $itemServerList = $authItemServersModel->find()->asArray()->all();
            $authItemServersDbsModel->deleteAll();
            foreach ($itemServerList as $server) {
                foreach (array_filter(explode(',', $server['server_ids'])) as $server_id) {
                    $db_name_array = array_filter(explode(',', $server['db_names']));
                    if (empty($db_name_array)) continue;
                    $privilege = [];
                    foreach ($db_name_array as $database) {
                        $privilege[$database] = 'all';
                    }
                    $authItemServersDbsModel->isNewRecord = true;
                    $authItemServersDbsModel->item_server_name = $server['item_name'];
                    $authItemServersDbsModel->server_ip = $serverList[$server_id];
                    $authItemServersDbsModel->privilege = json_encode($privilege);
                    $authItemServersDbsModel->insert();
                }
            }
            return "数据迁移成功";
        }catch (Exception $e){
            return json_encode($e->getMessage());
        }
    }

}

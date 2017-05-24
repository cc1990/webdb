<?php

namespace johnitvn\rbacplus\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\helpers\Html;
use johnitvn\rbacplus\Module;
use johnitvn\rbacplus\models\AssignmentSearch;
use johnitvn\rbacplus\models\RoleSearch;
use johnitvn\rbacplus\models\AssignmentForm;

use backend\controllers\SiteController;

/**
 * AssignmentController is controller for manager user assignment
 *
 * @author John Martin <john.itvn@gmail.com>
 * @since 1.0.0
 */
class AssignmentController extends SiteController {

    /**
     * The current rbac module
     * @var Module $rbacModule
     */
    protected $rbacModule;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        $this->rbacModule = Yii::$app->getModule('rbac');
    }

    /**
     * Show list of user for assignment
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new AssignmentSearch;
        $dataProvider = $searchModel->search();
        return $this->render('index', [
                    'dataProvider' => $dataProvider,
                    'searchModel' => $searchModel,
                    'idField' => $this->rbacModule->userModelIdField,
                    'usernameField' => $this->rbacModule->userModelLoginField,
        ]);
    }

    /**
     * Assignment roles to user
     * @param mixed $id The user id
     * @return mixed
     */
    public function actionAssignment($id) {
        $model = call_user_func($this->rbacModule->userModelClassName . '::findOne', $id);
        $formModel = new AssignmentForm($id);
        /*$searchModel = new RoleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);*/

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isPost) {
                $formModel->load(Yii::$app->request->post());
                $formModel->save();
                return [
//                    'forceReload' => 'true',
                    'title' => Yii::t('rbac', "操作结果"),
                    'content' => '<span class="text-success">' . Yii::t('rbac', "授权成功！") . '</span>',
                    'footer' => Html::button(Yii::t('rbac', 'Close'), ['class' => 'btn btn-default pull-right', 'data-dismiss' => "modal"])
                ];
            }else{
                return [
                    'title' => $model->{$this->rbacModule->userModelLoginField},
                    //'forceReload' => "true",
                    'content' => $this->renderPartial('assignment', [
                        'model' => $model,
                        'formModel' => $formModel,
                        /*'searchModel' => $searchModel,
                        'dataProvider' => $dataProvider,*/
                    ]),
                    'footer' => Html::button(Yii::t('rbac', 'Close'), ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::button(Yii::t('rbac', 'Save'), ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            }


        } else {
            return $this->renderAjax('assignment', [
                        'model' => $model,
                        'formModel' => $formModel,
                        /*'searchModel' => $searchModel,
                        'dataProvider' => $dataProvider,*/
            ]);
        }
    }

}

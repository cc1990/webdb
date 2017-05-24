<?php 
namespace backend\modules\logs\controllers;

use backend\controllers\SiteController;

use backend\modules\logs\models\Version;
use backend\modules\logs\models\VersionSearch;

use Yii;
use vendor\twl\tools\utils\Output;

/**
*  版本日志控制器
*/
class VersionController extends SiteController
{
    public function actionIndex()
    {
        $searchModel = new VersionSearch();
        $dataProvider = $searchModel->search( Yii::$app->request->queryParams );

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
            ]);
    }

    public function actionCreate()
    {
        $model = new VersionSearch();
        $model->scenario = 'create';
        if( $model->load( Yii::$app->request->post() ) && $model->validate() ){
            $model->author = Yii::$app->users->identity->username;
            if( $model->save() ){
                return $this->redirect('index');
            }else{
                return $this->render('create', [
                    'model' => $model
                ]);
            }
        }else{
            return $this->render('create', [
                'model' => $model
            ]);
        }
    }

    public function actionUpdate( $id )
    {
        $model = $this->findModel( $id );
        $model->scenario = 'update';
        if( $model->load( Yii::$app->request->post() ) && $model->validate() && $model->save() ){
            return $this->redirect('index');
        }else{
            return $this->render('update', [
                'model' => $model
            ]);
        }
    }

    public function actionDelete($id)
    {
        $model = VersionSearch::findOne($id);
        $model->delete();
        $this->redirect(['version/index']);
    }

    public function actionView( $id ){
        $model = $this->findModel( $id );
        return $this->render('view', [
            'model' => $model
        ]);
    }

    /**
     * Finds the Servers model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Servers the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = VersionSearch::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
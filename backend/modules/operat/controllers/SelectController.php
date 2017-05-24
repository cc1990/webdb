<?php 
namespace backend\modules\operat\controllers;

use Yii;
use backend\controllers\SiteController;

use backend\modules\operat\models\Select;
use backend\modules\operat\models\SelectWhite;

use yii\filters\VerbFilter;

class SelectController extends SiteController
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $model = new Select();

        $list = Select::find()->asArray()->one();
        $data['list'] = $list;
        $data['rule'] = $this->rule();

        return $this->render('index', $data);
    }

    public function actionUpdate($id)
    {
        $id = 1;
        $model = $this->findModel($id);
        if ( $model->load( Yii::$app->request->post() ) && $model->validate() ) {
            if( $model->save() ){
            //var_dump(Yii::$app->request->post());exit;
                return $this->redirect(['index']);
            }
        }

        $list = Select::find()->asArray()->one();
        $data['list'] = $list;
        $data['rule'] = $this->rule();
        return $this->render('update', $data);
    }

    public function rule(){
        return array(
            'dev' => '开发',
            'test' => '测试',
            'test_trunk' => '测试主干',
            'pre' => '预发布',
            'pro' => '线上',
            'dev_trunk' => '研发主干',
        );
    }

    protected function findModel($id)
    {
        if (($model = Select::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionWhite()
    {
        $searchModel = new SelectWhite();
        $searchModel->scenario = 'search';
        $dataProvider = $searchModel->search( Yii::$app->request->queryParams );
        return $this->render("white", [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
            ]);
    }

    public function actionWhiteAdd()
    {
        $model = new SelectWhite();
        $model->scenario = 'create';
        if ( $model->load( Yii::$app->request->post() ) && $model->validate() && $model->save() ) {
            return $this->redirect('white');
        } else {
            return $this->render("white-add", ['model' => $model]);
        }
        
    }

    public function actionWhiteUpdate( $id )
    {
        $model = SelectWhite::findOne($id);
        $model->scenario = 'update';
        if ( $model->load( Yii::$app->request->post() ) && $model->validate() && $model->save() ) {
            return $this->redirect('white');
        } else {
            return $this->render("white-update", ['model' => $model]);
        }
    }

    public function actionWhiteDel( $id ){
        $model = SelectWhite::findOne($id);
        $model->delete();
        return $this->redirect("white");
    }
}
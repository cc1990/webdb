<?php 
namespace backend\modules\projects\controllers;

use Yii;
use backend\controllers\SiteController;


use backend\modules\projects\models\Projects;
use backend\modules\projects\models\ProjectsInfo;
use backend\modules\projects\models\ProjectsInfoSearch;

use vendor\twl\tools\utils\Output;

/**
* 项目信息管理
*/
class InfoController extends SiteController
{
    /**
     * 项目列表
     * @return [type] [description]
     */
    public function actionIndex()
    {
        $connection = Yii::$app->db;        

        $sql_thisweek = "select sum(rt.pre) pres,sum(rt.pro) pros from ( 
        select b.*,@rownum:=@rownum+1 rownum, 
if(@lv=b.level,@rank:=@rank+1,@rank:=1) as rank, 
@lv:=b.level 
from ( 
select id,level,
case when YEARWEEK(date_format(pre_date,'%Y-%m-%d')) = YEARWEEK(now()) then 1 else 0 end as pre, 
case when YEARWEEK(date_format(pro_date,'%Y-%m-%d')) = YEARWEEK(now()) then 1 else 0 end as Pro
from projects_info pi order by level desc,id desc 
) b ,(select @rownum :=0 , @lv := null ,@rank:=0) c 
) rt 
where rank =1";

        $sql_lastweek = "select sum(rt.pre) pres,sum(rt.pro) pros from ( 
select b.*,@rownum:=@rownum+1 rownum, 
if(@lv=b.level,@rank:=@rank+1,@rank:=1) as rank, 
@lv:=b.level 
from ( 
select id,level,
case when YEARWEEK(date_format(pre_date,'%Y-%m-%d')) = YEARWEEK(now())-1 or YEARWEEK(date_format(pre_date,'%Y-%m-%d')) = YEARWEEK(now()) then 1 else 0 end as pre, 
case when YEARWEEK(date_format(pro_date,'%Y-%m-%d')) = YEARWEEK(now())-1 or YEARWEEK(date_format(pro_date,'%Y-%m-%d')) = YEARWEEK(now()) then 1 else 0 end as Pro
from projects_info pi order by level desc,id desc 
) b ,(select @rownum :=0 , @lv := null ,@rank:=0) c 
) rt 
where rank =1";

        $sql_lastmonth = "select sum(rt.pre) pres,sum(rt.pro) pros from ( 
select b.*,@rownum:=@rownum+1 rownum, 
if(@lv=b.level,@rank:=@rank+1,@rank:=1) as rank, 
@lv:=b.level 
from ( 
select id,level,
case when PERIOD_DIFF( date_format( now( ) , '%Y%m' ) , date_format( pre_date, '%Y%m' ) ) =1 then 1 else 0 end as pre, 
case when PERIOD_DIFF( date_format( now( ) , '%Y%m' ) , date_format( pro_date, '%Y%m' ) ) =1 then 1 else 0 end as Pro
from projects_info pi order by level desc,id desc 
) b ,(select @rownum :=0 , @lv := null ,@rank:=0) c 
) rt 
where rank =1";

        $count = $connection->createCommand($sql_thisweek)->queryAll();
        $data['thisweek'] = $count[0];
        $count = $connection->createCommand($sql_lastweek)->queryAll();
        $data['lastweek'] = $count[0];
        $count = $connection->createCommand($sql_lastmonth)->queryAll();
        $data['lastmonth'] = $count[0];

        $searchModel = new ProjectsInfoSearch();
        $dataProvider = $searchModel->search( Yii::$app->request->queryParams );
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'data' => $data
        ]);
    }

    public function actionCreate()
    {
        $model = new ProjectsInfo();

        if ($model->load(Yii::$app->request->post())) {
            if( $model->pro_id == 0 && empty( $model->pro_name ) ){//如果项目未选择并且项目名称为空时，跳转到添加页面
                $data['projects'] = Projects::find()->select(['pro_id', 'title', 'name'])->where(['status' => 1])->orderBy('updated_date desc')->asArray()->all();
                return $this->render('update', [
                    'model' => $model,
                    'data' => $data
                ]);
            }

            //如果选择了项目，则项目名称为空，不用填写
            if( $model->pro_id != 0 ){
                $model->pro_name = '';
            }
            
            if ( $model->insert() ) {
                $id = $model->attributes['id'];
                if( $model->pro_id != 0 ){
                    //如果项目ID不为空，则查询该项目ID是否存在历史信息
                    $projects_info = ProjectsInfo::find()->where(['pro_id' => $model->pro_id ])->andWhere("id != ".$id)->asArray()->one();
                    if( empty( $projects_info ) ){
                        //如果该项目ID的历史信息为空，则level的值为新增的ID值
                        $level = $id;
                    }else{//否则level的值为该项目ID的第一条历史信息的level的值
                        $level = $projects_info['level'];
                    }
                }else{ //如果项目ID等于0， 则level的值为新增的ID值
                    $level = $id;
                }
                
                if( $id ){
                    $model = ProjectsInfo::findOne($id);
                    $model->level = $level;
                    $model->save();
                }
            }
            return $this->redirect(['index']);
        } else {
            $data['projects'] = Projects::find()->select(['pro_id', 'title', 'name'])->orderBy('updated_date desc')->asArray()->all();
            return $this->render('create', [
                'model' => $model,
                'data' => $data
            ]);
        }
    
    }

    public function actionUpdate( $id )
    {
        $model = ProjectsInfo::findOne($id);
        if( $model->load( Yii::$app->request->post() ) ){
            if ( $model->pro_id == 0 ) {
                if( empty( $model->pro_name ) ){
                    $data['projects'] = Projects::find()->select(['pro_id', 'title', 'name'])->orderBy('updated_date desc')->asArray()->all();
                    return $this->render('update', [
                        'model' => $model,
                        'data' => $data
                    ]);
                }
            }else{
                $model->pro_name = '';
            }
            if( $_POST['is_create_history'] == 1 ){
                $model_new = new ProjectsInfo();
                $model_new->load( Yii::$app->request->post() );
                if ( $model_new->insert() ) {
                    $p_id = $model_new->attributes['id'];
                    $level = $model->level;
                    if( $level ){
                        $model = ProjectsInfo::findOne($p_id);
                        $model->level = $level;
                        $model->save();
                    }
                }
            }else{
                $model->save();
            }
            return $this->redirect(['index']);
        }else{
            $data['projects'] = Projects::find()->select(['pro_id', 'title', 'name'])->orderBy('updated_date desc')->asArray()->all();
            return $this->render('update', [
                'model' => $model,
                'data' => $data
            ]);
        }
    }

    public function actionGetProjectList( $id )
    {
        $data = ProjectsInfo::find()->select(['level'])->where(['id' => $id])->asArray()->one();
        $list = ProjectsInfo::find()->where(['level' => $data['level']])->andWhere("id != $id ")->orderBy('create_time desc')->asArray()->all();

        echo json_encode($list);
    }

    public function actionDelete( $id )
    {
        $model = ProjectsInfo::findOne($id);
        $model->delete();
        return $this->redirect(['index']);
    }
}
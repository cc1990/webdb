<?php 
namespace backend\modules\projects\models;

use Yii;
use backend\modules\projects\models\Projects;
use backend\modules\projects\models\ProjectsInfo;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
* 
*/
class ProjectsInfoSearch extends ProjectsInfo
{
    public $project_name;//项目名称
    public $project_title; //项目描述

    public function rules()
    {
        return [
            [['project_name', 'project_title', 'server_ip', 'test_trunck_date', 'pre_date', 'pro_date'], 'safe']
        ];
    }

    public function search( $params )
    {
        $pQuery = $this->find();
        $pQuery->select(['id', 'projects_info.pro_id', 'pro_name', 'server_ip', 'test_trunck_date',  'pre_date', 'pro_date', 'remark', 'create_time', 'projects.name', 'projects.title', 'level'])->orderBy("create_time desc");
        $pQuery->joinWith(['projects']);
        $query = (new \yii\db\Query())->select(['*'])->from( ['p' => $pQuery] );
        
        $query->groupBy(['level']);
        $query->orderBy("p.create_time desc");


        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        

        $this->load( $params );

        if( !$this->validate() ){
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'p.name', $this->project_name])
            ->andFilterWhere(['or', ['like', 'p.title', $this->project_title], ['like', 'pro_name', $this->project_title]])
            ->andFilterWhere(['>=', 'test_trunck_date', $this->test_trunck_date])
            ->andFilterWhere(['>=', 'pre_date', $this->pre_date])
            ->andFilterWhere(['>=', 'pro_date', $this->pro_date]);
            //->andFilterWhere();

        //$query->groupBy("pro_id");
        return $dataProvider;
    }

    /**
     * 获取项目信息
     * @return [type] [description]
     */
    public function getProjects()
    {
        return $this->hasOne(Projects::className(), ['pro_id' => 'pro_id']);
    }
}
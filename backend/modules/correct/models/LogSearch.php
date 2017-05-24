<?php 
namespace backend\modules\correct\models;

use Yii;

use backend\modules\correct\models\Log;
use yii\data\ActiveDataProvider;

class LogSearch extends Log
{
    public function rules()
    {
        return [
            [['workorder_no', 'workorder_user', 'workorder_time', 'workorder_title', 'workorder_end_time', 'db_names', 'work_line', 'workorder_dba'], 'safe']
        ];
    }
    public function search( $params )
    {

        $query = $this->find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'workorder_time' => SORT_DESC,
                ]
            ]
        ]);

        

        $this->load( $params );

        if( !$this->validate() ){
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'workorder_no', $this->workorder_no])
            ->andFilterWhere(['like', 'workorder_user', $this->workorder_user])
            ->andFilterWhere(['>=', 'workorder_time', $this->workorder_time])
            ->andFilterWhere(['like', 'workorder_title', $this->workorder_title])
            ->andFilterWhere(['like', 'module_name', $this->module_name])
            ->andFilterWhere(['like', 'workorder_dba', $this->workorder_dba])
            ->andFilterWhere(['like', 'work_line', $this->work_line])
            ->andFilterWhere(['like', 'db_names', $this->db_names])
            ->andFilterWhere(['>=', 'workorder_end_time', $this->workorder_end_time]);
            //->andFilterWhere();

        $query->groupBy("workorder_time");
        return $dataProvider;
    }
}
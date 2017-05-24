<?php 
namespace backend\modules\correct\models;

use Yii;

use common\models\SelfHelp;
use yii\data\ActiveDataProvider;

class SelfHelpSearch extends SelfHelp
{
    public function rules()
    {
        return [
            [['workorder_no', 'workorder_user', 'environment', 'server_ip', 'db_name', 'tb_name', 'sql', 'backup_status', 'backup_note', 'execute_status', 'execute_note'], 'safe']
        ];
    }
    public function search( $params )
    {

        $query = $this->find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'workorder_no' => SORT_DESC,
                ]
            ]
        ]);

        

        $this->load( $params );

        if( !$this->validate() ){
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'workorder_no', $this->workorder_no])
            ->andFilterWhere(['like', 'workorder_user', $this->workorder_user])
            ->andFilterWhere(['like', 'environment', $this->environment])
            ->andFilterWhere(['like', 'server_ip', $this->server_ip])
            ->andFilterWhere(['like', 'db_name', $this->db_name])
            ->andFilterWhere(['like', 'tb_name', $this->tb_name])
            ->andFilterWhere(['like', 'sql', $this->sql])
            ->andFilterWhere(['like', 'backup_note', $this->backup_note])
            ->andFilterWhere(['like', 'execute_note', $this->execute_note]);
            //->andFilterWhere();
        $query->groupBy('workorder_no');
        return $dataProvider;
    }
}
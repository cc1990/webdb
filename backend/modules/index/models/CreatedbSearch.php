<?php 
namespace backend\modules\index\models;

use Yii;

use backend\modules\index\models\Createdb;
use yii\data\ActiveDataProvider;

/**
* 
*/
class CreatedbSearch extends Createdb
{
    public function rules()
    {
        return [
            [['db_name', 'server_ip'], 'safe']
        ];
    }
    public function search( $params )
    {

        $query = $this->find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        

        $this->load( $params );

        if( !$this->validate() ){
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'db_name', $this->db_name])
            ->andFilterWhere(['like', 'server_ip', $this->server_ip]);

        return $dataProvider;
    }
}
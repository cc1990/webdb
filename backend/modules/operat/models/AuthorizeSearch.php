<?php 
namespace backend\modules\operat\models;

use common\models\Users;
//use backend\modules\operat\models\Authorize;

use yii\data\ActiveDataProvider;
/**
 * 搜索授权白名单
 */
class AuthorizeSearch extends Authorize
{
    public $chinesename;

    public function rules()
    {
        return [
            ['chinesename', 'safe'],
            [['username', 'stop_time', 'db_name', 'sqloperation'], 'safe'],
        ];
    }

    public function search( $param )
    {
        $query = $this->find();
        $query->joinWith(['users']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            ]);

        $this->load( $param );

        if( !$this->validate() ){
            return $dataProvider;
        }

        $query->andFilterWhere( ['like', 'operat_authorize_white.username', $this->username])
            ->andFilterWhere( ['like', 'users.chinesename', $this->chinesename])
            ->andFilterWhere(['like', 'db_name', $this->db_name])
            ->andFilterWhere(['like', 'sqloperation', $this->sqloperation])
            ->andFilterWhere(['>=', 'stop_time', $this->stop_time]);

        return $dataProvider;
    }

    /**
     * 获取用户信息
     * @return [type] [description]
     */
    public function getUsers()
    {
        return $this->hasOne(Users::className(), ['username' => 'username']);
    }
}
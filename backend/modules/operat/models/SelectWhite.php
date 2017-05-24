<?php 
namespace backend\modules\operat\models;

use common\models\Users;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class SelectWhite extends \yii\db\ActiveRecord
{
    public $chinesename;

    public static function tableName()
    {
        return 'operat_select_white';
    }

    public function rules()
    {
        return [
            [['username', 'number', 'stop_date', 'db_name'], 'required', 'on' => ['create', 'update'], 'message' => '必须填写'],
            [['username', 'db_name'], 'string', 'on' => ['search', 'create', 'update']],
            ['number', 'integer', 'on' => ['search', 'create', 'update']],
            //['stop_date', 'date', 'on' => ['search', 'create', 'update'], 'message' => '必须填写日期格式'],
            //['username', 'unique', 'on' => ['create', 'update']],
            ['chinesename', 'safe']
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => '工号',
            'number' => '限制条数',
            'stop_date' => '截止日期',
            'db_name' => '数据库'
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

        $query->andFilterWhere([ 'number' => $this->number ])
            ->andFilterWhere( ['like', 'operat_select_white.username', $this->username])
            ->andFilterWhere( ['like', 'users.chinesename', $this->chinesename])
            ->andFilterWhere(['like', 'db_name', $this->db_name])
            ->andFilterWhere(['<', 'stop_date', $this->stop_date]);

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
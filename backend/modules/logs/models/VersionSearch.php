<?php 
namespace backend\modules\logs\models;

use backend\modules\logs\models\Version;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
* 版本日志搜索
*/
class VersionSearch extends Version
{
    
    public function rules()
    {
        return [
            [['version_title', 'version_number', 'version_log'], 'required', 'on' => ['create', 'update']]
        ];
    }

    public function search( $params ){
        $query = $this->find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        $this->load($params);

        if( !$this->validate() ){
            return $dataProvider;
        }

        $query -> andFilterWhere(['like', 'version_title', $this->version_title])
            ->andFilterWhere(['like', 'version_number', $this->version_number]);

        return $dataProvider;
    }
}
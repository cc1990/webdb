<?php

namespace backend\modules\servers\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\servers\models\Servers;

/**
 * ServersSearch represents the model behind the search form about `backend\modules\servers\models\Servers`.
 */
class ServersSearch extends Servers
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['server_id'], 'integer'],
            [['ip', 'mirror_ip', 'name', 'updated_date', 'environment'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Servers::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'server_id' => $this->server_id,
            'updated_date' => $this->updated_date,
            'environment' => $this->environment,
        ]);

        $query->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}

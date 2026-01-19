<?php

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\OrderInstallation;

/**
 * OrderInstallationSearch represents the model behind the search form of `app\models\OrderInstallation`.
 */
class OrderInstallationSearch extends OrderInstallation
{
    public function rules()
    {
        return [
            [['id', 'estimated_duration', 'status'], 'integer'],
            [['name', 'service_type', 'pipe_grade', 'pipe_length', 'detail_service', 'detail_info'], 'safe'],
            [['base_price'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = OrderInstallation::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'base_price' => $this->base_price,
            'estimated_duration' => $this->estimated_duration,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'service_type', $this->service_type])
            ->andFilterWhere(['like', 'pipe_grade', $this->pipe_grade])
            ->andFilterWhere(['like', 'pipe_length', $this->pipe_length]);

        return $dataProvider;
    }
}

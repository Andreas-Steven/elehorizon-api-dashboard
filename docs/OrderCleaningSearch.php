<?php

namespace app\models\search;

/**
 * Yii required components
 */
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * Model required components
 */
use app\helpers\Constants;
use app\core\CoreModel;
use app\models\OrderCleaning;

class OrderCleaningSearch extends OrderCleaning
{
    public $page;
    public $page_size;
    public $sort_dir;
    public $sort_by;
    public $created_at;
    public $created_by;
    public $updated_at;
    public $updated_by;
    public $deleted_at;
    public $deleted_by;

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [['id', 'service_type_id', 'quantity'], 'integer'],
                [['price'], 'number'],
                [[
                    'name',
                    'detail_member',
                    'detail_address',
                    'location_detail',
                    'unit_detail',
                    'schedule_detail',
                    'notes',
                    'status',
                    'detail_info',
                    'created_at',
                    'created_by',
                    'updated_at',
                    'updated_by',
                    'deleted_at',
                    'deleted_by',
                ], 'safe'],
            ],
            CoreModel::getPaginationRules($this),
        );
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $this->load([CoreModel::getModelClassName($this) => $params]);

        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($this, $params)) {
            return $unavailableParams;
        }

        $query = OrderCleaning::find();
        $query->where(Constants::STATUS_NOT_DELETED);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(CoreModel::setLikeFilter($this->name, 'name'));

        $query->andFilterWhere([
            'id' => $this->id,
            'service_type_id' => $this->service_type_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
        ]);

        $query->andFilterWhere(['status' => $this->status ? explode(',', $this->status) : $this->status]);

        $query->andFilterWhere(CoreModel::setChangelogFilters($this));

        $dataProvider->setPagination(
            CoreModel::setPagination($params, $dataProvider)
        );

        $dataProvider->setSort(
            CoreModel::setSort($params)
        );

        return $dataProvider;
    }
}

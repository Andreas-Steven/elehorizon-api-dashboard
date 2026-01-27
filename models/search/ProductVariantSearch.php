<?php

namespace app\models\search;

/**
 * Yii required components
 */
use yii\BaseYii as Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * Model required components
 */
use app\helpers\Constants;
use app\core\CoreModel;
use app\core\CoreMongodb;
use app\models\ProductVariant;

class ProductVariantSearch extends ProductVariant
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

    public $pk;
    public $max_watt;
    public $product_name;
    public $product_type_id;

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [['pk', 'max_watt'], 'number'],
                [['id', 'product_id', 'product_type_id'], 'integer'],
                [['variant_code', 'name', 'sku', 'status', 'product_name', 'detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
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

        $query = ProductVariant::find()
            ->alias('v')
            ->joinWith(['product p'])
            ->where(['<>', 'v.status', Constants::STATUS_DELETED]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'v.id' => $this->id,
            'v.product_id' => $this->product_id,
        ]);

        $query->andFilterWhere(['v.status' => $this->status ? explode(',', $this->status) : $this->status]);

        $query->andFilterWhere(CoreModel::setLikeFilter($this->name, 'v.name'));
        $query->andFilterWhere(CoreModel::setLikeFilter($this->variant_code, 'v.variant_code'));
        $query->andFilterWhere(CoreModel::setLikeFilter($this->sku, 'v.sku'));
        $query->andFilterWhere(CoreModel::setLikeFilter($this->product_name, 'p.name'));
        $query->andFilterWhere(['p.product_type_id' => $this->product_type_id]);

        if ($this->pk !== null && $this->pk !== '') {
            $query->andWhere(
                new Expression("(v.detail_specs->>'power')::numeric = :pk"),
                [':pk' => $this->pk]
            );
        }

        if ($this->max_watt !== null && $this->max_watt !== '') {
            $query->andWhere(
                new Expression("(v.detail_specs->'power_consumption'->>'watt')::numeric <= :maxWatt"),
                [':maxWatt' => $this->max_watt]
            );
        }

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

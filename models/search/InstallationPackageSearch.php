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
use app\core\CoreMongodb;
use app\models\InstallationPackage;
use app\models\PipeGrade;
use app\models\PipePackage;
use app\models\ProductVariant;

class InstallationPackageSearch extends InstallationPackage
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
    public $product_variant_id;
    public $pipe_grade_id;

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [['id', 'product_variant_id', 'pipe_grade_id'], 'integer'],
                [['length'], 'number'],
                [['code', 'name', 'status', 'detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
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

        $query = InstallationPackage::find()
            ->alias('ip')
            ->where(['<>', 'ip.status', Constants::STATUS_DELETED]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['ip.id' => $this->id]);
        $query->andFilterWhere(CoreModel::setLikeFilter($this->code, 'ip.code'));
        $query->andFilterWhere(CoreModel::setLikeFilter($this->name, 'ip.name'));
        $query->andFilterWhere(['ip.status' => $this->status ? explode(',', $this->status) : $this->status]);

        if ($this->length !== null && $this->length !== '') {
            $query->andWhere(['ip.length' => $this->length]);
        }

        $pipeGroup = null;
        $variantPipeSize = [];
        $variantDetailSpecs = [];
        $pipeGradeOptions = [];

        if ($this->product_variant_id) {
            $variant = ProductVariant::find()
                ->alias('pv')
                ->where(['pv.id' => $this->product_variant_id])
                ->andWhere(['<>', 'pv.status', Constants::STATUS_DELETED])
                ->one();

            if ($variant !== null) {
                $variantDetailSpecs = $this->toArrayField($variant->detail_specs);
                $variantPipeSize = $this->toArrayField($variantDetailSpecs['pipe_size'] ?? []);
                $power = $variantDetailSpecs['power'] ?? null;

                if ($power !== null) {
                    $pipeGroup = PipePackage::find()
                        ->where(['<=', 'power_min', $power])
                        ->andWhere(['>=', 'power_max', $power])
                        ->andWhere(['<>', 'status', Constants::STATUS_DELETED])
                        ->one();
                }

                $liquidSize = $variantPipeSize['liquid'] ?? null;
                $gasSize = $variantPipeSize['gas'] ?? null;

                if ($liquidSize !== null && $gasSize !== null) {
                    if (
                        $pipeGroup === null ||
                        $pipeGroup->liquid_size !== $liquidSize ||
                        $pipeGroup->gas_size !== $gasSize
                    ) {
                        $pipeGroup = PipePackage::find()
                            ->where([
                                'liquid_size' => $liquidSize,
                                'gas_size' => $gasSize,
                            ])
                            ->andWhere(['<>', 'status', Constants::STATUS_DELETED])
                            ->one();
                    }
                }
            }
        }

        if ($pipeGroup !== null) {
            $grades = PipeGrade::find()
                ->with(['brand'])
                ->where(['<>', 'status', Constants::STATUS_DELETED])
                ->all();

            foreach ($grades as $grade) {
                $detailInfo = $this->toArrayField($grade->detail_info);
                $brand = null;

                if ($grade->brand) {
                    $brand = [
                        'id' => (int) $grade->brand->id,
                        'name' => $grade->brand->name,
                    ];
                }

                $pricePerMeter = ($grade->price_per_meter !== null && $grade->price_per_meter !== '')
                    ? (float) $grade->price_per_meter
                    : (float) $pipeGroup->price;

                $pipeGradeOptions[] = [
                    'id' => (int) $grade->id,
                    'name' => $grade->name,
                    'thickness_mm' => (float) $grade->thickness_mm,
                    'brand' => $brand,
                    'price_per_meter' => (float) $pricePerMeter,
                    'detail_info' => $detailInfo,
                ];
            }
        }

        $query->andFilterWhere(CoreModel::setChangelogFilters($this));

        $dataProvider->setPagination(
            CoreModel::setPagination($params, $dataProvider)
        );

        $dataProvider->setSort(
            CoreModel::setSort($params)
        );

        if (($pipeGroup !== null) || !empty($variantDetailSpecs)) {
            $models = $dataProvider->getModels();

            foreach ($models as $model) {
                if ($pipeGroup !== null && (float)$model->length > 0) {
                    $model->pipe_group = [
                        'id' => (int) $pipeGroup->id,
                        'code' => $pipeGroup->code,
                        'name' => $pipeGroup->name,
                        'power_min' => (float) $pipeGroup->power_min,
                        'power_max' => (float) $pipeGroup->power_max,
                        'btu_min' => (int) $pipeGroup->btu_min,
                        'btu_max' => (int) $pipeGroup->btu_max,
                        'liquid_size' => $pipeGroup->liquid_size,
                        'gas_size' => $pipeGroup->gas_size,
                    ];
                }

                if (!empty($pipeGradeOptions)) {
                    $model->pipe_grade_options = array_map(function ($option) use ($model) {
                        $length = (float) ($model->length ?? 0);
                        $packagePrice = (float) ($model->package_price ?? 0);
                        $pipeCost = (float) $option['price_per_meter'] * $length;

                        $option['pipe_price'] = $pipeCost;
                        $option['total_price'] = $packagePrice + $pipeCost;
                        return $option;
                    }, $pipeGradeOptions);
                }

                if ($pipeGroup !== null) {
                    $length = (float) ($model->length ?? 0);
                    $packagePrice = (float) ($model->package_price ?? 0);
                    $pricePerMeter = (float) ($pipeGroup->price ?? 0);

                    if ($this->pipe_grade_id && !empty($pipeGradeOptions)) {
                        foreach ($pipeGradeOptions as $option) {
                            if ((int) $option['id'] === (int) $this->pipe_grade_id) {
                                $pricePerMeter = (float) $option['price_per_meter'];
                                break;
                            }
                        }
                    }

                    $model->total_price = $packagePrice + ($pricePerMeter * $length);
                }

                if (!empty($variantPipeSize)) {
                    $model->variant_pipe_size = $variantPipeSize;
                }

                if (!empty($variantDetailSpecs)) {
                    $model->variant_detail_specs = $variantDetailSpecs;
                }
            }

            $dataProvider->setModels($models);
        }

        return $dataProvider;
    }

    private function toArrayField($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}

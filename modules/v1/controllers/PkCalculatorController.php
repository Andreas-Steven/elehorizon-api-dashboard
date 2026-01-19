<?php

namespace app\modules\v1\controllers;

/**
 * Yii required components
 */
use Yii;
use yii\base\DynamicModel;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreController;

/**
 * Model required components
 */
use app\models\search\ProductVariantSearch;

class PkCalculatorController extends CoreController
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
		#add your action here
        $behaviors['verbs']['actions'] = ArrayHelper::merge(
            $behaviors['verbs']['actions'],
            [
                'calculate' => ['post'],
            ]
        );

        $behaviors['authenticator']['except'] = ArrayHelper::merge(
            $behaviors['authenticator']['except'],
            [
                'calculate',
            ]
        );

        return $behaviors;
    }

    public function actionCalculate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $payload = [
            'area' => $params['area'] ?? null,
            'power' => $params['power'] ?? null,
            'type' => $params['type'] ?? null,
        ];

        $model = DynamicModel::validateData($payload, [
            [['area', 'power', 'type'], 'integer'],
            [['area', 'power', 'type'], 'required'],
            ['type', 'in', 'range' => array_keys(Constants::PRODUCT_TYPE)],
        ]);

        $this->applyBusinessValidation($model);

        if ($model->hasErrors()) {
            return $this->coreBadRequest($model);
        }

        $area = (int) $model->area;
        $power = (int) $model->power;
        $typeId = $model->type !== null ? (int) $model->type : null;
        $type = $typeId !== null ? (Constants::PRODUCT_TYPE[$typeId] ?? null) : null;

        $pk = $this->determinePk($area);

        if ($pk === null) {
            $model->addError('area', Yii::t('app', 'Luas area berada di luar rentang kalkulator.'));
            return $this->coreBadRequest($model);
        }

        $recommendations = $this->searchRecommendations($pk, $power, $typeId);

        return $this->coreCustomData([
            'input' => [
                'area' => $area,
                'power' => $power,
                'type' => $type,
                'pk' => $pk,
            ],
            'recommendations' => $recommendations,
            'isMatchFound' => !empty($recommendations),
            'notes' => [
                'Hasil dihitung berdasarkan cuaca Jabodetabek, ruangan tertutup, plafon 2.8m.',
                'Hasil akhir dapat berubah tergantung ventilasi dan intensitas panas ruangan.',
            ],
        ], Yii::t('app', 'success'));
    }

    private function applyBusinessValidation(DynamicModel $model): void
    {
        if ($model->area !== null) {
            $area = (int) $model->area;
            if ($area < 5) {
                $model->addError('area', Yii::t('app', 'Silakan masukkan luas area minimal 5 m².'));
            } elseif ($area > 50) {
                $model->addError('area', Yii::t('app', 'Ruangan terlalu besar untuk kapasitas AC 2.5 PK.'));
            }
        }

        if ($model->power !== null) {
            $power = (int) $model->power;
            if ($power < 320) {
                $model->addError('power', Yii::t('app', 'Daya listrik sangat kecil, mohon tambah kapasitas terlebih dahulu.'));
            }
        }
    }

    private function determinePk(int $area): ?float
    {
        foreach (Constants::PK_AREA_SEGMENTS as $segment) {
            if ($area >= $segment['min'] && $area <= $segment['max']) {
                return $segment['pk'];
            }
        }

        return null;
    }

    private function searchRecommendations(float $pk, int $power, ?int $typeId = null): array
    {
        $searchModel = new ProductVariantSearch();
        $searchParams = [
            'pk' => $pk,
            'max_watt' => $power,
            'status' => Constants::STATUS_ACTIVE,
        ];

        if ($typeId !== null) {
            $searchParams['product_type_id'] = $typeId;
        }

        $dataProvider = $searchModel->search($searchParams);
        $this->validateProvider($dataProvider, $searchModel);
        $dataProvider->pagination = false;

        $dataProvider->query->with(['product.productRating']);
        $variants = $dataProvider->getModels();

        return array_map(
            fn($variant) => $this->mapVariantToRecommendation($variant),
            $variants
        );
    }

    private function mapVariantToRecommendation($variant): array
    {
        $product = $variant->product ?? null;

        $detailSpecs = $this->decodeJsonField($variant->detail_specs);
        $detailInfo = $product ? $this->decodeJsonField($product->detail_info) : [];
        $images = $this->decodeJsonField($variant->image_url);
        $badges = $product ? $this->decodeJsonField($product->badges) : [];
        $powerConsumption = $detailSpecs['power_consumption'] ?? [];
        $coolingCapacity = $detailSpecs['cooling_capacity'] ?? [];
        $rating = $product ? ($product->productRating ?? null) : null;

        return [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'name' => $variant->name,
            'product_name' => $product->name ?? null,
            'brand' => $detailInfo['brand']['name'] ?? null,
            'product_type' => $detailInfo['product_type']['name'] ?? null,
            'pk' => isset($detailSpecs['power']) ? (float) $detailSpecs['power'] : null,
            'btu' => isset($coolingCapacity['btu']) ? (float) $coolingCapacity['btu'] : null,
            'watt' => isset($powerConsumption['watt']) ? (int) $powerConsumption['watt'] : null,
            'watt_range' => $powerConsumption['range'] ?? null,
            'badges' => $badges,
            'images' => $images,
            'pricing' => [
                'price' => (int) round((float) ($variant->price ?? 0)),
                'original_price' => (int) round((float) ($variant->original_price ?? 0)),
            ],
            'rating' => $rating ? ArrayHelper::toArray($rating) : null,
            'detail_specs' => $detailSpecs,
        ];
    }

    private function decodeJsonField($value): array
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

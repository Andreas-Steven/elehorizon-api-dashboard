<?php

namespace app\modules\v1\controllers;

use Yii;
use app\core\CoreController;
use yii\helpers\ArrayHelper;

use app\models\search\InstallationPackageSearch;
use app\models\search\TradeInSearch;

class AddonsController extends CoreController
{
    private const ADDON_pipe_installation = 'pipe_installation';
    private const ADDON_TRADE_IN = 'trade_in';

    private const ADDON_MAP = [
        self::ADDON_pipe_installation => InstallationPackageSearch::class,
        self::ADDON_TRADE_IN => TradeInSearch::class,
    ];

    private const ADDON_METADATA = [
        self::ADDON_pipe_installation => [
            'code' => 'installation',
            'name' => 'Paket Pemasangan',
            'label' => 'Paket Pasang AC',
            'description' => 'Jasa instalasi + material standar',
            'status' => 'available',
        ],
        self::ADDON_TRADE_IN => [
            'code' => 'trade-in',
            'name' => 'Tukar Tambah',
            'label' => 'Tukar AC Lama',
            'description' => 'Program tukar tambah AC lama',
            'status' => 'coming_soon',
        ],
    ];

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs']['actions'] = ArrayHelper::merge(
            $behaviors['verbs']['actions'],
            [
                'index' => ['get'],
            ]
        );

        $behaviors['authenticator']['except'] = ArrayHelper::merge(
            $behaviors['authenticator']['except'],
            [
                'data',
            ]
        );

        return $behaviors;
    }

    public function actionData()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $withData = $this->shouldIncludeData($params['with_data'] ?? true);
        unset($params['with_data']);
        $addonPayloads = $this->normalizeAddonPayloads($params);

        $responses = [];
        $errors = [];

        foreach ($addonPayloads as $payload) {
            $type = $payload['type'] ?? null;

            if (!$type || !isset(self::ADDON_MAP[$type])) {
                $errors[] = Yii::t('app', 'invalidField', ['label' => 'type']);
                continue;
            }

            $addonEntry = ArrayHelper::merge(
                $this->getAddonMeta($type),
                [
                    'data' => [],
                ]
            );

            if ($withData) {
                $addonParams = $payload['params'] ?? [];

                $searchModelClass = self::ADDON_MAP[$type];
                $searchModel = new $searchModelClass();
                $dataProvider = $searchModel->search($addonParams);

                CoreController::validateProvider($dataProvider, $searchModel);
                $addonEntry['data'] = $this->sanitizeAddonData($dataProvider->getModels());
            }

            $responses[] = $addonEntry;
        }

        if (!empty($errors)) {
            return CoreController::coreCustomData([
                'addons' => $responses,
                'errors' => $errors,
            ]);
        }

        return CoreController::coreCustomData([
            'addons' => array_values($responses),
        ]);
    }

    private function normalizeAddonPayloads(array $params): array
    {
        $addonPayloads = $params['addons'] ?? null;

        if ($addonPayloads === null) {
            return array_map(
                fn ($type) => ['type' => $type, 'params' => $params],
                array_keys(self::ADDON_MAP)
            );
        }

        if (!is_array($addonPayloads)) {
            $addonPayloads = [['type' => $addonPayloads, 'params' => $params]];
        }

        $normalized = [];
        foreach ($addonPayloads as $payload) {
            if (is_string($payload)) {
                $normalized[] = ['type' => $payload, 'params' => $params];
                continue;
            }

            if (!is_array($payload)) {
                continue;
            }

            $type = $payload['type'] ?? null;
            $addonParams = $payload['params'] ?? $params;

            if ($type === null) {
                continue;
            }

            $normalized[] = [
                'type' => $type,
                'params' => $addonParams,
            ];
        }

        return $normalized;
    }

    private function getAddonMeta(string $type): array
    {
        return self::ADDON_METADATA[$type] ?? [
            'id' => $type,
            'name' => ucfirst(str_replace('_', ' ', $type)),
            'label' => ucfirst(str_replace('_', ' ', $type)),
            'description' => null,
            'status' => 'unknown',
            'icon' => null,
        ];
    }

    private function shouldIncludeData($withData): bool
    {
        if (is_bool($withData)) {
            return $withData;
        }

        if (is_numeric($withData)) {
            return (bool) intval($withData);
        }

        if (is_string($withData)) {
            return filter_var($withData, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        }

        return true;
    }

    private function sanitizeAddonData(array $models): array
    {
        return array_map(function ($model) {
            if (is_object($model) && method_exists($model, 'toArray')) {
                $data = $model->toArray();
            } elseif (is_array($model)) {
                $data = $model;
            } else {
                return $model;
            }

            unset($data['status'], $data['detail_info']);

            return $data;
        }, $models);
    }
}

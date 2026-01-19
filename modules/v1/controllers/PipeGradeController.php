<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreController;

use app\models\PipeGrade;
use app\models\search\PipeGradeSearch;

class PipeGradeController extends CoreController
{
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

        $searchModel = new PipeGradeSearch();
        $dataProvider = $searchModel->search($params);

        CoreController::validateProvider($dataProvider, $searchModel);

        return CoreController::coreData($dataProvider);
    }

    public function actionCreate()
    {
        $model = new PipeGrade();
        $params = Yii::$app->getRequest()->getBodyParams();
        $scenario = Constants::SCENARIO_CREATE;

        CoreController::unavailableParams($model, $params);

        $model->scenario = $scenario;
        $params['status'] = Constants::STATUS_DRAFT;

        if ($model->load($params, '') && $model->validate()) {
            if ($model->save()) {
                return CoreController::coreSuccess($model);
            }
        }

        return CoreController::coreError($model);
    }

    public function actionUpdate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $scenario = Constants::SCENARIO_UPDATE;

        CoreController::validateParams($params, $scenario);

        $model = CoreController::coreFindModelOne(new PipeGrade(), $params);

        if ($model === null) {
            return CoreController::coreDataNotFound();
        }

        CoreController::unavailableParams($model, $params);

        $model->scenario = $scenario;

        if ($superadmin = CoreController::superadmin($params)) {
            return $superadmin;
        }

        if ($model->load($params, '') && $model->validate()) {
            CoreController::emptyParams($model);

            if ($model->save()) {
                return CoreController::coreSuccess($model);
            }
        }

        return CoreController::coreError($model);
    }

    public function actionDelete()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $scenario = Constants::SCENARIO_DELETE;

        CoreController::validateParams($params, $scenario);

        $model = CoreController::coreFindModelOne(new PipeGrade(), $params);

        if ($model === null) {
            return CoreController::coreDataNotFound();
        }

        $model->scenario = $scenario;
        $params['status'] = Constants::STATUS_DELETED;

        if ($superadmin = CoreController::superadmin($params)) {
            return $superadmin;
        }

        if ($model->load($params, '') && $model->validate()) {
            CoreController::emptyParams($model);

            if ($model->save()) {
                return CoreController::coreSuccess($model);
            }
        }

        return CoreController::coreError($model);
    }
}

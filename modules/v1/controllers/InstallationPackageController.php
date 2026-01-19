<?php

namespace app\modules\v1\controllers;

/**
 * Yii required components
 */
use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreController;

/**
 * Model required components
 */
use app\models\InstallationPackage;
use app\models\search\InstallationPackageSearch;

class InstallationPackageController extends CoreController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        #add your action here
        $behaviors['verbs']['actions'] = ArrayHelper::merge(
            $behaviors['verbs']['actions'],
            [
                'index' => ['get'],
                'data' => ['post'],
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

        $searchModel = new InstallationPackageSearch();
        $dataProvider = $searchModel->search($params);

        CoreController::validateProvider($dataProvider, $searchModel);

        return CoreController::coreData($dataProvider);
    }

    public function actionCreate()
    {
        $model = new InstallationPackage();
        $params = Yii::$app->getRequest()->getBodyParams();
        $scenario = Constants::SCENARIO_CREATE;

        CoreController::unavailableParams($model, $params);

        $model->scenario = $scenario;
        $params['status'] = Constants::STATUS_DRAFT;

        if ($model->load($params, '') && $model->validate()) {
            if ($model->save()) {
                #uncomment below code if you want to insert data to mongodb
				// Yii::$app->mongodb->upsert($model);

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

        $model = CoreController::coreFindModelOne(new InstallationPackage(), $params);

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
                #uncomment below code if you want to insert data to mongodb
				// Yii::$app->mongodb->upsert($model);

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

        $model = CoreController::coreFindModelOne(new InstallationPackage(), $params);

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
                #uncomment below code if you want to insert data to mongodb
				// Yii::$app->mongodb->upsert($model);

                return CoreController::coreSuccess($model);
            }
        }

        return CoreController::coreError($model);
    }
}

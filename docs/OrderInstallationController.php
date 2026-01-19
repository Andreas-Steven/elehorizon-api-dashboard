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
use app\models\OrderInstallation;
use app\models\search\OrderInstallationSearch;

class OrderInstallationController extends CoreController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        #add your action here
        $behaviors['verbs']['actions'] = ArrayHelper::merge(
            $behaviors['verbs']['actions'],
            [
                'index' => ['get'],
                'packages' => ['get'],
                'non-packages' => ['get'],
                'by-grade' => ['get'],
            ]
        );

        $behaviors['authenticator']['except'] = ArrayHelper::merge(
            $behaviors['authenticator']['except'],
            [
                'data',
                'packages',
                'non-packages',
                'by-grade',
            ]
        );

        return $behaviors;
    }

    public function actionData()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $searchModel = new OrderInstallationSearch();
        $dataProvider = $searchModel->search($params);

        CoreController::validateProvider($dataProvider, $searchModel);

        return CoreController::coreData($dataProvider);
    }

    /**
     * Get package services
     */
    public function actionPackages()
    {
        $grade = Yii::$app->request->get('grade');
        $length = Yii::$app->request->get('length');
        
        $query = OrderInstallation::find()
            ->where(['service_type' => 'package', 'status' => Constants::STATUS_ACTIVE]);
            
        if ($grade) {
            $query->andWhere(['pipe_grade' => $grade]);
        }
        
        if ($length) {
            $query->andWhere(['pipe_length' => $length]);
        }
        
        $services = $query->all();
        
        return CoreController::coreSuccess($services);
    }

    /**
     * Get non-package services
     */
    public function actionNonPackages()
    {
        $services = OrderInstallation::find()
            ->where(['service_type' => 'non_package', 'status' => Constants::STATUS_ACTIVE])
            ->all();
            
        return CoreController::coreSuccess($services);
    }

    /**
     * Get services by grade
     */
    public function actionByGrade($grade)
    {
        $services = OrderInstallation::find()
            ->where(['pipe_grade' => $grade, 'status' => Constants::STATUS_ACTIVE])
            ->all();
            
        return CoreController::coreSuccess($services);
    }

    public function actionCreate()
    {
        $model = new OrderInstallation();
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

        $model = CoreController::coreFindModelOne(new OrderInstallation(), $params);

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

        $model = CoreController::coreFindModelOne(new OrderInstallation(), $params);

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

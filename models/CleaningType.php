<?php

namespace app\models;

use yii\BaseYii as Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreModel;

class CleaningType extends ActiveRecord
{
    public static $connection = 'db';
    
    public static function tableName()
    {
        return 'cleaning_type';
    }

    public static function getDb()
    {
        return Yii::$app->{static::$connection};
    }

    public static function useDb($connectionName)
    {
        static::$connection = $connectionName;
        return new static();
    }

    public function optimisticLock() 
    {
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [['name'], 'string', 'min' => 3, 'max' => 255],
                [['name'], 'required', 'on' => Constants::SCENARIO_CREATE],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\CleaningTypeQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->name = CoreModel::htmlPurifier($this->name);

            return true;
        }

        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];

            return true;
        }

        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            // Info: Put your code here for insert action

        }
        
        // Info: Call parent afterSave in the end.
        parent::afterSave($insert, $changedAttributes);
    }

    public function afterFind()
    {
        // Info: Put your code here

        // Info: Call parent afterFind in the end.
        parent::afterFind();
    }

    /**
     * Get pipe grades from pipe_grade table
     */
    public static function getPipeGrades()
    {
        return \app\models\PipeGrade::find()->active()->all();
    }

    /**
     * Get installation services with pipe grade integration
     */
    public static function getInstallationServices()
    {
        $services = self::find()
            ->where(['like', 'name', 'Layanan'])
            ->andWhere(['status' => Constants::STATUS_ACTIVE])
            ->all();

        // Enrich with pipe grade data
        foreach ($services as $service) {
            if (isset($service->detail_service['service_type']) && $service->detail_service['service_type'] === 'package') {
                $pipeGrades = self::getPipeGrades();
                $service->detail_service['available_pipe_grades'] = $pipeGrades;
            }
        }

        return $services;
    }

    /**
     * Get package services by grade
     */
    public static function getPackageServicesByGrade($grade)
    {
        $service = self::find()
            ->where(['name' => 'Layanan Paket', 'status' => Constants::STATUS_ACTIVE])
            ->one();

        if (!$service || !isset($service->detail_service['pipe_options'][$grade])) {
            return null;
        }

        return $service->detail_service['pipe_options'][$grade];
    }

    /**
     * Get non-package services
     */
    public static function getNonPackageServices()
    {
        $service = self::find()
            ->where(['name' => 'Layanan Tanpa Paket', 'status' => Constants::STATUS_ACTIVE])
            ->one();

        if (!$service) {
            return [];
        }

        return $service->detail_service['services'] ?? [];
    }
}

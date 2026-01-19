<?php

namespace app\models;

use yii\BaseYii as Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreModel;

class OrderInstallation extends ActiveRecord
{
    public static $connection = 'db';
    
    public static function tableName()
    {
        return 'order_installation';
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
                [['name', 'service_type'], 'required'],
                [['name'], 'string', 'min' => 3, 'max' => 255],
                [['service_type'], 'string', 'max' => 50],
                [['service_type'], 'in', 'range' => ['package', 'non_package']],
                [['pipe_grade'], 'string', 'max' => 50],
                [['pipe_grade'], 'in', 'range' => ['premium', 'luxury']],
                [['pipe_length'], 'string', 'max' => 50],
                [['pipe_length'], 'in', 'range' => ['3M', '5M', 'per_meter']],
                [['base_price'], 'number', 'min' => 0],
                [['estimated_duration'], 'integer', 'min' => 1],
                [['name'], 'required', 'on' => Constants::SCENARIO_CREATE],
                [['pipe_grade', 'pipe_length'], 'required', 'when' => function ($model) {
                    return $model->service_type === 'package';
                }, 'whenClient' => "function (attribute, value) {
                    return $('#service_type').val() === 'package';
                }"],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'service_type', 'pipe_grade', 'pipe_length', 'base_price', 'estimated_duration', 'detail_service', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'service_type', 'pipe_grade', 'pipe_length', 'base_price', 'estimated_duration', 'detail_service', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'service_type' => 'Service Type',
            'pipe_grade' => 'Pipe Grade',
            'pipe_length' => 'Pipe Length',
            'base_price' => 'Base Price',
            'estimated_duration' => 'Estimated Duration',
            'detail_service' => 'Detail Service',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\OrderInstallationQuery(get_called_class());
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
     * Get package services by pipe grade
     */
    public static function getPackageServices($grade = null)
    {
        $query = self::find()->where(['service_type' => 'package', 'status' => Constants::STATUS_ACTIVE]);
        
        if ($grade) {
            $query->andWhere(['pipe_grade' => $grade]);
        }
        
        return $query->all();
    }

    /**
     * Get non-package services
     */
    public static function getNonPackageServices()
    {
        return self::find()->where(['service_type' => 'non_package', 'status' => Constants::STATUS_ACTIVE])->all();
    }

    /**
     * Get services by type and grade
     */
    public static function getServicesByTypeAndGrade($serviceType, $grade = null)
    {
        $query = self::find()->where(['service_type' => $serviceType, 'status' => Constants::STATUS_ACTIVE]);
        
        if ($grade) {
            $query->andWhere(['pipe_grade' => $grade]);
        }
        
        return $query->all();
    }
}

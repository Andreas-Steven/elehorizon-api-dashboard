<?php

namespace app\models;

use yii\BaseYii as Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreModel;

class InstallationService extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'installation_service';
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
                [['code', 'name', 'service_type', 'unit_type'], 'required'],
                [['code'], 'string', 'max' => 50],
                [['name'], 'string', 'min' => 3, 'max' => 255],
                [['description'], 'string'],
                [['service_type'], 'in', 'range' => ['non_package']],
                [['unit_type'], 'in', 'range' => ['unit', 'meter']],
                [['base_price'], 'number', 'min' => 0],
                [['estimated_duration'], 'integer', 'min' => 1],
                [['detail_service', 'detail_info'], 'safe'],
                [['code'], 'unique'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = ['code', 'name', 'description', 'service_type', 'unit_type', 'base_price', 'estimated_duration', 'detail_service', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = $scenarios[Constants::SCENARIO_CREATE];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public static function find()
    {
        return new \app\models\query\InstallationServiceQuery(get_called_class());
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $this->code = CoreModel::htmlPurifier($this->code);
        $this->name = CoreModel::htmlPurifier($this->name);
        $this->description = CoreModel::htmlPurifier($this->description);

        return true;
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
}

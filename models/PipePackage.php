<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;

use app\core\CoreModel;
use app\helpers\Constants;

class PipePackage extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'pipe_package';
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
                [['code', 'name', 'liquid_size', 'gas_size'], 'required', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
                [['power_min', 'power_max', 'price'], 'number'],
                [['btu_min', 'btu_max'], 'integer'],
                [['code'], 'string', 'max' => 20],
                [['name'], 'string', 'max' => 255],
                [['liquid_size', 'gas_size'], 'string', 'max' => 10],
                [['code'], 'unique'],
                [['detail_info'], 'safe'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['code', 'name', 'power_min', 'power_max', 'btu_min', 'btu_max', 'liquid_size', 'gas_size', 'price', 'brand_id', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = $scenarios[Constants::SCENARIO_CREATE];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'name' => 'Name',
            'power_min' => 'Power Min',
            'power_max' => 'Power Max',
            'btu_min' => 'BTU Min',
            'btu_max' => 'BTU Max',
            'liquid_size' => 'Liquid Size',
            'gas_size' => 'Gas Size',
            'price' => 'Price',
            'brand_id' => 'Brand ID',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\PipePackageQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();

        $fields['pipe_grade'] = function () {
            $brandName = null;

            if ($this->brand) {
                $brandName = $this->brand->name ?? null;
            }

            if ($brandName === null && is_array($this->detail_info) && isset($this->detail_info['brand']['name'])) {
                $brandName = $this->detail_info['brand']['name'];
            }

            if ($brandName === 'Hoda') {
                return [
                    'name' => 'Premium',
                    'color' => 'blue',
                    'thickness_mm' => 0.6,
                ];
            }

            if ($brandName === 'Saeki') {
                return [
                    'name' => 'Luxury',
                    'color' => 'gold',
                    'thickness_mm' => 0.8,
                ];
            }

            return null;
        };

        return $fields;
    }

    public function getBrand()
    {
        return $this->hasOne(Brand::class, ['id' => 'brand_id']);
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $this->code = CoreModel::htmlPurifier($this->code);
        $this->name = CoreModel::htmlPurifier($this->name);

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
}

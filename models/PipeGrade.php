<?php

namespace app\models;

use yii\BaseYii as Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreModel;

class PipeGrade extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'pipe_grade';
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
                [['thickness_mm'], 'number'],
                [['brand_id'], 'integer'],
                [['name', 'thickness_mm'], 'required', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
                [['brand_id'], 'exist', 'skipOnError' => true, 'targetClass' => Brand::class, 'targetAttribute' => ['brand_id' => 'id']],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'thickness_mm', 'brand_id', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'thickness_mm', 'brand_id', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'thickness_mm' => 'Thickness Mm',
            'brand_id' => 'Brand ID',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\PipeGradeQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();

        $fields['brand'] = function () {
            if ($this->brand) {
                return [
                    'id' => (int) $this->brand->id,
                    'name' => $this->brand->name,
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

        parent::afterSave($insert, $changedAttributes);
    }

    public function afterFind()
    {
        parent::afterFind();
    }
}

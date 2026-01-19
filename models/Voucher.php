<?php

namespace app\models;

/**
 * Yii required components
 */
use Yii;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;

/**
 * Model required components
 */
use app\core\CoreModel;
use app\helpers\Constants;

class Voucher extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'voucher';
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
                [['start_at', 'end_at'], 'safe'],
                [['value', 'min_purchase', 'max_discount', 'quota', 'used'], 'integer'],
                [['code', 'type', 'name', 'description'], 'string', 'min' => 3, 'max' => 255],

                [['code', 'name', 'type', 'value', 'start_at', 'end_at'], 'required', 'on' => Constants::SCENARIO_CREATE],

                [['type'], 'in', 'range' => ['fixed', 'percent']],
                [['value'], 'compare', 'compareValue' => 0, 'operator' => '>', 'type' => 'number'],
                [['min_purchase', 'max_discount', 'quota', 'used'], 'compare', 'compareValue' => 0, 'operator' => '>=', 'type' => 'number'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = ['code', 'name', 'description', 'type', 'value', 'min_purchase', 'max_discount', 'quota', 'used', 'start_at', 'end_at', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['code', 'name', 'description', 'type', 'value', 'min_purchase', 'max_discount', 'quota', 'used', 'start_at', 'end_at', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'name' => 'Name',
            'description' => 'Description',
            'type' => 'Type',
            'value' => 'Value',
            'min_purchase' => 'Min Purchase',
            'max_discount' => 'Max Discount',
            'quota' => 'Quota',
            'used' => 'Used',
            'start_at' => 'Start At',
            'end_at' => 'End At',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\VoucherQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->code = CoreModel::htmlPurifier($this->code);
            $this->name = CoreModel::htmlPurifier($this->name);
            $this->description = CoreModel::htmlPurifier($this->description);
            $this->type = CoreModel::htmlPurifier($this->type);

            if (is_string($this->code)) {
                $this->code = strtoupper(trim($this->code));
            }

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
}

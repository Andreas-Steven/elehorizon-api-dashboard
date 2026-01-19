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

class OrderCleaning extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'order_cleaning';
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
                [['price'], 'number'],
                [['quantity'], 'integer', 'min' => 1],
                [['name', 'notes'], 'string', 'max' => 255],
                [['detail_member', 'detail_address', 'location_detail', 'unit_detail', 'schedule_detail', 'detail_info'], 'safe'],

                [['name', 'service_type_id', 'quantity',], 'required', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'service_type_id', 'quantity', 'detail_member', 'detail_address', 'location_detail', 'unit_detail', 'schedule_detail', 'notes', 'price', 'status', 'detail_info',];
        $scenarios[Constants::SCENARIO_UPDATE] = $scenarios[Constants::SCENARIO_CREATE];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'service_type_id' => 'Service Type',
            'quantity' => 'Quantity',
            'detail_member' => 'Member Detail',
            'detail_address' => 'Address Detail',
            'location_detail' => 'Location Detail',
            'unit_detail' => 'Unit Detail',
            'schedule_detail' => 'Schedule Detail',
            'notes' => 'Notes',
            'price' => 'Price',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\OrderCleaningQuery(get_called_class());
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

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

    public function afterFind()
    {
        parent::afterFind();
    }

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }
}

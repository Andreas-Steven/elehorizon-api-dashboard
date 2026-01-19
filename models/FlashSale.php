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

class FlashSale extends ActiveRecord
{
    public static $connection = 'db';
    
    public static function tableName()
    {
        return 'flash_sale';
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
                [['product_id', 'flash_price', 'stock'], 'integer'],
                [['start_at', 'end_at'], 'safe'],
                [['product_id', 'flash_price', 'start_at', 'end_at'], 'required', 'on' => Constants::SCENARIO_CREATE],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = ['product_id', 'flash_price', 'stock', 'start_at', 'end_at', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['product_id', 'flash_price', 'stock', 'start_at', 'end_at', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'flash_price' => 'Flash Price',
            'stock' => 'Stock',
            'start_at' => 'Start At',
            'end_at' => 'End At',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\FlashSaleQuery(get_called_class());
    }

    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $detailInfo = is_array($this->detail_info) ? $this->detail_info : [];

            $this->detail_info = ArrayHelper::merge($detailInfo, [
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ]);

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

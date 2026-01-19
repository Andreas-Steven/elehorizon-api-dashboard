<?php

/**
 * Yii required components
 */
namespace app\models;

use yii\BaseYii as Yii;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;

/**
 * Model required components
 */
use app\core\CoreModel;
use app\helpers\Constants;

class ProductVariant extends ActiveRecord
{
    public $thumbnail;
    public $image;
    public $badges;
    public static $connection = 'db';

    public static function tableName()
    {
        return 'product_variant';
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
                [['product_id', 'status'], 'integer'],
                [['stock'], 'integer'],
                [['price', 'original_price'], 'number'],
                [['product_id', 'variant_code', 'name', 'sku'], 'required', 'on' => Constants::SCENARIO_CREATE],
                [['product_id', 'variant_code', 'name', 'sku'], 'required', 'on' => Constants::SCENARIO_UPDATE],
                [['variant_code'], 'string', 'max' => 100],
                [['name', 'sku'], 'string', 'max' => 255],
                [['image_url', 'detail_specs', 'detail_info', 'thumbnail', 'image', 'badges'], 'safe'],
                [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = ['product_id', 'variant_code', 'name', 'sku', 'price', 'original_price', 'stock', 'image_url', 'detail_specs', 'status', 'detail_info', 'thumbnail', 'image', 'badges'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['product_id', 'variant_code', 'name', 'sku', 'price', 'original_price', 'stock', 'image_url', 'detail_specs', 'status', 'detail_info', 'thumbnail', 'image', 'badges'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'variant_code' => 'Variant Code',
            'name' => 'Name',
            'price' => 'Price',
            'original_price' => 'Original Price',
            'stock' => 'Stock',
            'sku' => 'SKU',
            'image_url' => 'Image URL',
            'detail_specs' => 'Detail Specs',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
            'thumbnail' => 'Thumbnail',
            'image' => 'Image',
            'badges' => 'Badges',
        ];
    }

    public static function find()
    {
        return new \app\models\query\ProductVariantQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();

        unset(
            $fields['product_id'], 
            $fields['status'], 
            $fields['detail_info']
        );

        $fields['image_url'] = fn() => $this->decodeJsonField($this->image_url);
        $fields['detail_specs'] = fn() => $this->decodeJsonField($this->detail_specs);

        return $fields;
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        if (empty($this->image_url)) {
            if (!empty($this->image)) {
                $this->image_url = $this->image;
            } elseif (!empty($this->thumbnail)) {
                $this->image_url = [$this->thumbnail];
            }
        }

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

    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    private function decodeJsonField($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}

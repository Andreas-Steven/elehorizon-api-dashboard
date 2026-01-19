<?php

namespace app\models;

/**
 * Yii required components
 */
use yii\BaseYii as Yii;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;

/**
 * Model required components
 */
use app\core\CoreModel;
use app\helpers\Constants;
use app\models\ProductVariant;
use app\models\ProductRating;

/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property string $name
 * @property string $image
 * @property int $category_id
 * @property int $brand_id
 * @property string $badges
 * @property string $price_info
 * @property string $rating
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 */
class Product extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'product';
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
                [['category_id', 'brand_id'], 'integer'],
                [['name'], 'string', 'min' => 3, 'max' => 255],
                [['image', 'badges', 'price_info', 'rating'], 'safe'],

                [['image'], 'required' , 'on' => Constants::SCENARIO_UPDATE],
                [['name', 'category_id', 'brand_id', 'badges', 'price_info', 'rating'], 'required' , 'on' => Constants::SCENARIO_CREATE],

                [['category_id', 'brand_id'], 'compare', 'compareValue' => 0, 'operator' => '>', 'type' => 'integer', 
                    'message' => Yii::t('app', 'integerNoZero', ['label' => '{attribute}'])
                ],
                [['category_id', 'brand_id'], 'filter', 'filter' => 'intval', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'image', 'category_id', 'brand_id', 'badges', 'price_info', 'rating', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'image', 'category_id', 'brand_id', 'badges', 'price_info', 'rating', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'image' => 'Image',
            'category_id' => 'Category ID',
            'brand_id' => 'Brand ID',
            'badges' => 'Badges',
            'price_info' => 'Price Info',
            'rating' => 'Rating',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }
    public static function find()
    {
        return new \app\models\query\ProductQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();

        $fields['variant'] = fn() => $this->variants;

        return $fields;
    }

    public function extraFields()
    {
        $fields = parent::extraFields();
        $fields[] = 'variants';

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

    public function getProductRating()
    {
        return $this->hasOne(ProductRating::class, ['product_variant_id' => 'id'])
            ->via('variants')
            ->andWhere(['<>', ProductRating::tableName() . '.status', Constants::STATUS_DELETED]);
    }

    public function getVariants()
    {
        return $this->hasMany(ProductVariant::class, ['product_id' => 'id'])
            ->andWhere(['<>', 'status', Constants::STATUS_DELETED]);
    }
}
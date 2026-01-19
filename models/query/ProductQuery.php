<?php

namespace app\models\query;

/**
 * Yii required components
 */
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * Model required components
 */
use app\helpers\Constants;

/**
 * This is the ActiveQuery class for [[\app\models\Product]].
 *
 * @see \app\models\Product
 */
class ProductQuery extends ActiveQuery
{
    public function withRating()
    {
        return $this->with(['productRating']);
    }

    public function withVariants()
    {
        return $this->with(['variants']);
    }

    /**
     * {@inheritdoc}
     * @return \app\models\Product[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\models\Product|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * Filter by active status
     * @return ProductQuery
     */
    public function active()
    {
        return $this->andWhere(['status' => Constants::STATUS_ACTIVE]);
    }

    /**
     * Filter by inactive status
     * @return ProductQuery
     */
    public function inactive()
    {
        return $this->andWhere(['status' => Constants::STATUS_INACTIVE]);
    }

    /**
     * Filter by draft status
     * @return ProductQuery
     */
    public function draft()
    {
        return $this->andWhere(['status' => Constants::STATUS_DRAFT]);
    }

    /**
     * Filter by completed status
     * @return ProductQuery
     */
    public function completed()
    {
        return $this->andWhere(['status' => Constants::STATUS_COMPLETED]);
    }

    /**
     * Filter by maintenance status
     * @return ProductQuery
     */
    public function maintenance()
    {
        return $this->andWhere(['status' => Constants::STATUS_MAINTENANCE]);
    }

    /**
     * Filter by deleted status
     * @return ProductQuery
     */
    public function deleted()
    {
        return $this->andWhere(['status' => Constants::STATUS_DELETED]);
    }
}
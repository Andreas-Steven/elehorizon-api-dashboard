<?php

namespace app\models\query;

/**
 * This is the ActiveQuery class for [[\app\models\OrderInstallation]].
 *
 * @see \app\models\OrderInstallation
 */
class OrderInstallationQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        return $this->andWhere(['status' => \app\helpers\Constants::STATUS_ACTIVE]);
    }

    public function package()
    {
        return $this->andWhere(['service_type' => 'package']);
    }

    public function nonPackage()
    {
        return $this->andWhere(['service_type' => 'non_package']);
    }

    public function premium()
    {
        return $this->andWhere(['pipe_grade' => 'premium']);
    }

    public function luxury()
    {
        return $this->andWhere(['pipe_grade' => 'luxury']);
    }

    public function length3m()
    {
        return $this->andWhere(['pipe_length' => '3M']);
    }

    public function length5m()
    {
        return $this->andWhere(['pipe_length' => '5M']);
    }

    public function perMeter()
    {
        return $this->andWhere(['pipe_length' => 'per_meter']);
    }

    /**
     * {@inheritdoc}
     * @return \app\models\OrderInstallation[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\models\OrderInstallation|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}

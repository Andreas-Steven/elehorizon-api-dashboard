<?php

namespace app\models\query;

use yii\db\ActiveQuery;
use app\helpers\Constants;

class InstallationServiceQuery extends ActiveQuery
{
    public function active()
    {
        return $this->andWhere(['status' => Constants::STATUS_ACTIVE]);
    }

    public function nonPackage()
    {
        return $this->andWhere(['service_type' => 'non_package']);
    }
}

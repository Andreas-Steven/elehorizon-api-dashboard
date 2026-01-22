<?php

namespace app\models\query;

use app\helpers\Constants;

class TradeInQuery extends \yii\db\ActiveQuery
{
    public function notDeleted()
    {
        return $this->andWhere(Constants::STATUS_NOT_DELETED);
    }

    public function all($db = null)
    {
        return parent::all($db);
    }

    public function one($db = null)
    {
        return parent::one($db);
    }
}

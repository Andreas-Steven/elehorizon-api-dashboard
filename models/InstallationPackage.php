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

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property float $length
 * @property float $package_price
 * @property float $total_price
 * @property array|string $variant_pipe_size
 * @property array|string $pipe_group
 * @property array|string $included_items
 * @property int $status
 * @property array|string $detail_info
 */
class InstallationPackage extends ActiveRecord
{
    public $pipe_group;
    public $variant_pipe_size = [];
    public $variant_detail_specs = [];
    public $pipe_grade_options = [];
    public $total_price = 0;
    public static $connection = 'db';

    public static function tableName()
    {
        return 'pipe_installation';
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
                [['code', 'name'], 'required', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
                [['code'], 'string', 'max' => 50],
                [['name'], 'string', 'max' => 255],
                [['description'], 'string'],
                [['length', 'package_price', 'total_price'], 'number'],
                [['included_items', 'detail_info', 'pipe_group', 'variant_pipe_size', 'variant_detail_specs', 'pipe_grade_options'], 'safe'],
                [['code'], 'unique'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = [
            'code',
            'name',
            'description',
            'length',
            'package_price',
            'total_price',
            'variant_pipe_size',
            'pipe_group',
            'pipe_grade_options',
            'included_items',
            'status',
            'detail_info',
        ];

        $scenarios[Constants::SCENARIO_UPDATE] = $scenarios[Constants::SCENARIO_CREATE];
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
            'length' => 'Length Meter',
            'package_price' => 'Package Price',
            'total_price' => 'Total Price',
            'variant_pipe_size' => 'Variant Pipe Size',
            'pipe_group' => 'Pipe Group',
            'included_items' => 'Included Items',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\InstallationPackageQuery(get_called_class());
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $this->code = CoreModel::htmlPurifier($this->code);
        $this->name = CoreModel::htmlPurifier($this->name);
        $this->description = CoreModel::htmlPurifier($this->description);

        if (!is_array($this->included_items)) {
            if (!empty($this->included_items) && CoreModel::isJsonString($this->included_items)) {
                $this->included_items = json_decode($this->included_items, true);
            } elseif (empty($this->included_items)) {
                $this->included_items = [];
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

            if (!empty($this->included_items) && is_array($this->included_items)) {
                $this->included_items = json_encode($this->included_items);
            }

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
        $this->included_items = $this->decodeJsonField($this->included_items);
        $this->variant_pipe_size = $this->decodeJsonField($this->variant_pipe_size);
        $this->pipe_group = $this->decodeJsonField($this->pipe_group);
        parent::afterFind();
    }

    public function fields()
    {
        $fields = parent::fields();

        unset($fields['package_price']);
        unset($fields['total_price']);
        unset($fields['status'], $fields['detail_info']);

        $fields['package_price'] = function () {
            return (float)$this->package_price;
        };

        $fields['variant_pipe_size'] = function () {
            return $this->variant_pipe_size;
        };

        $fields['pipe_group'] = function () {
            return $this->pipe_group;
        };

        $fields['pipe_grade_options'] = function () {
            return $this->pipe_grade_options;
        };

        return $fields;
    }

    public function extraFields()
    {
        $fields = parent::extraFields();
        $fields[] = 'pipe_group';

        return $fields;
    }

    private function calculateTotalPrice(): float
    {
        if ($this->length <= 0) {
            return (float)$this->package_price;
        }

        $pipePrice = 0;

        if ($this->pipe_group instanceof PipePackage) {
            $pipePrice = (float)$this->pipe_group->price;
        } elseif (is_array($this->pipe_group) && isset($this->pipe_group['price'])) {
            $pipePrice = (float)$this->pipe_group['price'];
        }

        return (float)$this->package_price + $pipePrice;
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

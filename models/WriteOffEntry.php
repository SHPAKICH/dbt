<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $location_id
 * @property string $entry_date
 * @property string $category
 * @property string|null $product_id
 * @property string $product_name
 * @property float $weight
 * @property string|null $unit
 * @property float $unit_cost
 * @property float $amount
 * @property int|null $created_by
 * @property string $created_at
 * @property string $updated_at
 */
class WriteOffEntry extends ActiveRecord
{
    public const CATEGORY_SPOILAGE = 'spoilage';
    public const CATEGORY_HALL_SERVICE = 'hall_service';
    public const CATEGORY_MARKETING = 'marketing';
    public const CATEGORY_REWORK = 'rework';
    public const CATEGORY_TASTING = 'tasting';

    /** @var array<string, string> */
    public const CATEGORY_LABELS = [
        self::CATEGORY_SPOILAGE => 'Порча',
        self::CATEGORY_HALL_SERVICE => 'Сервис зала',
        self::CATEGORY_MARKETING => 'Маркетинг',
        self::CATEGORY_REWORK => 'Проработка',
        self::CATEGORY_TASTING => 'Бракераж',
    ];

    public static function tableName()
    {
        return 'write_off_entries';
    }

    public function rules()
    {
        return [
            [['location_id', 'entry_date', 'category', 'product_name'], 'required'],
            [['location_id', 'created_by'], 'integer'],
            [['entry_date'], 'date', 'format' => 'php:Y-m-d'],
            [['weight', 'unit_cost', 'amount'], 'number'],
            [['category'], 'in', 'range' => array_keys(self::CATEGORY_LABELS)],
            [['product_id'], 'string', 'max' => 64],
            [['product_name'], 'string', 'max' => 500],
            [['unit'], 'string', 'max' => 32],
        ];
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }
        $this->weight = round((float) $this->weight, 4);
        $this->unit_cost = round((float) $this->unit_cost, 4);
        $this->amount = round($this->weight * $this->unit_cost, 2);

        return true;
    }
}

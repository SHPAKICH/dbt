<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Надбавка к ставке (руб/час) по порогу прохождения КРО.
 *
 * @property int $id
 * @property float $min_percent
 * @property float $hourly_bonus
 * @property int $sort_order
 * @property string $created_at
 * @property string $updated_at
 */
class KroBonusBand extends ActiveRecord
{
    /** Значения по умолчанию, если таблица пуста. */
    public const DEFAULT_BANDS = [
        ['min_percent' => 99, 'hourly_bonus' => 50],
        ['min_percent' => 95, 'hourly_bonus' => 40],
        ['min_percent' => 91, 'hourly_bonus' => 30],
        ['min_percent' => 88, 'hourly_bonus' => 20],
        ['min_percent' => 85, 'hourly_bonus' => 10],
        ['min_percent' => 0, 'hourly_bonus' => 0],
    ];

    public static function tableName(): string
    {
        return 'kro_bonus_bands';
    }

    public function rules(): array
    {
        return [
            [['min_percent', 'hourly_bonus'], 'required'],
            [['min_percent'], 'number', 'min' => 0, 'max' => 100],
            [['hourly_bonus'], 'number', 'min' => 0],
            [['sort_order'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'min_percent' => 'Мин. % прохождения',
            'hourly_bonus' => 'Надбавка, ₽/час',
            'sort_order' => 'Порядок',
        ];
    }

    /**
     * @return array<int, array{min_percent: float, hourly_bonus: float}>
     */
    public static function getBands(): array
    {
        $rows = static::find()
            ->orderBy(['sort_order' => SORT_DESC, 'min_percent' => SORT_DESC])
            ->asArray()
            ->all();

        if ($rows === []) {
            return self::DEFAULT_BANDS;
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'min_percent' => (float) $row['min_percent'],
                'hourly_bonus' => (float) $row['hourly_bonus'],
            ];
        }

        return $out;
    }

    public static function passPercentToBonus(float $passPercent): float
    {
        $p = (float) $passPercent;
        foreach (self::getBands() as $band) {
            $min = (float) $band['min_percent'];
            if ($min <= 0) {
                return (float) $band['hourly_bonus'];
            }
            if ($p >= $min) {
                return (float) $band['hourly_bonus'];
            }
        }

        return 0.0;
    }
}

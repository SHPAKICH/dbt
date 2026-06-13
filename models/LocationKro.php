<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * КРО — процент прохождения проверки точки за месяц (вносит аудитор).
 *
 * @property int $id
 * @property int $location_id
 * @property int $year
 * @property int $month
 * @property float $pass_percent 0–100
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Location $location
 */
class LocationKro extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'location_kro';
    }

    public function rules(): array
    {
        return [
            [['location_id', 'year', 'month'], 'required'],
            [['location_id', 'year', 'month'], 'integer'],
            [['pass_percent'], 'number', 'min' => 0, 'max' => 100],
            [['location_id', 'year', 'month'], 'unique', 'targetAttribute' => ['location_id', 'year', 'month']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'location_id' => 'Точка',
            'year' => 'Год',
            'month' => 'Месяц',
            'pass_percent' => 'Проход проверки, %',
        ];
    }

    public function getLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'location_id']);
    }

    /**
     * Надбавка к ставке (руб/час) по проценту прохождения.
     * 99–100% → +50, 95–98% → +40, 91–94% → +30, 88–90% → +20, 85–87% → +10, ниже 85% → 0.
     */
    public static function passPercentToBonus(float $passPercent): float
    {
        return KroBonusBand::passPercentToBonus($passPercent);
    }

    /**
     * Получить КРО за месяц по точке (или null).
     */
    public static function getForLocationMonth(int $locationId, int $year, int $month): ?self
    {
        return static::findOne([
            'location_id' => $locationId,
            'year' => $year,
            'month' => (int) $month,
        ]);
    }
}

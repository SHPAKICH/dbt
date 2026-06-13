<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property float $hourly_rate
 * @property string $created_at
 * @property string $updated_at
 */
class PositionRate extends ActiveRecord
{
    public static function tableName()
    {
        return 'position_rates';
    }

    public function rules()
    {
        return [
            [['code', 'name'], 'required'],
            [['hourly_rate'], 'number', 'min' => 0],
            [['code'], 'string', 'max' => 50],
            [['name'], 'string', 'max' => 255],
            [['code'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Код должности',
            'name' => 'Название должности',
            'hourly_rate' => 'Ставка (₽/час)',
            'created_at' => 'Создана',
            'updated_at' => 'Обновлена',
        ];
    }

    /**
     * Получить ставку по коду должности.
     */
    public static function getRateByCode(string $code): ?float
    {
        $model = static::findOne(['code' => $code]);
        return $model ? (float)$model->hourly_rate : null;
    }
}




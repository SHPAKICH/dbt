<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Предпочтительный график сотрудника на конкретную дату.
 * Используется для карты возможностей «по числам» (разные недели — разное расписание).
 *
 * @property int $id
 * @property int $user_id
 * @property string $date Y-m-d
 * @property string|null $time_start
 * @property string|null $time_end
 * @property string $created_at
 *
 * @property User $user
 */
class UserAvailabilityByDate extends ActiveRecord
{
    public static function tableName()
    {
        return 'user_availability_by_date';
    }

    public function rules()
    {
        return [
            [['user_id', 'date'], 'required'],
            [['user_id'], 'integer'],
            [['date'], 'date', 'format' => 'php:Y-m-d'],
            [['time_start', 'time_end'], 'match', 'pattern' => '/^\d{2}:\d{2}$/'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['user_id', 'date'], 'unique', 'targetAttribute' => ['user_id', 'date']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Сотрудник',
            'date' => 'Дата',
            'time_start' => 'Начало',
            'time_end' => 'Окончание',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}

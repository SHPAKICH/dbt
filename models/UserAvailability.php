<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Предпочтительный график сотрудника по дням недели.
 *
 * @property int $id
 * @property int $user_id
 * @property int $weekday 1=Пн ... 7=Вс
 * @property string|null $time_start
 * @property string|null $time_end
 *
 * @property User $user
 */
class UserAvailability extends ActiveRecord
{
    public static function tableName()
    {
        return 'user_availability';
    }

    public function rules()
    {
        return [
            [['user_id', 'weekday'], 'required'],
            [['user_id', 'weekday'], 'integer'],
            [['time_start', 'time_end'], 'match', 'pattern' => '/^\d{2}:\d{2}$/'],
            [['weekday'], 'integer', 'min' => 1, 'max' => 7],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Сотрудник',
            'weekday' => 'День недели',
            'time_start' => 'Начало',
            'time_end' => 'Окончание',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}




<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property int $location_id
 * @property string $date
 * @property string|null $time_start
 * @property string|null $time_end
 * @property float $hours
 * @property int $is_night
 * @property int $is_overtime
 * @property int $is_day_off
 * @property string|null $comment
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 * @property Location $location
 */
class ScheduleShift extends ActiveRecord
{
    public static function tableName()
    {
        return 'schedule_shifts';
    }

    public function rules()
    {
        return [
            [['user_id', 'location_id', 'date'], 'required'],
            [['user_id', 'location_id', 'is_night', 'is_overtime', 'is_day_off'], 'integer'],
            [['date'], 'date', 'format' => 'php:Y-m-d'],
            [['time_start', 'time_end'], 'match', 'pattern' => '/^\d{2}:\d{2}$/'],
            [['hours'], 'number', 'min' => 0],
            [['comment'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
            [['user_id', 'location_id', 'date'], 'unique', 'targetAttribute' => ['user_id', 'location_id', 'date']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Сотрудник',
            'location_id' => 'Точка',
            'date' => 'Дата',
            'time_start' => 'Начало',
            'time_end' => 'Окончание',
            'hours' => 'Часы',
            'is_night' => 'Ночная смена',
            'is_overtime' => 'Переработка',
            'is_day_off' => 'Выходной',
            'comment' => 'Комментарий',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'location_id']);
    }

    /**
     * Пересчитывает количество часов и флаги на основе времени и даты.
     */
    public function recalculate(): void
    {
        if ($this->is_day_off || !$this->time_start || !$this->time_end) {
            $this->hours = 0;
            $this->is_night = 0;
            $this->is_overtime = 0;
            return;
        }

        $start = strtotime($this->date . ' ' . $this->time_start);
        $end = strtotime($this->date . ' ' . $this->time_end);
        if ($end <= $start) {
            // Смена может заканчиваться после полуночи
            $end = strtotime($this->date . ' ' . $this->time_end . ' +1 day');
        }

        $hours = ($end - $start) / 3600;
        $this->hours = round($hours, 2);

        // Ночные часы: если смена затрагивает интервал 22:00–06:00
        $nightStart = strtotime($this->date . ' 22:00');
        $nightEnd = strtotime($this->date . ' 06:00 +1 day');
        $this->is_night = ($end > $nightStart && $start < $nightEnd) ? 1 : 0;

        // Переработка: условно более 12 часов за смену
        $this->is_overtime = $this->hours > 12 ? 1 : 0;
    }
}




<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $location_id
 * @property integer $created_by
 * @property string $title
 * @property integer $requires_photo
 * @property string $shift_date
 * @property string $created_at
 *
 * @property Location $location
 * @property User $creator
 * @property ShiftTaskCompletion $completion
 */
class ShiftTask extends ActiveRecord
{
    public static function tableName()
    {
        return 'shift_tasks';
    }

    public function rules()
    {
        return [
            [['location_id', 'created_by', 'title', 'shift_date'], 'required'],
            [['location_id', 'created_by', 'requires_photo'], 'integer'],
            [['title'], 'string', 'max' => 500],
            [['shift_date'], 'date', 'format' => 'php:Y-m-d'],
            [['location_id'], 'exist', 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
            [['created_by'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    public function getLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'location_id']);
    }

    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getCompletion()
    {
        return $this->hasOne(ShiftTaskCompletion::class, ['task_id' => 'id']);
    }
}

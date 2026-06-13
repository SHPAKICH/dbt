<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $task_id
 * @property integer $completed_by
 * @property string|null $comment
 * @property string $completed_at
 *
 * @property ShiftTask $task
 * @property User $completedByUser
 * @property ShiftTaskPhoto[] $photos
 */
class ShiftTaskCompletion extends ActiveRecord
{
    public static function tableName()
    {
        return 'shift_task_completions';
    }

    public function rules()
    {
        return [
            [['task_id', 'completed_by'], 'required'],
            [['task_id', 'completed_by'], 'integer'],
            [['comment'], 'string'],
            [['task_id'], 'exist', 'targetClass' => ShiftTask::class, 'targetAttribute' => ['task_id' => 'id']],
            [['completed_by'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['completed_by' => 'id']],
        ];
    }

    public function getTask()
    {
        return $this->hasOne(ShiftTask::class, ['id' => 'task_id']);
    }

    public function getCompletedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'completed_by']);
    }

    public function getPhotos()
    {
        return $this->hasMany(ShiftTaskPhoto::class, ['completion_id' => 'id']);
    }
}

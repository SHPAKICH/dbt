<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Прогресс прохождения урока пользователем.
 *
 * @property int $id
 * @property int $user_id
 * @property int $material_id
 * @property string $completed_at
 *
 * @property User $user
 * @property TrainingMaterial $material
 */
class LessonProgress extends ActiveRecord
{
    public static function tableName()
    {
        return 'lesson_progress';
    }

    public function rules()
    {
        return [
            [['user_id', 'material_id'], 'required'],
            [['user_id', 'material_id'], 'integer'],
            [['completed_at'], 'safe'],
            [['user_id', 'material_id'], 'unique', 'targetAttribute' => ['user_id', 'material_id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['material_id'], 'exist', 'skipOnError' => true, 'targetClass' => TrainingMaterial::class, 'targetAttribute' => ['material_id' => 'id']],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getMaterial()
    {
        return $this->hasOne(TrainingMaterial::class, ['id' => 'material_id']);
    }
}

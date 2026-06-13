<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $completion_id
 * @property string $file_path
 * @property string $file_name
 * @property string $created_at
 *
 * @property ShiftTaskCompletion $completion
 */
class ShiftTaskPhoto extends ActiveRecord
{
    public static function tableName()
    {
        return 'shift_task_photos';
    }

    public function rules()
    {
        return [
            [['completion_id', 'file_path', 'file_name'], 'required'],
            [['completion_id'], 'integer'],
            [['file_path'], 'string', 'max' => 500],
            [['file_name'], 'string', 'max' => 255],
            [['completion_id'], 'exist', 'targetClass' => ShiftTaskCompletion::class, 'targetAttribute' => ['completion_id' => 'id']],
        ];
    }

    public function getCompletion()
    {
        return $this->hasOne(ShiftTaskCompletion::class, ['id' => 'completion_id']);
    }

    /**
     * Удаляет фото старше 48 часов и их файлы с диска.
     */
    public static function cleanupExpired(): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-48 hours'));
        $photos = static::find()->where(['<', 'created_at', $cutoff])->all();
        $count = 0;
        foreach ($photos as $photo) {
            $absPath = Yii::getAlias('@webroot') . '/' . ltrim($photo->file_path, '/');
            if (file_exists($absPath)) {
                @unlink($absPath);
            }
            $photo->delete();
            $count++;
        }
        return $count;
    }
}

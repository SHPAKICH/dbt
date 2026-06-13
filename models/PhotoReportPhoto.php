<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $location_id
 * @property integer $user_id
 * @property string $report_type   closing|opening
 * @property string $item_key
 * @property string $report_date
 * @property string $file_path
 * @property string $file_name
 * @property string $created_at
 */
class PhotoReportPhoto extends ActiveRecord
{
    public static function tableName()
    {
        return 'photo_report_photos';
    }

    public function rules()
    {
        return [
            [['location_id', 'user_id', 'report_type', 'item_key', 'report_date', 'file_path', 'file_name'], 'required'],
            [['location_id', 'user_id'], 'integer'],
            [['report_type'], 'in', 'range' => ['closing', 'opening']],
            [['report_date'], 'date', 'format' => 'php:Y-m-d'],
            [['item_key'], 'string', 'max' => 100],
            [['file_path'], 'string', 'max' => 500],
            [['file_name'], 'string', 'max' => 255],
        ];
    }

    public function getLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'location_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Delete photos older than 48 hours and remove files from disk.
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

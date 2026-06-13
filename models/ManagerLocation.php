<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель связи управляющих с точками
 *
 * @property integer $id
 * @property integer $manager_id
 * @property integer $location_id
 * @property string $created_at
 */
class ManagerLocation extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'manager_locations';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['manager_id', 'location_id'], 'required'],
            [['manager_id', 'location_id'], 'integer'],
            [['manager_id', 'location_id'], 'unique', 'targetAttribute' => ['manager_id', 'location_id']],
            [['manager_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['manager_id' => 'id']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'manager_id' => 'Управляющий',
            'location_id' => 'Точка',
            'created_at' => 'Создана',
        ];
    }

    /**
     * Связь с пользователем (управляющим)
     */
    public function getManager()
    {
        return $this->hasOne(User::class, ['id' => 'manager_id']);
    }

    /**
     * Связь с точкой
     */
    public function getLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'location_id']);
    }
}


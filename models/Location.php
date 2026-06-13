<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Location model
 *
 * @property integer $id
 * @property string $name
 * @property string $address
 * @property string $phone
 * @property integer $is_active
 * @property float|null $lat
 * @property float|null $lng
 * @property string|null $iiko_name
 * @property string $created_at
 * @property string $updated_at
 */
class Location extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'locations';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['is_active'], 'integer'],
            [['lat', 'lng'], 'number'],
            [['name'], 'string', 'max' => 255],
            [['iiko_name'], 'string', 'max' => 255],
            [['address'], 'string', 'max' => 500],
            [['phone'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'iiko_name' => 'Название в iiko',
            'address' => 'Адрес',
            'phone' => 'Телефон',
            'is_active' => 'Активна',
            'created_at' => 'Создана',
            'updated_at' => 'Обновлена',
        ];
    }

    /**
     * Relation to Users
     */
    public function getUsers()
    {
        return $this->hasMany(User::class, ['location_id' => 'id']);
    }

    /**
     * Строка для сопоставления с iiko: явное имя или название точки.
     */
    public function getIikoMatchName(): string
    {
        if ($this->hasAttribute('iiko_name')) {
            $explicit = trim((string) $this->iiko_name);
            if ($explicit !== '') {
                return $explicit;
            }
        }

        return trim((string) $this->name);
    }
}




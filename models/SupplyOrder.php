<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $location_id
 * @property int $created_by
 * @property string $status
 * @property string|null $comment
 * @property string $created_at
 *
 * @property Location $location
 * @property User $creator
 * @property SupplyOrderItem[] $items
 */
class SupplyOrder extends ActiveRecord
{
    public static function tableName()
    {
        return 'supply_orders';
    }

    public function rules()
    {
        return [
            [['location_id', 'created_by'], 'required'],
            [['location_id', 'created_by'], 'integer'],
            [['comment'], 'string'],
            [['status'], 'string', 'max' => 20],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'location_id' => 'Точка',
            'created_by' => 'Ответственный',
            'status' => 'Статус',
            'comment' => 'Комментарий',
            'created_at' => 'Создан',
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

    public function getItems()
    {
        return $this->hasMany(SupplyOrderItem::class, ['order_id' => 'id']);
    }
}




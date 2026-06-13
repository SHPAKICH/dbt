<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property float $quantity
 *
 * @property SupplyOrder $order
 * @property Product $product
 */
class SupplyOrderItem extends ActiveRecord
{
    public static function tableName()
    {
        return 'supply_order_items';
    }

    public function rules()
    {
        return [
            [['order_id', 'product_id', 'quantity'], 'required'],
            [['order_id', 'product_id'], 'integer'],
            [['quantity'], 'number', 'min' => 0],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => SupplyOrder::class, 'targetAttribute' => ['order_id' => 'id']],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Заказ',
            'product_id' => 'Товар',
            'quantity' => 'Количество',
        ];
    }

    public function getOrder()
    {
        return $this->hasOne(SupplyOrder::class, ['id' => 'order_id']);
    }

    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }
}




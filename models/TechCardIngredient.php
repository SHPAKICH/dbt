<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Ингредиент технологической карты.
 *
 * @property int $id
 * @property int $card_id
 * @property string $ingredient_name
 * @property string|null $size_code
 * @property float $quantity
 * @property string $unit
 * @property float|null $price_per_unit
 * @property int $sort_order
 *
 * @property TechCard $card
 */
class TechCardIngredient extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tech_card_ingredients';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['card_id', 'ingredient_name', 'quantity', 'unit'], 'required'],
            [['card_id', 'sort_order'], 'integer'],
            [['quantity', 'price_per_unit'], 'number', 'min' => 0],
            [['ingredient_name'], 'string', 'max' => 255],
            [['unit'], 'string', 'max' => 50],
            [['size_code'], 'string', 'max' => 20],
            [['sort_order'], 'default', 'value' => 0],
            [['card_id'], 'exist', 'skipOnError' => true, 'targetClass' => TechCard::class, 'targetAttribute' => ['card_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'card_id' => 'Карточка',
            'ingredient_name' => 'Ингредиент',
            'size_code' => 'Размер (S / M / L)',
            'quantity' => 'Количество',
            'unit' => 'Ед. изм.',
            'price_per_unit' => 'Цена за ед.',
            'sort_order' => 'Порядок',
        ];
    }

    public function getCard()
    {
        return $this->hasOne(TechCard::class, ['id' => 'card_id']);
    }

    /**
     * Стоимость строки (количество * цена за единицу).
     */
    public function getLineCost(): float
    {
        if ($this->price_per_unit === null) {
            return 0.0;
        }
        return round((float) $this->quantity * (float) $this->price_per_unit, 2);
    }
}

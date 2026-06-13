<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string|null $sku
 * @property string $unit
 * @property string|null $category
 * @property float|null $price_per_unit
 * @property string|null $package_quantity
 * @property string|null $package_description
 * @property int $sort_order
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 */
class Product extends ActiveRecord
{
    public static function tableName()
    {
        return 'products';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['is_active', 'sort_order'], 'integer'],
            [['price_per_unit'], 'number'],
            [['name'], 'string', 'max' => 255],
            [['sku'], 'string', 'max' => 100],
            [['unit'], 'string', 'max' => 50],
            [['category'], 'string', 'max' => 100],
            [['package_quantity'], 'string', 'max' => 100],
            [['package_description'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Наименование',
            'sku' => 'Артикул',
            'unit' => 'Ед. изм.',
            'category' => 'Категория',
            'price_per_unit' => 'Цена за упаковку',
            'package_quantity' => 'Кол-во в упаковке',
            'package_description' => 'Фасовка',
            'sort_order' => 'Порядок',
            'is_active' => 'Активен',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }
}




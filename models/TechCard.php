<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 * Технологическая карта (позиция меню).
 *
 * @property int $id
 * @property string $name
 * @property string|null $category
 * @property int|null $prep_time
 * @property string|null $description
 * @property string|null $serving
 * @property string|null $image
 * @property UploadedFile|null $imageFile виртуальный атрибут для загрузки
 * @property int $is_active
 * @property int|null $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TechCardIngredient[] $ingredients
 * @property User $createdBy
 */
class TechCard extends ActiveRecord
{
    /** @var UploadedFile|null виртуальный атрибут для загрузки изображения */
    public $imageFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tech_cards';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['description'], 'string'],
            [['prep_time', 'created_by', 'is_active'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['category', 'serving', 'image'], 'string', 'max' => 500],
            [['category'], 'string', 'max' => 100],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'maxSize' => 3 * 1024 * 1024],
            [['is_active'], 'default', 'value' => 1],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
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
            'category' => 'Категория',
            'prep_time' => 'Время приготовления (мин)',
            'description' => 'Описание / технология',
            'serving' => 'Подача',
            'image' => 'Изображение',
            'is_active' => 'Активна',
            'created_by' => 'Создал',
            'created_at' => 'Создана',
            'updated_at' => 'Обновлена',
        ];
    }

    public function getIngredients()
    {
        return $this->hasMany(TechCardIngredient::class, ['card_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }

    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Суммарная стоимость ингредиентов (себестоимость).
     */
    public function getTotalCost(): float
    {
        $sum = 0;
        foreach ($this->ingredients as $ing) {
            if ($ing->price_per_unit !== null) {
                $sum += (float) $ing->price_per_unit * (float) $ing->quantity;
            }
        }
        return round($sum, 2);
    }
}

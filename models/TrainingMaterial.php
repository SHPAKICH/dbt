<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Теоретический материал (модуль Меню Гуру).
 *
 * @property int $id
 * @property string $title
 * @property string|null $category
 * @property string $content
 * @property int|null $test_id
 * @property int $sort_order
 * @property int $is_active
 * @property int|null $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TrainingTest|null $test
 * @property User|null $createdBy
 */
class TrainingMaterial extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'training_materials';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'content'], 'required'],
            [['content'], 'string'],
            [['test_id', 'sort_order', 'is_active', 'created_by'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['category'], 'string', 'max' => 100],
            [['sort_order'], 'default', 'value' => 0],
            [['is_active'], 'default', 'value' => 1],
            [['test_id'], 'exist', 'skipOnError' => true, 'targetClass' => TrainingTest::class, 'targetAttribute' => ['test_id' => 'id']],
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
            'title' => 'Название',
            'category' => 'Категория',
            'content' => 'Содержимое',
            'test_id' => 'Связанный тест',
            'sort_order' => 'Порядок',
            'is_active' => 'Активен',
            'created_by' => 'Создал',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    public function getTest()
    {
        return $this->hasOne(TrainingTest::class, ['id' => 'test_id']);
    }

    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}

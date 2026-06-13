<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 * Тест / викторина (модуль Меню Гуру).
 *
 * @property int $id
 * @property string $title
 * @property string|null $category
 * @property string|null $description
 * @property int|null $time_limit
 * @property int|null $question_time_limit
 * @property float $pass_score
 * @property string|null $image
 * @property UploadedFile|null $imageFile виртуальный атрибут для загрузки
 * @property int $is_active
 * @property int|null $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TrainingQuestion[] $questions
 * @property TrainingResult[] $results
 * @property User $createdBy
 */
class TrainingTest extends ActiveRecord
{
    /** @var UploadedFile|null виртуальный атрибут для загрузки изображения */
    public $imageFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'training_tests';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['description'], 'string'],
            [['time_limit', 'question_time_limit', 'created_by', 'is_active'], 'integer'],
            [['time_limit', 'question_time_limit'], 'filter', 'filter' => function ($v) {
                return $v === '' || $v === null ? null : (int) $v;
            }],
            [['pass_score'], 'number', 'min' => 0, 'max' => 100],
            [['title'], 'string', 'max' => 255],
            [['category', 'image'], 'string', 'max' => 500],
            [['category'], 'string', 'max' => 100],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'maxSize' => 3 * 1024 * 1024],
            [['is_active'], 'default', 'value' => 1],
            [['pass_score'], 'default', 'value' => 70],
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
            'title' => 'Название теста',
            'category' => 'Категория',
            'description' => 'Описание',
            'time_limit' => 'Лимит времени (сек)',
            'question_time_limit' => 'Лимит на вопрос (сек)',
            'pass_score' => 'Проходной балл (%)',
            'image' => 'Изображение',
            'is_active' => 'Активен',
            'created_by' => 'Создал',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    public function getQuestions()
    {
        return $this->hasMany(TrainingQuestion::class, ['test_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }

    public function getResults()
    {
        return $this->hasMany(TrainingResult::class, ['test_id' => 'id']);
    }

    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Максимальная сумма баллов по всем вопросам.
     */
    public function getMaxPoints(): int
    {
        return (int) $this->getQuestions()->sum('points');
    }
}

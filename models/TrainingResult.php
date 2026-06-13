<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Результат прохождения теста пользователем.
 *
 * @property int $id
 * @property int $user_id
 * @property int $test_id
 * @property float $score
 * @property int $points_earned
 * @property int $points_max
 * @property int|null $time_spent
 * @property int $passed
 * @property string|null $answers_data JSON
 * @property string $started_at
 * @property string|null $finished_at
 *
 * @property User $user
 * @property TrainingTest $test
 */
class TrainingResult extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'training_results';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'test_id', 'started_at'], 'required'],
            [['user_id', 'test_id', 'points_earned', 'points_max', 'time_spent', 'passed'], 'integer'],
            [['score'], 'number'],
            [['answers_data'], 'safe'],
            [['started_at', 'finished_at'], 'safe'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['test_id'], 'exist', 'skipOnError' => true, 'targetClass' => TrainingTest::class, 'targetAttribute' => ['test_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Сотрудник',
            'test_id' => 'Тест',
            'score' => 'Балл (%)',
            'points_earned' => 'Набрано баллов',
            'points_max' => 'Макс. баллов',
            'time_spent' => 'Время (сек)',
            'passed' => 'Сдан',
            'answers_data' => 'Ответы',
            'started_at' => 'Начало',
            'finished_at' => 'Окончание',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getTest()
    {
        return $this->hasOne(TrainingTest::class, ['id' => 'test_id']);
    }

    /**
     * Ответы по вопросам (массив).
     */
    public function getAnswersDataArray(): array
    {
        if (empty($this->answers_data)) {
            return [];
        }
        if (is_array($this->answers_data)) {
            return $this->answers_data;
        }
        $decoded = json_decode($this->answers_data, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setAnswersDataArray(array $value): void
    {
        $this->answers_data = json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}

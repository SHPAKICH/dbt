<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Вопрос теста. Варианты ответов хранятся в JSON.
 *
 * @property int $id
 * @property int $test_id
 * @property int $sort_order
 * @property string $question_text
 * @property string $options JSON
 * @property string|null $correct_answer
 * @property string $answer_type single|multiple
 * @property int $points
 * @property string|null $image
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TrainingTest $test
 */
class TrainingQuestion extends ActiveRecord
{
    /** @var array Декодированные варианты ответов */
    private $_optionsArray = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'training_questions';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['test_id', 'question_text', 'options'], 'required'],
            [['test_id', 'sort_order', 'points'], 'integer'],
            [['question_text'], 'string'],
            [['options'], 'validateOptionsJson'],
            [['correct_answer'], 'string', 'max' => 500],
            [['answer_type'], 'string', 'max' => 20],
            [['answer_type'], 'in', 'range' => ['single', 'multiple']],
            [['answer_type'], 'default', 'value' => 'single'],
            [['image'], 'string', 'max' => 500],
            [['sort_order'], 'default', 'value' => 0],
            [['points'], 'default', 'value' => 1],
            [['test_id'], 'exist', 'skipOnError' => true, 'targetClass' => TrainingTest::class, 'targetAttribute' => ['test_id' => 'id']],
        ];
    }

    public function validateOptionsJson($attribute, $params, $validator)
    {
        if (is_array($this->options)) {
            return;
        }
        $decoded = json_decode($this->options, true);
        if (!is_array($decoded) || empty($decoded)) {
            $this->addError($attribute, 'Укажите варианты ответов (массив с полями id, text, is_correct).');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'test_id' => 'Тест',
            'sort_order' => 'Порядок',
            'question_text' => 'Текст вопроса',
            'options' => 'Варианты ответов',
            'correct_answer' => 'Правильный ответ',
            'answer_type' => 'Тип ответа',
            'points' => 'Баллы',
            'image' => 'Изображение',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    public function getTest()
    {
        return $this->hasOne(TrainingTest::class, ['id' => 'test_id']);
    }

    /**
     * Варианты ответов как массив (для формы и вывода).
     * Формат: [['id' => '1', 'text' => '...', 'is_correct' => true], ...]
     */
    public function getOptionsArray(): array
    {
        if ($this->_optionsArray !== []) {
            return $this->_optionsArray;
        }
        if (is_array($this->options)) {
            $this->_optionsArray = $this->options;
            return $this->_optionsArray;
        }
        $decoded = json_decode($this->options, true);
        $this->_optionsArray = is_array($decoded) ? $decoded : [];
        return $this->_optionsArray;
    }

    /**
     * Установить варианты из массива.
     */
    public function setOptionsArray(array $value): void
    {
        $this->_optionsArray = $value;
        $this->options = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->getOptionsArray(); // инициализация _optionsArray
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (is_array($this->options)) {
                $this->options = json_encode($this->options, JSON_UNESCAPED_UNICODE);
            }
            if ($this->hasAttribute('answer_type') && $this->answer_type === null) {
                $this->answer_type = 'single';
            }
            return true;
        }
        return false;
    }

    public function getAnswerType(): string
    {
        if ($this->hasAttribute('answer_type') && $this->answer_type === 'multiple') {
            return 'multiple';
        }

        return count($this->getCorrectAnswerIds()) > 1 ? 'multiple' : 'single';
    }

    public function isMultipleAnswer(): bool
    {
        return $this->getAnswerType() === 'multiple';
    }

    /**
     * @return string[]
     */
    public function getCorrectAnswerIds(): array
    {
        $fromOptions = [];
        foreach ($this->getOptionsArray() as $option) {
            if (!empty($option['is_correct']) && isset($option['id'])) {
                $fromOptions[] = (string)$option['id'];
            }
        }
        if (count($fromOptions) > 1) {
            return array_values(array_unique($fromOptions));
        }

        $raw = trim((string)$this->correct_answer);
        if ($raw === '') {
            return $fromOptions;
        }

        if ($raw[0] === '[') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_unique(array_map('strval', $decoded)));
            }
        }

        if (strpos($raw, ',') !== false) {
            return array_values(array_unique(array_map('trim', explode(',', $raw))));
        }

        if (!empty($fromOptions)) {
            return $fromOptions;
        }

        return [$raw];
    }

    /**
     * @param mixed $answer
     * @return string[]
     */
    public static function normalizeUserAnswerIds($answer): array
    {
        if ($answer === null || $answer === '') {
            return [];
        }
        if (is_array($answer)) {
            return array_values(array_unique(array_map('strval', $answer)));
        }

        $raw = trim((string)$answer);
        if ($raw === '') {
            return [];
        }
        if ($raw[0] === '[') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_unique(array_map('strval', $decoded)));
            }
        }
        if (strpos($raw, ',') !== false) {
            return array_values(array_unique(array_map('trim', explode(',', $raw))));
        }

        return [$raw];
    }

    /**
     * @param string[] $correct
     * @param string[] $user
     */
    public static function answersMatch(array $correct, array $user): bool
    {
        $correct = array_values(array_unique($correct));
        $user = array_values(array_unique($user));
        sort($correct);
        sort($user);

        return $correct === $user;
    }
}

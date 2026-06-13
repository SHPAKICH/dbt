<?php

namespace app\modules\guru\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use app\models\TrainingTest;
use app\models\TrainingQuestion;
use app\models\TrainingResult;
use app\modules\guru\components\GuruImageHelper;

/**
 * Тесты: список, прохождение, CRUD (для админов).
 */
class TestController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            $adminOnly = ['create', 'update', 'delete', 'results'];
                            if (in_array($action->id, $adminOnly, true)) {
                                return $user && $user->isAdmin();
                            }
                            return true;
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * Список тестов (для прохождения и для админа).
     */
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();

        $query = TrainingTest::find()->orderBy(['title' => SORT_ASC]);
        if (!$isAdmin) {
            $query->andWhere(['is_active' => 1]);
        }
        $tests = $query->all();

        return $this->render('index', [
            'tests' => $tests,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Просмотр теста (описание, кнопка «Начать»).
     */
    public function actionView($id)
    {
        $test = $this->findTest($id);
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();
        if (!$test->is_active && !$isAdmin) {
            throw new NotFoundHttpException('Тест недоступен.');
        }
        $questionsCount = $test->getQuestions()->count();
        $maxPoints = $test->getMaxPoints();

        return $this->render('view', [
            'test' => $test,
            'questionsCount' => $questionsCount,
            'maxPoints' => $maxPoints,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Прохождение теста (пошагово по одному вопросу).
     */
    public function actionTake($id)
    {
        $test = $this->findTest($id);
        if (!$test->is_active) {
            throw new NotFoundHttpException('Тест недоступен.');
        }
        $questions = $test->getQuestions()->all();
        if (empty($questions)) {
            Yii::$app->session->setFlash('warning', 'В тесте нет вопросов.');
            return $this->redirect(['view', 'id' => $id]);
        }

        return $this->render('take', [
            'test' => $test,
            'questions' => $questions,
        ]);
    }

    /**
     * Сохранение результата (AJAX или redirect после прохождения).
     */
    public function actionSubmit()
    {
        $this->enableCsrfValidation = true;
        if (!Yii::$app->request->isPost) {
            throw new NotFoundHttpException();
        }
        $testId = (int) Yii::$app->request->post('test_id');
        $test = $this->findTest($testId);
        $answers = Yii::$app->request->post('answers', []);
        $timeSpent = (int) Yii::$app->request->post('time_spent', 0);
        $startedAt = Yii::$app->request->post('started_at', date('Y-m-d H:i:s'));

        $questions = $test->getQuestions()->all();
        $pointsMax = 0;
        $pointsEarned = 0;
        $answersData = [];
        foreach ($questions as $q) {
            $pointsMax += (int) $q->points;
            $correctKey = $q->correct_answer;
            $userAnswer = $answers[$q->id] ?? null;
            $isCorrect = ($userAnswer !== null && (string) $userAnswer === (string) $correctKey);
            if ($isCorrect) {
                $pointsEarned += (int) $q->points;
            }
            $answersData[$q->id] = [
                'question_id' => $q->id,
                'user_answer' => $userAnswer,
                'correct' => $isCorrect,
            ];
        }
        $score = $pointsMax > 0 ? round(100 * $pointsEarned / $pointsMax, 2) : 0;
        $passed = $score >= (float) $test->pass_score;

        $result = new TrainingResult();
        $result->user_id = Yii::$app->user->id;
        $result->test_id = $test->id;
        $result->score = $score;
        $result->points_earned = $pointsEarned;
        $result->points_max = $pointsMax;
        $result->time_spent = $timeSpent > 0 ? $timeSpent : null;
        $result->passed = $passed ? 1 : 0;
        $result->answers_data = json_encode($answersData, JSON_UNESCAPED_UNICODE);
        $result->started_at = $startedAt;
        $result->finished_at = date('Y-m-d H:i:s');
        $result->save(false);

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'success' => true,
                'result_id' => $result->id,
                'score' => $score,
                'passed' => $passed,
                'points_earned' => $pointsEarned,
                'points_max' => $pointsMax,
                'time_spent' => $timeSpent,
            ];
        }
        return $this->redirect(['result', 'id' => $result->id]);
    }

    /**
     * Просмотр своего результата после прохождения.
     */
    public function actionResult($id)
    {
        $result = TrainingResult::find()
            ->where(['id' => $id, 'user_id' => Yii::$app->user->id])
            ->with(['test'])
            ->one();
        if (!$result) {
            throw new NotFoundHttpException('Результат не найден.');
        }
        return $this->render('result', ['result' => $result]);
    }

    /**
     * Таблица лидеров по тесту.
     */
    public function actionLeaderboard($id)
    {
        $test = $this->findTest($id);
        $leaders = TrainingResult::find()
            ->where(['test_id' => $test->id])
            ->andWhere(['not', ['finished_at' => null]])
            ->with('user')
            ->orderBy(['score' => SORT_DESC, 'time_spent' => SORT_ASC])
            ->limit(50)
            ->all();

        return $this->render('leaderboard', [
            'test' => $test,
            'leaders' => $leaders,
        ]);
    }

    // --- CRUD для админов ---

    public function actionCreate()
    {
        $model = new TrainingTest();
        $model->created_by = Yii::$app->user->id;
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->imageFile) {
                $path = GuruImageHelper::save($model->imageFile, GuruImageHelper::TYPE_TEST);
                if ($path) {
                    $model->image = $path;
                }
            }
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Тест создан.');
                return $this->redirect(['update', 'id' => $model->id]);
            }
        }
        return $this->render('form', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findTest($id);
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->imageFile) {
                $path = GuruImageHelper::save($model->imageFile, GuruImageHelper::TYPE_TEST);
                if ($path) {
                    $model->image = $path;
                }
            }
            if ($model->save(false)) {
                $this->saveQuestionsFromPost($model->id);
                Yii::$app->session->setFlash('success', 'Тест сохранён.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }
        $questions = $model->getQuestions()->all();
        return $this->render('form', ['model' => $model, 'questions' => $questions]);
    }

    /**
     * Сохранение вопросов из формы (динамические строки).
     * Форма: questions[i][question_text], questions[i][options][j][id], questions[i][options][j][text], questions[i][correct_answer], questions[i][image_file].
     */
    private function saveQuestionsFromPost(int $testId): void
    {
        $posts = Yii::$app->request->post('questions', []);
        $order = 0;
        $keepIds = [];
        foreach ($posts as $formIndex => $row) {
            if (empty(trim($row['question_text'] ?? ''))) {
                continue;
            }
            $rawOptions = $row['options'] ?? [];
            if (!is_array($rawOptions)) {
                continue;
            }
            $correct = (string)($row['correct_answer'] ?? '');
            $options = [];
            foreach ($rawOptions as $o) {
                $text = trim($o['text'] ?? '');
                if ($text === '') {
                    continue;
                }
                $optId = (string)($o['id'] ?? count($options) + 1);
                $options[] = [
                    'id' => $optId,
                    'text' => $text,
                    'is_correct' => ($optId === $correct),
                ];
            }
            if (empty($options)) {
                continue;
            }
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $question = $id > 0 ? TrainingQuestion::findOne(['id' => $id, 'test_id' => $testId]) : null;
            if (!$question) {
                $question = new TrainingQuestion();
                $question->test_id = $testId;
            }
            $question->sort_order = $order++;
            $question->question_text = $row['question_text'];
            $question->setOptionsArray($options);
            $question->correct_answer = $correct;
            $question->points = (int) ($row['points'] ?? 1);
            $file = UploadedFile::getInstanceByName('questions[' . $formIndex . '][image_file]');
            if ($file) {
                $path = GuruImageHelper::save($file, GuruImageHelper::TYPE_QUESTION);
                if ($path) {
                    $question->image = $path;
                }
            }
            // иначе оставляем прежнее image (для существующего вопроса) или null (для нового)
            $question->save(false);
            $keepIds[] = $question->id;
        }
        if (!empty($keepIds)) {
            TrainingQuestion::deleteAll(['and', ['test_id' => $testId], ['not in', 'id', $keepIds]]);
        } else {
            TrainingQuestion::deleteAll(['test_id' => $testId]);
        }
    }

    public function actionDelete($id)
    {
        $model = $this->findTest($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Тест удалён.');
        return $this->redirect(['index']);
    }

    /**
     * Все результаты по тесту (для админа).
     */
    public function actionResults($id)
    {
        $test = $this->findTest($id);
        $results = TrainingResult::find()
            ->where(['test_id' => $test->id])
            ->with('user')
            ->orderBy(['finished_at' => SORT_DESC])
            ->all();
        return $this->render('results', ['test' => $test, 'results' => $results]);
    }

    private function findTest($id): TrainingTest
    {
        $model = TrainingTest::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Тест не найден.');
        }
        return $model;
    }
}

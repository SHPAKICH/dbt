<?php

namespace app\modules\api\controllers;

use app\models\LessonProgress;
use app\models\Location;
use app\models\ManagerLocation;
use app\models\TechCard;
use app\models\TechCardIngredient;
use app\models\TrainingMaterial;
use app\models\TrainingQuestion;
use app\models\TrainingResult;
use app\models\TrainingTest;
use app\models\User;
use app\modules\guru\components\GuruImageHelper;
use app\services\TelegramNotificationService;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class GuruController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];
        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'dashboard' => ['GET'],
            'tests' => ['GET'],
            'test' => ['GET', 'DELETE'],
            'test-update' => ['POST'],
            'test-questions' => ['GET'],
            'test-questions-edit' => ['GET'],
            'test-submit' => ['POST'],
            'leaderboard' => ['GET'],
            'test-results' => ['GET'],
            'result' => ['GET'],
            'cards' => ['GET'],
            'card' => ['GET', 'DELETE'],
            'card-update' => ['POST'],
            'card-create' => ['POST'],
            'test-create' => ['POST'],
            'lessons' => ['GET'],
            'lesson' => ['GET'],
            'lesson-create' => ['POST'],
            'lesson' => ['GET', 'PUT', 'PATCH'],
            'lesson-upload-image' => ['POST'],
            'lesson-mark-read' => ['POST'],
            'training-progress' => ['GET'],
            'search' => ['GET'],
        ];
    }

    private function imageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        $url = GuruImageHelper::getUrl($path);
        if ($url && strpos($url, 'http') !== 0) {
            $base = rtrim(Yii::$app->request->hostInfo, '/');
            return $base . ($url[0] === '/' ? $url : '/' . $url);
        }
        return $url;
    }

    /**
     * GET /api/v1/guru/dashboard — дашборд Меню Гуру (тесты, счётчик карточек, последние результаты).
     */
    public function actionDashboard(): array
    {
        $user = Yii::$app->user->identity;
        $tests = TrainingTest::find()
            ->where(['is_active' => 1])
            ->orderBy(['title' => SORT_ASC])
            ->limit(8)
            ->all();
        $cardsCount = TechCard::find()->where(['is_active' => 1])->count();
        $lessonsCount = TrainingMaterial::find()->where(['is_active' => 1])->count();
        $testsCount = TrainingTest::find()->where(['is_active' => 1])->count();
        $lessons = TrainingMaterial::find()
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'title' => SORT_ASC])
            ->limit(6)
            ->all();
        $cards = TechCard::find()
            ->where(['is_active' => 1])
            ->orderBy(['name' => SORT_ASC])
            ->limit(6)
            ->all();
        $myResults = TrainingResult::find()
            ->where(['user_id' => $user->id])
            ->with('test')
            ->orderBy(['finished_at' => SORT_DESC])
            ->limit(5)
            ->all();

        $testsList = [];
        foreach ($tests as $t) {
            $testsList[] = [
                'id' => (int)$t->id,
                'title' => $t->title,
                'description' => $t->description,
                'category' => $t->category,
                'timeLimit' => $t->time_limit,
                'passScore' => (float)$t->pass_score,
            ];
        }
        $resultsList = [];
        foreach ($myResults as $r) {
            $resultsList[] = [
                'id' => (int)$r->id,
                'testId' => (int)$r->test_id,
                'testTitle' => $r->test ? $r->test->title : '',
                'passed' => (bool)$r->passed,
                'score' => (float)$r->score,
                'finishedAt' => $r->finished_at,
            ];
        }
        $lessonsList = [];
        foreach ($lessons as $m) {
            $lessonsList[] = [
                'id' => (int)$m->id,
                'title' => $m->title,
                'category' => $m->category,
            ];
        }
        $cardsList = [];
        foreach ($cards as $c) {
            $cardsList[] = [
                'id' => (int)$c->id,
                'title' => $c->name,
                'category' => $c->category ?? null,
                'image' => $this->imageUrl($c->image),
            ];
        }

        return $this->success([
            'tests' => $testsList,
            'cards' => $cardsList,
            'cardsCount' => (int)$cardsCount,
            'lessonsCount' => (int)$lessonsCount,
            'testsCount' => (int)$testsCount,
            'lessons' => $lessonsList,
            'myResults' => $resultsList,
            'isAdmin' => $user->isAdmin(),
            'position' => $user->position,
        ]);
    }

    /**
     * GET /api/v1/guru/search?q= — поиск по урокам, тестам и карточкам.
     */
    public function actionSearch(): array
    {
        $q = trim((string)(Yii::$app->request->get('q') ?? Yii::$app->request->get('search') ?? ''));
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();
        $limit = min(20, max(5, (int)(Yii::$app->request->get('limit') ?? 12)));

        if (mb_strlen($q) < 2) {
            return $this->success([
                'query' => $q,
                'lessons' => [],
                'tests' => [],
                'cards' => [],
            ]);
        }

        $lessonQuery = TrainingMaterial::find()
            ->orderBy(['sort_order' => SORT_ASC, 'title' => SORT_ASC]);
        if (!$isAdmin) {
            $lessonQuery->andWhere(['is_active' => 1]);
        }
        $lessonQuery->andWhere(['or',
            ['like', 'title', $q],
            ['like', 'content', $q],
            ['like', 'category', $q],
        ]);

        $testQuery = TrainingTest::find()->orderBy(['title' => SORT_ASC]);
        if (!$isAdmin) {
            $testQuery->andWhere(['is_active' => 1]);
        }
        $testQuery->andWhere(['or',
            ['like', 'title', $q],
            ['like', 'description', $q],
            ['like', 'category', $q],
        ]);

        $cardQuery = TechCard::find()->orderBy(['name' => SORT_ASC]);
        if (!$isAdmin) {
            $cardQuery->andWhere(['is_active' => 1]);
        }
        $cardQuery->andWhere(['or',
            ['like', 'name', $q],
            ['like', 'description', $q],
            ['like', 'category', $q],
        ]);

        $lessons = [];
        foreach ($lessonQuery->limit($limit)->all() as $m) {
            $lessons[] = [
                'id' => (int)$m->id,
                'title' => $m->title,
                'category' => $m->category,
                'type' => 'lesson',
            ];
        }

        $tests = [];
        foreach ($testQuery->limit($limit)->all() as $t) {
            $tests[] = [
                'id' => (int)$t->id,
                'title' => $t->title,
                'description' => $t->description,
                'category' => $t->category,
                'type' => 'test',
            ];
        }

        $cards = [];
        foreach ($cardQuery->limit($limit)->all() as $c) {
            $cards[] = [
                'id' => (int)$c->id,
                'title' => $c->name,
                'category' => $c->category ?? null,
                'image' => $this->imageUrl($c->image),
                'type' => 'card',
            ];
        }

        return $this->success([
            'query' => $q,
            'lessons' => $lessons,
            'tests' => $tests,
            'cards' => $cards,
        ]);
    }

    /**
     * GET /api/v1/guru/tests — список тестов.
     */
    public function actionTests(): array
    {
        $search = (string)(Yii::$app->request->get('search') ?? '');
        $query = TrainingTest::find()->orderBy(['title' => SORT_ASC]);
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();
        if (!$isAdmin) {
            $query->andWhere(['is_active' => 1]);
        }
        if ($search !== '') {
            $query->andWhere(['or',
                ['like', 'title', $search],
                ['like', 'description', $search],
                ['like', 'category', $search],
            ]);
        }
        $tests = $query->limit(100)->all();
        $list = [];
        foreach ($tests as $t) {
            $list[] = [
                'id' => (int)$t->id,
                'title' => $t->title,
                'description' => $t->description,
                'category' => $t->category,
                'timeLimit' => $t->time_limit,
                'passScore' => (float)$t->pass_score,
            ];
        }
        return $this->success(['tests' => $list]);
    }

    /**
     * GET /api/v1/guru/cards — список технологических карт (поиск, категория).
     */
    public function actionCards(): array
    {
        $search = (string)(Yii::$app->request->get('search') ?? '');
        $category = (string)(Yii::$app->request->get('category') ?? '');
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();

        $query = TechCard::find()->orderBy(['name' => SORT_ASC]);
        if (!$isAdmin) {
            $query->andWhere(['is_active' => 1]);
        }
        if ($search !== '') {
            $query->andWhere(['or',
                ['like', 'name', $search],
                ['like', 'description', $search],
                ['like', 'category', $search],
            ]);
        }
        if ($category !== '') {
            $query->andWhere(['category' => $category]);
        }
        $cards = $query->limit(100)->all();
        $list = [];
        foreach ($cards as $c) {
            $list[] = [
                'id' => (int)$c->id,
                'title' => $c->name,
                'category' => $c->category ?? null,
                'prepTime' => $c->prep_time ? (int)$c->prep_time : null,
                'image' => $this->imageUrl($c->image),
            ];
        }
        $totalQuery = clone $query;
        return $this->success([
            'cards' => $list,
            'total' => (int)$totalQuery->count(),
            'categories' => $this->getCardCategories($isAdmin),
        ]);
    }

    /**
     * POST /api/v1/guru/cards/create — создание технологической карты (админ).
     */
    public function actionCardCreate(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isAdmin()) {
            return $this->error('Доступ запрещён.', [], 403);
        }

        $request = Yii::$app->request;
        $model = new TechCard();
        $model->created_by = Yii::$app->user->id;
        $model->name = trim((string)$request->post('title', ''));
        $model->category = trim((string)$request->post('category', '')) ?: null;
        $model->prep_time = $this->parseNullableInt($request->post('prepTime'));
        $model->serving = trim((string)$request->post('serving', '')) ?: null;
        $model->description = (string)$request->post('description', '');
        $model->is_active = (int)$request->post('isActive', 1) ? 1 : 0;

        $imageFile = UploadedFile::getInstanceByName('imageFile');
        if ($imageFile) {
            $path = GuruImageHelper::save($imageFile, GuruImageHelper::TYPE_CARD);
            if ($path) {
                $model->image = $path;
            }
        }

        $ingredientsRaw = $request->post('ingredients', '[]');
        $ingredients = is_array($ingredientsRaw) ? $ingredientsRaw : json_decode((string)$ingredientsRaw, true);
        if (!is_array($ingredients)) {
            $ingredients = [];
        }

        if (!$model->validate()) {
            return $this->error('Проверьте поля карточки.', $model->getErrors(), 422);
        }

        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            if (!$model->save(false)) {
                throw new \RuntimeException('Не удалось сохранить карточку.');
            }

            $sortOrder = 0;
            foreach ($ingredients as $row) {
                $name = trim((string)($row['ingredientName'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $ing = new TechCardIngredient();
                $ing->card_id = (int)$model->id;
                $ing->ingredient_name = $name;
                $size = strtoupper(trim((string)($row['sizeCode'] ?? '')));
                $ing->size_code = in_array($size, ['S', 'M', 'L'], true) ? $size : null;
                $quantity = $this->parseNullableFloat($row['quantity'] ?? null);
                $ing->quantity = $quantity ?? 0;
                $unit = trim((string)($row['unit'] ?? ''));
                $ing->unit = $unit !== '' ? $unit : 'г';
                $ing->price_per_unit = $this->parseNullableFloat($row['pricePerUnit'] ?? null);
                $ing->sort_order = $sortOrder++;
                if (!$ing->save()) {
                    throw new \RuntimeException('Ошибка сохранения ингредиентов.');
                }
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            return $this->error($e->getMessage(), [], 422);
        }

        (new TelegramNotificationService())->onGuruCardPublished($model, true);

        return $this->success([
            'id' => (int)$model->id,
            'message' => 'Карточка создана.',
        ]);
    }

    /**
     * GET /api/v1/guru/cards/<id> — одна технологическая карта. DELETE — удаление (админ).
     */
    public function actionCard($id): array
    {
        if (Yii::$app->request->isDelete) {
            return $this->actionCardDelete($id);
        }
        $card = TechCard::findOne($id);
        if (!$card) {
            throw new NotFoundHttpException('Карточка не найдена.');
        }
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();
        if (!$card->is_active && !$isAdmin) {
            throw new NotFoundHttpException('Карточка недоступна.');
        }
        $card->getIngredients()->all();
        $ingredientsBySize = [];
        foreach ($card->ingredients as $ing) {
            $key = $ing->size_code ?: '_base';
            if (!isset($ingredientsBySize[$key])) {
                $ingredientsBySize[$key] = [];
            }
            $ingredientsBySize[$key][] = [
                'id' => (int)$ing->id,
                'ingredientName' => $ing->ingredient_name,
                'sizeCode' => $ing->size_code,
                'quantity' => (float)$ing->quantity,
                'unit' => $ing->unit,
                'pricePerUnit' => $ing->price_per_unit !== null ? (float)$ing->price_per_unit : null,
                'lineCost' => $ing->price_per_unit !== null ? round((float)$ing->quantity * (float)$ing->price_per_unit, 2) : null,
            ];
        }
        $totalCost = 0;
        foreach ($card->ingredients as $ing) {
            if ($ing->price_per_unit !== null) {
                $totalCost += (float)$ing->price_per_unit * (float)$ing->quantity;
            }
        }
        $totalCost = round($totalCost, 2);
        return $this->success([
            'id' => (int)$card->id,
            'title' => $card->name,
            'category' => $card->category,
            'prepTime' => $card->prep_time ? (int)$card->prep_time : null,
            'serving' => $card->serving,
            'description' => $card->description,
            'image' => $this->imageUrl($card->image),
            'ingredientsBySize' => $ingredientsBySize,
            'totalCost' => $totalCost,
            'isAdmin' => $isAdmin,
            'isActive' => (bool)$card->is_active,
        ]);
    }

    /**
     * POST /api/v1/guru/cards/<id>/update — обновление технологической карты (админ).
     */
    public function actionCardUpdate($id): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isAdmin()) {
            return $this->error('Доступ запрещён.', [], 403);
        }
        $model = TechCard::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Карточка не найдена.');
        }

        $wasActive = (bool) $model->is_active;
        $request = Yii::$app->request;
        $model->name = trim((string)$request->post('title', $model->name));
        $model->category = trim((string)$request->post('category', '')) ?: null;
        $model->prep_time = $this->parseNullableInt($request->post('prepTime'));
        $model->serving = trim((string)$request->post('serving', '')) ?: null;
        $model->description = (string)$request->post('description', $model->description);
        $model->is_active = (int)$request->post('isActive', $model->is_active) ? 1 : 0;

        $imageFile = UploadedFile::getInstanceByName('imageFile');
        if ($imageFile) {
            $path = GuruImageHelper::save($imageFile, GuruImageHelper::TYPE_CARD);
            if ($path) {
                $model->image = $path;
            }
        }

        $ingredientsRaw = $request->post('ingredients', '[]');
        $ingredients = is_array($ingredientsRaw) ? $ingredientsRaw : json_decode((string)$ingredientsRaw, true);
        if (!is_array($ingredients)) {
            $ingredients = [];
        }

        if (!$model->validate()) {
            return $this->error('Проверьте поля карточки.', $model->getErrors(), 422);
        }

        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            if (!$model->save(false)) {
                throw new \RuntimeException('Не удалось сохранить карточку.');
            }

            TechCardIngredient::deleteAll(['card_id' => $model->id]);
            $sortOrder = 0;
            foreach ($ingredients as $row) {
                $name = trim((string)($row['ingredientName'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $ing = new TechCardIngredient();
                $ing->card_id = (int)$model->id;
                $ing->ingredient_name = $name;
                $size = strtoupper(trim((string)($row['sizeCode'] ?? '')));
                $ing->size_code = in_array($size, ['S', 'M', 'L'], true) ? $size : null;
                $quantity = $this->parseNullableFloat($row['quantity'] ?? null);
                $ing->quantity = $quantity ?? 0;
                $unit = trim((string)($row['unit'] ?? ''));
                $ing->unit = $unit !== '' ? $unit : 'г';
                $ing->price_per_unit = $this->parseNullableFloat($row['pricePerUnit'] ?? null);
                $ing->sort_order = $sortOrder++;
                if (!$ing->save()) {
                    throw new \RuntimeException('Ошибка сохранения ингредиентов.');
                }
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            return $this->error($e->getMessage(), [], 422);
        }

        if ($model->is_active && !$wasActive) {
            (new TelegramNotificationService())->onGuruCardPublished($model, false);
        }

        return $this->success([
            'id' => (int)$model->id,
            'message' => 'Карточка обновлена.',
        ]);
    }

    /**
     * DELETE /api/v1/guru/cards/<id> — удаление технологической карты (админ).
     */
    private function actionCardDelete($id): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isAdmin()) {
            return $this->error('Доступ запрещён.', [], 403);
        }
        $card = TechCard::findOne($id);
        if (!$card) {
            throw new NotFoundHttpException('Карточка не найдена.');
        }
        TechCardIngredient::deleteAll(['card_id' => $card->id]);
        $card->delete();
        return $this->success(['message' => 'Карточка удалена.']);
    }

    /**
     * POST /api/v1/guru/tests/create — создание теста с вопросами (админ).
     */
    public function actionTestCreate(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isAdmin()) {
            return $this->error('Доступ запрещён.', [], 403);
        }

        $request = Yii::$app->request;
        $test = new TrainingTest();
        $test->created_by = Yii::$app->user->id;
        $test->title = trim((string)$request->post('title', ''));
        $test->category = trim((string)$request->post('category', '')) ?: null;
        $test->description = (string)$request->post('description', '');
        $test->time_limit = $this->parseNullableInt($request->post('timeLimit'));
        $test->question_time_limit = $this->parseNullableInt($request->post('questionTimeLimit'));
        $test->pass_score = (float)($request->post('passScore', 70) ?: 70);
        $test->is_active = (int)$request->post('isActive', 1) ? 1 : 0;

        $imageFile = UploadedFile::getInstanceByName('imageFile');
        if ($imageFile) {
            $path = GuruImageHelper::save($imageFile, GuruImageHelper::TYPE_TEST);
            if ($path) {
                $test->image = $path;
            }
        }

        $questionsRaw = $request->post('questions', '[]');
        $questions = is_array($questionsRaw) ? $questionsRaw : json_decode((string)$questionsRaw, true);
        if (!is_array($questions)) {
            $questions = [];
        }

        if (!$test->validate()) {
            return $this->error('Проверьте поля теста.', $test->getErrors(), 422);
        }

        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            if (!$test->save(false)) {
                throw new \RuntimeException('Не удалось сохранить тест.');
            }

            $savedQuestions = 0;
            foreach ($questions as $index => $row) {
                $questionText = trim((string)($row['questionText'] ?? ''));
                if ($questionText === '') {
                    continue;
                }

                $options = $this->buildOptionsFromRow($row);
                $answerData = $this->applyAnswerDataToOptions($row, $options);
                if ($answerData === null) {
                    continue;
                }

                $question = new TrainingQuestion();
                $question->test_id = (int)$test->id;
                $question->sort_order = $savedQuestions;
                $question->question_text = $questionText;
                $question->setOptionsArray($answerData['options']);
                $question->correct_answer = $answerData['correct_answer'];
                if ($question->hasAttribute('answer_type')) {
                    $question->answer_type = $answerData['answer_type'];
                }
                $question->points = max(1, (int)($row['points'] ?? 1));

                $questionImage = UploadedFile::getInstanceByName('questionImage_' . $index);
                if ($questionImage) {
                    $qPath = GuruImageHelper::save($questionImage, GuruImageHelper::TYPE_QUESTION);
                    if ($qPath) {
                        $question->image = $qPath;
                    }
                }

                if (!$question->save()) {
                    throw new \RuntimeException('Ошибка сохранения вопросов.');
                }
                $savedQuestions++;
            }

            if ($savedQuestions === 0) {
                throw new \RuntimeException('Добавьте хотя бы один корректный вопрос.');
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            return $this->error($e->getMessage(), [], 422);
        }

        (new TelegramNotificationService())->onGuruTestPublished($test, true);

        return $this->success([
            'id' => (int)$test->id,
            'message' => 'Тест создан.',
        ]);
    }

    /**
     * GET /api/v1/guru/tests/<id> — данные теста. PUT — обновление. DELETE — удаление (админ).
     */
    public function actionTest($id): array
    {
        $request = Yii::$app->request;
        if ($request->isGet) {
            return $this->actionTestGet($id);
        }
        if ($request->isDelete) {
            return $this->actionTestDelete($id);
        }
        return $this->error('Метод не разрешён.', [], 405);
    }

    private function actionTestGet($id): array
    {
        $test = TrainingTest::findOne($id);
        if (!$test) {
            throw new NotFoundHttpException('Тест не найден.');
        }
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();
        if (!$test->is_active && !$isAdmin) {
            throw new NotFoundHttpException('Тест недоступен.');
        }
        $questionsCount = (int)$test->getQuestions()->count();
        $maxPoints = (int)$test->getMaxPoints();
        return $this->success([
            'id' => (int)$test->id,
            'title' => $test->title,
            'description' => $test->description,
            'category' => $test->category,
            'timeLimit' => $test->time_limit ? (int)$test->time_limit : null,
            'questionTimeLimit' => $test->question_time_limit ? (int)$test->question_time_limit : null,
            'passScore' => (float)$test->pass_score,
            'image' => $this->imageUrl($test->image),
            'questionsCount' => $questionsCount,
            'maxPoints' => $maxPoints,
            'isAdmin' => $isAdmin,
            'isActive' => (bool)$test->is_active,
        ]);
    }

    /**
     * POST /api/v1/guru/tests/<id>/update — обновление теста с вопросами (админ).
     */
    public function actionTestUpdate($id): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isAdmin()) {
            return $this->error('Доступ запрещён.', [], 403);
        }
        $test = TrainingTest::findOne($id);
        if (!$test) {
            throw new NotFoundHttpException('Тест не найден.');
        }

        $wasActive = (bool) $test->is_active;
        $request = Yii::$app->request;
        $test->title = trim((string)$request->post('title', $test->title));
        $test->category = trim((string)$request->post('category', '')) ?: null;
        $test->description = (string)$request->post('description', $test->description);
        $test->time_limit = $this->parseNullableInt($request->post('timeLimit'));
        $test->question_time_limit = $this->parseNullableInt($request->post('questionTimeLimit'));
        $test->pass_score = (float)($request->post('passScore', $test->pass_score) ?: 70);
        $test->is_active = (int)$request->post('isActive', $test->is_active) ? 1 : 0;

        $imageFile = UploadedFile::getInstanceByName('imageFile');
        if ($imageFile) {
            $path = GuruImageHelper::save($imageFile, GuruImageHelper::TYPE_TEST);
            if ($path) {
                $test->image = $path;
            }
        }

        $questionsRaw = $request->post('questions', '[]');
        $questions = is_array($questionsRaw) ? $questionsRaw : json_decode((string)$questionsRaw, true);
        if (!is_array($questions)) {
            $questions = [];
        }

        if (!$test->validate()) {
            return $this->error('Проверьте поля теста.', $test->getErrors(), 422);
        }

        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            if (!$test->save(false)) {
                throw new \RuntimeException('Не удалось сохранить тест.');
            }

            TrainingQuestion::deleteAll(['test_id' => $test->id]);
            $savedQuestions = 0;
            foreach ($questions as $index => $row) {
                $questionText = trim((string)($row['questionText'] ?? ''));
                if ($questionText === '') {
                    continue;
                }

                $options = $this->buildOptionsFromRow($row);
                $answerData = $this->applyAnswerDataToOptions($row, $options);
                if ($answerData === null) {
                    continue;
                }

                $question = new TrainingQuestion();
                $question->test_id = (int)$test->id;
                $question->sort_order = $savedQuestions;
                $question->question_text = $questionText;
                $question->setOptionsArray($answerData['options']);
                $question->correct_answer = $answerData['correct_answer'];
                if ($question->hasAttribute('answer_type')) {
                    $question->answer_type = $answerData['answer_type'];
                }
                $question->points = max(1, (int)($row['points'] ?? 1));

                $questionImage = UploadedFile::getInstanceByName('questionImage_' . $index);
                if ($questionImage) {
                    $qPath = GuruImageHelper::save($questionImage, GuruImageHelper::TYPE_QUESTION);
                    if ($qPath) {
                        $question->image = $qPath;
                    }
                }

                if (!$question->save()) {
                    throw new \RuntimeException('Ошибка сохранения вопросов.');
                }
                $savedQuestions++;
            }

            if ($savedQuestions === 0) {
                throw new \RuntimeException('Добавьте хотя бы один корректный вопрос.');
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            return $this->error($e->getMessage(), [], 422);
        }

        if ($test->is_active && !$wasActive) {
            (new TelegramNotificationService())->onGuruTestPublished($test, false);
        }

        return $this->success([
            'id' => (int)$test->id,
            'message' => 'Тест обновлён.',
        ]);
    }

    /**
     * DELETE /api/v1/guru/tests/<id> — удаление теста (админ).
     */
    private function actionTestDelete($id): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isAdmin()) {
            return $this->error('Доступ запрещён.', [], 403);
        }
        $test = TrainingTest::findOne($id);
        if (!$test) {
            throw new NotFoundHttpException('Тест не найден.');
        }
        TrainingQuestion::deleteAll(['test_id' => $test->id]);
        TrainingResult::deleteAll(['test_id' => $test->id]);
        $test->delete();
        return $this->success(['message' => 'Тест удалён.']);
    }

    /**
     * GET /api/v1/guru/tests/<id>/questions-edit — вопросы с правильными ответами для редактирования (админ).
     */
    public function actionTestQuestionsEdit($id): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isAdmin()) {
            return $this->error('Доступ запрещён.', [], 403);
        }
        $test = TrainingTest::findOne($id);
        if (!$test) {
            throw new NotFoundHttpException('Тест не найден.');
        }
        $questions = $test->getQuestions()->orderBy(['sort_order' => SORT_ASC])->all();
        $list = [];
        foreach ($questions as $q) {
            $list[] = $this->serializeQuestionForEdit($q);
        }
        return $this->success(['questions' => $list]);
    }

    /**
     * GET /api/v1/guru/tests/<id>/questions — вопросы для прохождения (без правильного ответа).
     */
    public function actionTestQuestions($id): array
    {
        $test = TrainingTest::findOne($id);
        if (!$test || !$test->is_active) {
            throw new NotFoundHttpException('Тест не найден или недоступен.');
        }
        $questions = $test->getQuestions()->all();
        $list = [];
        foreach ($questions as $q) {
            $opts = $q->getOptionsArray();
            $options = [];
            foreach ($opts as $o) {
                $options[] = [
                    'id' => (string)($o['id'] ?? ''),
                    'text' => (string)($o['text'] ?? ''),
                ];
            }
            $list[] = [
                'id' => (int)$q->id,
                'questionText' => $q->question_text,
                'options' => $options,
                'points' => (int)$q->points,
                'answerType' => $q->getAnswerType(),
                'image' => $this->imageUrl($q->image),
            ];
        }
        return $this->success(['questions' => $list]);
    }

    /**
     * POST /api/v1/guru/test/submit — отправить ответы теста.
     */
    public function actionTestSubmit(): array
    {
        $body = Yii::$app->request->getBodyParams();
        $testId = (int)($body['testId'] ?? 0);
        $answers = $body['answers'] ?? [];
        $timeSpent = (int)($body['timeSpent'] ?? 0);
        $startedAt = $body['startedAt'] ?? date('Y-m-d H:i:s');

        $test = TrainingTest::findOne($testId);
        if (!$test || !$test->is_active) {
            return $this->error('Тест не найден или недоступен.', [], 404);
        }
        $questions = $test->getQuestions()->all();
        $pointsMax = 0;
        $pointsEarned = 0;
        $answersData = [];
        foreach ($questions as $q) {
            $pointsMax += (int)$q->points;
            $correctIds = $q->getCorrectAnswerIds();
            $userAnswer = $answers[(string)$q->id] ?? null;
            $userIds = TrainingQuestion::normalizeUserAnswerIds($userAnswer);
            $isCorrect = !empty($correctIds) && TrainingQuestion::answersMatch($correctIds, $userIds);
            if ($isCorrect) {
                $pointsEarned += (int)$q->points;
            }
            $answersData[(string)$q->id] = [
                'question_id' => $q->id,
                'user_answer' => $userAnswer,
                'correct' => $isCorrect,
            ];
        }
        $score = $pointsMax > 0 ? round(100 * $pointsEarned / $pointsMax, 2) : 0;
        $passed = $score >= (float)$test->pass_score;

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

        return $this->success([
            'resultId' => $result->id,
            'score' => $score,
            'passed' => $passed,
            'pointsEarned' => $pointsEarned,
            'pointsMax' => $pointsMax,
            'timeSpent' => $timeSpent,
        ]);
    }

    /**
     * GET /api/v1/guru/result/<id> — один результат (свой).
     */
    public function actionResult($id): array
    {
        $result = TrainingResult::find()
            ->where(['id' => $id, 'user_id' => Yii::$app->user->id])
            ->with(['test'])
            ->one();
        if (!$result) {
            throw new NotFoundHttpException('Результат не найден.');
        }
        return $this->success([
            'id' => (int)$result->id,
            'testId' => (int)$result->test_id,
            'testTitle' => $result->test ? $result->test->title : '',
            'score' => (float)$result->score,
            'pointsEarned' => (int)$result->points_earned,
            'pointsMax' => (int)$result->points_max,
            'timeSpent' => $result->time_spent ? (int)$result->time_spent : null,
            'passed' => (bool)$result->passed,
            'finishedAt' => $result->finished_at,
        ]);
    }

    /**
     * GET /api/v1/guru/tests/<id>/leaderboard — таблица лидеров.
     */
    public function actionLeaderboard($id): array
    {
        $test = TrainingTest::findOne($id);
        if (!$test) {
            throw new NotFoundHttpException('Тест не найден.');
        }
        $leaders = TrainingResult::find()
            ->where(['test_id' => $test->id])
            ->andWhere(['not', ['finished_at' => null]])
            ->with('user')
            ->orderBy(['score' => SORT_DESC, 'time_spent' => SORT_ASC])
            ->limit(50)
            ->all();
        $list = [];
        foreach ($leaders as $i => $r) {
            $list[] = [
                'rank' => $i + 1,
                'userName' => $r->user ? $r->user->getFullName() : '—',
                'score' => (float)$r->score,
                'pointsEarned' => (int)$r->points_earned,
                'pointsMax' => (int)$r->points_max,
                'timeSpent' => $r->time_spent ? (int)$r->time_spent : null,
                'finishedAt' => $r->finished_at,
            ];
        }
        return $this->success([
            'testId' => (int)$test->id,
            'testTitle' => $test->title,
            'leaders' => $list,
        ]);
    }

    /**
     * GET /api/v1/guru/tests/<id>/results — все результаты по тесту (админ).
     */
    public function actionTestResults($id): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isAdmin()) {
            Yii::$app->response->statusCode = 403;
            return $this->error('Доступ запрещён.', [], 403);
        }
        $test = TrainingTest::findOne($id);
        if (!$test) {
            throw new NotFoundHttpException('Тест не найден.');
        }
        $results = TrainingResult::find()
            ->where(['test_id' => $test->id])
            ->with('user')
            ->orderBy(['finished_at' => SORT_DESC])
            ->all();
        $list = [];
        foreach ($results as $r) {
            $list[] = [
                'id' => (int)$r->id,
                'userName' => $r->user ? $r->user->getFullName() : '—',
                'score' => (float)$r->score,
                'pointsEarned' => (int)$r->points_earned,
                'pointsMax' => (int)$r->points_max,
                'timeSpent' => $r->time_spent ? (int)$r->time_spent : null,
                'passed' => (bool)$r->passed,
                'finishedAt' => $r->finished_at,
            ];
        }
        return $this->success([
            'testId' => (int)$test->id,
            'testTitle' => $test->title,
            'results' => $list,
        ]);
    }

    /**
     * GET /api/v1/guru/lessons — список уроков.
     */
    public function actionLessons(): array
    {
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();
        $search = (string)(Yii::$app->request->get('search') ?? '');
        $category = (string)(Yii::$app->request->get('category') ?? '');

        $query = TrainingMaterial::find()->orderBy(['sort_order' => SORT_ASC, 'title' => SORT_ASC]);
        if (!$isAdmin) {
            $query->andWhere(['is_active' => 1]);
        }
        if ($search !== '') {
            $query->andWhere(['or', ['like', 'title', $search], ['like', 'content', $search], ['like', 'category', $search]]);
        }
        if ($category !== '') {
            $query->andWhere(['category' => $category]);
        }
        $materials = $query->limit(100)->all();
        $list = [];
        foreach ($materials as $m) {
            $list[] = [
                'id' => (int)$m->id,
                'title' => $m->title,
                'category' => $m->category,
            ];
        }
        $categories = TrainingMaterial::find()
            ->select('category')
            ->distinct()
            ->where(['not', ['category' => null]])
            ->andWhere(['<>', 'category', '']);
        if (!$isAdmin) {
            $categories->andWhere(['is_active' => 1]);
        }
        $categories = $categories->orderBy('category')->column();
        return $this->success([
            'lessons' => $list,
            'categories' => $categories,
        ]);
    }

    /**
     * GET /api/v1/guru/lessons/<id> — один урок. PUT — обновление (админ).
     */
    public function actionLesson($id): array
    {
        $model = TrainingMaterial::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Урок не найден.');
        }
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();

        if (Yii::$app->request->isPut || Yii::$app->request->isPatch) {
            if (!$isAdmin) {
                return $this->error('Доступ запрещён.', [], 403);
            }
            $request = Yii::$app->request;
            $body = $request->getBodyParams();
            if (isset($body['title'])) {
                $model->title = trim((string)$body['title']);
            }
            if (array_key_exists('category', $body)) {
                $model->category = trim((string)$body['category']) ?: null;
            }
            if (isset($body['content'])) {
                $model->content = (string)$body['content'];
            }
            if (array_key_exists('testId', $body)) {
                $model->test_id = $this->parseNullableInt($body['testId']);
            }
            if (array_key_exists('sortOrder', $body)) {
                $model->sort_order = (int)($body['sortOrder'] ?? 0);
            }
            $wasActive = (bool) $model->is_active;
            if (array_key_exists('isActive', $body)) {
                $model->is_active = (int)($body['isActive'] ? 1 : 0);
            }
            if (!$model->validate()) {
                return $this->error('Проверьте поля урока.', $model->getErrors(), 422);
            }
            if (!$model->save(false)) {
                return $this->error('Не удалось сохранить урок.', [], 422);
            }
            if ($model->is_active && !$wasActive) {
                (new TelegramNotificationService())->onGuruLessonPublished($model, false);
            }
            return $this->success(['id' => (int)$model->id, 'message' => 'Урок обновлён.']);
        }

        if (!$model->is_active && !$isAdmin) {
            throw new NotFoundHttpException('Урок недоступен.');
        }
        return $this->success([
            'id' => (int)$model->id,
            'title' => $model->title,
            'category' => $model->category,
            'content' => $model->content,
            'testId' => $model->test_id ? (int)$model->test_id : null,
            'sortOrder' => (int)$model->sort_order,
            'isActive' => (bool)$model->is_active,
            'createdAt' => $model->created_at,
            'updatedAt' => $model->updated_at,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * POST /api/v1/guru/lessons/create — создание урока (админ).
     */
    public function actionLessonCreate(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isAdmin()) {
            return $this->error('Доступ запрещён.', [], 403);
        }
        $request = Yii::$app->request;
        $body = $request->getBodyParams() ?: [];
        $model = new TrainingMaterial();
        $model->created_by = Yii::$app->user->id;
        $model->title = trim((string)($body['title'] ?? $request->post('title', '')));
        $model->category = trim((string)($body['category'] ?? $request->post('category', ''))) ?: null;
        $model->content = (string)($body['content'] ?? $request->post('content', ''));
        $model->test_id = $this->parseNullableInt($body['testId'] ?? $request->post('testId'));
        $model->sort_order = (int)($body['sortOrder'] ?? $request->post('sortOrder', 0) ?: 0);
        $model->is_active = (int)($body['isActive'] ?? $request->post('isActive', 1) ?: 1) ? 1 : 0;
        if (!$model->validate()) {
            return $this->error('Проверьте поля урока.', $model->getErrors(), 422);
        }
        if (!$model->save(false)) {
            return $this->error('Не удалось сохранить урок.', [], 422);
        }

        (new TelegramNotificationService())->onGuruLessonPublished($model, true);

        return $this->success([
            'id' => (int)$model->id,
            'message' => 'Урок создан.',
        ]);
    }

    /**
     * POST /api/v1/guru/lessons/upload-image — загрузка изображения для урока (админ).
     * multipart/form-data, поле "file". Возвращает { url }.
     */
    public function actionLessonUploadImage(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isAdmin()) {
            return $this->error('Доступ запрещён.', [], 403);
        }
        $file = UploadedFile::getInstanceByName('file');
        if (!$file || !$file->tempName) {
            return $this->error('Файл не загружен', [], 422);
        }
        $path = GuruImageHelper::save($file, GuruImageHelper::TYPE_LESSON);
        if (!$path) {
            return $this->error('Не удалось сохранить изображение. Допустимы: JPEG, PNG, GIF, WebP.', [], 422);
        }
        $url = $this->imageUrl($path);
        if (!$url) {
            $base = rtrim(Yii::$app->request->hostInfo, '/');
            $url = $base . '/uploads/guru/' . $path;
        }
        return $this->success(['url' => $url]);
    }

    /**
     * POST /api/v1/guru/lessons/<id>/mark-read — отметить урок как прочитанный.
     */
    public function actionLessonMarkRead($id): array
    {
        $material = TrainingMaterial::findOne($id);
        if (!$material) {
            throw new NotFoundHttpException('Урок не найден.');
        }
        $userId = Yii::$app->user->id;
        $exists = LessonProgress::find()->where(['user_id' => $userId, 'material_id' => (int)$id])->exists();
        if (!$exists) {
            $lp = new LessonProgress();
            $lp->user_id = $userId;
            $lp->material_id = (int)$id;
            $lp->save(false);
        }
        return $this->success(['ok' => true]);
    }

    /**
     * GET /api/v1/guru/training-progress — прогресс обучения сотрудников.
     * Админ видит всех, управляющий — свою территорию, менеджер точки — свою точку.
     */
    public function actionTrainingProgress(): array
    {
        $currentUser = Yii::$app->user->identity;
        if (!$currentUser) {
            return $this->error('Не авторизован.', [], 401);
        }

        $isAdmin = $currentUser->isAdmin();
        $isManager = ($currentUser->position === 'manager');
        $isLocationManager = ($currentUser->position === 'location_manager');

        if (!$isAdmin && !$isManager && !$isLocationManager) {
            return $this->error('Доступ запрещён.', [], 403);
        }

        $userQuery = User::find()->where(['is_active' => 1]);

        if ($isLocationManager && !$isAdmin) {
            $userQuery->andWhere(['location_id' => $currentUser->location_id]);
        } elseif ($isManager && !$isAdmin) {
            $locationIds = ManagerLocation::find()
                ->select('location_id')
                ->where(['manager_id' => $currentUser->id])
                ->column();
            if (empty($locationIds)) {
                $locationIds = [$currentUser->location_id];
            }
            $userQuery->andWhere(['location_id' => $locationIds]);
        }

        $users = $userQuery->with('location')->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])->all();
        $userIds = array_map(fn($u) => $u->id, $users);

        $totalTests = (int)TrainingTest::find()->where(['is_active' => 1])->count();
        $totalLessons = (int)TrainingMaterial::find()->where(['is_active' => 1])->count();

        $testResults = TrainingResult::find()
            ->where(['user_id' => $userIds])
            ->all();

        $resultsByUser = [];
        foreach ($testResults as $r) {
            $uid = (int)$r->user_id;
            if (!isset($resultsByUser[$uid])) {
                $resultsByUser[$uid] = [];
            }
            $resultsByUser[$uid][] = $r;
        }

        $lessonProgress = LessonProgress::find()
            ->where(['user_id' => $userIds])
            ->all();

        $lessonsByUser = [];
        foreach ($lessonProgress as $lp) {
            $uid = (int)$lp->user_id;
            if (!isset($lessonsByUser[$uid])) {
                $lessonsByUser[$uid] = [];
            }
            $lessonsByUser[$uid][] = $lp;
        }

        $rows = [];
        foreach ($users as $u) {
            $uid = (int)$u->id;
            $userResults = $resultsByUser[$uid] ?? [];
            $userLessons = $lessonsByUser[$uid] ?? [];

            $passedTestIds = [];
            $bestScores = [];
            $lastActivity = null;

            foreach ($userResults as $r) {
                $tid = (int)$r->test_id;
                if ($r->passed) {
                    $passedTestIds[$tid] = true;
                }
                if (!isset($bestScores[$tid]) || (float)$r->score > $bestScores[$tid]) {
                    $bestScores[$tid] = (float)$r->score;
                }
                if ($r->finished_at && ($lastActivity === null || $r->finished_at > $lastActivity)) {
                    $lastActivity = $r->finished_at;
                }
            }

            $completedLessonIds = [];
            foreach ($userLessons as $lp) {
                $completedLessonIds[(int)$lp->material_id] = true;
                if ($lp->completed_at && ($lastActivity === null || $lp->completed_at > $lastActivity)) {
                    $lastActivity = $lp->completed_at;
                }
            }

            $avgScore = count($bestScores) > 0 ? round(array_sum($bestScores) / count($bestScores), 1) : null;

            $rows[] = [
                'userId' => $uid,
                'fullName' => $u->getFullName(),
                'position' => $u->getPositionLabel(),
                'location' => $u->location ? $u->location->name : '—',
                'testsPassed' => count($passedTestIds),
                'testsTotal' => $totalTests,
                'avgScore' => $avgScore,
                'lessonsCompleted' => count($completedLessonIds),
                'lessonsTotal' => $totalLessons,
                'lastActivity' => $lastActivity,
            ];
        }

        $locations = [];
        if ($isAdmin) {
            $locations = Location::find()->where(['is_active' => 1])->orderBy('name')->all();
        } elseif ($isManager) {
            $locationIds = ManagerLocation::find()->select('location_id')->where(['manager_id' => $currentUser->id])->column();
            if (empty($locationIds)) {
                $locationIds = [$currentUser->location_id];
            }
            $locations = Location::find()->where(['id' => $locationIds])->orderBy('name')->all();
        } elseif ($isLocationManager) {
            $locations = Location::find()->where(['id' => $currentUser->location_id])->all();
        }

        $locationsList = [];
        foreach ($locations as $loc) {
            $locationsList[] = ['id' => (int)$loc->id, 'name' => $loc->name];
        }

        return $this->success([
            'users' => $rows,
            'locations' => $locationsList,
            'totalTests' => $totalTests,
            'totalLessons' => $totalLessons,
        ]);
    }

    private function getCardCategories(bool $isAdmin): array
    {
        $query = TechCard::find()->select('category')->distinct()->where(['not', ['category' => null]])->andWhere(['<>', 'category', '']);
        if (!$isAdmin) {
            $query->andWhere(['is_active' => 1]);
        }
        return $query->orderBy('category')->column();
    }

    /**
     * @return array<int, array{id: string, text: string, is_correct: bool}>
     */
    private function buildOptionsFromRow(array $row): array
    {
        $rawOptions = $row['options'] ?? [];
        if (!is_array($rawOptions)) {
            $rawOptions = [];
        }

        $options = [];
        foreach ($rawOptions as $optIndex => $opt) {
            $text = trim((string)($opt['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $options[] = [
                'id' => (string)($opt['id'] ?? ($optIndex + 1)),
                'text' => $text,
                'is_correct' => false,
            ];
        }

        return $options;
    }

    /**
     * @param array<int, array{id: string, text: string, is_correct: bool}> $options
     * @return array{options: array, correct_answer: string, answer_type: string}|null
     */
    private function applyAnswerDataToOptions(array $row, array $options): ?array
    {
        if (empty($options)) {
            return null;
        }

        $answerType = (($row['answerType'] ?? $row['answer_type'] ?? 'single') === 'multiple') ? 'multiple' : 'single';
        $optionIds = array_column($options, 'id');

        if ($answerType === 'multiple') {
            $correctIds = $row['correctAnswers'] ?? $row['correct_answers'] ?? [];
            if (!is_array($correctIds)) {
                $correctIds = [];
            }
            $correctIds = array_values(array_unique(array_map('strval', $correctIds)));
            $correctIds = array_values(array_intersect($correctIds, $optionIds));

            if (empty($correctIds)) {
                foreach ($row['options'] ?? [] as $opt) {
                    if (!empty($opt['is_correct']) && isset($opt['id'])) {
                        $correctIds[] = (string)$opt['id'];
                    }
                }
                $correctIds = array_values(array_unique(array_intersect($correctIds, $optionIds)));
            }

            if (empty($correctIds)) {
                $correctIds = [(string)$options[0]['id']];
            }

            foreach ($options as &$opt) {
                $opt['is_correct'] = in_array($opt['id'], $correctIds, true);
            }
            unset($opt);

            return [
                'options' => $options,
                'correct_answer' => json_encode($correctIds, JSON_UNESCAPED_UNICODE),
                'answer_type' => 'multiple',
            ];
        }

        $correctAnswer = (string)($row['correctAnswer'] ?? $row['correct_answer'] ?? '');
        if ($correctAnswer === '' || !in_array($correctAnswer, $optionIds, true)) {
            $correctAnswer = (string)$options[0]['id'];
        }

        foreach ($options as &$opt) {
            $opt['is_correct'] = $opt['id'] === $correctAnswer;
        }
        unset($opt);

        return [
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'answer_type' => 'single',
        ];
    }

    private function serializeQuestionForEdit(TrainingQuestion $q): array
    {
        $opts = $q->getOptionsArray();
        $options = [];
        foreach ($opts as $o) {
            $options[] = [
                'id' => (string)($o['id'] ?? ''),
                'text' => (string)($o['text'] ?? ''),
                'is_correct' => !empty($o['is_correct']),
            ];
        }

        $answerType = $q->getAnswerType();
        $correctIds = $q->getCorrectAnswerIds();

        return [
            'id' => (int)$q->id,
            'questionText' => $q->question_text,
            'options' => $options,
            'points' => (int)$q->points,
            'answerType' => $answerType,
            'correctAnswer' => $answerType === 'single' ? ($correctIds[0] ?? '') : '',
            'correctAnswers' => $answerType === 'multiple' ? $correctIds : [],
            'image' => $this->imageUrl($q->image),
        ];
    }

    private function parseNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }

    private function parseNullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float)str_replace(',', '.', (string)$value);
    }
}

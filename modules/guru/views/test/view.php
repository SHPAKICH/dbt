<?php

/** @var yii\web\View $this */
/** @var app\models\TrainingTest $test */
/** @var int $questionsCount */
/** @var int $maxPoints */
/** @var bool $isAdmin */

use yii\bootstrap5\Html;
use app\modules\guru\components\GuruImageHelper;

$this->title = $test->title;
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = ['label' => 'Тесты', 'url' => ['/guru/test/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="guru-test-view">
    <div class="card">
        <?php if ($test->image): ?>
            <img src="<?= Html::encode(GuruImageHelper::getUrl($test->image)) ?>" class="card-img-top" alt="" style="max-height: 200px; object-fit: cover;">
        <?php endif; ?>
        <div class="card-body">
            <h1 class="card-title"><?= Html::encode($test->title) ?></h1>
            <?php if ($test->category): ?>
                <span class="badge bg-secondary"><?= Html::encode($test->category) ?></span>
            <?php endif; ?>
            <?php if ($test->description): ?>
                <div class="mt-3"><?= nl2br(Html::encode($test->description)) ?></div>
            <?php endif; ?>
            <ul class="list-unstyled mt-3">
                <li>Вопросов: <strong><?= $questionsCount ?></strong></li>
                <li>Максимум баллов: <strong><?= $maxPoints ?></strong></li>
                <li>Проходной балл: <strong><?= (float)$test->pass_score ?>%</strong></li>
                <?php if ($test->time_limit): ?>
                    <li>Лимит времени на тест: <strong><?= (int)$test->time_limit ?> сек</strong></li>
                <?php endif; ?>
                <?php if ($test->question_time_limit): ?>
                    <li>Лимит на вопрос: <strong><?= (int)$test->question_time_limit ?> сек</strong></li>
                <?php endif; ?>
            </ul>
            <div class="mt-4 d-flex gap-2 flex-wrap">
                <?php if ($test->is_active && $questionsCount > 0): ?>
                    <?= Html::a('Начать тест', ['take', 'id' => $test->id], ['class' => 'btn btn-primary btn-lg']) ?>
                <?php endif; ?>
                <?= Html::a('Таблица лидеров', ['leaderboard', 'id' => $test->id], ['class' => 'btn btn-outline-secondary']) ?>
                <?php if ($isAdmin): ?>
                    <?= Html::a('Редактировать', ['update', 'id' => $test->id], ['class' => 'btn btn-warning']) ?>
                    <?= Html::a('Все результаты', ['results', 'id' => $test->id], ['class' => 'btn btn-outline-info']) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

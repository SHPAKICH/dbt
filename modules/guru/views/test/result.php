<?php

/** @var yii\web\View $this */
/** @var app\models\TrainingResult $result */

use yii\bootstrap5\Html;

$this->title = 'Результат теста';
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = ['label' => 'Тесты', 'url' => ['/guru/test/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="guru-test-result">
    <div class="card">
        <div class="card-body text-center">
            <h2><?= Html::encode($result->test->title ?? 'Тест') ?></h2>
            <p class="lead">
                <?php if ($result->passed): ?>
                    <span class="text-success">Поздравляем! Тест сдан.</span>
                <?php else: ?>
                    <span class="text-danger">Тест не сдан. Попробуйте ещё раз.</span>
                <?php endif; ?>
            </p>
            <p class="mb-1">Набрано баллов: <strong><?= $result->points_earned ?> / <?= $result->points_max ?></strong></p>
            <p class="mb-1">Процент: <strong><?= Yii::$app->formatter->asPercent($result->score / 100, 1) ?></strong></p>
            <?php if ($result->time_spent): ?>
                <p class="mb-1">Время: <strong><?= gmdate('i:s', $result->time_spent) ?></strong></p>
            <?php endif; ?>
            <?php if ($result->finished_at): ?>
                <p class="text-muted small"><?= Yii::$app->formatter->asDatetime($result->finished_at) ?></p>
            <?php endif; ?>
            <div class="mt-4">
                <?= Html::a('К списку тестов', ['/guru/test/index'], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Таблица лидеров', ['leaderboard', 'id' => $result->test_id], ['class' => 'btn btn-outline-secondary']) ?>
                <?= Html::a('Пройти снова', ['view', 'id' => $result->test_id], ['class' => 'btn btn-outline-primary']) ?>
            </div>
        </div>
    </div>
</div>

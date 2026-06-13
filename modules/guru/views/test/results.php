<?php

/** @var yii\web\View $this */
/** @var app\models\TrainingTest $test */
/** @var app\models\TrainingResult[] $results */

use yii\bootstrap5\Html;

$this->title = 'Результаты: ' . $test->title;
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = ['label' => 'Тесты', 'url' => ['/guru/test/index']];
$this->params['breadcrumbs'][] = ['label' => $test->title, 'url' => ['view', 'id' => $test->id]];
$this->params['breadcrumbs'][] = 'Результаты';
?>
<div class="guru-test-results">
    <h1 class="mb-4">Результаты по тесту «<?= Html::encode($test->title) ?>»</h1>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Сотрудник</th>
                        <th>Балл (%)</th>
                        <th>Баллы</th>
                        <th>Время</th>
                        <th>Сдан</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td><?= Html::encode($r->user ? $r->user->getFullName() : '—') ?></td>
                            <td><?= Yii::$app->formatter->asPercent($r->score / 100, 1) ?></td>
                            <td><?= $r->points_earned ?> / <?= $r->points_max ?></td>
                            <td><?= $r->time_spent ? gmdate('i:s', $r->time_spent) : '—' ?></td>
                            <td><?= $r->passed ? 'Да' : 'Нет' ?></td>
                            <td><?= $r->finished_at ? Yii::$app->formatter->asDatetime($r->finished_at) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (empty($results)): ?>
            <div class="card-body text-muted">Нет прохождений.</div>
        <?php endif; ?>
    </div>

    <p class="mt-3">
        <?= Html::a('← К тесту', ['view', 'id' => $test->id], ['class' => 'btn btn-outline-secondary']) ?>
    </p>
</div>

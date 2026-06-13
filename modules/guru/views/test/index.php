<?php

/** @var yii\web\View $this */
/** @var app\models\TrainingTest[] $tests */
/** @var bool $isAdmin */

use yii\bootstrap5\Html;
use app\modules\guru\components\GuruImageHelper;

$this->title = 'Тесты и аттестации';
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="guru-test-index">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?php if ($isAdmin): ?>
            <?= Html::a('Создать тест', ['/learning/tests/create'], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
    </div>

    <div class="row">
        <?php foreach ($tests as $test): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 <?= $test->is_active ? '' : 'border-secondary' ?>">
                    <?php if ($test->image): ?>
                        <img src="<?= Html::encode(GuruImageHelper::getUrl($test->image)) ?>" class="card-img-top" alt="" style="height: 140px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= Html::encode($test->title) ?></h5>
                        <?php if ($test->category): ?>
                            <span class="badge bg-secondary"><?= Html::encode($test->category) ?></span>
                        <?php endif; ?>
                        <?php if ($test->description): ?>
                            <p class="card-text text-muted small mt-2"><?= Html::encode(mb_substr($test->description, 0, 100)) ?><?= mb_strlen($test->description) > 100 ? '…' : '' ?></p>
                        <?php endif; ?>
                        <p class="small mb-2">
                            Вопросов: <?= $test->getQuestions()->count() ?> ·
                            Проходной балл: <?= (float)$test->pass_score ?>%
                            <?php if ($test->time_limit): ?>
                                · Время: <?= (int)$test->time_limit ?> сек
                            <?php endif; ?>
                        </p>
                        <div class="d-flex gap-1">
                            <?= Html::a('Подробнее', ['view', 'id' => $test->id], ['class' => 'btn btn-primary btn-sm']) ?>
                            <?= Html::a('Таблица лидеров', ['leaderboard', 'id' => $test->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
                            <?php if ($isAdmin): ?>
                                <?= Html::a('Изменить', ['update', 'id' => $test->id], ['class' => 'btn btn-outline-warning btn-sm']) ?>
                                <?= Html::a('Результаты', ['results', 'id' => $test->id], ['class' => 'btn btn-outline-info btn-sm']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($tests)): ?>
        <div class="alert alert-info">Нет доступных тестов.</div>
    <?php endif; ?>
</div>

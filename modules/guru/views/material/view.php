<?php

/** @var yii\web\View $this */
/** @var app\models\TrainingMaterial $model */
/** @var bool $isAdmin */

use yii\bootstrap5\Html;

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = ['label' => 'Библиотека', 'url' => ['/guru/material/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="guru-material-view">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <h1><?= Html::encode($model->title) ?></h1>
        <?php if ($isAdmin): ?>
            <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Удалить', ['delete', 'id' => $model->id], ['class' => 'btn btn-danger', 'data-method' => 'post', 'data-confirm' => 'Удалить материал?']) ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if ($model->category): ?>
                <span class="badge bg-secondary"><?= Html::encode($model->category) ?></span>
            <?php endif; ?>
            <?php if ($model->test): ?>
                <p class="small text-muted">Связанный тест: <?= Html::a($model->test->title, ['/guru/test/view', 'id' => $model->test_id]) ?></p>
            <?php endif; ?>
            <div class="guru-material-content mt-3"><?= $model->content ?></div>
        </div>
    </div>

    <p class="mt-3">
        <?= Html::a('← К библиотеке', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
    </p>
</div>

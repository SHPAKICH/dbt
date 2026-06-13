<?php

/** @var yii\web\View $this */
/** @var app\models\TrainingMaterial[] $materials */
/** @var string[] $categories */
/** @var array $tests */
/** @var string $category */
/** @var int $testId */
/** @var bool $isAdmin */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Библиотека / теория';
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="guru-material-index">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?php if ($isAdmin): ?>
            <?= Html::a('Добавить материал', ['create'], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
    </div>

    <?php $form = ActiveForm::begin(['method' => 'get', 'options' => ['class' => 'mb-4']]); ?>
    <div class="row g-2">
        <div class="col-md-5">
            <select name="category" class="form-select">
                <option value="">Все категории</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= Html::encode($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= Html::encode($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <select name="test_id" class="form-select">
                <option value="0">Все тесты</option>
                <?php foreach ($tests as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= $testId === (int)$t['id'] ? 'selected' : '' ?>><?= Html::encode($t['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Показать</button>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <div class="list-group">
        <?php foreach ($materials as $m): ?>
            <a href="<?= \yii\helpers\Url::to(['view', 'id' => $m->id]) ?>" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1"><?= Html::encode($m->title) ?></h5>
                    <?php if ($m->category): ?>
                        <span class="badge bg-secondary"><?= Html::encode($m->category) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($m->test_id && $m->test): ?>
                    <p class="small text-muted mb-0">Связан с тестом: <?= Html::encode($m->test->title) ?></p>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($materials)): ?>
        <div class="alert alert-info">Нет материалов.</div>
    <?php endif; ?>
</div>

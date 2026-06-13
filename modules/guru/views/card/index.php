<?php

/** @var yii\web\View $this */
/** @var app\models\TechCard[] $cards */
/** @var string[] $categories */
/** @var string $search */
/** @var string $category */
/** @var bool $isAdmin */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use app\modules\guru\components\GuruImageHelper;

$this->title = 'Технологические карты';
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="guru-card-index">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?php if ($isAdmin): ?>
            <?= Html::a('Создать карточку', ['/learning/cards/create'], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
    </div>

    <?php $form = ActiveForm::begin(['method' => 'get', 'options' => ['class' => 'mb-4']]); ?>
    <div class="row g-2">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Поиск по названию, описанию, категории" value="<?= Html::encode($search) ?>">
        </div>
        <div class="col-md-4">
            <select name="category" class="form-select">
                <option value="">Все категории</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= Html::encode($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= Html::encode($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Найти</button>
            <?= Html::a('Сбросить', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <div class="row">
        <?php foreach ($cards as $card): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 <?= $card->is_active ? '' : 'border-secondary' ?>">
                    <?php if ($card->image): ?>
                        <img src="<?= Html::encode(GuruImageHelper::getUrl($card->image)) ?>" class="card-img-top" alt="" style="height: 140px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= Html::encode($card->name) ?></h5>
                        <?php if ($card->category): ?>
                            <span class="badge bg-secondary"><?= Html::encode($card->category) ?></span>
                        <?php endif; ?>
                        <?php if ($card->prep_time): ?>
                            <p class="small text-muted mb-1">Время: <?= (int)$card->prep_time ?> мин</p>
                        <?php endif; ?>
                        <?php if ($card->description): ?>
                            <p class="card-text small text-muted"><?= Html::encode(mb_substr(strip_tags($card->description), 0, 80)) ?>…</p>
                        <?php endif; ?>
                        <div class="d-flex gap-1">
                            <?= Html::a('Открыть', ['view', 'id' => $card->id], ['class' => 'btn btn-primary btn-sm']) ?>
                            <?php if ($isAdmin): ?>
                                <?= Html::a('Изменить', ['update', 'id' => $card->id], ['class' => 'btn btn-outline-warning btn-sm']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($cards)): ?>
        <div class="alert alert-info">Нет карточек по заданным критериям.</div>
    <?php endif; ?>
</div>

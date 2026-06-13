<?php

/** @var yii\web\View $this */
/** @var app\models\TechCard $card */
/** @var bool $isAdmin */

use yii\bootstrap5\Html;
use app\modules\guru\components\GuruImageHelper;

$this->title = $card->name;
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = ['label' => 'Технологические карты', 'url' => ['/guru/card/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="guru-card-view">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <h1><?= Html::encode($card->name) ?></h1>
        <?php if ($isAdmin): ?>
            <?= Html::a('Редактировать', ['update', 'id' => $card->id], ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Удалить', ['delete', 'id' => $card->id], ['class' => 'btn btn-danger', 'data-method' => 'post', 'data-confirm' => 'Удалить карточку?']) ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <?php if ($card->image): ?>
            <img src="<?= Html::encode(GuruImageHelper::getUrl($card->image)) ?>" class="card-img-top" alt="" style="max-height: 280px; object-fit: cover;">
        <?php endif; ?>
        <div class="card-body">
            <?php if ($card->category): ?>
                <span class="badge bg-secondary"><?= Html::encode($card->category) ?></span>
            <?php endif; ?>
            <?php if ($card->prep_time): ?>
                <p class="mb-1"><strong>Время приготовления:</strong> <?= (int)$card->prep_time ?> мин</p>
            <?php endif; ?>
            <?php if ($card->serving): ?>
                <p class="mb-1"><strong>Подача:</strong> <?= Html::encode($card->serving) ?></p>
            <?php endif; ?>

            <h5 class="mt-4">Ингредиенты</h5>
            <?php
            $sizeOrder = ['S' => 1, 'M' => 2, 'L' => 3];
            $sizeLabels = ['S' => 'Размер S', 'M' => 'Размер M', 'L' => 'Размер L'];

            $grouped = [];
            foreach ($card->ingredients as $ing) {
                $key = $ing->size_code ?: '_base';
                $grouped[$key][] = $ing;
            }
            $sizes = array_values(array_filter(array_keys($grouped), fn($k) => $k !== '_base'));
            usort($sizes, fn($a, $b) => ($sizeOrder[$a] ?? 99) - ($sizeOrder[$b] ?? 99));

            $ingredientMap = [];
            if (!empty($grouped['_base'])) {
                foreach ($grouped['_base'] as $ing) {
                    $name = $ing->ingredient_name;
                    if (!isset($ingredientMap[$name])) {
                        $ingredientMap[$name] = ['unit' => $ing->unit, 'qty' => []];
                    }
                    foreach ($sizes as $s) {
                        $ingredientMap[$name]['qty'][$s] = $ing->quantity;
                    }
                }
            }
            foreach ($sizes as $size) {
                if (empty($grouped[$size])) continue;
                foreach ($grouped[$size] as $ing) {
                    $name = $ing->ingredient_name;
                    if (!isset($ingredientMap[$name])) {
                        $ingredientMap[$name] = ['unit' => $ing->unit, 'qty' => []];
                    }
                    $ingredientMap[$name]['qty'][$size] = $ing->quantity;
                }
            }
            ?>
            <div class="table-responsive mb-2">
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th>Наименование</th>
                        <?php foreach ($sizes as $s): ?>
                            <th class="text-center"><?= Html::encode($sizeLabels[$s] ?? ('Размер ' . $s)) ?></th>
                        <?php endforeach; ?>
                        <th>Ед. измерения</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ingredientMap as $name => $data): ?>
                        <tr>
                            <td><?= Html::encode($name) ?></td>
                            <?php foreach ($sizes as $s): ?>
                                <td class="text-center"><?= isset($data['qty'][$s]) ? Yii::$app->formatter->asDecimal($data['qty'][$s], 2) : '—' ?></td>
                            <?php endforeach; ?>
                            <td><?= Html::encode($data['unit']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($card->description): ?>
                <h5 class="mt-4">Описание / технология</h5>
                <div class="guru-card-description"><?= $card->description ?></div>
            <?php endif; ?>
        </div>
    </div>

    <p class="mt-3">
        <?= Html::a('← К каталогу', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
    </p>
</div>

<?php

/** @var yii\web\View $this */
/** @var app\models\TechCard $model */
/** @var app\models\TechCardIngredient[] $ingredients */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use app\modules\guru\components\GuruImageHelper;

$this->title = $model->isNewRecord ? 'Новая технологическая карта' : 'Редактировать: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = ['label' => 'Технологические карты', 'url' => ['/guru/card/index']];
$this->params['breadcrumbs'][] = $model->isNewRecord ? 'Создать' : 'Редактировать';

$ingredients = $ingredients ?? [];
if (empty($ingredients)) {
    $ingredients = [new \app\models\TechCardIngredient()];
}
$units = ['г' => 'г', 'мл' => 'мл', 'шт' => 'шт', 'порц.' => 'порц.'];
$sizeOptions = [
    '' => 'Общий (для всех объёмов)',
    'S' => 'S',
    'M' => 'M',
    'L' => 'L',
];
$nextIngredientIndex = count($ingredients);
?>
<div class="guru-card-form">
    <?php $form = ActiveForm::begin(['id' => 'card-form', 'options' => ['enctype' => 'multipart/form-data']]); ?>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Основные данные</h5></div>
        <div class="card-body">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'category')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'prep_time')->textInput(['type' => 'number', 'min' => 0])->hint('Минуты') ?>
            <?= $form->field($model, 'serving')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'description')->textarea(['rows' => 5])->hint('Можно использовать HTML') ?>
            <?= $form->field($model, 'imageFile')->fileInput(['accept' => 'image/*'])->hint('JPG, PNG, GIF, WebP до 3 МБ') ?>
            <?php if ($model->image): ?>
                <p class="text-muted small">Текущее: <?= Html::img(GuruImageHelper::getUrl($model->image), ['style' => 'max-height:60px', 'alt' => '']) ?></p>
            <?php endif; ?>
            <?= $form->field($model, 'is_active')->checkbox() ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Ингредиенты</h5>
            <button type="button" class="btn btn-sm btn-success" id="add-ingredient">+ Добавить строку</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="ingredients-table">
                    <thead>
                        <tr>
                            <th>Наименование</th>
                            <th>Размер</th>
                            <th>Количество</th>
                            <th>Ед. изм.</th>
                            <th>Цена за ед.</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredients as $i => $ing): ?>
                            <tr class="ingredient-row">
                                <td><input type="text" name="ingredients[<?= $i ?>][ingredient_name]" class="form-control form-control-sm" value="<?= Html::encode($ing->ingredient_name) ?>" placeholder="Название"></td>
                                <td>
                                    <select name="ingredients[<?= $i ?>][size_code]" class="form-select form-select-sm">
                                        <?php foreach ($sizeOptions as $sVal => $sLabel): ?>
                                            <option value="<?= Html::encode($sVal) ?>" <?= ($ing->size_code ?? '') === $sVal ? 'selected' : '' ?>><?= Html::encode($sLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="ingredients[<?= $i ?>][quantity]" class="form-control form-control-sm" value="<?= Html::encode($ing->quantity) ?>" placeholder="0"></td>
                                <td>
                                    <select name="ingredients[<?= $i ?>][unit]" class="form-select form-select-sm">
                                        <?php foreach ($units as $uVal => $uLabel): ?>
                                            <option value="<?= Html::encode($uVal) ?>" <?= ($ing->unit ?? 'г') === $uVal ? 'selected' : '' ?>><?= Html::encode($uLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="ingredients[<?= $i ?>][price_per_unit]" class="form-control form-control-sm price-input" value="<?= $ing->price_per_unit !== null ? Html::encode($ing->price_per_unit) : '' ?>" placeholder="0.00"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-ingredient">×</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', $model->isNewRecord ? ['/guru/card/index'] : ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>

<?php
$unitsJson = json_encode($units);
$sizeOptionsJson = json_encode($sizeOptions);
$this->registerJs(<<<JS
(function() {
    var rowIndex = {$nextIngredientIndex};
    var units = {$unitsJson};
    var sizeOptions = {$sizeOptionsJson};
    var unitOptions = '';
    for (var k in units) { unitOptions += '<option value="'+k+'">'+units[k]+'</option>'; }
    var sizeSelectOptions = '';
    for (var s in sizeOptions) { sizeSelectOptions += '<option value="'+s+'">'+sizeOptions[s]+'</option>'; }

    document.getElementById('add-ingredient').onclick = function() {
        var tr = document.createElement('tr');
        tr.className = 'ingredient-row';
        tr.innerHTML = '<td><input type="text" name="ingredients['+rowIndex+'][ingredient_name]" class="form-control form-control-sm" placeholder="Название"></td>' +
            '<td><select name="ingredients['+rowIndex+'][size_code]" class="form-select form-select-sm">'+sizeSelectOptions+'</select></td>' +
            '<td><input type="text" name="ingredients['+rowIndex+'][quantity]" class="form-control form-control-sm" placeholder="0"></td>' +
            '<td><select name="ingredients['+rowIndex+'][unit]" class="form-select form-select-sm">'+unitOptions+'</select></td>' +
            '<td><input type="text" name="ingredients['+rowIndex+'][price_per_unit]" class="form-control form-control-sm price-input" placeholder="0.00"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger remove-ingredient">×</button></td>';
        document.querySelector('#ingredients-table tbody').appendChild(tr);
        rowIndex++;
        tr.querySelector('.remove-ingredient').onclick = function() { tr.remove(); };
    };
    document.querySelectorAll('.remove-ingredient').forEach(function(btn) {
        btn.onclick = function() { this.closest('tr').remove(); };
    });
})();
JS
);
?>

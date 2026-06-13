<?php

/** @var yii\web\View $this */
/** @var app\models\Location $location */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Создание точки';
$this->params['breadcrumbs'][] = ['label' => 'Админ-панель', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Точки', 'url' => ['locations']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-create-location">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <div class="col-md-8">
            <?php $form = ActiveForm::begin([
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'form-label'],
                    'inputOptions' => ['class' => 'form-control'],
                    'errorOptions' => ['class' => 'invalid-feedback'],
                ],
            ]); ?>

            <?= $form->field($location, 'name')->textInput() ?>
            <?= $form->field($location, 'address')->textarea(['rows' => 3]) ?>
            <?= $form->field($location, 'phone')->textInput() ?>
            <?= $form->field($location, 'is_active')->checkbox(['checked' => true]) ?>

            <div class="form-group mt-3">
                <?= Html::submitButton('Создать', ['class' => 'btn btn-success']) ?>
                <?= Html::a('Отмена', ['locations'], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>


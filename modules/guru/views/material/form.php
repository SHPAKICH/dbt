<?php

/** @var yii\web\View $this */
/** @var app\models\TrainingMaterial $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = $model->isNewRecord ? 'Новый материал' : 'Редактировать: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = ['label' => 'Библиотека', 'url' => ['/guru/material/index']];
$this->params['breadcrumbs'][] = $model->isNewRecord ? 'Создать' : 'Редактировать';
?>
<div class="guru-material-form">
    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'category')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'content')->textarea(['rows' => 12])->hint('Можно использовать HTML') ?>
    <?= $form->field($model, 'test_id')->dropDownList(
        \yii\helpers\ArrayHelper::map(\app\models\TrainingTest::find()->orderBy('title')->all(), 'id', 'title'),
        ['prompt' => '— Не привязан к тесту —']
    ) ?>
    <?= $form->field($model, 'sort_order')->textInput(['type' => 'number']) ?>
    <?= $form->field($model, 'is_active')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', $model->isNewRecord ? ['index'] : ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>

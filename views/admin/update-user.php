<?php

/** @var yii\web\View $this */
/** @var app\models\UserUpdateForm $model */
/** @var app\models\User $user */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use app\models\Location;

$this->title = 'Редактирование пользователя';
$this->params['breadcrumbs'][] = ['label' => 'Админ-панель', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['users']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-update-user">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <div class="col-12 col-md-8">
            <?php $form = ActiveForm::begin([
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'form-label'],
                    'inputOptions' => ['class' => 'form-control'],
                    'errorOptions' => ['class' => 'invalid-feedback'],
                ],
            ]); ?>

            <?= $form->field($model, 'first_name')->textInput() ?>
            <?= $form->field($model, 'last_name')->textInput() ?>
            <?= $form->field($model, 'email')->textInput(['type' => 'email']) ?>
            <?= $form->field($model, 'phone')->textInput() ?>
            <?= $form->field($model, 'telegram')->textInput(['placeholder' => '@username']) ?>
            <?= $form->field($model, 'birthday')->input('date') ?>
            <?= $form->field($model, 'certification_date')->input('date') ?>

            <?= $form->field($model, 'position')->dropDownList([
                'manager' => 'Управляющий',
                'location_manager' => 'Менеджер точки',
                'senior_teamaker' => 'Старший тимейкер',
                'teamaker' => 'Тимейкер',
                'trainee' => 'Стажер',
            ], ['prompt' => 'Выберите должность']) ?>

            <?= $form->field($model, 'location_id')->dropDownList(
                Location::find()->select(['name', 'id'])->indexBy('id')->column(),
                ['prompt' => 'Не закреплен']
            ) ?>

            <?= $form->field($model, 'is_active')->checkbox() ?>

            <hr>
            <h4>Смена пароля</h4>
            <p class="text-muted">Оставьте поля пустыми, если не хотите менять пароль</p>

            <?= $form->field($model, 'new_password')->passwordInput(['placeholder' => 'Новый пароль (минимум 6 символов)']) ?>
            <?= $form->field($model, 'confirm_password')->passwordInput(['placeholder' => 'Подтвердите новый пароль']) ?>

            <div class="form-group mt-3">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Отмена', ['users'], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
        <div class="col-12 col-md-4 mt-3 mt-md-0">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Информация</h5>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?= $user->id ?></p>
                    <p><strong>Создан:</strong> <?= Yii::$app->formatter->asDate($user->created_at, 'long') ?></p>
                    <p><strong>Обновлен:</strong> <?= Yii::$app->formatter->asDate($user->updated_at, 'long') ?></p>
                    <?php if ($user->isAdmin()): ?>
                        <p><span class="badge bg-danger">Администратор</span></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


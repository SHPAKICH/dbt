<?php

/** @var yii\web\View $this */
/** @var app\models\UserCreateForm $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use app\models\Location;

$this->title = 'Создание пользователя';
$this->params['breadcrumbs'][] = ['label' => 'Админ-панель', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['users']];
$this->params['breadcrumbs'][] = $this->title;

$currentUser = Yii::$app->user->identity;
$isManager = $currentUser->position === 'manager' && !$currentUser->isAdmin();
?>
<div class="admin-create-user">
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
            
            <?php
            $positions = [
                'manager' => 'Управляющий',
                'location_manager' => 'Менеджер точки',
                'senior_teamaker' => 'Старший тимейкер',
                'teamaker' => 'Тимейкер',
                'trainee' => 'Стажер',
            ];
            
            // Если управляющий (не админ), ограничиваем выбор должностей
            if ($isManager) {
                $positions = array_filter($positions, function($key) {
                    return in_array($key, ['location_manager', 'senior_teamaker', 'teamaker', 'trainee']);
                }, ARRAY_FILTER_USE_KEY);
            }
            ?>
            
            <?= $form->field($model, 'position')->dropDownList($positions, ['prompt' => 'Выберите должность']) ?>
            
            <?php if ($isManager): ?>
                <?php
                // Для управляющего показываем только его точки
                $locationIds = (new \yii\db\Query())
                    ->from('manager_locations')
                    ->where(['manager_id' => $currentUser->id])
                    ->select('location_id')
                    ->column();
                
                $locationsList = [];
                if (!empty($locationIds)) {
                    $locations = Location::find()
                        ->where(['id' => $locationIds])
                        ->all();
                    foreach ($locations as $location) {
                        $locationsList[$location->id] = $location->name;
                    }
                }
                ?>
                <?= $form->field($model, 'location_id')->dropDownList(
                    $locationsList,
                    ['prompt' => 'Выберите точку', 'required' => true]
                )->label('Точка <span class="text-danger">*</span>') ?>
            <?php else: ?>
                <?= $form->field($model, 'location_id')->dropDownList(
                    Location::find()->select(['name', 'id'])->indexBy('id')->column(),
                    ['prompt' => 'Не закреплен']
                ) ?>
            <?php endif; ?>

            <?= $form->field($model, 'is_active')->checkbox(['checked' => true]) ?>

            <hr>
            <h4>Пароль</h4>
            <p class="text-muted">Минимум 6 символов</p>

            <?= $form->field($model, 'password')->passwordInput(['placeholder' => 'Пароль (минимум 6 символов)']) ?>
            <?= $form->field($model, 'confirm_password')->passwordInput(['placeholder' => 'Подтвердите пароль']) ?>

            <?php if ($isManager): ?>
                <div class="alert alert-info">
                    <strong>Ограничение:</strong> Управляющий может создавать пользователей только до уровня "Менеджер точки".
                </div>
            <?php endif; ?>

            <div class="form-group mt-3">
                <?= Html::submitButton('Создать', ['class' => 'btn btn-success']) ?>
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
                    <p><strong>Создатель:</strong> <?= Html::encode($currentUser->getFullName()) ?></p>
                    <p><strong>Роль:</strong> 
                        <?php if ($currentUser->isAdmin()): ?>
                            <span class="badge bg-danger">Администратор</span>
                        <?php else: ?>
                            <span class="badge bg-primary">Управляющий</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>


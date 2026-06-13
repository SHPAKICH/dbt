<?php

/** @var yii\web\View $this */
/** @var app\models\User[] $users */
/** @var bool $isManager */
/** @var array $managerLocations */

use yii\bootstrap5\Html;
use yii\bootstrap5\Alert;
use app\models\Location;

$this->title = 'Управление пользователями';
$this->params['breadcrumbs'][] = ['label' => 'Админ-панель', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-users">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?php
        $currentUser = Yii::$app->user->identity;
        if ($currentUser->isAdmin() || $currentUser->position === 'manager'):
        ?>
            <?= Html::a('Создать пользователя', ['create-user'], ['class' => 'btn btn-success']) ?>
        <?php endif; ?>
    </div>

    <?php if ($isManager): ?>
        <?php
        $locations = Location::find()
            ->where(['id' => $managerLocations])
            ->all();
        ?>
        <?php if (!empty($locations)): ?>
            <div class="alert alert-info">
                <strong>Ваши точки:</strong> 
                <?= implode(', ', array_map(function($loc) {
                    return Html::encode($loc->name);
                }, $locations)) ?>
                <br>
                <small>Отображаются все пользователи, привязанные к вашим точкам.</small>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>Внимание:</strong> У вас нет закрепленных точек. Обратитесь к администратору.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <?= Alert::widget([
            'options' => ['class' => 'alert-success'],
            'body' => Yii::$app->session->getFlash('success'),
        ]) ?>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <?= Alert::widget([
            'options' => ['class' => 'alert-danger'],
            'body' => Yii::$app->session->getFlash('error'),
        ]) ?>
    <?php endif; ?>

    <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Должность</th>
                    <th>Точка</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user->id ?></td>
                        <td><?= Html::encode($user->getFullName()) ?></td>
                        <td><?= Html::encode($user->email) ?></td>
                        <td><?= Html::encode($user->phone) ?></td>
                        <td>
                            <?= Html::encode($user->getPositionLabel()) ?>
                            <?php if ($user->isAdmin()): ?>
                                <span class="badge bg-danger">Админ</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $user->location ? Html::encode($user->location->name) : '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <?php if ($user->is_active): ?>
                                <span class="badge bg-success">Активен</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Неактивен</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= Html::a('Редактировать', ['update-user', 'id' => $user->id], ['class' => 'btn btn-sm btn-primary']) ?>
                            <?php if ($user->id != Yii::$app->user->id && !$user->isAdmin()): ?>
                                <?= Html::a('Удалить', ['delete-user', 'id' => $user->id], [
                                    'class' => 'btn btn-sm btn-danger',
                                    'data' => [
                                        'confirm' => 'Вы уверены, что хотите удалить этого пользователя?',
                                        'method' => 'post',
                                    ],
                                ]) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<?php

/** @var yii\web\View $this */
/** @var app\models\Location[] $locations */

use yii\bootstrap5\Html;
use yii\bootstrap5\Alert;

$this->title = 'Управление точками';
$this->params['breadcrumbs'][] = ['label' => 'Админ-панель', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-locations">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Создать точку', ['create-location'], ['class' => 'btn btn-success']) ?>
    </div>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <?= Alert::widget([
            'options' => ['class' => 'alert-success'],
            'body' => Yii::$app->session->getFlash('success'),
        ]) ?>
    <?php endif; ?>

    <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Адрес</th>
                    <th>Телефон</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($locations as $location): ?>
                    <tr>
                        <td><?= $location->id ?></td>
                        <td><?= Html::encode($location->name) ?></td>
                        <td><?= Html::encode($location->address) ?></td>
                        <td><?= Html::encode($location->phone) ?></td>
                        <td>
                            <?php if ($location->is_active): ?>
                                <span class="badge bg-success">Активна</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Неактивна</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= Html::a('Редактировать', ['update-location', 'id' => $location->id], ['class' => 'btn btn-sm btn-primary']) ?>
                            <?= Html::a('Удалить', ['delete-location', 'id' => $location->id], [
                                'class' => 'btn btn-sm btn-danger',
                                'data' => [
                                    'confirm' => 'Вы уверены, что хотите удалить эту точку?',
                                    'method' => 'post',
                                ],
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


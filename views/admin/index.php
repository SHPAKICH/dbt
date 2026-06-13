<?php

/** @var yii\web\View $this */
/** @var integer $usersCount */
/** @var integer $locationsCount */
/** @var integer $activeUsersCount */
/** @var array $recentBackups */
/** @var string $backupDirectory */
/** @var integer $backupRetentionDays */
/** @var string $backupScheduleLabel */

use yii\bootstrap5\Html;

$this->title = 'Админ-панель';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mt-4">
        <div class="col-12 col-md-4 mb-3 mb-md-0">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="card-title"><?= $usersCount ?></h2>
                    <p class="card-text">Всего пользователей</p>
                    <?= Html::a('Управление пользователями', ['users'], ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-3 mb-md-0">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="card-title"><?= $activeUsersCount ?></h2>
                    <p class="card-text">Активных пользователей</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="card-title"><?= $locationsCount ?></h2>
                    <p class="card-text">Точек (кафе)</p>
                    <?= Html::a('Управление точками', ['locations'], ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h3 class="h5 mb-2">Резервное копирование БД</h3>
                            <p class="mb-1"><strong>Расписание:</strong> <?= Html::encode($backupScheduleLabel) ?></p>
                            <p class="mb-1"><strong>Хранение:</strong> <?= Html::encode((string) $backupRetentionDays) ?> дн.</p>
                            <p class="mb-0"><strong>Каталог:</strong> <code><?= Html::encode($backupDirectory) ?></code></p>
                        </div>
                        <div>
                            <?= Html::beginForm(['backup-database'], 'post') ?>
                                <?= Html::submitButton('Скачать свежий дамп', ['class' => 'btn btn-success']) ?>
                            <?= Html::endForm() ?>
                        </div>
                    </div>

                    <hr>

                    <h4 class="h6">Последние резервные копии</h4>
                    <?php if (empty($recentBackups)): ?>
                        <p class="text-muted mb-0">Резервные копии ещё не создавались.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Файл</th>
                                        <th>Дата</th>
                                        <th>Размер</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBackups as $backup): ?>
                                        <tr>
                                            <td><code><?= Html::encode($backup['filename']) ?></code></td>
                                            <td><?= Html::encode($backup['modifiedAt'] ?? '-') ?></td>
                                            <td><?= Html::encode(Yii::$app->formatter->asShortSize($backup['size'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<?php

use app\models\User;
use yii\bootstrap5\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var Location[] $locations */
/** @var int|null $locationId */
/** @var User[] $employees */
/** @var array $upcomingBirthdays */
/** @var bool $isManager */

$this->title = 'Календарь сотрудников';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="team-calendar-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (count($locations) > 1): ?>
        <div class="mb-3">
            <form method="get" action="<?= Url::to(['index']) ?>" class="d-inline">
                <label class="me-2">Точка:</label>
                <select name="location_id" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc->id ?>" <?= $locationId === (int)$loc->id ? 'selected' : '' ?>><?= Html::encode($loc->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($isManager && !empty($upcomingBirthdays)): ?>
        <div class="card border-warning mb-4">
            <div class="card-header bg-warning bg-opacity-25">
                <h5 class="mb-0">🎁 Напоминание: дни рождения в ближайшие 30 дней</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <?php foreach ($upcomingBirthdays as $item): ?>
                        <li>
                            <strong><?= Html::encode($item['user']->getFullName()) ?></strong>
                            — <?= Yii::$app->formatter->asDate($item['date'], 'd MMMM') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Сотрудники</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($employees)): ?>
                <p class="p-3 text-muted mb-0">Нет сотрудников по выбранной точке.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Имя</th>
                                <th>Должность</th>
                                <th>📞 Телефон</th>
                                <th>Telegram</th>
                                <th>🎂 День рождения</th>
                                <th>Стаж (лет)</th>
                                <th>Дата аттестации</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><?= Html::encode($emp->getFullName()) ?></td>
                                    <td><?= Html::encode($emp->getPositionLabel()) ?></td>
                                    <td><?= $emp->phone ? Html::a(Html::encode($emp->phone), 'tel:' . preg_replace('/\D/', '', $emp->phone)) : '—' ?></td>
                                    <td><?= $emp->telegram ? Html::a(Html::encode($emp->telegram), 'https://t.me/' . ltrim($emp->telegram, '@'), ['target' => '_blank']) : '—' ?></td>
                                    <td><?= $emp->birthday ? Yii::$app->formatter->asDate($emp->birthday, 'dd.MM.yyyy') : '—' ?></td>
                                    <td><?= $emp->created_at ? (string)$emp->getTenureYears() : '—' ?></td>
                                    <td><?= $emp->certification_date ? Yii::$app->formatter->asDate($emp->certification_date, 'dd.MM.yyyy') : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

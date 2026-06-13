<?php

use app\models\Location;
use app\models\User;
use app\models\UserAvailabilityByDate;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var int $year */
/** @var int $month */
/** @var int $lastDay */
/** @var array $daysOfMonth */
/** @var array $datesOfMonth */
/** @var array $weekdayNamesShort */
/** @var array $monthNames */
/** @var Location[] $locations */
/** @var int|null $locationId */
/** @var array $sections [['location' => Location, 'employees' => User[], 'byUserByDate' => [user_id => [date => UserAvailabilityByDate]]]] */

$this->title = 'Карта возможностей';
$this->params['breadcrumbs'][] = $this->title;

function formatAvailTime(?string $timeStart, ?string $timeEnd): string {
    if (!$timeStart || !$timeEnd) return '';
    $sH = (int)substr($timeStart, 0, 2);
    $sM = substr($timeStart, 3, 2);
    $eH = (int)substr($timeEnd, 0, 2);
    $eM = substr($timeEnd, 3, 2);
    $s = $sH . ($sM === '30' ? '.5' : '');
    $e = $eH . ($eM === '30' ? '.5' : '');
    return $s . '-' . $e;
}
?>
<div class="availability-team">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= Html::encode($this->title) ?> — по точке</h1>
        <?= Html::a('Моя карта возможностей', ['index', 'mode' => 'my', 'month' => $month, 'year' => $year], ['class' => 'btn btn-outline-primary btn-sm']) ?>
    </div>

    <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index'], 'options' => ['class' => 'mb-4']]); ?>
        <div class="row g-2 align-items-end">
            <?php if (count($locations) > 1): ?>
                <div class="col-auto">
                    <label class="form-label">Точка</label>
                    <select name="location_id" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="">— все точки —</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc->id ?>" <?= $locationId === (int)$loc->id ? 'selected' : '' ?>><?= Html::encode($loc->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-auto">
                <label class="form-label">Месяц</label>
                <select name="month" class="form-select form-select-sm" style="width: auto;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= (int)$month === $m ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label">Год</label>
                <input type="number" name="year" value="<?= (int)$year ?>" min="2020" max="2030" class="form-control form-control-sm" style="width: 90px;">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Показать</button>
            </div>
        </div>
    <?php ActiveForm::end(); ?>

    <p class="text-muted small">
        Отображается карта возможностей сотрудников выбранной точки. Редактировать свою карту можно в разделе «Моя карта возможностей».
    </p>

    <?php foreach ($sections as $section): ?>
        <?php $loc = $section['location']; $employees = $section['employees']; $byUserByDate = $section['byUserByDate']; ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><?= Html::encode($loc->name) ?></h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($employees)): ?>
                    <p class="p-3 text-muted mb-0">Нет сотрудников в этой точке.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0 availability-team-table">
                            <thead>
                                <tr>
                                    <th class="availability-team-col-name">Сотрудник</th>
                                    <th class="availability-team-col-role">Должность</th>
                                    <?php for ($d = 1; $d <= $lastDay; $d++): ?>
                                        <th class="availability-team-day text-center" title="<?= $weekdayNamesShort[$daysOfMonth[$d]] ?? '' ?>">
                                            <?= $d ?>
                                            <span class="d-block small opacity-75"><?= $weekdayNamesShort[$daysOfMonth[$d]] ?? '' ?></span>
                                        </th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $emp): ?>
                                    <tr>
                                        <td class="availability-team-col-name"><?= Html::encode($emp->getFullName()) ?></td>
                                        <td class="availability-team-col-role"><?= Html::encode($emp->getPositionLabel()) ?></td>
                                        <?php for ($d = 1; $d <= $lastDay; $d++): ?>
                                            <?php
                                            $dateStr = $datesOfMonth[$d] ?? '';
                                            $rec = $byUserByDate[$emp->id][$dateStr] ?? null;
                                            $text = formatAvailTime($rec ? $rec->time_start : null, $rec ? $rec->time_end : null);
                                            ?>
                                            <td class="availability-team-cell text-center"><?= Html::encode($text) ?></td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.availability-team-table th, .availability-team-table td { padding: 6px 8px; border: 1px solid #D4B8BE; font-size: 0.9em; }
.availability-team-col-name { min-width: 140px; }
.availability-team-col-role { min-width: 120px; }
.availability-team-day { min-width: 42px; }
.availability-team-cell { min-width: 42px; color: #333; }
</style>

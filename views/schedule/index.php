<?php

use app\models\Location;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var Location[] $locations */
/** @var int $locationId */
/** @var string $period */
/** @var string $date */
/** @var string $from */
/** @var string $to */
/** @var array|null $grid */

$this->title = 'График смен';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss(<<<CSS
.schedule-table-wrapper {
    overflow-x: auto;
}
.schedule-table {
    min-width: 900px;
    border-collapse: separate;
    border-spacing: 0;
}
.schedule-table th,
.schedule-table td {
    text-align: center;
    vertical-align: middle;
    padding: 8px 10px;
    border: 1px solid #D4B8BE;
    white-space: nowrap;
    color: #333;
}
.schedule-table thead th {
    position: sticky;
    top: 0;
    background-color: var(--color-primary);
    color: #fff;
    border-color: var(--color-primary-dark);
    font-weight: 600;
    z-index: 2;
}
.schedule-table tbody th {
    position: sticky;
    left: 0;
    background-color: #EDE0E3;
    color: #333;
    border-color: #D4B8BE;
    font-weight: 600;
    z-index: 1;
}
.schedule-table tbody tr:nth-child(odd) td {
    background-color: #fff;
}
.schedule-table tbody tr:nth-child(even) td {
    background-color: #F0E4E8;
}
.schedule-table tbody tr:hover td {
    background-color: #E8D3D6;
}
.schedule-cell {
    cursor: pointer;
    min-width: 70px;
}
.schedule-cell.editing {
    background-color: #FDFBF8 !important;
    box-shadow: inset 0 0 0 2px var(--color-primary);
}
.schedule-cell.overtime {
    box-shadow: inset 0 0 0 2px #dc3545;
}
.schedule-cell.night {
    box-shadow: inset 0 0 0 2px #0d6efd;
}
.schedule-cell.dayoff {
    color: #6c757d;
    font-style: italic;
}
.schedule-cell.avail-hint {
    border-bottom: 2px dashed var(--color-primary);
}
.schedule-table td.schedule-total {
    font-weight: bold;
    background-color: var(--color-primary-dark) !important;
    color: #fff !important;
    border-color: var(--color-accent);
}
.schedule-table tr.schedule-total td {
    background-color: var(--color-primary-dark) !important;
    color: #fff !important;
    border-color: var(--color-accent);
    font-weight: 600;
}
.schedule-col-employee {
    min-width: 140px;
    max-width: 200px;
    text-align: left !important;
    padding-left: 10px !important;
}
.schedule-employee-name { display: block; font-weight: 600; }
.schedule-employee-meta { font-size: 0.85em; margin-top: 2px; }
@media (max-width: 767px) {
    .schedule-table th, .schedule-table td { padding: 6px 4px; font-size: 12px; }
    .schedule-col-employee { min-width: 100px; max-width: 130px; }
    .schedule-employee-meta { display: none; }
}
CSS);

$updateUrl = Url::to(['schedule/update-cell']);

$this->registerJs(<<<JS
(function() {
    const table = document.getElementById('scheduleTable');
    if (!table) {
        return;
    }

    let editingCell = null;
    let originalValue = '';

    function startEdit(cell) {
        if (editingCell === cell) {
            return;
        }
        if (editingCell) {
            cancelEdit();
        }
        editingCell = cell;
        originalValue = cell.innerText.trim();
        const input = document.createElement('input');
        input.type = 'text';
        input.value = originalValue === '0' ? '' : originalValue;
        input.className = 'form-control form-control-sm';
        input.style.minWidth = '70px';
        input.placeholder = 'например 10-22, 0 = выходной';
        cell.classList.add('editing');
        cell.innerHTML = '';
        cell.appendChild(input);
        input.focus();
        input.select();

        input.addEventListener('blur', function() {
            saveEdit(input.value);
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveEdit(input.value);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelEdit();
            }
        });
    }

    function cancelEdit() {
        if (!editingCell) {
            return;
        }
        editingCell.classList.remove('editing');
        editingCell.innerText = originalValue;
        editingCell = null;
        originalValue = '';
    }

    function saveEdit(value) {
        if (!editingCell) {
            return;
        }
        const cell = editingCell;
        const userId = cell.getAttribute('data-user-id');
        const locationId = cell.getAttribute('data-location-id');
        const date = cell.getAttribute('data-date');

        const raw = value.trim();

        // Локальная валидация формата времени:
        // '' или '0' -> выходной, не требуем формат
        if (raw !== '' && raw !== '0') {
            // Форматы: 10-22, 9-21.5, 9.5-21.5 (шаг 0.5 часа)
            const match = raw.match(/^([01]?\\d|2[0-3])(\\.5)?\\s*-\\s*([01]?\\d|2[0-3])(\\.5)?$/);
            if (!match) {
                alert('Неверный формат времени. Используйте, например, "10-22" или "9-21.5" или "0" для выходного.');
                cancelEdit();
                return;
            }
            const startHour = parseInt(match[1], 10) + (match[2] ? 0.5 : 0);
            const endHour = parseInt(match[3], 10) + (match[4] ? 0.5 : 0);
            if (startHour >= endHour) {
                alert('Время начала должно быть меньше времени окончания.');
                cancelEdit();
                return;
            }
        }

        const normalized = raw;

        fetch('{$updateUrl}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-CSRF-Token': yii.getCsrfToken()
            },
            body: new URLSearchParams({
                user_id: userId,
                location_id: locationId,
                date: date,
                value: normalized
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.error || 'Ошибка сохранения');
                cancelEdit();
                return;
            }

            cell.classList.remove('editing', 'overtime', 'night', 'dayoff');
            cell.innerText = data.cell.text;
            cell.setAttribute('data-hours', data.cell.hours);

            if (data.cell.isOvertime) {
                cell.classList.add('overtime');
            }
            if (data.cell.isNight) {
                cell.classList.add('night');
            }
            if (data.cell.isDayOff) {
                cell.classList.add('dayoff');
            }

            const rowTotalEl = document.querySelector('td[data-row-total-for=\"' + userId + '\"]');
            if (rowTotalEl) {
                rowTotalEl.innerText = data.totals.userHours.toFixed(2);
            }
            const colTotalEl = document.querySelector('td[data-col-total-for=\"' + date + '\"]');
            if (colTotalEl) {
                colTotalEl.innerText = data.totals.dateHours.toFixed(2);
            }

            editingCell = null;
        })
        .catch(() => {
            alert('Ошибка сети при сохранении');
            cancelEdit();
        });
    }

    table.addEventListener('click', function(e) {
        const cell = e.target.closest('.schedule-cell');
        if (!cell) {
            return;
        }
        startEdit(cell);
    });

    // Инициализация тултипов Bootstrap для ячеек с картой возможностей
    if (window.bootstrap) {
        const tooltipTriggerList = [].slice.call(table.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }
})();
JS);
?>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Точка</label>
                <?php $form = ActiveForm::begin([
                    'method' => 'get',
                    'action' => ['index'],
                    'options' => ['class' => 'row g-2'],
                ]); ?>
                <div class="col-12">
                    <select name="location_id" class="form-select">
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc->id ?>" <?= $loc->id == $locationId ? 'selected' : '' ?>>
                                <?= Html::encode($loc->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Период</label>
                <select name="period" class="form-select">
                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Неделя</option>
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Месяц</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Дата (любая внутри периода)</label>
                <input type="date" name="date" value="<?= Html::encode($date) ?>" class="form-control">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Показать</button>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php if ($grid && $grid['users']): ?>
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <?= Html::a(
            '✨ Автозаполнить по карте возможностей',
            ['generate', 'location_id' => $locationId, 'from' => $from, 'to' => $to, 'period' => $period, 'date' => $date],
            ['class' => 'btn btn-outline-primary']
        ) ?>
        <span class="small text-muted">Период на экране: <?= Yii::$app->formatter->asDate($from, 'dd.MM.yyyy') ?> — <?= Yii::$app->formatter->asDate($to, 'dd.MM.yyyy') ?></span>
        <span class="small text-muted">Пустые ячейки заполнятся по карте возможностей; уже проставленные смены не изменятся.</span>
    </div>
<?php endif; ?>

<?php if ($grid && $grid['users']): ?>
    <div class="schedule-table-wrapper">
        <table class="schedule-table table table-dark table-hover" id="scheduleTable">
            <thead>
            <tr>
                <th class="schedule-col-employee">Сотрудник</th>
                <?php foreach ($grid['dates'] as $d): ?>
                    <?php
                    $ts = strtotime($d);
                    $dow = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'][(int)date('w', $ts)];
                    ?>
                    <th><?= $dow ?><br><?= date('d.m', $ts) ?></th>
                <?php endforeach; ?>
                <th>Часы</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($grid['users'] as $userId => $user): ?>
                <tr>
                    <th class="schedule-col-employee" data-bs-toggle="tooltip" title="<?= Html::encode(trim(
                        ($user->phone ? '📞 ' . $user->phone : '') .
                        ($user->birthday ? ' | 🎂 ' . Yii::$app->formatter->asDate($user->birthday, 'dd.MM') : '')
                    )) ?>">
                        <span class="schedule-employee-name"><?= Html::encode($user->getFullName()) ?></span>
                        <?php if ($user->phone || $user->birthday): ?>
                            <span class="schedule-employee-meta d-block small text-muted">
                                <?php if ($user->phone): ?>📞 <?= Html::encode($user->phone) ?><?php endif; ?>
                                <?php if ($user->phone && $user->birthday): ?> · <?php endif; ?>
                                <?php if ($user->birthday): ?>🎂 <?= Yii::$app->formatter->asDate($user->birthday, 'dd.MM') ?><?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </th>
                    <?php foreach ($grid['dates'] as $d): ?>
                        <?php
                        $shift = $grid['shifts'][$userId][$d] ?? null;
                        $text = '';
                        $classes = ['schedule-cell'];
                        $hours = 0;
                        $isNight = false;
                        $isOvertime = false;
                        $isDayOff = false;
                        if ($shift) {
                            $hours = (float)$shift->hours;
                            $isNight = (bool)$shift->is_night;
                            $isOvertime = (bool)$shift->is_overtime;
                            $isDayOff = (bool)$shift->is_day_off;
                            if ($shift->is_day_off || !$shift->time_start || !$shift->time_end) {
                                $text = '0';
                            } else {
                                $startHour = substr($shift->time_start, 0, 2);
                                $startMin = substr($shift->time_start, 3, 2);
                                $endHour = substr($shift->time_end, 0, 2);
                                $endMin = substr($shift->time_end, 3, 2);

                                $startText = (int)$startHour . ($startMin === '30' ? '.5' : '');
                                $endText = (int)$endHour . ($endMin === '30' ? '.5' : '');

                                $text = $startText . '-' . $endText;
                            }
                        } else {
                            $text = '';
                        }
                        // Карта возможностей: по конкретной дате
                        $availability = $grid['availability'][$userId][$d] ?? null;
                        $hasAvailability = $availability !== null;
                        $availText = '';
                        if ($availability && $availability->time_start && $availability->time_end) {
                            $aStartHour = substr($availability->time_start, 0, 2);
                            $aStartMin = substr($availability->time_start, 3, 2);
                            $aEndHour = substr($availability->time_end, 0, 2);
                            $aEndMin = substr($availability->time_end, 3, 2);

                            $aStartText = (int)$aStartHour . ($aStartMin === '30' ? '.5' : '');
                            $aEndText = (int)$aEndHour . ($aEndMin === '30' ? '.5' : '');

                            $availText = $aStartText . '-' . $aEndText;
                        }

                        if ($hasAvailability) {
                            $classes[] = 'avail-hint';
                        }

                        if ($isNight) {
                            $classes[] = 'night';
                        }
                        if ($isOvertime) {
                            $classes[] = 'overtime';
                        }
                        if ($isDayOff) {
                            $classes[] = 'dayoff';
                        }
                        ?>
                        <td class="<?= implode(' ', $classes) ?>"
                            data-user-id="<?= $userId ?>"
                            data-location-id="<?= $locationId ?>"
                            data-date="<?= $d ?>"
                            data-hours="<?= $hours ?>"
                            <?php if ($availText): ?>
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Желаемый график: <?= Html::encode($availText) ?>"
                            <?php endif; ?>>
                            <?= Html::encode($text) ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="schedule-total" data-row-total-for="<?= $userId ?>">
                        <?= number_format($grid['totalsByUser'][$userId] ?? 0, 2, '.', '') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <!-- Итоговая строка по датам -->
            <tr class="schedule-total">
                <td>Итого по дню</td>
                <?php foreach ($grid['dates'] as $d): ?>
                    <td data-col-total-for="<?= $d ?>">
                        <?= number_format($grid['totalsByDate'][$d] ?? 0, 2, '.', '') ?>
                    </td>
                <?php endforeach; ?>
                <td></td>
            </tr>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        Для выбранных параметров нет данных графика.
    </div>
<?php endif; ?>



<?php

use app\models\UserAvailabilityByDate;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var UserAvailabilityByDate[] $records по дате (ключ — Y-m-d) */
/** @var int $year */
/** @var int $month */
/** @var int $lastDay */
/** @var array $daysOfMonth day number => weekday (1-7) */
/** @var array $datesOfMonth day number => Y-m-d */
/** @var array $weekdayNamesShort */
/** @var bool $canSeeTeamView */

$this->title = 'Карта возможностей';
$this->params['breadcrumbs'][] = $this->title;

$monthNames = [
    1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель', 5 => 'Май', 6 => 'Июнь',
    7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
];

function formatAvailTime(?string $timeStart, ?string $timeEnd): string {
    if (!$timeStart || !$timeEnd) {
        return '';
    }
    $sH = (int)substr($timeStart, 0, 2);
    $sM = substr($timeStart, 3, 2);
    $eH = (int)substr($timeEnd, 0, 2);
    $eM = substr($timeEnd, 3, 2);
    $s = $sH . ($sM === '30' ? '.5' : '');
    $e = $eH . ($eM === '30' ? '.5' : '');
    return $s . '-' . $e;
}

$updateUrl = Url::to(['availability/update-cell']);

$this->registerCss(<<<CSS
.availability-table-wrapper {
    overflow-x: auto;
}
.availability-table {
    min-width: 600px;
    border-collapse: separate;
    border-spacing: 0;
}
.availability-table th,
.availability-table td {
    text-align: center;
    vertical-align: middle;
    padding: 8px 10px;
    border: 1px solid #D4B8BE;
    white-space: nowrap;
    color: #333;
}
.availability-table thead th {
    position: sticky;
    top: 0;
    background-color: var(--color-primary);
    color: #fff;
    border-color: var(--color-primary-dark);
    font-weight: 600;
    z-index: 2;
}
.availability-table tbody th {
    background-color: #EDE0E3;
    color: #333;
    font-weight: 600;
}
.availability-table tbody tr:nth-child(odd) td {
    background-color: #fff;
}
.availability-table tbody tr:nth-child(even) td {
    background-color: #F0E4E8;
}
.availability-table tbody tr:hover td {
    background-color: #E8D3D6;
}
.availability-cell {
    cursor: pointer;
    min-width: 44px;
}
.availability-table th.availability-day-num { font-size: 0.9em; }
.availability-table th.availability-dow { font-size: 0.75em; opacity: 0.9; font-weight: 500; }
.availability-cell.editing {
    background-color: #FDFBF8 !important;
    box-shadow: inset 0 0 0 2px var(--color-primary);
}
CSS);

$this->registerJs(<<<JS
(function() {
    const table = document.getElementById('availabilityTable');
    if (!table) return;

    let editingCell = null;
    let originalValue = '';

    function startEdit(cell) {
        if (editingCell === cell) return;
        if (editingCell) cancelEdit();

        editingCell = cell;
        originalValue = cell.innerText.trim();
        const input = document.createElement('input');
        input.type = 'text';
        input.value = originalValue;
        input.className = 'form-control form-control-sm';
        input.style.minWidth = '80px';
        input.placeholder = 'напр. 9-21.5, 0=нет';

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
        if (!editingCell) return;
        editingCell.classList.remove('editing');
        editingCell.innerText = originalValue;
        editingCell = null;
        originalValue = '';
    }

    function saveEdit(value) {
        if (!editingCell) return;
        const cell = editingCell;
        const date = cell.getAttribute('data-date');
        const raw = value.trim();

        // '' или '0' -> очищаем предпочтение
        if (raw !== '' && raw !== '0') {
            // Формат как в графике: H-H, H-H.5, H.5-H.5
            const match = raw.match(/^([01]?\\d|2[0-3])(\\.5)?\\s*-\\s*([01]?\\d|2[0-3])(\\.5)?$/);
            if (!match) {
                alert('Неверный формат. Используйте, например, "9-21" или "9.5-21.5" или "0".');
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
                cell.classList.remove('editing');
                cell.innerText = data.cell.text || '';
                editingCell = null;
            })
            .catch(() => {
                alert('Ошибка сети при сохранении');
                cancelEdit();
            });
    }

    table.addEventListener('click', function(e) {
        const cell = e.target.closest('.availability-cell');
        if (!cell) return;
        startEdit(cell);
    });
})();
JS);
?>

<div class="card mb-3">
    <div class="card-body">
        <?php if (!empty($canSeeTeamView)): ?>
            <p class="mb-3">
                <?= \yii\bootstrap5\Html::a('Карта возможностей сотрудников точки', ['index', 'month' => $month, 'year' => $year], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            </p>
        <?php endif; ?>
        <p class="text-muted mb-3">
            Укажите желаемые часы работы по числам месяца. Каждая дата хранится отдельно — можно задать одну неделю иначе, чем другую (например, две недели вперёд с разным расписанием).
            Формат: <code>9-21</code>, <code>9-21.5</code>, <code>9.5-21.5</code>. <code>0</code> или пусто — не готов выходить в этот день.
        </p>

        <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index'], 'options' => ['class' => 'mb-3']]); ?>
        <div class="row g-2 align-items-end">
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

        <div class="availability-table-wrapper">
            <table class="availability-table table table-dark table-hover" id="availabilityTable">
                <thead>
                <tr>
                    <th class="availability-day-num">День</th>
                    <?php for ($d = 1; $d <= $lastDay; $d++): ?>
                        <th class="availability-day-num" title="<?= $weekdayNamesShort[$daysOfMonth[$d]] ?? '' ?>">
                            <?= $d ?>
                            <span class="availability-dow d-block"><?= $weekdayNamesShort[$daysOfMonth[$d]] ?? '' ?></span>
                        </th>
                    <?php endfor; ?>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th>Желаемый график</th>
                    <?php for ($d = 1; $d <= $lastDay; $d++): ?>
                        <?php $dateStr = $datesOfMonth[$d] ?? ''; $rec = $records[$dateStr] ?? null; ?>
                        <td class="availability-cell"
                            data-date="<?= Html::encode($dateStr) ?>"
                            title="<?= $d ?> <?= $monthNames[$month] ?? '' ?> (<?= $weekdayNamesShort[$daysOfMonth[$d]] ?? '' ?>)">
                            <?= Html::encode(formatAvailTime($rec ? $rec->time_start : null, $rec ? $rec->time_end : null)) ?>
                        </td>
                    <?php endfor; ?>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>




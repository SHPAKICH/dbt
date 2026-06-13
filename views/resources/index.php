<?php

use app\models\ResourceMatrix;
use app\models\ResourceRow;
use app\models\ResourceColumn;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var ResourceMatrix[] $matrices */
/** @var ResourceMatrix $matrix */
/** @var ResourceRow[] $rows */
/** @var ResourceColumn[] $columns */
/** @var array $cellMap */

$this->title = 'Таблица ресурсов';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss(<<<CSS
.resources-table-wrapper {
    overflow-x: auto;
}
.resources-table {
    min-width: 600px;
    border-collapse: separate;
    border-spacing: 0;
}
.resources-table th,
.resources-table td {
    border: 1px solid #D4B8BE;
    padding: 8px 10px;
    vertical-align: middle;
    color: #333;
}
.resources-table thead th {
    position: sticky;
    top: 0;
    background-color: var(--color-primary);
    color: #fff;
    border-color: var(--color-primary-dark);
    font-weight: 600;
    z-index: 2;
    text-align: center;
    white-space: nowrap;
}
.resources-table tbody th {
    position: sticky;
    left: 0;
    background-color: #EDE0E3;
    color: #333;
    border-color: #D4B8BE;
    font-weight: 600;
    z-index: 1;
    white-space: nowrap;
}
.resources-table tbody tr:nth-child(odd) td {
    background-color: #fff;
}
.resources-table tbody tr:nth-child(even) td {
    background-color: #F0E4E8;
}
.resources-table tbody tr:hover td {
    background-color: #E8D3D6;
}
.resource-cell {
    cursor: text;
    min-width: 120px;
}
.resource-cell.editing {
    background-color: #FDFBF8 !important;
    box-shadow: inset 0 0 0 2px var(--color-primary);
}
CSS);

$this->registerJs(<<<JS
(function() {
    const table = document.getElementById('resourcesTable');
    if (!table) return;

    let editingCell = null;
    let originalValue = '';

    function startEdit(cell) {
        if (editingCell === cell) return;
        if (editingCell) {
            cancelEdit();
        }
        editingCell = cell;
        originalValue = cell.innerText.trim();
        const textarea = document.createElement('textarea');
        textarea.className = 'form-control form-control-sm';
        textarea.style.minWidth = '120px';
        textarea.style.minHeight = '32px';
        textarea.value = originalValue;
        cell.classList.add('editing');
        cell.innerHTML = '';
        cell.appendChild(textarea);
        textarea.focus();

        textarea.addEventListener('blur', function() {
            saveEdit(textarea.value);
        });

        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                saveEdit(textarea.value);
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
        const rowId = cell.getAttribute('data-row-id');
        const columnId = cell.getAttribute('data-column-id');
        const version = cell.getAttribute('data-version') || '0';

        fetch('update-cell', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-CSRF-Token': yii.getCsrfToken()
            },
            body: new URLSearchParams({
                row_id: rowId,
                column_id: columnId,
                value: value,
                version: version
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                if (data.conflict) {
                    const userChoice = confirm((data.message || 'Конфликт изменений.') + "\\n\\nТекущее значение: " + (data.currentValue || '') + "\\nВаше значение: " + value + "\\n\\nПерезаписать текущее значение?");
                    if (userChoice) {
                        // отправляем ещё раз, но уже с новой версией
                        cell.setAttribute('data-version', data.currentVersion);
                        saveEdit(value);
                        return;
                    } else {
                        cell.innerText = data.currentValue || '';
                        cell.setAttribute('data-version', data.currentVersion);
                    }
                } else {
                    alert(data.error || 'Ошибка сохранения');
                }
            } else {
                cell.innerText = data.value || '';
                cell.setAttribute('data-version', data.version);
            }
            cell.classList.remove('editing');
            editingCell = null;
        })
        .catch(() => {
            alert('Ошибка сети при сохранении');
            cancelEdit();
        });
    }

    table.addEventListener('click', function(e) {
        const cell = e.target.closest('.resource-cell');
        if (!cell) return;
        startEdit(cell);
    });
})();
JS);
?>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
        <div class="me-2 fw-bold">Матрица:</div>
        <?php foreach ($matrices as $m): ?>
            <?= Html::a(
                Html::encode($m->name),
                ['index', 'id' => $m->id],
                ['class' => 'btn btn-sm ' . ($m->id === $matrix->id ? 'btn-primary' : 'btn-outline-secondary')]
            ) ?>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$rows || !$columns): ?>
    <div class="alert alert-warning">
        Для выбранной матрицы ещё не настроены строки или колонки. Добавьте их в админке или напрямую в БД.
    </div>
<?php else: ?>
    <div class="resources-table-wrapper">
        <table class="resources-table table table-dark table-hover" id="resourcesTable">
            <thead>
            <tr>
                <th></th>
                <?php foreach ($columns as $column): ?>
                    <th><?= Html::encode($column->label) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <th><?= Html::encode($row->label) ?></th>
                    <?php foreach ($columns as $column): ?>
                        <?php
                        $cell = $cellMap[$row->id][$column->id] ?? null;
                        $value = $cell ? $cell->value : '';
                        $version = $cell ? $cell->version : 0;
                        ?>
                        <td class="resource-cell"
                            data-row-id="<?= $row->id ?>"
                            data-column-id="<?= $column->id ?>"
                            data-version="<?= $version ?>">
                            <?= Html::encode($value) ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>




<?php

use app\models\Location;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var Location[] $locations */
/** @var int $locationId */
/** @var string $period */
/** @var string $date */
/** @var string $from */
/** @var string $to */
/** @var array $result */

$this->title = 'Расчёт заработной платы';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss(<<<CSS
.table-dark th,
.table-dark td {
    vertical-align: middle;
}
CSS);
?>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <?php $form = ActiveForm::begin([
                'method' => 'get',
                'action' => ['index'],
                'options' => ['class' => 'row g-2'],
            ]); ?>
            <div class="col-md-4">
                <label class="form-label">Точка</label>
                <select name="location_id" class="form-select">
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc->id ?>" <?= $loc->id == $locationId ? 'selected' : '' ?>>
                            <?= Html::encode($loc->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary" type="submit">Показать</button>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <strong>Период:</strong> <?= Html::encode($from) ?> — <?= Html::encode($to) ?>
    </div>
    <div>
        <?= Html::a('📄 Экспорт в XLSX', [
            'export',
            'location_id' => $locationId,
            'period' => $period,
            'date' => $date,
        ], ['class' => 'btn btn-success btn-sm']) ?>
    </div>
</div>

<?php if (!empty($result['rows'])): ?>
    <div class="table-responsive">
        <table class="table table-dark table-hover table-striped align-middle">
            <thead>
            <tr>
                <th>Сотрудник</th>
                <th>Должность</th>
                <th>Точка</th>
                <th class="text-end">Часы</th>
                <th class="text-end">Ставка (₽/час)</th>
                <th class="text-end">Сумма (₽)</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result['rows'] as $row): ?>
                <tr>
                    <td><?= Html::encode($row['user']->getFullName()) ?></td>
                    <td><?= Html::encode($row['positionLabel']) ?></td>
                    <td><?= $row['location'] ? Html::encode($row['location']->name) : '' ?></td>
                    <td class="text-end"><?= number_format($row['hours'], 2, '.', ' ') ?></td>
                    <td class="text-end"><?= number_format($row['rate'], 2, '.', ' ') ?></td>
                    <td class="text-end"><?= number_format($row['amount'], 2, '.', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="3" class="text-end">Итого:</th>
                <th class="text-end"><?= number_format($result['totalHours'], 2, '.', ' ') ?></th>
                <th></th>
                <th class="text-end"><?= number_format($result['totalAmount'], 2, '.', ' ') ?></th>
            </tr>
            </tfoot>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        Нет данных по сменам за выбранный период.
    </div>
<?php endif; ?>




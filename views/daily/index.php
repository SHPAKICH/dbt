<?php

use app\models\DailyReport;
use app\models\Location;
use yii\bootstrap5\Html;
use yii\helpers\Url;
use yii\helpers\Json;

/** @var yii\web\View $this */
/** @var Location[] $locations */
/** @var int|null $locationId */
/** @var Location|null $location */
/** @var DailyReport[] $reports */
/** @var string $yearMonth */
/** @var \app\models\User[] $users */
/** @var bool $canEditPlan */

$this->title = 'Дейли';
$this->params['breadcrumbs'][] = ['label' => 'Документация', 'url' => ['/documentation/index']];
$this->params['breadcrumbs'][] = $this->title;

$formatNum = static function ($v) {
    return $v !== null && $v !== '' ? number_format((float)$v, 2, '.', ' ') : '';
};
$formatInt = static function ($v) {
    return $v !== null && $v !== '' ? (int)$v : '';
};
?>
<div class="daily-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (empty($locations)): ?>
        <div class="alert alert-warning">У вас нет доступа ни к одной точке.</div>
        <?php return; ?>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <?= Html::beginForm(['index'], 'get', ['class' => 'row g-3 align-items-end']) ?>
            <div class="col-md-4">
                <label class="form-label">Точка</label>
                <select name="location_id" class="form-select">
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= (int)$loc->id ?>" <?= $loc->id == $locationId ? 'selected' : '' ?>>
                            <?= Html::encode($loc->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Месяц</label>
                <input type="month" name="month" value="<?= Html::encode($yearMonth) ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Показать</button>
                <?= Html::a('📄 Экспорт в XLSX', [
                    'export',
                    'location_id' => $locationId,
                    'month' => $yearMonth,
                ], ['class' => 'btn btn-success']) ?>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>

    <p class="text-muted small mb-2">
        ТО/План — только тер.управ+. БАР/ДОСТАВКА/САМОВЫВОЗ/БОНУСЫ, Чеки — закрывающий смену. Часы — из графика.
    </p>

    <div class="table-responsive">
        <table class="table table-bordered table-hover daily-table">
            <thead class="table-light">
            <tr>
                <th style="min-width:70px">Дата</th>
                <th style="min-width:45px">День</th>
                <th style="min-width:95px">План на день</th>
                <th style="min-width:85px">ТО</th>
                <th style="min-width:75px">DELTA</th>
                <th style="min-width:70px">БАР</th>
                <th style="min-width:85px">ДОСТАВКА</th>
                <th style="min-width:95px">САМОВЫВОЗ</th>
                <th style="min-width:75px">БОНУСЫ</th>
                <th style="min-width:65px">Чеки БАР</th>
                <th style="min-width:75px">Чеки ДОСТ</th>
                <th style="min-width:85px">Чеки САМ</th>
                <th style="min-width:70px">Заказов</th>
                <th style="min-width:80px">Ср. чек БАР</th>
                <th style="min-width:85px">Ср. чек ДОСТ</th>
                <th style="min-width:85px">Ср. чек САМ</th>
                <th style="min-width:70px">Часы</th>
                <th style="min-width:85px">Произв. зак</th>
                <th style="min-width:85px">Произв. ₽</th>
                <th style="min-width:150px">Менеджер</th>
                <th style="min-width:60px"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
                <tr data-id="<?= (int)$r->id ?>">
                    <td><?= date('d.m.Y', strtotime($r->report_date)) ?></td>
                    <td><?= Html::encode($r->getDayOfWeekShort()) ?></td>
                    <?php if ($canEditPlan): ?>
                        <td><input type="text" class="form-control form-control-sm daily-input" data-field="plan_daily" value="<?= Html::encode($formatNum($r->plan_daily)) ?>" inputmode="decimal"></td>
                    <?php else: ?>
                        <td class="daily-readonly" data-field="plan_daily"><?= Html::encode($formatNum($r->plan_daily)) ?></td>
                    <?php endif; ?>
                    <td class="daily-readonly" data-field="to_revenue"><?= Html::encode($formatNum($r->to_revenue)) ?></td>
                    <td class="daily-readonly" data-field="delta_plan"><?= Html::encode($formatNum($r->delta_plan)) ?></td>
                    <td><input type="text" class="form-control form-control-sm daily-input" data-field="bar" value="<?= Html::encode($formatNum($r->bar)) ?>" inputmode="decimal"></td>
                    <td><input type="text" class="form-control form-control-sm daily-input" data-field="delivery" value="<?= Html::encode($formatNum($r->delivery)) ?>" inputmode="decimal"></td>
                    <td><input type="text" class="form-control form-control-sm daily-input" data-field="self_pickup" value="<?= Html::encode($formatNum($r->self_pickup)) ?>" inputmode="decimal"></td>
                    <td><input type="text" class="form-control form-control-sm daily-input" data-field="bonuses" value="<?= Html::encode($formatNum($r->bonuses)) ?>" inputmode="decimal"></td>
                    <td><input type="text" class="form-control form-control-sm daily-input" data-field="checks_bar" value="<?= Html::encode($formatInt($r->checks_bar)) ?>" inputmode="numeric"></td>
                    <td><input type="text" class="form-control form-control-sm daily-input" data-field="checks_delivery" value="<?= Html::encode($formatInt($r->checks_delivery)) ?>" inputmode="numeric"></td>
                    <td><input type="text" class="form-control form-control-sm daily-input" data-field="checks_self_pickup" value="<?= Html::encode($formatInt($r->checks_self_pickup)) ?>" inputmode="numeric"></td>
                    <td class="daily-readonly" data-field="orders_count"><?= Html::encode($formatInt($r->orders_count)) ?></td>
                    <td class="daily-readonly" data-field="avg_check_bar"><?= Html::encode($formatNum($r->avg_check_bar)) ?></td>
                    <td class="daily-readonly" data-field="avg_check_delivery"><?= Html::encode($formatNum($r->avg_check_delivery)) ?></td>
                    <td class="daily-readonly" data-field="avg_check_self_pickup"><?= Html::encode($formatNum($r->avg_check_self_pickup)) ?></td>
                    <td class="daily-readonly" data-field="worker_hours"><?= Html::encode($formatNum($r->worker_hours)) ?></td>
                    <td class="daily-readonly" data-field="productivity_orders"><?= Html::encode($formatNum($r->productivity_orders)) ?></td>
                    <td class="daily-readonly" data-field="productivity_money"><?= Html::encode($formatNum($r->productivity_money)) ?></td>
                    <td>
                        <select class="form-select form-select-sm daily-input" data-field="manager_id">
                            <option value="">—</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int)$u->id ?>" <?= $r->manager_id == $u->id ? 'selected' : '' ?>>
                                    <?= Html::encode($u->getFullName()) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary daily-save" title="Сохранить">✓</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="mt-3 small text-muted">
        Нажмите ✓ у строки для сохранения. ТО, DELTA, Заказов, ср. чеки, часы, производительность считаются автоматически.
    </p>
</div>

<?php
$dailyJsUrl = Json::encode(Url::to(['daily/update']));
$dailyCsrfParam = Json::encode(Yii::$app->request->csrfParam);
$dailyCsrfToken = Json::encode(Yii::$app->request->csrfToken);
$dailyCanEditPlan = Json::encode($canEditPlan ?? false);
$this->registerJs(<<<JS
(function(){
    var url = $dailyJsUrl;
    var csrfParam = $dailyCsrfParam;
    var csrfToken = $dailyCsrfToken;
    var canEditPlan = $dailyCanEditPlan;

    function fmtNum(v) { return v != null && v !== '' ? Number(v).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,' ') : ''; }
    function fmtInt(v) { return v != null && v !== '' ? String(Math.round(v)) : ''; }

    function updateRowFromServer(tr, data) {
        if (!data) return;
        var fields = ['to_revenue','delta_plan','orders_count','avg_check_bar','avg_check_delivery','avg_check_self_pickup','worker_hours','productivity_orders','productivity_money'];
        fields.forEach(function(f) {
            var v = data[f];
            var el = tr.querySelector('[data-field="' + f + '"]');
            if (el) el.textContent = (f === 'orders_count' ? fmtInt(v) : fmtNum(v));
        });
    }
    function setCell(tr, field, val) {
        var el = tr.querySelector('[data-field="' + field + '"]');
        if (el) el.textContent = val;
    }

    function saveRow(tr) {
        var id = tr.dataset.id;
        var inputs = tr.querySelectorAll('.daily-input');
        var fd = new FormData();
        fd.append('id', id);
        fd.append(csrfParam, csrfToken);
        inputs.forEach(function(inp) {
            var f = inp.dataset.field;
            var v = inp.tagName === 'SELECT' ? inp.value : inp.value.trim();
            fd.append(f, v);
        });
        if (canEditPlan) {
            var planInp = tr.querySelector('[data-field="plan_daily"]');
            if (planInp && planInp.classList && planInp.classList.contains('daily-input'))
                fd.append('plan_daily', planInp.value.trim());
        }

        var btn = tr.querySelector('.daily-save');
        btn.disabled = true;
        btn.textContent = '...';

        fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r){ return r.json(); })
        .then(function(res) {
            btn.disabled = false;
            btn.textContent = '✓';
            if (res.ok) {
                if (res.data) updateRowFromServer(tr, res.data);
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-primary');
                setTimeout(function(){ btn.classList.remove('btn-success'); btn.classList.add('btn-outline-primary'); }, 800);
            } else {
                alert(res.error || 'Ошибка сохранения');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = '✓';
            alert('Ошибка сети');
        });
    }

    document.querySelectorAll('.daily-save').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tr = btn.closest('tr');
            if (tr) saveRow(tr);
        });
    });
})();
JS
);
?>

<?php

/** @var yii\web\View $this */

use yii\bootstrap5\Html;

$this->title = 'Настройки';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="settings-page">
    <h1 class="mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card settings-card">
                <div class="card-header">
                    <h5 class="mb-0">Внешний вид</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item d-flex align-items-center justify-content-between py-3 border-bottom">
                        <div>
                            <strong>Тёмная тема</strong>
                            <p class="text-muted small mb-0 mt-1">Включить тёмное оформление интерфейса</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="settingDarkTheme" autocomplete="off">
                            <label class="form-check-label" for="settingDarkTheme">Вкл.</label>
                        </div>
                    </div>

                    <div class="settings-item d-flex align-items-center justify-content-between py-3">
                        <div>
                            <strong>Компактный вид таблиц</strong>
                            <p class="text-muted small mb-0 mt-1">Уменьшить отступы в таблицах графиков и отчётов</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="settingCompactTables" autocomplete="off">
                            <label class="form-check-label" for="settingCompactTables">Вкл.</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card settings-card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Уведомления</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item d-flex align-items-center justify-content-between py-3 border-bottom">
                        <div>
                            <strong>Напоминания о сменах</strong>
                            <p class="text-muted small mb-0 mt-1">Получать push-уведомления о предстоящих сменах</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="settingShiftReminders" autocomplete="off" checked>
                            <label class="form-check-label" for="settingShiftReminders">Вкл.</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card settings-card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Дата и время</h5>
                </div>
                <div class="card-body">
                    <div class="settings-item py-3">
                        <strong>Формат даты</strong>
                        <p class="text-muted small mb-2 mt-1">Как отображать даты в графиках и отчётах</p>
                        <select class="form-select form-select-sm w-auto" id="settingDateFormat">
                            <option value="d.m.Y">31.12.2025</option>
                            <option value="d.m.y">31.12.25</option>
                            <option value="Y-m-d">2025-12-31</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card settings-card">
                <div class="card-header">
                    <h5 class="mb-0">Справка</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-0">
                        Настройки сохраняются в вашем браузере и применяются только на этом устройстве.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$storageKey = 'dbt_settings';
$this->registerJs(<<<JS
(function() {
    var key = '{$storageKey}';
    function load() {
        try {
            var s = JSON.parse(localStorage.getItem(key) || '{}');
            document.getElementById('settingDarkTheme').checked = !!s.darkTheme;
            document.getElementById('settingCompactTables').checked = !!s.compactTables;
            document.getElementById('settingShiftReminders').checked = s.shiftReminders !== false;
            if (s.dateFormat) {
                var sel = document.getElementById('settingDateFormat');
                if (sel) sel.value = s.dateFormat;
            }
            applyTheme(s.darkTheme);
            document.body.classList.toggle('compact-tables', !!s.compactTables);
        } catch (e) {}
    }
    function save() {
        var s = {
            darkTheme: document.getElementById('settingDarkTheme').checked,
            compactTables: document.getElementById('settingCompactTables').checked,
            shiftReminders: document.getElementById('settingShiftReminders').checked,
            dateFormat: document.getElementById('settingDateFormat').value
        };
        localStorage.setItem(key, JSON.stringify(s));
        applyTheme(s.darkTheme);
        document.body.classList.toggle('compact-tables', !!s.compactTables);
    }
    function applyTheme(dark) {
        document.body.classList.toggle('theme-dark', !!dark);
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.content = dark ? '#1a1a1a' : '#F07E9B';
    }
    load();
    ['settingDarkTheme','settingCompactTables','settingShiftReminders'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', save);
    });
    var df = document.getElementById('settingDateFormat');
    if (df) df.addEventListener('change', save);
})();
JS);
?>

<?php

use yii\bootstrap5\Html;

/** @var yii\web\View $this */

$this->title = 'Документация';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="documentation-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p class="text-muted mb-4">
        Здесь ответственные лица заполняют документы и отчёты по окончании смены:
        выручка, отчёты по точке и прочее.
    </p>

    <div class="card mt-3 border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📊 Категория документов — Дейли</h5>
            <?= Html::a('Открыть Дейли →', ['/daily/index'], ['class' => 'btn btn-light btn-sm']) ?>
        </div>
        <div class="card-body">
            <h6 class="card-subtitle mb-3 text-muted">Ежедневный отчёт по точке (заполняется в конце смены, экспорт в конце месяца)</h6>
            <ul class="mb-3">
                <li><strong>Автогенерация:</strong> документ Дейли автоматически создаётся на каждый следующий месяц.</li>
                <li><strong>Когда заполняется:</strong> в конце смены сотрудником, закрывающим смену.</li>
                <li><strong>Где ведётся:</strong> отдельный Дейли заводится на каждой точке (например, Меркурий, и др.).</li>
            </ul>
            <p class="mb-2">В Дейли указываются основные показатели дня:</p>
            <ul class="mb-0">
                <li>Дата, день недели</li>
                <li>ТО (выручка), план на день, DELTA к дневному плану</li>
                <li>Разбивка продаж: БАР, ДОСТАВКА, САМОВЫВОЗ, ОЗ, БОНУСЫ, заказы</li>
                <li>Средний чек по каналам</li>
                <li>Отработанные часы, производительность (в заказах и деньгах)</li>
                <li>ФИО менеджера, ответственного за смену</li>
            </ul>
            <p class="mt-3 mb-0 small text-muted">
                <em>Пример структуры: см. шаблон для точки «Меркурий» — там заданы столбцы, недельные и месячные итоги, плановый товарооборот и % выполнения.</em>
            </p>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">📋 Прочие документы и отчёты</h5>
        </div>
        <div class="card-body">
            <p class="mb-0 text-muted">
                Дополнительные категории документов будут добавлены в следующих обновлениях.
            </p>
        </div>
    </div>
</div>

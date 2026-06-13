<?php

/** @var yii\web\View $this */
/** @var app\models\TrainingTest[] $tests */
/** @var app\models\TrainingResult[] $myResults */
/** @var int $cardsCount */
/** @var bool $isAdmin */

use yii\bootstrap5\Html;

$this->title = 'Меню Гуру';
$this->params['breadcrumbs'][] = $this->title;

$totalTests = count($tests);
$passedTests = 0;
foreach ($myResults as $r) {
    if ($r->passed) {
        $passedTests++;
    }
}

$this->registerCss(<<<CSS
.guru-dashboard {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.guru-top-bar {
    display: grid;
    grid-template-columns: 2fr 1.2fr 1.5fr;
    gap: 16px;
    align-items: stretch;
}

.guru-top-pill {
    background: var(--color-surface, #fff);
    border-radius: 16px;
    padding: 14px 18px;
    box-shadow: 0 2px 6px rgba(0,0,0,.06);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.guru-top-pill-title {
    font-weight: 600;
    font-size: 14px;
    letter-spacing: .04em;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.guru-top-pill-text {
    font-size: 13px;
    color: var(--color-text-muted, #6c757d);
}

.guru-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.guru-section-title {
    font-weight: 600;
    font-size: 18px;
}

.guru-card-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.guru-card {
    border-radius: 18px;
    background: var(--color-surface, #fff);
    box-shadow: 0 3px 10px rgba(0,0,0,.08);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-height: 170px;
}

.guru-card-thumb {
    background: linear-gradient(135deg, #ffe7f0, #fff7e6);
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 24px;
}

.guru-card-body {
    padding: 12px 14px 14px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}

.guru-card-title {
    font-size: 14px;
    font-weight: 600;
}

.guru-card-meta {
    font-size: 12px;
    color: var(--color-text-muted, #6c757d);
}

.guru-card-footer {
    margin-top: auto;
}

.guru-section-subtitle {
    font-size: 13px;
    color: var(--color-text-muted, #6c757d);
}

.guru-bottom-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.guru-bottom-card {
    border-radius: 18px;
    background: var(--color-surface, #fff);
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-height: 120px;
}

.guru-bottom-card-title {
    font-size: 14px;
    font-weight: 600;
}

.guru-bottom-card-meta {
    font-size: 12px;
    color: var(--color-text-muted, #6c757d);
}

@media (max-width: 991.98px) {
    .guru-top-bar {
        grid-template-columns: 1.6fr 1.2fr;
    }
    .guru-card-grid,
    .guru-bottom-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575.98px) {
    .guru-top-bar {
        grid-template-columns: 1fr;
    }
    .guru-card-grid,
    .guru-bottom-grid {
        grid-template-columns: 1fr;
    }
}
CSS);
?>

<div class="guru-default-index guru-dashboard">
    <div class="guru-top-bar">
        <div class="guru-top-pill">
            <div class="guru-top-pill-title">База знаний</div>
            <div class="guru-top-pill-text">
                Материалы, тесты и карточки по меню DBT.
            </div>
        </div>
        <div class="guru-top-pill">
            <div class="guru-top-pill-title">Тесты</div>
            <div class="guru-top-pill-text">
                Пройдено: <strong><?= $passedTests ?></strong>
                <?= $totalTests > 0 ? 'из ' . $totalTests : '' ?>
            </div>
        </div>
        <div class="guru-top-pill">
            <div class="guru-top-pill-title">Новости</div>
            <div class="guru-top-pill-text">
                Новое весеннее меню DBT! 📣
            </div>
        </div>
    </div>

    <section>
        <div class="guru-section-header">
            <div>
                <div class="guru-section-title">Уроки и тесты</div>
                <div class="guru-section-subtitle">Подготовьтесь по теории и сразу закрепите тестами.</div>
            </div>
            <?= Html::a('Все уроки и тесты →', ['/guru/test/index'], ['class' => 'btn btn-sm btn-outline-primary']) ?>
        </div>

        <?php if (empty($tests)): ?>
            <p class="text-muted mb-0">Пока нет доступных тестов. Как только они появятся, вы увидите их здесь.</p>
        <?php else: ?>
            <div class="guru-card-grid">
                <?php foreach (array_slice($tests, 0, 3) as $test): ?>
                    <div class="guru-card">
                        <div class="guru-card-thumb">
                            <span class="bi bi-image"></span>
                        </div>
                        <div class="guru-card-body">
                            <div class="guru-card-title">
                                <?= Html::encode($test->title) ?>
                            </div>
                            <div class="guru-card-meta">
                                <?= $test->category ? Html::encode($test->category) . ' • ' : '' ?>
                                Тест по меню и стандартам
                            </div>
                            <div class="guru-card-footer">
                                <?= Html::a('Пройти', ['/guru/test/view', 'id' => $test->id], ['class' => 'btn btn-sm btn-primary w-100']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section>
        <div class="guru-section-header">
            <div>
                <div class="guru-section-title">Заготовки и продукты, напитки, вафли</div>
                <div class="guru-section-subtitle">
                    Карточки ТТК по категориям. Всего карточек: <strong><?= (int)$cardsCount ?></strong>.
                </div>
            </div>
            <?= Html::a('Все карточки меню →', ['/guru/card/index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
        </div>

        <div class="guru-bottom-grid">
            <div class="guru-bottom-card">
                <div class="guru-bottom-card-title">Заготовки и продукты</div>
                <div class="guru-bottom-card-meta">
                    Основные ингредиенты, сроки годности, условия хранения.
                </div>
                <div class="mt-auto">
                    <?= Html::a('Открыть каталог', ['/guru/card/index'], ['class' => 'btn btn-sm btn-outline-secondary w-100']) ?>
                </div>
            </div>
            <div class="guru-bottom-card">
                <div class="guru-bottom-card-title">Напитки</div>
                <div class="guru-bottom-card-meta">
                    Рецептуры авторских напитков, последовательность сборки.
                </div>
                <div class="mt-auto">
                    <?= Html::a('Открыть каталог', ['/guru/card/index'], ['class' => 'btn btn-sm btn-outline-secondary w-100']) ?>
                </div>
            </div>
            <div class="guru-bottom-card">
                <div class="guru-bottom-card-title">Вафли и десерты</div>
                <div class="guru-bottom-card-meta">
                    Подача, вес, варианты топпингов и начинок.
                </div>
                <div class="mt-auto">
                    <?= Html::a('Открыть каталог', ['/guru/card/index'], ['class' => 'btn btn-sm btn-outline-secondary w-100']) ?>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="row g-3 align-items-stretch">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Мои последние результаты</h6>
                        <?= Html::a('История тестов', ['/guru/test/result'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($myResults)): ?>
                            <p class="text-muted mb-0">Вы ещё не проходили тесты.</p>
                        <?php else: ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($myResults as $r): ?>
                                    <li class="mb-2">
                                        <strong><?= Html::encode($r->test->title ?? '') ?></strong>
                                        —
                                        <span class="<?= $r->passed ? 'text-success' : 'text-danger' ?>">
                                            <?= Yii::$app->formatter->asPercent($r->score / 100, 0) ?>
                                        </span>
                                        <?php if ($r->finished_at): ?>
                                            <br>
                                            <small class="text-muted"><?= Yii::$app->formatter->asDatetime($r->finished_at) ?></small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($isAdmin): ?>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Гуру — управление</h6>
                        </div>
                        <div class="card-body d-flex flex-column gap-2">
                            <?= Html::a('Создать тест', ['/learning/tests/create'], ['class' => 'btn btn-sm btn-outline-primary w-100']) ?>
                            <?= Html::a('Создать карточку ТТК', ['/learning/cards/create'], ['class' => 'btn btn-sm btn-outline-success w-100']) ?>
                            <?= Html::a('Добавить материал', ['/guru/material/create'], ['class' => 'btn btn-sm btn-outline-info w-100']) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

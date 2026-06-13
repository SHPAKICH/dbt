<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);
$this->registerLinkTag(['rel' => 'manifest', 'href' => Yii::getAlias('@web/manifest.json')]);
$this->registerMetaTag(['name' => 'theme-color', 'content' => '#F07E9B']);
$this->registerMetaTag(['name' => 'apple-mobile-web-app-capable', 'content' => 'yes']);
$this->registerMetaTag(['name' => 'apple-mobile-web-app-status-bar-style', 'content' => 'default']);

$isGuest = Yii::$app->user->isGuest;
$user = Yii::$app->user->identity;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <style>
        body {
            padding-top: <?= $isGuest ? '56px' : '0' ?>;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: var(--color-primary-dark);
            padding-top: 20px;
            z-index: 1000;
            overflow-y: auto;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .sidebar.active {
            transform: translateX(0);
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .sidebar-overlay.active {
            display: block;
        }
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
            background-color: var(--color-primary-dark);
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 20px;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
            display: block;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: var(--color-accent);
            border-left-color: var(--color-primary);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }
        .main-content {
            margin-left: 0;
            padding: 20px;
            min-height: 100vh;
            transition: margin-left 0.3s;
        }
        .sidebar-header {
            padding: 20px;
            color: #fff;
            border-bottom: 1px solid var(--color-border);
            margin-bottom: 20px;
        }
        .sidebar-header h4 {
            margin: 0;
            font-size: 18px;
        }
        .sidebar-header .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
            font-size: 14px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--color-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: bold;
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        /* Desktop styles */
        @media (min-width: 768px) {
            .sidebar {
                transform: translateX(0);
            }
            .sidebar-overlay {
                display: none !important;
            }
            .sidebar-toggle {
                display: none !important;
            }
            .main-content {
                margin-left: <?= $isGuest ? '0' : '250px' ?>;
            }
        }
        
        /* Mobile styles */
        @media (max-width: 767px) {
            .main-content {
                padding: 10px;
                padding-top: 60px;
            }
            .sidebar-header {
                padding: 15px;
            }
            .sidebar-header .d-flex {
                flex-direction: column;
                text-align: center;
            }
            .sidebar-header .d-flex > div:first-child {
                margin-bottom: 10px;
            }
            .user-avatar {
                margin-right: 0;
                margin-bottom: 10px;
            }
            .sidebar .nav-link {
                padding: 15px 20px;
                font-size: 16px;
            }
            .sidebar-toggle {
                display: block;
            }
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>
<script>
(function(){
    try {
        var s = JSON.parse(localStorage.getItem('dbt_settings') || '{}');
        if (s.darkTheme) document.body.classList.add('theme-dark');
        if (s.compactTables) document.body.classList.add('compact-tables');
    } catch(e){}
})();
</script>
<?php if (!$isGuest): ?>
    <!-- Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Открыть меню">☰</button>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <?php
            $cardService = new \app\services\ProfileCardService();
            $selectedTemplate = $cardService->getSelectedTemplate($user);
            $cardCssClass = $selectedTemplate && $selectedTemplate->css_class
                ? $selectedTemplate->css_class
                : 'profile-card-test-gold';
            ?>
            <?php
            $avatarUrl = $user->avatar;
            if ($avatarUrl && strpos($avatarUrl, 'http://') !== 0 && strpos($avatarUrl, 'https://') !== 0) {
                $avatarUrl = Yii::getAlias('@web') . '/' . ltrim($avatarUrl, '/');
            }
            ?>
            <div class="profile-card <?= Html::encode($cardCssClass) ?>">
                <div class="d-flex align-items-center">
                    <?php if ($user->avatar): ?>
                        <?= Html::img($avatarUrl, ['class' => 'user-avatar', 'alt' => 'Avatar']) ?>
                    <?php else: ?>
                        <div class="user-avatar">
                            <?= strtoupper(mb_substr($user->first_name ?: $user->email, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="profile-card-name"><?= Html::encode($user->getFullName()) ?></div>
                        <div class="profile-card-status"><?= Html::encode($user->getPositionLabel()) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <nav class="nav flex-column">
            <?php
            $isAdminUser = $user && $user->isAdmin();
            $pos = $user ? $user->position : null;
            $canSeeSupply = $isAdminUser || in_array($pos, ['senior_teamaker', 'location_manager', 'manager'], true);
            ?>
            <?= Html::a(
                '👤 Личный кабинет',
                ['/site/profile'],
                ['class' => 'nav-link' . (Yii::$app->controller->id === 'site' && Yii::$app->controller->action->id === 'profile' ? ' active' : '')]
            ) ?>

            <?= Html::a(
                '📅 График смен',
                ['/schedule/index'],
                ['class' => 'nav-link' . (Yii::$app->controller->id === 'schedule' ? ' active' : '')]
            ) ?>

            <?= Html::a(
                '💰 Зарплата',
                ['/payroll/index'],
                ['class' => 'nav-link' . (Yii::$app->controller->id === 'payroll' ? ' active' : '')]
            ) ?>

            <?php if ($canSeeSupply): ?>
                <?= Html::a(
                    '📦 Заказ поставки',
                    ['/supply/create'],
                    ['class' => 'nav-link' . (Yii::$app->controller->id === 'supply' ? ' active' : '')]
                ) ?>
            <?php endif; ?>

            <?= Html::a(
                '🧩 Карта возможностей',
                ['/availability/index'],
                ['class' => 'nav-link' . (Yii::$app->controller->id === 'availability' ? ' active' : '')]
            ) ?>

            <?= Html::a(
                '📖 DBT.INFO',
                ['/guru/default/index'],
                ['class' => 'nav-link' . (Yii::$app->controller->module && Yii::$app->controller->module->id === 'guru' ? ' active' : '')]
            ) ?>

            <?php
            $canSeeDocs = $user && ($user->isAdmin() || in_array($user->position, ['manager', 'location_manager', 'senior_teamaker'], true));
            if ($canSeeDocs):
            ?>
                <?= Html::a(
                    '📋 Документация',
                    ['/documentation/index'],
                    ['class' => 'nav-link' . (Yii::$app->controller->id === 'documentation' ? ' active' : '')]
                ) ?>
                <?= Html::a(
                    '📊 Дейли',
                    ['/daily/index'],
                    ['class' => 'nav-link' . (Yii::$app->controller->id === 'daily' ? ' active' : '')]
                ) ?>
            <?php endif; ?>

            <?php if ($user && ($user->isAdmin() || $user->position === 'manager')): ?>
                <?php if ($user->isAdmin()): ?>
                    <?= Html::a(
                        '⚙️ Админ-панель',
                        ['/admin/index'],
                        ['class' => 'nav-link' . (Yii::$app->controller->id === 'admin' ? ' active' : '')]
                    ) ?>
                <?php else: ?>
                    <?= Html::a(
                        '👥 Пользователи',
                        ['/admin/users'],
                        ['class' => 'nav-link' . (Yii::$app->controller->id === 'admin' && Yii::$app->controller->action->id === 'users' ? ' active' : '')]
                    ) ?>
                <?php endif; ?>
            <?php endif; ?>

            <?= Html::a(
                '⚙️ Настройки',
                ['/settings/index'],
                ['class' => 'nav-link' . (Yii::$app->controller->id === 'settings' ? ' active' : '')]
            ) ?>
            <!-- Здесь будут добавлены другие ссылки в будущем -->
        </nav>
        <div class="mt-auto p-3">
            <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline']) ?>
                <?= Html::submitButton(
                    '🚪 Выход',
                    ['class' => 'btn btn-outline-danger btn-sm w-100']
                ) ?>
            <?= Html::endForm() ?>
        </div>
    </div>
<?php else: ?>
    <!-- Header для гостей -->
    <header id="header">
        <?php
        NavBar::begin([
            'brandLabel' => Yii::$app->name,
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
        ]);
        echo Nav::widget([
            'options' => ['class' => 'navbar-nav ms-auto'],
            'items' => [
                ['label' => 'Вход', 'url' => ['/site/login']],
            ]
        ]);
        NavBar::end();
        ?>
    </header>
<?php endif; ?>

<main id="main" class="main-content" role="main">
    <?php if (!empty($this->params['breadcrumbs'])): ?>
        <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
    <?php endif ?>
    <?= Alert::widget() ?>
    <?= $content ?>
</main>

<?php if ($isGuest): ?>
<footer id="footer" class="mt-auto py-3 bg-light">
    <div class="container">
        <div class="row text-muted">
            <div class="col-md-6 text-center text-md-start">&copy; <?= Yii::$app->name ?> <?= date('Y') ?></div>
            <div class="col-md-6 text-center text-md-end"><?= Yii::powered() ?></div>
        </div>
    </div>
</footer>
<?php endif; ?>

<?php $this->endBody() ?>
<script>
// PWA: регистрация Service Worker для push-уведомлений
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= Yii::getAlias('@web/sw.js') ?>').catch(function() {});
}
// Mobile sidebar toggle
(function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (sidebar && sidebarToggle && sidebarOverlay) {
        function openSidebar() {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeSidebar() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
        
        sidebarOverlay.addEventListener('click', closeSidebar);
        
        // Close sidebar when clicking on a link (mobile)
        const sidebarLinks = sidebar.querySelectorAll('.nav-link');
        sidebarLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    closeSidebar();
                }
            });
        });
        
        // Close sidebar on window resize if desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                closeSidebar();
            }
        });
    }
})();
</script>
</body>
</html>
<?php $this->endPage() ?>

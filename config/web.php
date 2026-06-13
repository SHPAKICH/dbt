<?php

$localConfig = [];
$localConfigPath = __DIR__ . '/local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
}

$params = require __DIR__ . '/params.php';
if (!empty($localConfig['params']) && is_array($localConfig['params'])) {
    $params = array_merge($params, $localConfig['params']);
}
$db = require __DIR__ . '/db.php';
$cookieValidationKey = $localConfig['app']['cookieValidationKey'] ?? (getenv('APP_COOKIE_VALIDATION_KEY') ?: '');
if ($cookieValidationKey === '') {
    throw new RuntimeException('APP_COOKIE_VALIDATION_KEY must be configured via config/local.php or environment variables.');
}

// PhpSpreadsheet иногда зависит от `myclabs/php-enum`. Если composer-пакет не установлен (например, из-за ограничений PHP),
// подставляем полифилл, чтобы генерация XLSX не падала.
if (!class_exists(\MyCLabs\Enum\Enum::class, false)) {
    $polyfillPath = __DIR__ . '/../components/Polyfills/MyCLabsEnumEnum.php';
    if (is_file($polyfillPath)) {
        require_once $polyfillPath;
    }
}

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'modules' => [
        'gridview' => [
            'class' => '\kartik\grid\Module',
        ],
        'guru' => [
            'class' => 'app\modules\guru\Module',
        ],
        'api' => [
            'class' => 'app\modules\api\Module',
        ],
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => $cookieValidationKey,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'class' => \app\components\ApiErrorHandler::class,
            'errorAction' => 'site/error',
        ],
        'mailer' => array_merge([
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => true,
        ], (function () {
            $dsn = trim((string) (getenv('MAILER_DSN') ?: ''));
            if ($dsn === '') {
                return [];
            }
            // Без timeout SMTP при заблокированном порте (Hostkey) висит до 504 Gateway Timeout
            if (stripos($dsn, 'timeout=') === false) {
                $dsn .= (strpos($dsn, '?') !== false ? '&' : '?') . 'timeout=10';
            }
            return [
                'useFileTransport' => false,
                'transport' => ['dsn' => $dsn],
            ];
        })()),
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // REST API v1 (метод проверяется VerbFilter в контроллерах)
                'api/v1/auth/login' => 'api/auth/login',
                'api/v1/auth/logout' => 'api/auth/logout',
                'api/v1/auth/user' => 'api/auth/user',
                'api/v1/auth/forgot-password' => 'api/auth/forgot-password',
                'api/v1/auth/reset-password' => 'api/auth/reset-password',
                'api/v1/auth/validate-reset-token' => 'api/auth/validate-reset-token',
                'api/v1/telegram/confirm-link' => 'api/telegram/confirm-link',
                'api/v1/profile/telegram' => 'api/profile/telegram',
                'api/v1/profile/telegram-link' => 'api/profile/telegram-link',
                'api/v1/profile/telegram-unlink' => 'api/profile/telegram-unlink',
                'api/v1/profile' => 'api/profile/index',
                'api/v1/profile/update' => 'api/profile/update',
                'api/v1/profile/avatar' => 'api/profile/avatar',
                'api/v1/profile/avatar-file' => 'api/profile/avatar-file',
                'api/v1/profile/cards' => 'api/profile/cards',
                'api/v1/profile/cards/select' => 'api/profile/card-select',
                'api/v1/profile/cards/remove' => 'api/profile/card-remove',
                'api/v1/profile/cards/visibility' => 'api/profile/card-visibility',

                'api/v1/schedule/locations' => 'api/schedule/locations',
                'api/v1/schedule/grid' => 'api/schedule/grid',
                'api/v1/schedule/update-cell' => 'api/schedule/update-cell',
                'api/v1/schedule/helpers' => 'api/schedule/helpers',
                'api/v1/schedule/send-invite' => 'api/schedule/send-invite',
                'api/v1/schedule/my-invites' => 'api/schedule/my-invites',
                'api/v1/schedule/accept-invite' => 'api/schedule/accept-invite',
                'api/v1/schedule/decline-invite' => 'api/schedule/decline-invite',

                'api/v1/availability/my' => 'api/availability/my',
                'api/v1/availability/team' => 'api/availability/team',
                'api/v1/availability/update-cell' => 'api/availability/update-cell',

                'api/v1/guru/dashboard' => 'api/guru/dashboard',
                'api/v1/guru/search' => 'api/guru/search',
                'api/v1/guru/tests' => 'api/guru/tests',
                'api/v1/guru/tests/<id:\d+>' => 'api/guru/test',
                'api/v1/guru/tests/<id:\d+>/update' => 'api/guru/test-update',
                'api/v1/guru/tests/<id:\d+>/questions' => 'api/guru/test-questions',
                'api/v1/guru/tests/<id:\d+>/questions-edit' => 'api/guru/test-questions-edit',
                'api/v1/guru/test/submit' => 'api/guru/test-submit',
                'api/v1/guru/tests/<id:\d+>/leaderboard' => 'api/guru/leaderboard',
                'api/v1/guru/tests/<id:\d+>/results' => 'api/guru/test-results',
                'api/v1/guru/result/<id:\d+>' => 'api/guru/result',
                'api/v1/guru/cards' => 'api/guru/cards',
                'api/v1/guru/cards/<id:\d+>' => 'api/guru/card',
                'api/v1/guru/cards/<id:\d+>/update' => 'api/guru/card-update',
                'api/v1/guru/cards/create' => 'api/guru/card-create',
                'api/v1/guru/tests/create' => 'api/guru/test-create',
                'api/v1/guru/lessons' => 'api/guru/lessons',
                'api/v1/guru/lessons/upload-image' => 'api/guru/lesson-upload-image',
                'api/v1/guru/lessons/create' => 'api/guru/lesson-create',
                'api/v1/guru/lessons/<id:\d+>' => 'api/guru/lesson',
                'api/v1/guru/lessons/<id:\d+>/mark-read' => 'api/guru/lesson-mark-read',
                'api/v1/guru/training-progress' => 'api/guru/training-progress',

                'api/v1/admin/dashboard' => 'api/admin/dashboard',
                'api/v1/admin/users' => 'api/admin/users',
                'api/v1/admin/users/<id:\d+>' => 'api/admin/user',
                'api/v1/admin/locations' => 'api/admin/locations',
                'api/v1/admin/locations/<id:\d+>' => 'api/admin/location',
                'api/v1/admin/analytics-locations' => 'api/admin/analytics-locations',
                'api/v1/analytics/points' => 'api/analytics/points',
                'api/v1/analytics/points/<id:\d+>' => 'api/analytics/point',
                'api/v1/analytics/leaderboard' => 'api/analytics/leaderboard',
                'api/v1/admin/db-backup' => 'api/admin/db-backup',
                'api/v1/admin/push/stats' => 'api/admin/push-stats',
                'api/v1/admin/push/send' => 'api/admin/push-send',
                'api/v1/admin/profile-cards' => 'api/admin/profile-cards',
                'api/v1/admin/profile-cards/upload-bg' => 'api/admin/profile-card-upload-bg',
                'api/v1/admin/profile-cards/<id:\d+>' => 'api/admin/profile-card',
                'api/v1/admin/position-rates' => 'api/admin/position-rates',
                'api/v1/admin/kro-bands' => 'api/admin/kro-bands',
                'api/v1/admin/location-kro' => 'api/admin/location-kro',

                'api/v1/supply/locations' => 'api/supply/locations',
                'api/v1/supply/products' => 'api/supply/products',
                'api/v1/supply/orders' => 'api/supply/create-order',
                'api/v1/supply/orders/<id:\d+>' => 'api/supply/order',
                'api/v1/supply/orders/<id:\d+>/export' => 'api/supply/export',

                'api/v1/iiko-stock/test' => 'api/iiko-stock/test',
                'api/v1/iiko-stock/stores' => 'api/iiko-stock/stores',
                'api/v1/iiko-stock/balance' => 'api/iiko-stock/balance',
                'api/v1/iiko-stock/product-documents' => 'api/iiko-stock/product-documents',
                'api/v1/iiko-stock/cloud-test' => 'api/iiko-stock/cloud-test',
                'api/v1/iiko-stock/cloud-organizations' => 'api/iiko-stock/cloud-organizations',
                'api/v1/iiko-stock/cloud-stores' => 'api/iiko-stock/cloud-stores',
                'api/v1/iiko-stock/documents-list' => 'api/iiko-stock/documents-list',
                'api/v1/iiko-stock/document-by-id' => 'api/iiko-stock/document-by-id',

                'api/v1/iiko-checks/departments' => 'api/iiko-checks/departments',
                'api/v1/iiko-checks/list' => 'api/iiko-checks/list',
                'api/v1/iiko-checks/detail' => 'api/iiko-checks/detail',
                'api/v1/iiko-checks/shift-summary' => 'api/iiko-checks/shift-summary',
                'api/v1/iiko-sales-reports/departments' => 'api/iiko-sales-reports/departments',
                'api/v1/iiko-sales-reports/nomenclature' => 'api/iiko-sales-reports/nomenclature',
                'api/v1/iiko-sales-reports/report' => 'api/iiko-sales-reports/report',

                'api/v1/daily/locations' => 'api/daily/locations',
                'api/v1/daily/reports' => 'api/daily/reports',
                'api/v1/daily/update' => 'api/daily/update',
                'api/v1/daily/export' => 'api/daily/export',
                'api/v1/daily/users' => 'api/daily/users',

                'api/v1/write-off/locations' => 'api/write-off/locations',
                'api/v1/write-off/entries' => 'api/write-off/entries',
                'api/v1/write-off/save' => 'api/write-off/save',

                'api/v1/push/subscribe' => 'api/push/subscribe',
                'api/v1/push/unsubscribe' => 'api/push/unsubscribe',

                'api/v1/news' => 'api/news/index',
                'api/v1/news/<id:\d+>' => 'api/news/view',

                'api/v1/payroll/locations' => 'api/payroll/locations',
                'api/v1/payroll/kro' => 'api/payroll/kro',
                'api/v1/payroll/calculations' => 'api/payroll/calculations',
                'api/v1/payroll/my-daily' => 'api/payroll/my-daily',
                'api/v1/payroll/formula' => 'api/payroll/formula',

                'api/v1/admin/news' => 'api/admin/news',
                'api/v1/admin/news/<id:\d+>' => 'api/admin/news-item',
                'api/v1/admin/news/upload-image' => 'api/admin/news-upload-image',

                'api/v1/accounting/locations' => 'api/accounting/locations',
                'api/v1/accounting/last-messages' => 'api/accounting/last-messages',
                'api/v1/accounting/messages' => 'api/accounting/messages',

                'api/v1/shift-tasks/locations' => 'api/shift-task/locations',
                'api/v1/shift-tasks/tasks' => 'api/shift-task/tasks',
                'api/v1/shift-tasks/create' => 'api/shift-task/create',
                'api/v1/shift-tasks/complete' => 'api/shift-task/complete',
                'api/v1/shift-tasks/delete' => 'api/shift-task/delete',
                'api/v1/shift-tasks/history' => 'api/shift-task/history',

                'api/v1/photo-report/locations' => 'api/photo-report/locations',
                'api/v1/photo-report/items' => 'api/photo-report/items',
                'api/v1/photo-report/photos' => 'api/photo-report/photos',
                'api/v1/photo-report/upload' => 'api/photo-report/upload',
                'api/v1/photo-report/delete' => 'api/photo-report/delete',
                'api/v1/photo-report/overview' => 'api/photo-report/overview',
            ],
        ],
        
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    // Safety guard for production servers deployed with --no-dev.
    if (class_exists(\yii\debug\Module::class)) {
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => \yii\debug\Module::class,
            // uncomment the following to add your IP if you are not connecting from localhost.
            //'allowedIPs' => ['127.0.0.1', '::1'],
        ];
    }

    if (class_exists(\yii\gii\Module::class)) {
        $config['bootstrap'][] = 'gii';
        $config['modules']['gii'] = [
            'class' => \yii\gii\Module::class,
            // uncomment the following to add your IP if you are not connecting from localhost.
            //'allowedIPs' => ['127.0.0.1', '::1'],
        ];
    }
}

return $config;

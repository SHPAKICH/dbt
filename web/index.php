<?php

// Default to production to avoid loading debug modules on servers without dev packages.
$yiiEnv = getenv('YII_ENV') ?: 'prod';
$yiiDebugEnv = getenv('YII_DEBUG');
$yiiDebug = $yiiDebugEnv !== false
    ? in_array(strtolower((string)$yiiDebugEnv), ['1', 'true', 'yes', 'on'], true)
    : ($yiiEnv === 'dev');

defined('YII_DEBUG') or define('YII_DEBUG', $yiiDebug);
defined('YII_ENV') or define('YII_ENV', $yiiEnv);

$uri = $_SERVER['REQUEST_URI'] ?? '';
$path = trim((string) parse_url($uri, PHP_URL_PATH), '/');
// Убираем index.php из пути, если есть
$path = preg_replace('#^index\.php/?#', '', $path);

// Разрешаем: API (api/v1/) и веб-страницы Yii (guru, site и т.д.)
$isApi = (strpos($path, 'api/v1/') === 0);
$isWeb = (strpos($path, 'guru') === 0) || (strpos($path, 'site') === 0) || ($path === '' || $path === 'index.php');
if (!$isApi && !$isWeb) {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 Not Found';
    exit;
}

// Для nginx + PHP-FPM: явно задаём entry script, иначе Yii не может определить getScriptUrl()
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();

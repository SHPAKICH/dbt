<?php

$localConfig = [];
$localConfigPath = __DIR__ . '/local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
}

$dbConfig = $localConfig['db'] ?? [];
$dsn = $dbConfig['dsn'] ?? (getenv('DB_DSN') ?: null);
$username = $dbConfig['username'] ?? (getenv('DB_USERNAME') ?: null);
$password = array_key_exists('password', $dbConfig) ? $dbConfig['password'] : getenv('DB_PASSWORD');

if ($dsn === null || $username === null || $password === false || $password === null) {
    throw new RuntimeException('Database credentials must be provided via config/local.php or DB_DSN, DB_USERNAME, DB_PASSWORD environment variables.');
}

return [
    'class' => 'yii\db\Connection',
    'dsn' => $dsn,
    'username' => $username,
    'password' => $password,
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];

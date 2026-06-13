<?php
/**
 * Скрипт для исправления структуры БД
 * Добавляет поле auth_key если его нет
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
new yii\web\Application($config);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Исправление структуры БД</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Исправление структуры базы данных</h1>
    
    <?php
    try {
        $db = Yii::$app->db;
        
        echo "<h2>1. Проверка подключения к БД</h2>";
        $db->open();
        echo "<p class='success'>✓ Подключение успешно</p><br>";
        
        echo "<h2>2. Проверка таблицы users</h2>";
        $tableSchema = $db->schema->getTableSchema('users');
        if (!$tableSchema) {
            echo "<p class='error'>✗ Таблица users не найдена!</p>";
            echo "<p>Выполните сначала: <code>database/schema.sql</code></p>";
            exit;
        }
        echo "<p class='success'>✓ Таблица users существует</p><br>";
        
        echo "<h2>3. Проверка поля auth_key</h2>";
        $hasAuthKey = isset($tableSchema->columns['auth_key']);
        
        if (!$hasAuthKey) {
            echo "<p class='error'>✗ Поле auth_key отсутствует</p>";
            echo "<p class='info'>Добавляем поле auth_key...</p>";
            
            try {
                $db->createCommand()->addColumn('users', 'auth_key', 'VARCHAR(32) DEFAULT NULL')->execute();
                echo "<p class='success'>✓ Поле auth_key успешно добавлено!</p>";
            } catch (\Exception $e) {
                echo "<p class='error'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p>Выполните вручную в MySQL:</p>";
                echo "<pre>ALTER TABLE users ADD COLUMN auth_key VARCHAR(32) DEFAULT NULL AFTER last_name;</pre>";
            }
        } else {
            echo "<p class='success'>✓ Поле auth_key уже существует</p>";
        }
        echo "<br>";
        
        echo "<h2>4. Обновление существующих пользователей</h2>";
        $usersWithoutAuthKey = (new \yii\db\Query())
            ->from('users')
            ->where(['or', ['auth_key' => null], ['auth_key' => '']])
            ->count();
        
        if ($usersWithoutAuthKey > 0) {
            echo "<p class='info'>Найдено пользователей без auth_key: $usersWithoutAuthKey</p>";
            echo "<p>Обновляем...</p>";
            
            $users = (new \yii\db\Query())
                ->from('users')
                ->where(['or', ['auth_key' => null], ['auth_key' => '']])
                ->all();
            
            foreach ($users as $userData) {
                $authKey = substr(md5($userData['id'] . $userData['email'] . time() . rand()), 0, 32);
                $db->createCommand()
                    ->update('users', ['auth_key' => $authKey], ['id' => $userData['id']])
                    ->execute();
            }
            
            echo "<p class='success'>✓ Обновлено пользователей: " . count($users) . "</p>";
        } else {
            echo "<p class='success'>✓ Все пользователи имеют auth_key</p>";
        }
        echo "<br>";
        
        echo "<h2>5. Итоговая проверка</h2>";
        $totalUsers = (new \yii\db\Query())->from('users')->count();
        $usersWithAuthKey = (new \yii\db\Query())
            ->from('users')
            ->where(['and', ['not', ['auth_key' => null]], ['!=', 'auth_key', '']])
            ->count();
        
        echo "<p>Всего пользователей: $totalUsers</p>";
        echo "<p>С auth_key: $usersWithAuthKey</p>";
        
        if ($totalUsers == $usersWithAuthKey) {
            echo "<p class='success'>✓ Все в порядке! Теперь можно создавать пользователей.</p>";
            echo "<p><a href='/create-test-user.php'>Создать тестового пользователя</a></p>";
        } else {
            echo "<p class='error'>⚠ Есть пользователи без auth_key</p>";
        }
        
    } catch (\Exception $e) {
        echo "<p class='error'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    ?>
</body>
</html>


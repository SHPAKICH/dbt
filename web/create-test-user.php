<?php
/**
 * Веб-скрипт для создания тестового пользователя
 * Откройте в браузере: http://your-site/create-test-user.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
new yii\web\Application($config);

use app\models\User;
use app\models\Location;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Создание тестового пользователя</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Создание тестового пользователя</h1>
    
    <?php
    try {
        // Проверяем наличие поля auth_key в таблице
        echo "<h2>Проверка структуры БД...</h2>";
        $db = Yii::$app->db;
        $tableSchema = $db->schema->getTableSchema('users');
        $hasAuthKey = isset($tableSchema->columns['auth_key']);
        
        if (!$hasAuthKey) {
            echo "<p class='error'>⚠ Поле auth_key отсутствует в таблице users</p>";
            echo "<p>Добавляем поле auth_key...</p>";
            try {
                $db->createCommand()->addColumn('users', 'auth_key', 'VARCHAR(32) DEFAULT NULL')->execute();
                echo "<p class='success'>✓ Поле auth_key добавлено</p>";
            } catch (\Exception $e) {
                echo "<p class='error'>Ошибка добавления поля: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p>Выполните вручную: <code>ALTER TABLE users ADD COLUMN auth_key VARCHAR(32) DEFAULT NULL AFTER last_name;</code></p>";
            }
        } else {
            echo "<p class='success'>✓ Поле auth_key существует</p>";
        }
        echo "<br>";

        // Проверяем существование пользователя
        $existingUser = User::findByEmailOrPhone('manager@cafe.ru');
        if ($existingUser) {
            echo "<p>Пользователь manager@cafe.ru уже существует.</p>";
            echo "<p>Удаляем старую запись...</p>";
            try {
                $existingUser->delete();
                echo "<p class='success'>✓ Старая запись удалена</p>";
            } catch (\Exception $e) {
                echo "<p class='error'>Ошибка удаления: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }

        // Создаем точку если нет
        $location = Location::findOne(1);
        if (!$location) {
            $location = new Location();
            $location->name = 'Тестовое кафе';
            $location->address = 'Тестовый адрес';
            if (!$location->save()) {
                echo "<p class='error'>Ошибка создания точки:</p>";
                echo "<pre>";
                print_r($location->errors);
                echo "</pre>";
                exit;
            }
            echo "<p class='success'>✓ Создана тестовая точка</p>";
        }

        // Создаем пользователя
        echo "<h2>Создание пользователя...</h2>";
        $user = new User();
        $user->email = 'manager@cafe.ru';
        $user->phone = '+7 (999) 111-11-11';
        $user->setPassword('password123');
        $user->position = 'manager';
        $user->first_name = 'Иван';
        $user->last_name = 'Петров';
        $user->is_active = 1;
        // auth_key будет сгенерирован автоматически в beforeSave()

        if ($user->save()) {
            echo "<p class='success'>✓ Пользователь успешно создан!</p>";
            echo "<h2>Данные для входа:</h2>";
            echo "<pre>";
            echo "Email: manager@cafe.ru\n";
            echo "Телефон: +7 (999) 111-11-11\n";
            echo "Пароль: password123\n";
            echo "</pre>";
            echo "<p><a href='/site/login'>Перейти к странице входа</a></p>";
        } else {
            echo "<p class='error'>✗ Ошибка создания пользователя:</p>";
            echo "<pre>";
            foreach ($user->errors as $field => $errors) {
                echo "$field: " . implode(', ', $errors) . "\n";
            }
            echo "</pre>";
        }
    } catch (\Exception $e) {
        echo "<p class='error'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    ?>
</body>
</html>


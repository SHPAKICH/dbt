<?php
/**
 * Скрипт для тестирования входа и создания тестового пользователя
 * Запуск: php yii test-login
 */

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\User;
use app\models\Location;

class TestLoginController extends Controller
{
    /**
     * Создает тестового пользователя и проверяет вход
     */
    public function actionIndex()
    {
        echo "=== Тест системы входа ===\n\n";

        // Проверка подключения к БД
        echo "1. Проверка подключения к БД...\n";
        try {
            $db = Yii::$app->db;
            $db->open();
            echo "   ✓ Подключение успешно\n\n";
        } catch (\Exception $e) {
            echo "   ✗ Ошибка подключения: " . $e->getMessage() . "\n";
            return 1;
        }

        // Проверка существования таблиц
        echo "2. Проверка таблиц...\n";
        $tables = ['users', 'locations'];
        foreach ($tables as $table) {
            $exists = Yii::$app->db->schema->getTableSchema($table);
            if ($exists) {
                echo "   ✓ Таблица '$table' существует\n";
            } else {
                echo "   ✗ Таблица '$table' не найдена\n";
                return 1;
            }
        }
        echo "\n";

        // Проверка поля auth_key
        echo "3. Проверка структуры таблицы users...\n";
        $tableSchema = Yii::$app->db->schema->getTableSchema('users');
        $columns = $tableSchema->columns;
        $hasAuthKey = isset($columns['auth_key']);
        if ($hasAuthKey) {
            echo "   ✓ Поле 'auth_key' существует\n";
        } else {
            echo "   ✗ Поле 'auth_key' отсутствует. Выполните миграцию!\n";
            echo "   SQL: ALTER TABLE users ADD COLUMN auth_key VARCHAR(32) DEFAULT NULL AFTER last_name;\n";
        }
        echo "\n";

        // Проверка существующих пользователей
        echo "4. Проверка пользователей в БД...\n";
        $userCount = User::find()->count();
        echo "   Найдено пользователей: $userCount\n";
        
        if ($userCount > 0) {
            $testUser = User::findByEmailOrPhone('manager@cafe.ru');
            if ($testUser) {
                echo "   ✓ Тестовый пользователь manager@cafe.ru найден\n";
                echo "   ID: {$testUser->id}\n";
                echo "   Email: {$testUser->email}\n";
                echo "   Телефон: {$testUser->phone}\n";
                echo "   Должность: {$testUser->position}\n";
                echo "   Активен: " . ($testUser->is_active ? 'Да' : 'Нет') . "\n";
                echo "   Auth Key: " . ($testUser->auth_key ? 'Есть' : 'Отсутствует') . "\n";
                
                // Тест проверки пароля
                echo "\n5. Тест проверки пароля...\n";
                $testPassword = 'password123';
                if ($testUser->validatePassword($testPassword)) {
                    echo "   ✓ Пароль 'password123' валиден\n";
                } else {
                    echo "   ✗ Пароль 'password123' неверен\n";
                    echo "   Хеш в БД: {$testUser->password_hash}\n";
                    echo "   Попробуйте пересоздать пользователя с правильным хешем\n";
                }
            } else {
                echo "   ✗ Тестовый пользователь manager@cafe.ru не найден\n";
            }
        } else {
            echo "   ✗ Пользователи не найдены. Создайте тестового пользователя.\n";
        }
        echo "\n";

        // Создание тестового пользователя
        echo "6. Создание тестового пользователя (если не существует)...\n";
        $testUser = User::findByEmailOrPhone('manager@cafe.ru');
        if (!$testUser) {
            // Создаем точку если нет
            $location = Location::findOne(1);
            if (!$location) {
                $location = new Location();
                $location->name = 'Тестовое кафе';
                $location->address = 'Тестовый адрес';
                $location->save();
                echo "   ✓ Создана тестовая точка\n";
            }

            $user = new User();
            $user->email = 'manager@cafe.ru';
            $user->phone = '+7 (999) 111-11-11';
            $user->setPassword('password123');
            $user->position = 'manager';
            $user->first_name = 'Иван';
            $user->last_name = 'Петров';
            $user->is_active = 1;
            
            if ($user->save()) {
                echo "   ✓ Тестовый пользователь создан\n";
                echo "   Email: manager@cafe.ru\n";
                echo "   Телефон: +7 (999) 111-11-11\n";
                echo "   Пароль: password123\n";
            } else {
                echo "   ✗ Ошибка создания пользователя:\n";
                foreach ($user->errors as $field => $errors) {
                    echo "     $field: " . implode(', ', $errors) . "\n";
                }
            }
        } else {
            echo "   Тестовый пользователь уже существует\n";
        }
        echo "\n";

        echo "=== Тест завершен ===\n";
        return 0;
    }
}


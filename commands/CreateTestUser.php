<?php
/**
 * Скрипт для создания тестового пользователя
 * Запуск через браузер: /create-test-user
 * Или через консоль: php yii create-test-user
 */

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\User;
use app\models\Location;

class CreateTestUserController extends Controller
{
    public function actionIndex()
    {
        echo "Создание тестового пользователя...\n\n";

        // Проверяем существование пользователя
        $existingUser = User::findByEmailOrPhone('manager@cafe.ru');
        if ($existingUser) {
            echo "Пользователь manager@cafe.ru уже существует.\n";
            echo "Удаляем старую запись...\n";
            $existingUser->delete();
        }

        // Создаем точку если нет
        $location = Location::findOne(1);
        if (!$location) {
            $location = new Location();
            $location->name = 'Тестовое кафе';
            $location->address = 'Тестовый адрес';
            if (!$location->save()) {
                echo "Ошибка создания точки:\n";
                print_r($location->errors);
                return 1;
            }
            echo "✓ Создана тестовая точка\n";
        }

        // Создаем пользователя
        $user = new User();
        $user->email = 'manager@cafe.ru';
        $user->phone = '+7 (999) 111-11-11';
        $user->setPassword('password123');
        $user->position = 'manager';
        $user->first_name = 'Иван';
        $user->last_name = 'Петров';
        $user->is_active = 1;

        if ($user->save()) {
            echo "✓ Пользователь успешно создан!\n\n";
            echo "Данные для входа:\n";
            echo "Email: manager@cafe.ru\n";
            echo "Телефон: +7 (999) 111-11-11\n";
            echo "Пароль: password123\n";
            return 0;
        } else {
            echo "✗ Ошибка создания пользователя:\n";
            foreach ($user->errors as $field => $errors) {
                echo "  $field: " . implode(', ', $errors) . "\n";
            }
            return 1;
        }
    }
}


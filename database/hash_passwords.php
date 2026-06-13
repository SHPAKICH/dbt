<?php
/**
 * Скрипт для генерации хешей паролей для seed_data.sql
 * Запустите этот скрипт, чтобы получить правильные хеши паролей
 * 
 * Использование: php database/hash_passwords.php
 */

$password = 'password123'; // Пароль для всех тестовых пользователей

echo "Хеш пароля для '{$password}':\n";
echo password_hash($password, PASSWORD_DEFAULT) . "\n\n";

echo "Пример использования в SQL:\n";
echo "INSERT INTO users (phone, email, password_hash, position, first_name, last_name) VALUES\n";
echo "('+7 (999) 111-11-11', 'manager@cafe.ru', '" . password_hash($password, PASSWORD_DEFAULT) . "', 'manager', 'Иван', 'Петров');\n";




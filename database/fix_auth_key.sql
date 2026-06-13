-- Исправление проблемы с полем auth_key
-- Выполните этот скрипт, если получаете ошибку "Getting unknown property: app\models\User::auth_key"

-- Проверяем наличие поля
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'auth_key';

-- Если поле отсутствует, добавляем его
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `auth_key` VARCHAR(32) DEFAULT NULL 
AFTER `last_name`;

-- Обновляем существующих пользователей, у которых нет auth_key
UPDATE `users` 
SET `auth_key` = SUBSTRING(MD5(CONCAT(id, email, RAND())), 1, 32)
WHERE `auth_key` IS NULL OR `auth_key` = '';

-- Проверяем результат
SELECT id, email, 
       CASE 
           WHEN auth_key IS NULL OR auth_key = '' THEN 'НЕТ'
           ELSE 'ЕСТЬ'
       END as auth_key_status
FROM `users`;


-- Миграция для добавления поля auth_key в таблицу users
-- Выполните этот скрипт, если таблица users уже создана без поля auth_key

ALTER TABLE `users` 
ADD COLUMN `auth_key` VARCHAR(32) DEFAULT NULL COMMENT 'Ключ авторизации' 
AFTER `last_name`;




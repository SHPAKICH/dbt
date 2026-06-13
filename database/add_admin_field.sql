-- Добавление поля is_admin для суперюзера
-- Выполните этот скрипт для добавления поддержки админа

ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `is_admin` TINYINT(1) DEFAULT 0 COMMENT 'Суперюзер (администратор системы)' 
AFTER `is_active`;

-- Создание суперюзера admin/admin
-- Пароль: admin
INSERT INTO `users` (
    `phone`, 
    `email`, 
    `password_hash`, 
    `position`, 
    `first_name`, 
    `last_name`, 
    `is_active`,
    `is_admin`,
    `auth_key`
) VALUES (
    '+7 (999) 000-00-01',
    'admin',
    '$2y$12$1pPg38OvGfVUEBWBqpizt.cN9qUACYwuxwjDCSPGm.VUFUTJeJbru', -- хеш от 'admin'
    'manager',
    'Администратор',
    'Системы',
    1,
    1,
    SUBSTRING(MD5(RAND()), 1, 32)
) ON DUPLICATE KEY UPDATE 
    `is_admin` = 1,
    `is_active` = 1;

-- Обновление пароля для существующего admin (если есть)
UPDATE `users` 
SET 
    `password_hash` = '$2y$12$1pPg38OvGfVUEBWBqpizt.cN9qUACYwuxwjDCSPGm.VUFUTJeJbru',
    `is_admin` = 1,
    `is_active` = 1
WHERE `email` = 'admin';


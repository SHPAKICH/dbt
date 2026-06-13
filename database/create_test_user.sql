-- Быстрое создание тестового пользователя для входа
-- Выполните этот скрипт, если у вас проблемы со входом

-- Сначала создаем точку, если её нет
INSERT IGNORE INTO `locations` (`id`, `name`, `address`, `phone`, `is_active`) VALUES
(1, 'Тестовое кафе', 'Тестовый адрес', '+7 (495) 000-00-00', 1);

-- Удаляем старый тестовый пользователь, если есть
DELETE FROM `users` WHERE `email` = 'manager@cafe.ru' OR `phone` = '+7 (999) 111-11-11';

-- Создаем тестового пользователя
-- Пароль: password123
-- Хеш сгенерирован через PHP password_hash()
INSERT INTO `users` (
    `phone`, 
    `email`, 
    `password_hash`, 
    `position`, 
    `first_name`, 
    `last_name`, 
    `is_active`,
    `auth_key`
) VALUES (
    '+7 (999) 111-11-11',
    'manager@cafe.ru',
    '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S',
    'manager',
    'Иван',
    'Петров',
    1,
    SUBSTRING(MD5(RAND()), 1, 32)
);

-- Проверка созданного пользователя
SELECT 
    id,
    email,
    phone,
    position,
    first_name,
    last_name,
    is_active,
    CASE 
        WHEN auth_key IS NULL OR auth_key = '' THEN 'НЕТ (проблема!)'
        ELSE 'ЕСТЬ'
    END as auth_key_status
FROM `users` 
WHERE `email` = 'manager@cafe.ru';


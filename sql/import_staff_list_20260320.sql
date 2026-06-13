-- Импорт списка сотрудников (ФИО, должность, точка, телефон, почта) от 2026-03-20.
-- Соответствует модели app\models\User: users.position, users.location_id, first_name/last_name (первое слово ФИО → first_name).
--
-- Должности → users.position:
--   Управляющий → manager (location_id NULL, точка в manager_locations)
--   Менеджер → location_manager
--   Старший тимейкер → senior_teamaker
--   Тимейкер → teamaker
--   Партнер, Тестер → teamaker (как в import_users_from_waiters_export; без расширенных прав)
--
-- Рестораны из таблицы → canonical locations.name (см. UNION в блоке локаций ниже).
--
-- Пароль для всех новых записей: password123 (bcrypt-хеш ниже).
-- Повторный запуск: локации не дублируются (LEFT JOIN ... WHERE l.id IS NULL);
--   пользователи — INSERT IGNORE (пропуск при совпадении email или phone);
--   manager_locations — INSERT IGNORE.
SET NAMES utf8mb4;

-- 1) Локации
INSERT INTO `locations` (`name`, `address`, `phone`, `is_active`, `color`, `created_at`, `updated_at`)
SELECT src.`name`, src.`address`, src.`phone`, src.`is_active`, src.`color`, src.`created_at`, src.`updated_at`
FROM (
    SELECT '5 Столиц' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT '5е Авеню' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'АкадемПарк' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Атлантик Сити' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Бухарестская' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Василеостровский' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Галерея Чижова' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Гончарная' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Гороховая' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Горьковская' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Гулливер' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Европолис' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Жемчужная Плаза' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Июнь' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Калининград' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Каменоостровская 39' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Кирочная 27' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Колпино' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Краснознаменск' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Легенда' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Леомолл' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Ломоносова 1' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Ломоносова 12/66' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Лондон Молл' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Марата' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Мега Парнас' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Меркурий' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Мозайка' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Московская 191' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Новослободская Москва' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Омск' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Офис' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Охта Молл' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'ПИК' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Парк Победы' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Петергоф' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Петрозаводск' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'ПитерЛэнд' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Прометей' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Родео Драйв' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Сенная' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Сити молл' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Стачек' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'ТК Парнас' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'ТРК Лето' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'ТЦ Небо' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'ТЦ Радуга' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Чкаловская' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL
    SELECT 'Южный полюс' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
) src
LEFT JOIN `locations` l ON l.`name` = src.`name`
WHERE l.`id` IS NULL;

-- 2) Юзеры
INSERT IGNORE INTO `users` (`phone`, `email`, `password_hash`, `position`, `location_id`, `first_name`, `last_name`, `is_active`, `is_admin`, `auth_key`) VALUES
    ('+79006480106', 'karakhanyan_90@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Меркурий' LIMIT 1), 'Караханян', 'Елизавета', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79000455391', 'soksi.rebil@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Родео Драйв' LIMIT 1), 'Столярова', 'Анастасия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79801862370', 'fammaksim91@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5е Авеню' LIMIT 1), 'Фам', 'Максим', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79955483763', 'heyarina@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Гороховая' LIMIT 1), 'Власенко', 'Арина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79009476420', 'lomovavaria@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5 Столиц' LIMIT 1), 'Ломова', 'Варвара', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79004624321', 'rostkovasona@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Росткова', 'Софья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79616104952', 'julialot2333@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Ломоносова 1' LIMIT 1), 'Бойцева', 'Юлия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79995496769', 'iisshumilova@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Атлантик Сити' LIMIT 1), 'Шумилова', 'Ира', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79110046655', 'import+0009@placeholder.local', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Южный полюс' LIMIT 1), 'Матвеева', 'Жанна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79534645980', 'samovarinya@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Гулливер' LIMIT 1), 'Сизова', 'Милана', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79053960838', 'markova_6996@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Легенда' LIMIT 1), 'Маркова', 'Ада', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79217668244', 'alionababakova@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Лондон Молл' LIMIT 1), 'Бабакова', 'Алёна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79114241059', 'airich.alina11@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Айрих', 'Алина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79961276754', 'polinka150800@icloud.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Московская 191' LIMIT 1), 'Кушнерук', 'Полина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79117518826', 'semerenko_m@bk.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Европолис' LIMIT 1), 'Семеренко', 'Мария', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79516588285', 'kpetlyakov40@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТК Парнас' LIMIT 1), 'Петляков', 'Константин', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79218878960', 'nikitarabota352@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Южный полюс' LIMIT 1), 'Неретин', 'Никита', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79811733782', 'sorysto0o0@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Ломоносова 1' LIMIT 1), 'Бойко', 'Дарья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79215620308', 'kondratievmaksim@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПитерЛэнд' LIMIT 1), 'Кондратьев', 'Максим', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79052526350', 'augustdbush@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Жемчужная Плаза' LIMIT 1), 'Кудрявцева', 'Ульяна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79315351929', 'dsar366@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Московская 191' LIMIT 1), 'Саражина', 'Дарья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79201476181', 'ozornik6@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Родео Драйв' LIMIT 1), 'Мустафин', 'Рустам', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79181463298', 'vikaarg29012005@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Прометей' LIMIT 1), 'Аргудаева', 'Виктория', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79111942569', 'fedorsokolov56@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Леомолл' LIMIT 1), 'Соколов', 'Фёдор', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79692132772', 'taranyuk.anita@bk.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Каменоостровская 39' LIMIT 1), 'Таранюк', 'Анита', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79650019787', 'diannaqooopp@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Чкаловская' LIMIT 1), 'Герасименко', 'Диана', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79216330553', 'ega-22@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='ТРК Лето' LIMIT 1), 'Яковлев', 'Егор', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79623314546', 'yeryrad@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5 Столиц' LIMIT 1), 'Шарабарина', 'Дарья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79119357037', 'lar.sveta.ina@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Московская 191' LIMIT 1), 'Ларина', 'Светлана', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79961074908', 'margaritazu313@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Горьковская' LIMIT 1), 'Зубакова', 'Маргарита', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79046370715', 'slavikbrote@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Сенная' LIMIT 1), 'Абрамов', 'Вячеслав', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79006586470', 'yepiolu@gmail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='ТРК Лето' LIMIT 1), 'Морозова', 'Дарья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79119357513', 'natasha30042003@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Стачек' LIMIT 1), 'Петрова', 'Наталья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79919194094', 'olgapanova1620@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Петергоф' LIMIT 1), 'Панова', 'Ольга', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79522026762', 'rosahalmadova@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Каменоостровская 39' LIMIT 1), 'Халмадова', 'Роза', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79046117984', 'sol0202@bk.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Петергоф' LIMIT 1), 'Новикова', 'Ксения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79012752309', 'iakim0@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Якимова', 'Надежда', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79682642147', 'valentinaraven03@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТЦ Радуга' LIMIT 1), 'Тарасюк', 'Валентина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79602822696', 'rgul556@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='АкадемПарк' LIMIT 1), 'Смирнов', 'Богдан', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79219169442', 'anaagurzenkova@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Петергоф' LIMIT 1), 'Гурзенкова', 'Анна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79303600009', 'aller999ies@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Чкаловская' LIMIT 1), 'Фомин', 'Даниил', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79692078938', 'alexpetrova3430@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Родео Драйв' LIMIT 1), 'Петрова', 'Александра', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79112571111', 'milooovany@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Ломоносова 1' LIMIT 1), 'Агутина', 'Лада', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79313034722', 'ibulkotina@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Меркурий' LIMIT 1), 'Булкотина', 'Ирина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79994555009', 'leonid.sidorenko.1997@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Европолис' LIMIT 1), 'Сидоренко', 'Леонид', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79967457692', 'p.vyshinskaya@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Марата' LIMIT 1), 'Вышинская', 'Полина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79500473442', 'alyonagogina123@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Европолис' LIMIT 1), 'Гогина', 'Алёна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79633455550', 'alinayakhyaevaa@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Парк Победы' LIMIT 1), 'Яхьяева', 'Алина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79223320432', 'anna.lkv.28@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Лыкова', 'Анна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79202088856', 'halilovmark2@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Атлантик Сити' LIMIT 1), 'Халилов', 'Марк', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79204364050', 'taisiashalimova@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5 Столиц' LIMIT 1), 'Шалимова', 'Таисия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79992453701', 'dzaynuk013@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Парк Победы' LIMIT 1), 'Дзайнукова', 'Катерина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79118289965', 'httttsmnv@icloud.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Сити молл' LIMIT 1), 'Коваль', 'Амелия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79675387196', 'kuznetsova373737@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='ТРК Лето' LIMIT 1), 'Кузнецова', 'Светлана', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79042366776', 'datsisdiana@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Дацис', 'Диана', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79009489985', 'nkkstn@icloud.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5 Столиц' LIMIT 1), 'Зайцева', 'Анастасия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79110182656', 'misakovlev@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Сити молл' LIMIT 1), 'Яковлев', 'Михаил', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79637471261', 'spoyalova2004@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Споялова', 'Алина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79910060684', 'samplin62@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Леомолл' LIMIT 1), 'Зодлаева', 'Диана', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79677479701', 'marin4.bulga@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Жемчужная Плаза' LIMIT 1), 'Булгакова', 'Марина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79507108250', 'sprous.iza@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Галерея Чижова' LIMIT 1), 'Зорина', 'Елизавета', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79103741293', 'import+0062@placeholder.local', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Офис' LIMIT 1), 'Яровой', 'Даниил', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79608999539', 'devillnor@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Ломоносова 1' LIMIT 1), 'Сидоренко', 'Валерий', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79204439490', 'slakov2000@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5 Столиц' LIMIT 1), 'Слаков', 'Александр', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79379961880', 'mitya.goncharov.19@list.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Меркурий' LIMIT 1), 'Гончаров', 'Дмитрий', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79516520313', 'denermakiv@list.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Колпино' LIMIT 1), 'Ермаков', 'Денис', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79775105801', 'aalvegg@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Новослободская Москва' LIMIT 1), 'Нигаматулина', 'Виктория', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79675717155', 'dmitriy.vershina@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Вершинин', 'Дмитрий', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79684410839', 's.zarnitskaya.02@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='5е Авеню' LIMIT 1), 'Буркина', 'София', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79650411266', 'yudakov.vladislav@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Европолис' LIMIT 1), 'Юдаков', 'Владислав', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79675962966', 'grigor60369@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Мега Парнас' LIMIT 1), 'Карапетян', 'Григорий', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79241785503', 'duranovaveronika07@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Парк Победы' LIMIT 1), 'Дуранова', 'Вероника', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79500373710', 'cristayn.ya3eva@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Июнь' LIMIT 1), 'Язева', 'Кристина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79131873157', 'andreev.artem.25.04.2007@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Кирочная 27' LIMIT 1), 'Андреев', 'Артем', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79114695081', 'zmarkatenko@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Европолис' LIMIT 1), 'Маркатенко', 'Зоя', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79818233070', 'vasilisaignatova0409@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Июнь' LIMIT 1), 'Игнатова', 'Василиса', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79103224192', 'dashulya.usikova@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Усикова', 'Дарья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79500069948', 'stasy.enko@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='ТЦ Радуга' LIMIT 1), 'Алещенко', 'Анастасия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79531541698', 'fozilova.arina@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Марата' LIMIT 1), 'Фозилова', 'Арина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79117648411', 'arhivd2012@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Архипов', 'Иван', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79046329112', 'kseniaisakadze@icloud.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Атлантик Сити' LIMIT 1), 'Исакадзе', 'Ксения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79149742920', 'darimiko87@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Парк Победы' LIMIT 1), 'Имаева', 'Дарья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79811122927', 'nastazelenina503@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Европолис' LIMIT 1), 'Зеленина', 'Анастасия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79216476951', 'artemlezhnin210105@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТЦ Радуга' LIMIT 1), 'Лежнин', 'Артем', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79873527476', 'asemdyusenbaeva2001@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Гороховая' LIMIT 1), 'Дюсенбаева', 'Асем', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79114130773', 'arutuniankristina777@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Арутюнян', 'Кристина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79173763050', 'zbatyr88@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТРК Лето' LIMIT 1), 'Уланов', 'Батыр', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79081400338', 'angelinanoskova4@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Кирочная 27' LIMIT 1), 'Носкова', 'Ангелина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79219440175', 'kseniasaitova@dbtea.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Саитова', 'Ксения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79110113661', 'evaclifford53@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Сенная' LIMIT 1), 'Захарова', 'Полина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79503881881', 'itryujisan@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Гончарная' LIMIT 1), 'Санжиева', 'Арюна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79914871210', 'irishkinsss@icloud.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Прометей' LIMIT 1), 'Боброва', 'Ирина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79523868889', 'unicornny@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='ТЦ Радуга' LIMIT 1), 'Куралева', 'Мария', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79532366434', 'igorshaba1980@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Чкаловская' LIMIT 1), 'Андреева', 'Марина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79204205839', 'anastasiya-smirnova-07@bk.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5 Столиц' LIMIT 1), 'Смирнова', 'Анастасия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79522449642', 'shadrunovave@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Колпино' LIMIT 1), 'Шадрунова', 'Вероника', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79531476340', 'aizirekmamanova14@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Сенная' LIMIT 1), 'Маманова', 'Айзирек', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79890811875', 'jenett789@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТК Парнас' LIMIT 1), 'Гаджиева', 'Дженетт', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79871175905', 'msobolev111@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПитерЛэнд' LIMIT 1), 'Соболев', 'Михаил', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79111184634', 'leksakova2005@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Лондон Молл' LIMIT 1), 'Лексакова', 'Кристина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79216580767', 'belinskijdaniil.777@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТЦ Небо' LIMIT 1), 'Спиридонов', 'Данила', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79185097013', 'ilyalee06@icloud.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТЦ Небо' LIMIT 1), 'Ли', 'Илья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79192202264', 'prisuxina05@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Ломоносова 12/66' LIMIT 1), 'Присухина', 'Ксения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79142525077', 'dariabalahon@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Сити молл' LIMIT 1), 'Балахонская', 'Дарья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79378648001', 'nkimono95@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Жемчужная Плаза' LIMIT 1), 'Калабухова', 'Анастасия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79990000106', 'gelatikhonova@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Южный полюс' LIMIT 1), 'Тихонова', 'Ангелина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79817102829', 'evrick.13d13@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Меркурий' LIMIT 1), 'Эрик', 'Диана', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79114250380', 'lizkun97@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Кунгурова', 'Елизавета', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79526681886', 'barrbyyvvv@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Европолис' LIMIT 1), 'Гусинская', 'Василиса', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79650670713', 'nemoy.introvert@gmail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Июнь' LIMIT 1), 'Ленивко', 'Илья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79897760026', 'ssnejana2008@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Родео Драйв' LIMIT 1), 'Самойлова', 'Снежана', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79022182547', 'zhanmyorly@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Сенная' LIMIT 1), 'Терехина', 'Жанна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79996551921', 'valeriaantia018@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Ломоносова 12/66' LIMIT 1), 'Антия', 'Валерия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79535430375', 'spllushqa@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Ласточкина', 'Александра', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79509811204', 'taisia377@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Атлантик Сити' LIMIT 1), 'Грибалева', 'Таисия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79652452407', 'ssliva.ss@bk.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Гороховая' LIMIT 1), 'Кирштейн', 'Евгения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79603642584', 'vikanaumenko060505@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Лондон Молл' LIMIT 1), 'Науменко', 'Виктория', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79642958326', 'kiprianovaanzhelika@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Жемчужная Плаза' LIMIT 1), 'Киприянова', 'Анжелика', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79874884851', 'import+0119@placeholder.local', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Лондон Молл' LIMIT 1), 'Ибрагимов', 'Рафаэль', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79531401475', 'vishnevskii.nik2003@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Стачек' LIMIT 1), 'Ботнаренко', 'Никита', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79129696786', 'nik.k2002@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Гончарная' LIMIT 1), 'Ковалев', 'Никита', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79992016113', 'travkin.10@inbox.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Меркурий' LIMIT 1), 'Травкин', 'Вячеслав', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79522660005', 'nataliagrisina0411@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Стачек' LIMIT 1), 'Гришина', 'Наталия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79819421604', 'import+0124@placeholder.local', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='ТЦ Радуга' LIMIT 1), 'Юрченко', 'Светлана', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79939584054', 'jgrmkrv@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Чкаловская' LIMIT 1), 'Макаров', 'Егор', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79291047705', 'import+0126@placeholder.local', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Стачек' LIMIT 1), 'Панкратьева', 'Дарина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79602712980', 'koulpolina@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПИК' LIMIT 1), 'Герасимова', 'Полина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79617169294', 'e.laymina2000@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Прометей' LIMIT 1), 'Лямина', 'Елена', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79950878931', 'sonya_novikova2019@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Омск' LIMIT 1), 'Новикова', 'Софья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79533687982', 'zverkololitata@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Южный полюс' LIMIT 1), 'Зверко', 'Лолита', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79819831595', 'vlada.vladaka@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Бухарестская' LIMIT 1), 'Ахметзянова', 'Влада', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79384302960', 'vasilisam297@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПИК' LIMIT 1), 'Макарова', 'Василиса', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79500160965', 'import+0133@placeholder.local', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Парк Победы' LIMIT 1), 'Потанина', 'Анна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79291036360', 'egorhamatkoev04@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПитерЛэнд' LIMIT 1), 'Хаматкоев', 'Егор', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79045130975', 'malcevat653@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТЦ Радуга' LIMIT 1), 'Мальцева', 'Татьяна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79997001617', 'alanaswan99@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Ломоносова 12/66' LIMIT 1), 'Сердечнева', 'Алена', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79110639917', 'sonismetanina@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Колпино' LIMIT 1), 'Сметанина', 'София', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79213565744', 'nikol.e.iger@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Прометей' LIMIT 1), 'Эйгер', 'Николь', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79258686248', 'khramslili@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Меркурий' LIMIT 1), 'Храмцова', 'Лилиана', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79668788124', 'mari.shuu@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='АкадемПарк' LIMIT 1), 'Шубина', 'Мария', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79882521066', 'abegale.homma@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТРК Лето' LIMIT 1), 'Собянина', 'Валерия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79112985898', 'import+0142@placeholder.local', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Ломоносова 12/66' LIMIT 1), 'Прохорова', 'Евгения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79514001488', 'ksenyaevd2002@icloud.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Легенда' LIMIT 1), 'Евдокимова', 'Ксения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79966839055', 'twinkle_es@bk.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Атлантик Сити' LIMIT 1), 'Самарцева', 'Елена', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79688969644', 'julee.basova04@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Новослободская Москва' LIMIT 1), 'Басова', 'Юлия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79633049435', 'margo.golovina00@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='АкадемПарк' LIMIT 1), 'Головина', 'Маргарита', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79990000147', 'maroz.1999@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПИК' LIMIT 1), 'Морозова', 'Елена', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79967973948', 'ponomareva_ksenia06@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Чкаловская' LIMIT 1), 'Пономарева', 'Ксения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79811253937', 'yana.rezanova.91@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='АкадемПарк' LIMIT 1), 'Резанова', 'Яна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79533505890', 'max.lisa.w@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТЦ Небо' LIMIT 1), 'Максимова', 'Елизавета', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79535323533', 'plesanovaregina@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Плешанова', 'Регина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79628430470', 'olhaguzel04@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='АкадемПарк' LIMIT 1), 'Гузель', 'Ольга', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79222474607', 'aleksandrdepner@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Лондон Молл' LIMIT 1), 'Дмитрий', 'Кузнецов', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79821235609', 'zavalina-anna42@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Горьковская' LIMIT 1), 'Завалина', 'Анна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79110998225', 'dandplat1980@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПитерЛэнд' LIMIT 1), 'Платонов', 'Даниил', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79815441005', 'sm1rnova192005@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТРК Лето' LIMIT 1), 'Смирнова', 'Александра', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79260362636', 'alex.dolich.005@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Мозайка' LIMIT 1), 'Долич', 'Александра', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79111576427', 'tusevich.twins@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Василеостровский' LIMIT 1), 'Карпусь', 'Наталья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79114118771', 'vulana147@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Васильева', 'Ульяна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79277782301', 'skovorodka.u@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПИК' LIMIT 1), 'Сковородская', 'Ульяна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79811277489', 'asya.gnatyuk.06@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Каменоостровская 39' LIMIT 1), 'Гнатюк', 'Анастасия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79632784454', 'sonooy3@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПИК' LIMIT 1), 'Холина', 'София', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79995368388', 'surkova_0606@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Кирочная 27' LIMIT 1), 'Суркова', 'Мария', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79822667242', 'fovaek@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Чкаловская' LIMIT 1), 'Кривощекова', 'Виктория', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79281946711', 'yelisaveta.shulga.11@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Прометей' LIMIT 1), 'Шульга', 'Елизавета', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79312610455', 'nchueeshkov@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Меркурий' LIMIT 1), 'Чуешков', 'Никита', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79967767998', 'n.tasha.she@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Колпино' LIMIT 1), 'Шевцова', 'Наталия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79046125331', 'gttdmusical7@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Горьковская' LIMIT 1), 'Литвинова', 'Юлия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79024782246', 'import+0169@placeholder.local', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Офис' LIMIT 1), 'Лопатина', 'Ксения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79697069370', 'maslakov.daniel@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Мега Парнас' LIMIT 1), 'Маслаков', 'Даниил', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79671001523', 'lerrmontovva@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5е Авеню' LIMIT 1), 'Кузнецова', 'Маргарита', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79215298671', 'xelylia1337@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Охта Молл' LIMIT 1), 'Пенцов', 'Степан', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79118530203', 'ntkcoach@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Пермякова', 'Анна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79110147336', 'd-chueva@list.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Гончарная' LIMIT 1), 'Чуева', 'Дарья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79602689784', 'denisfrosty@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Ломоносова 12/66' LIMIT 1), 'Писарев', 'Денис', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79118890711', 'jane_nikolaeva@bk.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПИК' LIMIT 1), 'Николаева', 'Евгения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79006332315', 'kkarina.kormich@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Сенная' LIMIT 1), 'Кормич', 'Карина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79939341097', 'aleksj.a@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Легенда' LIMIT 1), 'Швицгебель', 'Александр', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79581736441', 'hh3r3w3g0aga1n@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Прометей' LIMIT 1), 'Воскресенский', 'Филипп', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79205727094', 'iamnastyyyaaa@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПИК' LIMIT 1), 'Артамонова', 'Анастасия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79082912776', 'am73336858@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТЦ Небо' LIMIT 1), 'Михайлова', 'Яна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79600911874', 'marselan@bk.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5 Столиц' LIMIT 1), 'Сметанкина', 'Юлия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79817500505', 'karinaspec@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Июнь' LIMIT 1), 'Калантарян', 'Карина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79254706189', 'maripenushka717@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Новослободская Москва' LIMIT 1), 'Муратова', 'Мария', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79516798347', 'anekrebenok@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПИК' LIMIT 1), 'Ребенок', 'Анна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79990000186', 'dragalinadara0@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПИК' LIMIT 1), 'Драгалина', 'Дарья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79084773795', 'jima19921993@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Гончарная' LIMIT 1), 'житченко', 'варвара', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79316162203', 'asha2003.as@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Шабловская', 'Алина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79205031796', 'lesyaschneider36@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5 Столиц' LIMIT 1), 'Чернова', 'Олеся', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79052572576', 'opc-fedosimova@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Басалык', 'Евгения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79375410007', 'sam.noise@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Южный полюс' LIMIT 1), 'Шумов', 'Семен', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79992477300', 'gldmvr@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Каменоостровская 39' LIMIT 1), 'Вронский', 'Глеб', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79119859805', 'mariya.tsv05@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Атлантик Сити' LIMIT 1), 'Цветкова', 'Мария', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79211814376', 'ms_l4@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Сенная' LIMIT 1), 'лебедева', 'мария', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79214575150', 'polinapomoshchikova@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Помощикова', 'Полина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79253856288', 'anguyiris@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Стачек' LIMIT 1), 'Иргашева', 'Ангелина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79814069428', 'dbtea.ptz@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Ткаченко', 'Алина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79112972037', 'china_fire_dragon@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Июнь' LIMIT 1), 'Якубова', 'Инна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79220800899', 'polltos00@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Ломоносова 1' LIMIT 1), 'Павлова', 'Полина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79601255553', 'ekaterinakunichkina228@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5 Столиц' LIMIT 1), 'Куничкина', 'Екатерина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79263111345', 'vik.vid.07@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5е Авеню' LIMIT 1), 'Виданова', 'Вика', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79050512876', 'dimatkacenko676@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='5 Столиц' LIMIT 1), 'Ткаченко', 'Дмитрий', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79315353816', 'valeriayuganova@list.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Каменоостровская 39' LIMIT 1), 'Юганова', 'Валерия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79038968222', 'dwple@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Московская 191' LIMIT 1), 'Беляев', 'Кирилл', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79780317944', 'malinkavi6@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Марата' LIMIT 1), 'Таринская', 'Алина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79219416103', 'filippkarpinskiy@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Каменоостровская 39' LIMIT 1), 'Карпинский', 'Филипп', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79112706265', 'yana.slavinskaya2007@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Кирочная 27' LIMIT 1), 'Славинская', 'Яна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79818251880', 'milona_oiyalina7@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Леомолл' LIMIT 1), 'Коновалова', 'Алина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79939853340', 'paveleal@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Родео Драйв' LIMIT 1), 'Ганичев', 'Анатолий', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79004551708', 'danilarasputin81@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Петрозаводск' LIMIT 1), 'Распутин', 'Данила', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79203140544', 'polinakim3453@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Кирочная 27' LIMIT 1), 'Ковалева', 'Полина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79211368874', 'ksenyapriiihodko@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Гороховая' LIMIT 1), 'Приходько', 'Ксения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79503361913', 'ubykova63@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Василеостровский' LIMIT 1), 'Быкова', 'Юлия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79775696418', 'kvas.fu@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Новослободская Москва' LIMIT 1), 'Бахнарь', 'Елизавета', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79118354917', 'kuznitsova.kristina_1234567@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Марата' LIMIT 1), 'Иванова', 'Кристина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79264032668', 'valeriya-narzieva@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Нарзиева', 'Валерия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79939294699', 'blinblaa@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Кочарян', 'Эдгар', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79134992324', 'ermakv81@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', (SELECT id FROM locations WHERE name='Леомолл' LIMIT 1), 'Ермак', 'Виктория', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79669240233', 'kesterbreger@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Европолис' LIMIT 1), 'Андрианова', 'Екатерина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79043214744', 'nastena6913@bk.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Омск' LIMIT 1), 'Кондратова', 'Анастасия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79229808301', '1712sashakozlova02@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Родео Драйв' LIMIT 1), 'Козлова', 'Александра', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79397097309', 'vovasubbotin809@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Каменоостровская 39' LIMIT 1), 'Субботин', 'Владимир', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79213621160', 'vetasizikova@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Охта Молл' LIMIT 1), 'Кострица', 'Елизавета', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79222308656', 'mariakurandina997@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Горьковская' LIMIT 1), 'Курандина', 'Мария', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79936399287', 'ender_fox19@list.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ПитерЛэнд' LIMIT 1), 'Михайлова', 'Татьяна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79111231345', 'keskahueska@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Мега Парнас' LIMIT 1), 'Щедрин', 'Кирилл', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79530360439', 'amonona.ru@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Галерея Чижова' LIMIT 1), 'Титов', 'Сергей', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79121495725', 'qwerq2819@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='ТЦ Небо' LIMIT 1), 'Кириллова', 'Валерия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79643791703', 'mihoteev@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Мега Парнас' LIMIT 1), 'Иванюшинко', 'Михаил', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79780263929', 'rat02vika@inbox.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Леомолл' LIMIT 1), 'Виктория', 'Рат', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79001261505', 'kira.gunkina@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Легенда' LIMIT 1), 'Гунькина', 'Кира', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79814209446', 'shepp@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Горьковская' LIMIT 1), 'Шепринская', 'Мария', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79644281635', 'ivmafe@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Мозайка' LIMIT 1), 'Иванова', 'Мария', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79081455621', 'nadiamalygina1719@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Галерея Чижова' LIMIT 1), 'Малыгина', 'Надежда', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79811546460', 'demoninmymind.st@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Июнь' LIMIT 1), 'Мосягин', 'Никита', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79522229181', 'polinaaleksandrova93familia@yandex.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Колпино' LIMIT 1), 'Александрова', 'Полина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79012003822', 'ertineevgenia7v@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Гончарная' LIMIT 1), 'Эртине', 'Евгения', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79939814394', 'angelinakoloskova960@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Июнь' LIMIT 1), 'Колоскова', 'Ангелина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79272243638', 'karinchikzaya@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Жемчужная Плаза' LIMIT 1), 'Зайцева', 'Карина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79610673425', 'diana2005zelenova@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Бухарестская' LIMIT 1), 'Зеленова', 'Диана', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79819746233', 'alina180181@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Горьковская' LIMIT 1), 'Королева', 'Алина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79043385877', 'isinukov221@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Каменоостровская 39' LIMIT 1), 'Синюков', 'Илья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79148168490', 'kotova.alyona29@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Ломоносова 12/66' LIMIT 1), 'Коряко', 'Алёна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79525502622', 'mp33312@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Галерея Чижова' LIMIT 1), 'Павлов', 'Максим', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79811234475', 'dulmapiter@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Гулливер' LIMIT 1), 'Баранова', 'Дулма', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79062252702', 'varmoka02@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='АкадемПарк' LIMIT 1), 'Корнева', 'Варвара', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79179544918', 'bryanskaya_lera@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Новослободская Москва' LIMIT 1), 'Брянская', 'Валерия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79111486638', 'iuriiprikhodko1237@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', (SELECT id FROM locations WHERE name='Чкаловская' LIMIT 1), 'Приходько', 'Юрий', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79818625916', 'moodme_ivansokolov@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Сити молл' LIMIT 1), 'Соколов', 'Иван', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79963978722', 'as89963978722@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Сорокина', 'Анна', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79516870611', 'korolenkonatasha91@gmail.com', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', NULL, 'Короленко', 'Наталья', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79819422741', 'iphonepro11@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Гончарная' LIMIT 1), 'Веннерхолм', 'Екатерина', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32)),
    ('+79990638775', '89990638775@mail.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', (SELECT id FROM locations WHERE name='Европолис' LIMIT 1), 'Телючик', 'Анастасия', 1, 0, LEFT(REPLACE(UUID(), '-', ''), 32));

-- 3) Привязки управляющих
INSERT IGNORE INTO `manager_locations` (`manager_id`, `location_id`, `created_at`) VALUES
    ((SELECT id FROM users WHERE email='anna.lkv.28@gmail.com' AND phone='+79223320432' LIMIT 1), (SELECT id FROM locations WHERE name='Офис' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='datsisdiana@gmail.com' AND phone='+79042366776' LIMIT 1), (SELECT id FROM locations WHERE name='Мозайка' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='dmitriy.vershina@gmail.com' AND phone='+79675717155' LIMIT 1), (SELECT id FROM locations WHERE name='Московская 191' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='dashulya.usikova@yandex.ru' AND phone='+79103224192' LIMIT 1), (SELECT id FROM locations WHERE name='ТРК Лето' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='arhivd2012@gmail.com' AND phone='+79117648411' LIMIT 1), (SELECT id FROM locations WHERE name='Офис' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='kseniasaitova@dbtea.ru' AND phone='+79219440175' LIMIT 1), (SELECT id FROM locations WHERE name='Офис' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='ntkcoach@yandex.ru' AND phone='+79118530203' LIMIT 1), (SELECT id FROM locations WHERE name='Калининград' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='asha2003.as@gmail.com' AND phone='+79316162203' LIMIT 1), (SELECT id FROM locations WHERE name='Калининград' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='opc-fedosimova@yandex.ru' AND phone='+79052572576' LIMIT 1), (SELECT id FROM locations WHERE name='Кирочная 27' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='valeriya-narzieva@yandex.ru' AND phone='+79264032668' LIMIT 1), (SELECT id FROM locations WHERE name='Краснознаменск' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='blinblaa@mail.ru' AND phone='+79939294699' LIMIT 1), (SELECT id FROM locations WHERE name='Московская 191' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='as89963978722@gmail.com' AND phone='+79963978722' LIMIT 1), (SELECT id FROM locations WHERE name='Офис' LIMIT 1), NOW()),
    ((SELECT id FROM users WHERE email='korolenkonatasha91@gmail.com' AND phone='+79516870611' LIMIT 1), (SELECT id FROM locations WHERE name='Парк Победы' LIMIT 1), NOW());

-- Диагностика (при ручном запуске в клиенте):
-- SELECT COUNT(*) AS imported_placeholder_emails FROM `users` WHERE `email` LIKE 'import+%@placeholder.local';
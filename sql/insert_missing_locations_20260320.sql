-- Недостающие локации для импорта пользователей из waiters_export.
-- Формат под текущую таблицу locations:
-- name, address, phone, is_active, color, created_at, updated_at
--
-- Важно:
-- - address и phone сейчас заполняются NULL, потому что их нет в исходном CSV.
-- - Скрипт безопасен для повторного запуска: не вставляет точку, если такое name уже есть.

SET NAMES utf8mb4;

INSERT INTO `locations` (`name`, `address`, `phone`, `is_active`, `color`, `created_at`, `updated_at`)
SELECT
    src.`name`,
    src.`address`,
    src.`phone`,
    src.`is_active`,
    src.`color`,
    src.`created_at`,
    src.`updated_at`
FROM (
    SELECT '5 Столиц' AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`
    UNION ALL SELECT 'АкадемПарк', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Василеостровский', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Галерея Чижова', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Гончарная', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Горьковская', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Гулливер', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Европолис', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Июнь', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Калининград', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Каменоостровская 39', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Кирочная 27', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Колпино', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Краснознаменск', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Мега Парнас', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Московская 191', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Новослободская Москва', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Омск', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Офис', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Охта Молл', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'ПИК', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Парк Победы', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Петергоф', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Петрозаводск', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'ПитерЛэнд', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Родео Драйв', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Сенная', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Сити молл', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'ТК Парнас', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'ТЦ Небо', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
    UNION ALL SELECT 'Южный полюс', NULL, NULL, 1, '#2b2b2b', NOW(), NOW()
) AS src
LEFT JOIN `locations` l
    ON l.`name` = src.`name`
WHERE l.`id` IS NULL;

SELECT `id`, `name`, `address`, `phone`, `is_active`, `color`, `created_at`, `updated_at`
FROM `locations`
WHERE `name` IN (
    '5 Столиц',
    'АкадемПарк',
    'Василеостровский',
    'Галерея Чижова',
    'Гончарная',
    'Горьковская',
    'Гулливер',
    'Европолис',
    'Июнь',
    'Калининград',
    'Каменоостровская 39',
    'Кирочная 27',
    'Колпино',
    'Краснознаменск',
    'Мега Парнас',
    'Московская 191',
    'Новослободская Москва',
    'Омск',
    'Офис',
    'Охта Молл',
    'ПИК',
    'Парк Победы',
    'Петергоф',
    'Петрозаводск',
    'ПитерЛэнд',
    'Родео Драйв',
    'Сенная',
    'Сити молл',
    'ТК Парнас',
    'ТЦ Небо',
    'Южный полюс'
)
ORDER BY `name`;

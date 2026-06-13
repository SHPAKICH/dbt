-- Импорт пользователей из файла:
-- C:/Users/Ира/Downloads/546390_waiters_export - employees.csv
--
-- Что делает скрипт:
-- 1. Загружает CSV во временную таблицу.
-- 2. Маппит русские должности в enum users.position.
-- 3. Маппит названия ресторанов в canonical locations.name.
-- 4. Создает пользователей, которых еще нет по email/phone.
-- 5. Для manager создает связи в manager_locations.
-- 6. Показывает диагностику по импортированным, пропущенным и проблемным строкам.
--
-- Принятые допущения:
-- - Пароль для всех импортированных пользователей: password123
-- - Должности "Партнер" и "Тестер" импортируются как "teamaker"
--   чтобы не выдавать лишние права автоматически.
-- - Если email или phone отсутствует, генерируется техническое значение,
--   чтобы загрузить всех пользователей без нарушения NOT NULL / UNIQUE.
-- - Если точка не найдена в locations, пользователь все равно создается
--   с location_id = NULL (для последующего ручного доназначения).

SET NAMES utf8mb4;

SET @default_password_hash := '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S';

DROP TEMPORARY TABLE IF EXISTS tmp_user_import_raw;
CREATE TEMPORARY TABLE tmp_user_import_raw (
    row_num INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fio_raw VARCHAR(255) NULL,
    position_raw VARCHAR(255) NULL,
    restaurant_raw VARCHAR(255) NULL,
    phone_raw VARCHAR(64) NULL,
    email_raw VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOAD DATA LOCAL INFILE 'C:/Users/Ира/Downloads/546390_waiters_export - employees.csv'
INTO TABLE tmp_user_import_raw
CHARACTER SET utf8mb4
FIELDS TERMINATED BY ','
OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 3 LINES
(fio_raw, position_raw, restaurant_raw, phone_raw, email_raw);

DROP TEMPORARY TABLE IF EXISTS tmp_position_map;
CREATE TEMPORARY TABLE tmp_position_map (
    position_ru VARCHAR(255) NOT NULL PRIMARY KEY,
    position_code VARCHAR(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_position_map (position_ru, position_code) VALUES
('Менеджер', 'location_manager'),
('Партнер', 'teamaker'),
('Старший тимейкер', 'senior_teamaker'),
('Тестер', 'teamaker'),
('Тимейкер', 'teamaker'),
('Управляющий', 'manager');

DROP TEMPORARY TABLE IF EXISTS tmp_location_map;
CREATE TEMPORARY TABLE tmp_location_map (
    alias_name VARCHAR(255) NOT NULL PRIMARY KEY,
    canonical_name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_location_map (alias_name, canonical_name) VALUES
('DBT 5 Столиц', '5 Столиц'),
('DBT Атлантик Сити', 'Атлантик Сити'),
('DBT Василеостровский', 'Василеостровский'),
('DBT Галерея Чижова', 'Галерея Чижова'),
('DBT Гончарная', 'Гончарная'),
('DBT Гороховая', 'Гороховая'),
('DBT Горьковская', 'Горьковская'),
('DBT Гулливер', 'Гулливер'),
('DBT Европолиc', 'Европолис'),
('DBT Жемчужная Плаза', 'Жемчужная Плаза'),
('DBT Июнь', 'Июнь'),
('DBT Калининград', 'Калининград'),
('DBT Каменоостровская 39', 'Каменоостровская 39'),
('DBT Кирочная 27', 'Кирочная 27'),
('DBT Колпино', 'Колпино'),
('DBT Краснознаменск', 'Краснознаменск'),
('DBT Легенда', 'Легенда'),
('DBT Леомолл', 'Леомолл'),
('DBT Лондон Молл', 'Лондон Молл'),
('DBT Марата', 'Марата'),
('DBT Мега Парнас', 'Мега Парнас'),
('DBT Меркурий', 'Меркурий'),
('DBT Московская 191', 'Московская 191'),
('DBT Новослободская Москва', 'Новослободская Москва'),
('DBT Омск', 'Омск'),
('DBT Охта Молл', 'Охта Молл'),
('DBT ПИК', 'ПИК'),
('DBT Парк Победы', 'Парк Победы'),
('DBT Петергоф', 'Петергоф'),
('DBT Петрозаводск', 'Петрозаводск'),
('DBT ПитерЛэнд', 'ПитерЛэнд'),
('DBT Радуга', 'ТЦ Радуга'),
('DBT Родео Драйв', 'Родео Драйв'),
('DBT Южный полюс', 'Южный полюс'),
('DBt Ломоносова 1', 'Ломоносова 1'),
('DBt Пр-кт Стачек', 'Стачек'),
('DBt Сенная', 'Сенная'),
('DBt Сити молл', 'Сити молл'),
('DBt ТРК Лето', 'ТРК Лето'),
('DBt Ул. Бухарестская', 'Бухарестская'),
('DBt Чкаловская', 'Чкаловская'),
('DBt ломо', 'Ломоносова 12/66'),
('Dbt 5-ая Авеню', '5е Авеню'),
('Dbt АкадемПарк', 'АкадемПарк'),
('Dbt Мозайка', 'Мозайка'),
('Dbt прометей', 'Прометей'),
('Офис', 'Офис'),
('ТК Парнас', 'ТК Парнас'),
('ТЦ Небо', 'ТЦ Небо');

DROP TEMPORARY TABLE IF EXISTS tmp_user_import_prepared;
CREATE TEMPORARY TABLE tmp_user_import_prepared AS
SELECT
    s.row_num,
    s.fio_clean,
    SUBSTRING_INDEX(s.fio_clean, ' ', 1) AS first_name,
    NULLIF(TRIM(SUBSTRING(s.fio_clean, CHAR_LENGTH(SUBSTRING_INDEX(s.fio_clean, ' ', 1)) + 2)), '') AS last_name,
    s.position_ru,
    pm.position_code,
    s.restaurant_alias,
    lm.canonical_name AS location_name,
    loc.id AS location_id,
    CASE
        WHEN s.email_clean <> '' THEN LOWER(s.email_clean)
        ELSE CONCAT('import+', LPAD(s.row_num, 4, '0'), '@placeholder.local')
    END AS final_email,
    CASE
        WHEN s.phone_digits = '' THEN CONCAT('+7999', LPAD(s.row_num, 7, '0'))
        WHEN CHAR_LENGTH(s.phone_digits) = 10 THEN CONCAT('+7', s.phone_digits)
        WHEN CHAR_LENGTH(s.phone_digits) = 11 AND LEFT(s.phone_digits, 1) IN ('7', '8') THEN CONCAT('+7', RIGHT(s.phone_digits, 10))
        ELSE CONCAT('+', s.phone_digits)
    END AS final_phone,
    CASE WHEN s.email_clean = '' THEN 1 ELSE 0 END AS generated_email,
    CASE WHEN s.phone_digits = '' THEN 1 ELSE 0 END AS generated_phone
FROM (
    SELECT
        r.row_num,
        TRIM(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(IFNULL(r.fio_raw, ''), '\r', ''),
                    '\n', ''),
                '  ', ' '),
            '  ', ' ')
        ) AS fio_clean,
        TRIM(
            REPLACE(
                REPLACE(
                    REPLACE(IFNULL(r.position_raw, ''), '\r', ''),
                '\n', ''),
            '  ', ' ')
        ) AS position_ru,
        TRIM(
            REPLACE(
                REPLACE(
                    REPLACE(IFNULL(r.restaurant_raw, ''), '\r', ''),
                '\n', ''),
            '  ', ' ')
        ) AS restaurant_alias,
        LOWER(TRIM(IFNULL(r.email_raw, ''))) AS email_clean,
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(IFNULL(r.phone_raw, '')), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') AS phone_digits
    FROM tmp_user_import_raw r
) s
LEFT JOIN tmp_position_map pm
    ON pm.position_ru = s.position_ru
LEFT JOIN tmp_location_map lm
    ON lm.alias_name = s.restaurant_alias
LEFT JOIN locations loc
    ON loc.name = lm.canonical_name;

ALTER TABLE tmp_user_import_prepared
    ADD PRIMARY KEY (row_num);

DROP TEMPORARY TABLE IF EXISTS tmp_existing_users_snapshot;
CREATE TEMPORARY TABLE tmp_existing_users_snapshot AS
SELECT
    id,
    email,
    phone
FROM users;

SET @inserted_users := 0;
SET @inserted_manager_links := 0;

INSERT INTO users (
    phone,
    email,
    password_hash,
    position,
    location_id,
    first_name,
    last_name,
    is_active,
    is_admin,
    auth_key
)
SELECT
    p.final_phone,
    p.final_email,
    @default_password_hash,
    p.position_code,
    CASE
        WHEN p.position_code = 'manager' THEN NULL
        ELSE p.location_id
    END AS user_location_id,
    p.first_name,
    p.last_name,
    1,
    0,
    LEFT(REPLACE(UUID(), '-', ''), 32)
FROM tmp_user_import_prepared p
LEFT JOIN tmp_existing_users_snapshot ue
    ON ue.email = p.final_email
LEFT JOIN tmp_existing_users_snapshot up
    ON up.phone = p.final_phone
WHERE p.position_code IS NOT NULL
  AND ue.id IS NULL
  AND up.id IS NULL;

SET @inserted_users := ROW_COUNT();

INSERT IGNORE INTO manager_locations (manager_id, location_id)
SELECT
    u.id,
    p.location_id
FROM tmp_user_import_prepared p
JOIN users u
    ON u.email = p.final_email
   AND u.phone = p.final_phone
LEFT JOIN manager_locations ml
    ON ml.manager_id = u.id
   AND ml.location_id = p.location_id
WHERE p.position_code = 'manager'
  AND p.location_id IS NOT NULL
  AND ml.id IS NULL;

SET @inserted_manager_links := ROW_COUNT();

SELECT
    COUNT(*) AS csv_rows,
    SUM(CASE WHEN generated_email = 1 THEN 1 ELSE 0 END) AS generated_emails,
    SUM(CASE WHEN generated_phone = 1 THEN 1 ELSE 0 END) AS generated_phones,
    SUM(CASE WHEN position_code IS NULL THEN 1 ELSE 0 END) AS unresolved_positions,
    SUM(CASE WHEN location_name IS NULL THEN 1 ELSE 0 END) AS unmapped_restaurant_aliases,
    SUM(CASE WHEN location_name IS NOT NULL AND location_id IS NULL THEN 1 ELSE 0 END) AS missing_locations_in_db,
    @inserted_users AS inserted_users,
    @inserted_manager_links AS inserted_manager_locations
FROM tmp_user_import_prepared;

SELECT
    row_num,
    fio_clean,
    position_ru,
    restaurant_alias,
    'Не удалось сматчить должность' AS issue
FROM tmp_user_import_prepared
WHERE position_code IS NULL
ORDER BY row_num;

SELECT
    restaurant_alias,
    location_name,
    COUNT(*) AS affected_rows
FROM tmp_user_import_prepared
WHERE location_name IS NULL
   OR (location_name IS NOT NULL AND location_id IS NULL)
GROUP BY restaurant_alias, location_name
ORDER BY affected_rows DESC, restaurant_alias;

SELECT
    p.row_num,
    p.fio_clean,
    p.final_email,
    p.final_phone,
    CASE
        WHEN ue.id IS NOT NULL THEN CONCAT('Конфликт по email, users.id=', ue.id)
        WHEN up.id IS NOT NULL THEN CONCAT('Конфликт по phone, users.id=', up.id)
        ELSE NULL
    END AS conflict_reason
FROM tmp_user_import_prepared p
LEFT JOIN tmp_existing_users_snapshot ue
    ON ue.email = p.final_email
LEFT JOIN tmp_existing_users_snapshot up
    ON up.phone = p.final_phone
WHERE ue.id IS NOT NULL
   OR up.id IS NOT NULL
ORDER BY p.row_num;

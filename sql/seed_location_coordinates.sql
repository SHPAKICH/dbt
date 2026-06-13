-- Координаты точек для карты аналитики.
-- Требует колонок lat/lng (миграция m260605_120000_add_location_coordinates).
-- Безопасно для повторного запуска.

SET NAMES utf8mb4;

UPDATE `locations` SET `lat` = 59.849583, `lng` = 30.143701, `address` = 'Санкт-Петербург, Петергофское шоссе, 51 (Жемчужная Plaza)' WHERE `name` = 'Жемчужная Плаза';
UPDATE `locations` SET `lat` = 59.858180, `lng` = 30.247402, `address` = 'Санкт-Петербург, проспект Стачек, 99 (ТРК Континент)' WHERE `name` = 'Стачек';
UPDATE `locations` SET `lat` = 59.820204, `lng` = 30.315764, `address` = 'Санкт-Петербург, Пулковское шоссе, 25, к. 1 (ТРК Лето)' WHERE `name` = 'ТРК Лето';
UPDATE `locations` SET `lat` = 59.852748, `lng` = 30.320832, `address` = 'Санкт-Петербург, Московский проспект, 191' WHERE `name` = 'Московская 191';
UPDATE `locations` SET `lat` = 59.869998, `lng` = 30.348304, `address` = 'Санкт-Петербург, проспект Космонавтов, 14 (ТЦ Питер Радуга)' WHERE `name` = 'ТЦ Радуга';
UPDATE `locations` SET `lat` = 59.884009, `lng` = 30.370015, `address` = 'Санкт-Петербург, Бухарестская улица, 32 (ТЦ Континент)' WHERE `name` = 'Бухарестская';
UPDATE `locations` SET `lat` = 59.912152, `lng` = 30.447622, `address` = 'Санкт-Петербург, улица Коллонтай, 3Б (ТЦ London Mall)' WHERE `name` = 'Лондон Молл';
UPDATE `locations` SET `lat` = 59.930362, `lng` = 30.363695, `address` = 'Санкт-Петербург, улица Гончарная' WHERE `name` = 'Гончарная';
UPDATE `locations` SET `lat` = 59.930906, `lng` = 30.355265, `address` = 'Санкт-Петербург, улица Марата' WHERE `name` = 'Марата';
UPDATE `locations` SET `lat` = 59.928156, `lng` = 30.336884, `address` = 'Санкт-Петербург, улица Ломоносова, 12/66' WHERE `name` = 'Ломоносова 12/66';
UPDATE `locations` SET `lat` = 59.931880, `lng` = 30.328807, `address` = 'Санкт-Петербург, набережная канала Грибоедова, 30-32 (Ломоносова 1)' WHERE `name` = 'Ломоносова 1';
UPDATE `locations` SET `lat` = 59.934755, `lng` = 30.313140, `address` = 'Санкт-Петербург, Гороховая улица, 12' WHERE `name` = 'Гороховая';
UPDATE `locations` SET `lat` = 59.944426, `lng` = 30.282110, `address` = 'Санкт-Петербург, Средний проспект В.О., 19' WHERE `name` = 'Василеостровский';
UPDATE `locations` SET `lat` = 59.944124, `lng` = 30.363624, `address` = 'Санкт-Петербург, улица Кирочная, 27' WHERE `name` = 'Кирочная 27';
UPDATE `locations` SET `lat` = 59.959028, `lng` = 30.288736, `address` = 'Санкт-Петербург, Чкаловский проспект, 5' WHERE `name` = 'Чкаловская';
UPDATE `locations` SET `lat` = 59.967282, `lng` = 30.311624, `address` = 'Санкт-Петербург, Каменноостровский проспект, 39' WHERE `name` = 'Каменоостровская 39';
UPDATE `locations` SET `lat` = 59.986302, `lng` = 30.204210, `address` = 'Санкт-Петербург, улица Савушкина, 126 (ТЦ Atlantic City)' WHERE `name` = 'Атлантик Сити';
UPDATE `locations` SET `lat` = 59.991935, `lng` = 30.206619, `address` = 'Санкт-Петербург, улица Савушкина, 141 (ТЦ Меркурий)' WHERE `name` = 'Меркурий';
UPDATE `locations` SET `lat` = 60.022296, `lng` = 30.225693, `address` = 'Санкт-Петербург, Планерная улица, 59 (ТЦ Leo Mall)' WHERE `name` = 'Леомолл';
UPDATE `locations` SET `lat` = 60.031274, `lng` = 30.230613, `address` = 'Санкт-Петербург, Комендантский проспект, 54' WHERE `name` = 'Легенда';
UPDATE `locations` SET `lat` = 60.038918, `lng` = 30.407654, `address` = 'Санкт-Петербург, проспект Просвещения, 80, к. 1' WHERE `name` = 'Прометей';
UPDATE `locations` SET `lat` = 60.091093, `lng` = 30.379511, `address` = 'Санкт-Петербург, КАД, 117-й км, 1' WHERE `name` = 'Мега Парнас';

INSERT INTO `locations` (`name`, `address`, `phone`, `is_active`, `color`, `lat`, `lng`, `created_at`, `updated_at`)
SELECT src.name, src.address, NULL, 1, '#2b2b2b', src.lat, src.lng, NOW(), NOW()
FROM (
    SELECT 'Московская 165' AS name, 'Санкт-Петербург, Московский проспект, 165' AS address, 59.866695 AS lat, 30.320050 AS lng
    UNION ALL SELECT 'Пражская', 'Санкт-Петербург, улица Пражская, 48/50', 59.864696, 30.404117
    UNION ALL SELECT 'Брантовская', 'Санкт-Петербург, Брантовская дорога, 3', 59.940745, 30.417330
    UNION ALL SELECT 'Индустриальный', 'Санкт-Петербург, проспект Индустриальный, 24', 59.945701, 30.475281
    UNION ALL SELECT 'Садовая', 'Санкт-Петербург, улица Садовая, 40', 59.927647, 30.320898
    UNION ALL SELECT 'Ефимова', 'Санкт-Петербург, улица Ефимова, 2', 59.926609, 30.320898
    UNION ALL SELECT 'Кронверкский', 'Санкт-Петербург, Кронверкский проспект, 45', 59.957316, 30.312427
    UNION ALL SELECT 'Полюстровский', 'Санкт-Петербург, Полюстровский проспект, 84', 59.986850, 30.355399
    UNION ALL SELECT 'Коломяжский', 'Санкт-Петербург, Коломяжский проспект, 17, к. 2', 60.005309, 30.301183
    UNION ALL SELECT 'Торфяная', 'Санкт-Петербург, Торфяная дорога, 7В', 59.990627, 30.257013
    UNION ALL SELECT 'Приморский', 'Санкт-Петербург, Приморский проспект, 72', 59.981774, 30.210635
    UNION ALL SELECT 'Гражданский', 'Санкт-Петербург, проспект Гражданский, 41, к. 2Б', 60.012328, 30.398193
    UNION ALL SELECT 'Культуры', 'Санкт-Петербург, проспект Культуры, 1', 60.033453, 30.367606
    UNION ALL SELECT 'Романовская', 'Санкт-Петербург, Романовская улица, 1/31', 60.068241, 30.436462
) AS src
LEFT JOIN `locations` l ON l.name = src.name
WHERE l.id IS NULL;

SELECT `name`, `lat`, `lng`, `address`
FROM `locations`
WHERE `lat` IS NOT NULL
ORDER BY `name`;

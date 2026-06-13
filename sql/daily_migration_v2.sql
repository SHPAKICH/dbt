-- Миграция Дейли v2: автозаполнение, удаление ОЗ, добавление Чеки по каналам
-- Выполнить вручную в MySQL. Если колонка отсутствует — соответствующий шаг даст ошибку, пропустить его.
-- MySQL 8.0.29+: DROP COLUMN IF EXISTS. Иначе: ALTER TABLE daily_reports DROP COLUMN oz;

ALTER TABLE `daily_reports` DROP COLUMN `oz`;

ALTER TABLE `daily_reports`
  ADD COLUMN `checks_bar` int(11) DEFAULT NULL COMMENT 'Чеки БАР' AFTER `bonuses`,
  ADD COLUMN `checks_delivery` int(11) DEFAULT NULL COMMENT 'Чеки ДОСТАВКА' AFTER `checks_bar`,
  ADD COLUMN `checks_self_pickup` int(11) DEFAULT NULL COMMENT 'Чеки САМОВЫВОЗ' AFTER `checks_delivery`;

ALTER TABLE `daily_reports` CHANGE COLUMN `avg_check_oz` `avg_check_self_pickup` decimal(10,2) DEFAULT NULL COMMENT 'Ср. чек САМОВЫВОЗ';

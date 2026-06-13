-- Таблица Дейли: ежедневный отчёт по точке (заполняется в конце смены)
-- Выполнить вручную в MySQL.
-- Категория: Дейли. Автогенерация на каждый месяц. Ведётся на каждой точке.
-- v2: чеки по каналам, ТО/DELTA/ср. чеки/производительность считаются автоматически,
--     часы — из графика сотрудников. План на день — только тер. управ и выше.

CREATE TABLE IF NOT EXISTS `daily_reports` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `location_id` int(11) UNSIGNED NOT NULL COMMENT 'ID точки',
  `report_date` date NOT NULL COMMENT 'Дата отчёта',
  `plan_daily` decimal(12,2) DEFAULT NULL COMMENT 'План на день (только тер. управ и выше)',
  `bar` decimal(12,2) DEFAULT NULL COMMENT 'БАР',
  `delivery` decimal(12,2) DEFAULT NULL COMMENT 'ДОСТАВКА',
  `self_pickup` decimal(12,2) DEFAULT NULL COMMENT 'САМОВЫВОЗ',
  `bonuses` decimal(12,2) DEFAULT NULL COMMENT 'БОНУСЫ',
  `checks_bar` int(11) DEFAULT NULL COMMENT 'Чеки БАР',
  `checks_delivery` int(11) DEFAULT NULL COMMENT 'Чеки ДОСТАВКА',
  `checks_self_pickup` int(11) DEFAULT NULL COMMENT 'Чеки САМОВЫВОЗ',
  `to_revenue` decimal(12,2) DEFAULT NULL COMMENT 'ТО = БАР+ДОСТАВКА+САМОВЫВОЗ+БОНУСЫ (авто)',
  `delta_plan` decimal(12,2) DEFAULT NULL COMMENT 'DELTA = ТО - План (авто)',
  `orders_count` int(11) DEFAULT NULL COMMENT 'Заказов = Чеки БАР+ДОСТАВКА+САМОВЫВОЗ (авто)',
  `avg_check_bar` decimal(10,2) DEFAULT NULL COMMENT 'Ср. чек БАР = БАР/Чеки БАР (авто)',
  `avg_check_delivery` decimal(10,2) DEFAULT NULL COMMENT 'Ср. чек ДОСТАВКА (авто)',
  `avg_check_self_pickup` decimal(10,2) DEFAULT NULL COMMENT 'Ср. чек САМОВЫВОЗ (авто)',
  `worker_hours` decimal(6,2) DEFAULT NULL COMMENT 'Часы из графика сотрудников (авто)',
  `productivity_orders` decimal(10,2) DEFAULT NULL COMMENT 'Произв. в заказах = Заказов/часы (авто)',
  `productivity_money` decimal(12,2) DEFAULT NULL COMMENT 'Произв. в деньгах = ТО/часы (авто)',
  `manager_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'ID менеджера закрывающего смену',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_daily_location_date` (`location_id`, `report_date`),
  KEY `idx_daily_reports_location_date` (`location_id`, `report_date`),
  KEY `idx_daily_reports_manager` (`manager_id`),
  CONSTRAINT `fk_daily_reports_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_daily_reports_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Дейли: ежедневный отчёт по точке';

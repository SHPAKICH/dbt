-- Добавляет поля для календаря сотрудников (др, телеграм, аттестация) и таблицу подписок на push.
-- Выполнить в MySQL (phpMyAdmin, консоль и т.д.).
-- Если колонка уже есть — пропустить соответствующий ALTER или игнорировать ошибку "Duplicate column".
-- Если в проекте используется префикс таблиц (например dbt_), замените users на dbt_users и push_subscriptions на dbt_push_subscriptions.

-- 1. Новые колонки в таблице users

ALTER TABLE `users` ADD COLUMN `birthday` DATE NULL COMMENT 'День рождения';
ALTER TABLE `users` ADD COLUMN `telegram` VARCHAR(100) NULL COMMENT 'Telegram';
ALTER TABLE `users` ADD COLUMN `certification_date` DATE NULL COMMENT 'Дата аттестации';


-- 2. Таблица подписок на push-уведомления

CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `endpoint` TEXT NOT NULL,
  `p256dh` VARCHAR(255) NOT NULL,
  `auth` VARCHAR(255) NOT NULL,
  `user_agent` VARCHAR(500) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_push_subscriptions_user_id` (`user_id`),
  CONSTRAINT `fk_push_subscriptions_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

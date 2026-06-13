-- Карта возможностей только по конкретным датам (старая по дням недели удалена).

CREATE TABLE IF NOT EXISTS `user_availability_by_date` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `time_start` TIME NULL,
  `time_end` TIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_date` (`user_id`, `date`),
  INDEX `idx_user_availability_by_date_user_id` (`user_id`),
  INDEX `idx_user_availability_by_date_date` (`date`),
  CONSTRAINT `fk_user_availability_by_date_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

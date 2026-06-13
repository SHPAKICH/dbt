-- База данных для корпоративной системы контроля сотрудников сети кафе
-- MySQL 5.7+

-- Создание базы данных (раскомментируйте, если нужно создать БД)
-- CREATE DATABASE IF NOT EXISTS cafe_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE cafe_management;

-- Таблица точек (кафе)
CREATE TABLE IF NOT EXISTS `locations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COMMENT 'Название точки',
  `address` VARCHAR(500) DEFAULT NULL COMMENT 'Адрес точки',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT 'Телефон точки',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Активна ли точка',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата обновления',
  PRIMARY KEY (`id`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Таблица точек (кафе)';

-- Таблица пользователей (сотрудников)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(20) NOT NULL COMMENT 'Номер телефона',
  `email` VARCHAR(255) NOT NULL COMMENT 'Электронная почта',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Хеш пароля',
  `position` ENUM(
    'manager',           -- Управляющий
    'location_manager',  -- Менеджер точки
    'senior_teamaker',   -- Старший тимейкер
    'teamaker',          -- Тимейкер
    'trainee'            -- Стажер
  ) NOT NULL COMMENT 'Должность',
  `location_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'ID точки закрепления (NULL для управляющих)',
  `avatar` VARCHAR(500) DEFAULT NULL COMMENT 'Путь к аватарке',
  `first_name` VARCHAR(100) DEFAULT NULL COMMENT 'Имя',
  `last_name` VARCHAR(100) DEFAULT NULL COMMENT 'Фамилия',
  `auth_key` VARCHAR(32) DEFAULT NULL COMMENT 'Ключ авторизации',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Активен ли пользователь',
  `is_admin` TINYINT(1) DEFAULT 0 COMMENT 'Суперюзер (администратор системы)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата обновления',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_phone` (`phone`),
  UNIQUE KEY `unique_email` (`email`),
  INDEX `idx_position` (`position`),
  INDEX `idx_location_id` (`location_id`),
  INDEX `idx_is_active` (`is_active`),
  CONSTRAINT `fk_users_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Таблица пользователей (сотрудников)';

-- Таблица связи управляющих с точками (many-to-many)
-- Управляющий может контролировать несколько точек
CREATE TABLE IF NOT EXISTS `manager_locations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `manager_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID управляющего (пользователь с должностью manager)',
  `location_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID точки',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_manager_location` (`manager_id`, `location_id`),
  INDEX `idx_manager_id` (`manager_id`),
  INDEX `idx_location_id` (`location_id`),
  CONSTRAINT `fk_manager_locations_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_manager_locations_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Связь управляющих с точками';

-- Добавление комментариев для лучшего понимания структуры
ALTER TABLE `users` COMMENT = 'Пользователи системы. Управляющий (manager) может управлять несколькими точками через таблицу manager_locations. Менеджер точки (location_manager) закреплен за одной точкой через location_id. Остальные сотрудники также могут быть закреплены за точкой через location_id.';


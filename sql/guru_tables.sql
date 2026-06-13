-- Модуль «Меню Гуру» (Обучение и аттестация): создание таблиц
-- Выполнить вручную в MySQL. Миграции Yii не используются.

-- Тесты / викторины
CREATE TABLE IF NOT EXISTS `training_tests` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'Название теста',
  `category` varchar(100) DEFAULT NULL COMMENT 'Категория (охрана труда, меню, стандарты и т.д.)',
  `description` text COMMENT 'Описание теста',
  `time_limit` int(11) UNSIGNED DEFAULT NULL COMMENT 'Лимит времени на весь тест (секунды), NULL = без лимита',
  `question_time_limit` int(11) UNSIGNED DEFAULT NULL COMMENT 'Лимит времени на один вопрос (секунды), NULL = без лимита',
  `pass_score` decimal(5,2) NOT NULL DEFAULT 70.00 COMMENT 'Проходной балл (%)',
  `image` varchar(500) DEFAULT NULL COMMENT 'Изображение теста (URL или путь)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_tests_category` (`category`),
  KEY `idx_training_tests_is_active` (`is_active`),
  KEY `idx_training_tests_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Тесты/викторины';

-- Вопросы теста (варианты ответов в JSON для гибкости: одиночный/множественный выбор в будущем)
CREATE TABLE IF NOT EXISTS `training_questions` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_id` int(11) UNSIGNED NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Порядок вопроса',
  `question_text` text NOT NULL COMMENT 'Текст вопроса',
  `options` json NOT NULL COMMENT 'Варианты ответов: [{"id":1,"text":"...","is_correct":true}, ...]',
  `correct_answer` varchar(50) DEFAULT NULL COMMENT 'ID правильного ответа (для одиночного выбора — индекс или ключ)',
  `points` int(11) NOT NULL DEFAULT 1 COMMENT 'Баллы за правильный ответ',
  `image` varchar(500) DEFAULT NULL COMMENT 'Изображение к вопросу',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_questions_test_id` (`test_id`),
  CONSTRAINT `fk_training_questions_test` FOREIGN KEY (`test_id`) REFERENCES `training_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Вопросы тестов';

-- Результаты прохождения тестов
CREATE TABLE IF NOT EXISTS `training_results` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `test_id` int(11) UNSIGNED NOT NULL,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Набранный балл (%)',
  `points_earned` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Набранные баллы',
  `points_max` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Максимум баллов в тесте',
  `time_spent` int(11) UNSIGNED DEFAULT NULL COMMENT 'Время прохождения (секунды)',
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `answers_data` json DEFAULT NULL COMMENT 'Ответы по вопросам (для разбора)',
  `started_at` datetime NOT NULL,
  `finished_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_training_results_user_id` (`user_id`),
  KEY `idx_training_results_test_id` (`test_id`),
  KEY `idx_training_results_finished_at` (`finished_at`),
  KEY `idx_training_results_score` (`score`),
  CONSTRAINT `fk_training_results_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_training_results_test` FOREIGN KEY (`test_id`) REFERENCES `training_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Результаты прохождения тестов';

-- Технологические карты (позиции меню)
CREATE TABLE IF NOT EXISTS `tech_cards` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Название позиции',
  `category` varchar(100) DEFAULT NULL COMMENT 'Категория (напитки, десерты, блюда и т.д.)',
  `prep_time` int(11) UNSIGNED DEFAULT NULL COMMENT 'Время приготовления (минуты)',
  `description` text COMMENT 'Описание / технология приготовления',
  `serving` varchar(255) DEFAULT NULL COMMENT 'Вариант подачи',
  `image` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tech_cards_category` (`category`),
  KEY `idx_tech_cards_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Технологические карты';

-- Ингредиенты технологических карт
CREATE TABLE IF NOT EXISTS `tech_card_ingredients` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `card_id` int(11) UNSIGNED NOT NULL,
  `size_code` varchar(20) DEFAULT NULL COMMENT 'Размер: S, M, L и т.п. (NULL = общий для всех)',
  `ingredient_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0 COMMENT 'Количество',
  `unit` varchar(50) NOT NULL DEFAULT 'г' COMMENT 'Единица: г, мл, шт и т.д.',
  `price_per_unit` decimal(10,2) DEFAULT NULL COMMENT 'Цена за единицу (для расчёта себестоимости)',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_tech_card_ingredients_card_id` (`card_id`),
  CONSTRAINT `fk_tech_card_ingredients_card` FOREIGN KEY (`card_id`) REFERENCES `tech_cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ингредиенты ТТК';

-- Прогресс прохождения уроков
CREATE TABLE IF NOT EXISTS `lesson_progress` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `material_id` int(11) UNSIGNED NOT NULL,
  `completed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_lesson_progress_unique` (`user_id`, `material_id`),
  KEY `idx_lesson_progress_material` (`material_id`),
  CONSTRAINT `fk_lesson_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lesson_progress_material` FOREIGN KEY (`material_id`) REFERENCES `training_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Прогресс прохождения уроков';

-- Теоретические материалы (привязка к тесту или отдельно)
CREATE TABLE IF NOT EXISTS `training_materials` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `content` longtext NOT NULL COMMENT 'HTML-контент',
  `test_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'Привязка к тесту (опционально)',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_materials_test_id` (`test_id`),
  KEY `idx_training_materials_category` (`category`),
  CONSTRAINT `fk_training_materials_test` FOREIGN KEY (`test_id`) REFERENCES `training_tests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Теоретические материалы';

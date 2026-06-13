-- Примеры данных для тестирования системы
-- ВНИМАНИЕ: Пароли в примерах - это хеши от пароля "password123"
-- В реальной системе используйте password_hash() или bcrypt для хеширования паролей
-- Хеш пароля сгенерирован: $2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S

-- Вставка точек (кафе)
INSERT INTO `locations` (`name`, `address`, `phone`) VALUES
('Кафе "Центральное"', 'г. Москва, ул. Тверская, д. 1', '+7 (495) 123-45-67'),
('Кафе "Северное"', 'г. Москва, ул. Ленинградская, д. 10', '+7 (495) 234-56-78'),
('Кафе "Южное"', 'г. Москва, ул. Южная, д. 25', '+7 (495) 345-67-89');

-- ============================================
-- УПРАВЛЯЮЩИЙ (manager)
-- ============================================
-- Управляющий (может управлять несколькими точками)
INSERT INTO `users` (`phone`, `email`, `password_hash`, `position`, `first_name`, `last_name`) VALUES
('+7 (999) 111-11-11', 'manager@cafe.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'manager', 'Иван', 'Петров');

-- ============================================
-- МЕНЕДЖЕРЫ ТОЧЕК (location_manager)
-- ============================================
-- Менеджеры точек (закреплены за одной точкой)
INSERT INTO `users` (`phone`, `email`, `password_hash`, `position`, `location_id`, `first_name`, `last_name`) VALUES
('+7 (999) 222-22-22', 'manager1@cafe.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', 1, 'Мария', 'Иванова'),
('+7 (999) 333-33-33', 'manager2@cafe.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', 2, 'Алексей', 'Сидоров'),
('+7 (999) 444-44-44', 'manager3@cafe.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'location_manager', 3, 'Елена', 'Козлова');

-- ============================================
-- СТАРШИЕ ТИМЕЙКЕРЫ (senior_teamaker)
-- ============================================
INSERT INTO `users` (`phone`, `email`, `password_hash`, `position`, `location_id`, `first_name`, `last_name`) VALUES
('+7 (999) 555-55-55', 'senior1@cafe.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', 1, 'Дмитрий', 'Смирнов'),
('+7 (999) 666-66-66', 'senior2@cafe.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'senior_teamaker', 2, 'Ольга', 'Волкова');

-- ============================================
-- ТИМЕЙКЕРЫ (teamaker)
-- ============================================
INSERT INTO `users` (`phone`, `email`, `password_hash`, `position`, `location_id`, `first_name`, `last_name`) VALUES
('+7 (999) 777-77-77', 'teamaker1@cafe.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', 1, 'Анна', 'Новикова'),
('+7 (999) 888-88-88', 'teamaker2@cafe.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', 2, 'Сергей', 'Морозов'),
('+7 (999) 999-99-99', 'teamaker3@cafe.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'teamaker', 3, 'Татьяна', 'Петрова');

-- ============================================
-- СТАЖЕРЫ (trainee)
-- ============================================
INSERT INTO `users` (`phone`, `email`, `password_hash`, `position`, `location_id`, `first_name`, `last_name`) VALUES
('+7 (999) 000-00-00', 'trainee1@cafe.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'trainee', 1, 'Михаил', 'Лебедев'),
('+7 (999) 101-01-01', 'trainee2@cafe.ru', '$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S', 'trainee', 3, 'Наталья', 'Соколова');

-- ============================================
-- СВЯЗЬ УПРАВЛЯЮЩЕГО С ТОЧКАМИ
-- ============================================
-- Связь управляющего с точками (управляющий контролирует все три точки)
INSERT INTO `manager_locations` (`manager_id`, `location_id`) VALUES
(1, 1),  -- Управляющий контролирует точку 1
(1, 2),  -- Управляющий контролирует точку 2
(1, 3);  -- Управляющий контролирует точку 3



-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Мар 04 2026 г., 19:43
-- Версия сервера: 11.4.7-MariaDB-ubu2404
-- Версия PHP: 8.3.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `chueshkov_dbt`
--

-- --------------------------------------------------------

--
-- Структура таблицы `daily_reports`
--

CREATE TABLE `daily_reports` (
  `id` int(11) UNSIGNED NOT NULL,
  `location_id` int(11) UNSIGNED NOT NULL COMMENT 'ID точки',
  `report_date` date NOT NULL COMMENT 'Дата отчёта',
  `plan_daily` decimal(12,2) DEFAULT NULL COMMENT 'План на день',
  `to_revenue` decimal(12,2) DEFAULT NULL COMMENT 'ТО (выручка)',
  `delta_plan` decimal(12,2) DEFAULT NULL COMMENT 'DELTA к дневному плану',
  `bar` decimal(12,2) DEFAULT NULL COMMENT 'БАР',
  `delivery` decimal(12,2) DEFAULT NULL COMMENT 'ДОСТАВКА',
  `self_pickup` decimal(12,2) DEFAULT NULL COMMENT 'САМОВЫВОЗ',
  `bonuses` decimal(12,2) DEFAULT NULL COMMENT 'БОНУСЫ',
  `checks_bar` int(11) DEFAULT NULL COMMENT 'Чеки БАР',
  `checks_delivery` int(11) DEFAULT NULL COMMENT 'Чеки ДОСТАВКА',
  `checks_self_pickup` int(11) DEFAULT NULL COMMENT 'Чеки САМОВЫВОЗ',
  `orders_count` int(11) DEFAULT NULL COMMENT 'Заказов',
  `avg_check_bar` decimal(10,2) DEFAULT NULL COMMENT 'Ср. чек БАР',
  `avg_check_delivery` decimal(10,2) DEFAULT NULL COMMENT 'Ср. чек ДОСТАВКА',
  `avg_check_self_pickup` decimal(10,2) DEFAULT NULL COMMENT 'Ср. чек САМОВЫВОЗ',
  `worker_hours` decimal(6,2) DEFAULT NULL COMMENT 'Кол-во часов работника',
  `productivity_orders` decimal(10,2) DEFAULT NULL COMMENT 'Производительность (в заказах)',
  `productivity_money` decimal(12,2) DEFAULT NULL COMMENT 'Производительность (в деньгах)',
  `manager_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'ID менеджера закрывающего смену',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Дейли: ежедневный отчёт по точке';

--
-- Дамп данных таблицы `daily_reports`
--

INSERT INTO `daily_reports` (`id`, `location_id`, `report_date`, `plan_daily`, `to_revenue`, `delta_plan`, `bar`, `delivery`, `self_pickup`, `bonuses`, `checks_bar`, `checks_delivery`, `checks_self_pickup`, `orders_count`, `avg_check_bar`, `avg_check_delivery`, `avg_check_self_pickup`, `worker_hours`, `productivity_orders`, `productivity_money`, `manager_id`, `created_at`, `updated_at`) VALUES
(1, 4, '2026-02-01', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(2, 4, '2026-02-02', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(3, 4, '2026-02-03', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(4, 4, '2026-02-04', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(5, 4, '2026-02-05', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(6, 4, '2026-02-06', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(7, 4, '2026-02-07', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(8, 4, '2026-02-08', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(9, 4, '2026-02-09', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(10, 4, '2026-02-10', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(11, 4, '2026-02-11', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(12, 4, '2026-02-12', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(13, 4, '2026-02-13', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(14, 4, '2026-02-14', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(15, 4, '2026-02-15', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(16, 4, '2026-02-16', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(17, 4, '2026-02-17', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(18, 4, '2026-02-18', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(19, 4, '2026-02-19', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(20, 4, '2026-02-20', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(21, 4, '2026-02-21', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(22, 4, '2026-02-22', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(23, 4, '2026-02-23', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(24, 4, '2026-02-24', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(25, 4, '2026-02-25', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(26, 4, '2026-02-26', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(27, 4, '2026-02-27', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(28, 4, '2026-02-28', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:30', '2026-02-28 00:31:56'),
(29, 7, '2026-02-01', 50000.00, 23559.00, -26441.00, 23000.00, 1.00, 557.00, 1.00, 53, 4, 1, 58, 433.96, 0.25, 557.00, 0.00, NULL, NULL, 17, '2026-02-27 23:58:47', '2026-02-28 23:54:40'),
(30, 7, '2026-02-02', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(31, 7, '2026-02-03', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(32, 7, '2026-02-04', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(33, 7, '2026-02-05', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 12.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(34, 7, '2026-02-06', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(35, 7, '2026-02-07', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(36, 7, '2026-02-08', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(37, 7, '2026-02-09', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(38, 7, '2026-02-10', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(39, 7, '2026-02-11', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(40, 7, '2026-02-12', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(41, 7, '2026-02-13', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(42, 7, '2026-02-14', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(43, 7, '2026-02-15', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(44, 7, '2026-02-16', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(45, 7, '2026-02-17', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(46, 7, '2026-02-18', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(47, 7, '2026-02-19', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(48, 7, '2026-02-20', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(49, 7, '2026-02-21', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(50, 7, '2026-02-22', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(51, 7, '2026-02-23', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(52, 7, '2026-02-24', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(53, 7, '2026-02-25', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(54, 7, '2026-02-26', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(55, 7, '2026-02-27', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(56, 7, '2026-02-28', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-02-27 23:58:47', '2026-02-28 00:16:19'),
(57, 4, '2026-03-01', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(58, 4, '2026-03-02', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(59, 4, '2026-03-03', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(60, 4, '2026-03-04', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(61, 4, '2026-03-05', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(62, 4, '2026-03-06', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(63, 4, '2026-03-07', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(64, 4, '2026-03-08', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(65, 4, '2026-03-09', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(66, 4, '2026-03-10', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(67, 4, '2026-03-11', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(68, 4, '2026-03-12', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(69, 4, '2026-03-13', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(70, 4, '2026-03-14', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(71, 4, '2026-03-15', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(72, 4, '2026-03-16', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(73, 4, '2026-03-17', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(74, 4, '2026-03-18', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(75, 4, '2026-03-19', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(76, 4, '2026-03-20', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(77, 4, '2026-03-21', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(78, 4, '2026-03-22', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(79, 4, '2026-03-23', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(80, 4, '2026-03-24', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(81, 4, '2026-03-25', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(82, 4, '2026-03-26', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(83, 4, '2026-03-27', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(84, 4, '2026-03-28', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(85, 4, '2026-03-29', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(86, 4, '2026-03-30', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(87, 4, '2026-03-31', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-01 21:20:27', '2026-03-01 21:20:27'),
(88, 7, '2026-03-01', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(89, 7, '2026-03-02', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(90, 7, '2026-03-03', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(91, 7, '2026-03-04', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(92, 7, '2026-03-05', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(93, 7, '2026-03-06', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(94, 7, '2026-03-07', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(95, 7, '2026-03-08', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(96, 7, '2026-03-09', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(97, 7, '2026-03-10', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(98, 7, '2026-03-11', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(99, 7, '2026-03-12', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(100, 7, '2026-03-13', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(101, 7, '2026-03-14', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(102, 7, '2026-03-15', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(103, 7, '2026-03-16', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(104, 7, '2026-03-17', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(105, 7, '2026-03-18', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(106, 7, '2026-03-19', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(107, 7, '2026-03-20', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(108, 7, '2026-03-21', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(109, 7, '2026-03-22', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(110, 7, '2026-03-23', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(111, 7, '2026-03-24', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(112, 7, '2026-03-25', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(113, 7, '2026-03-26', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(114, 7, '2026-03-27', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(115, 7, '2026-03-28', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(116, 7, '2026-03-29', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(117, 7, '2026-03-30', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27'),
(118, 7, '2026-03-31', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-03-02 14:39:27', '2026-03-02 14:39:27');

-- --------------------------------------------------------

--
-- Структура таблицы `locations`
--

CREATE TABLE `locations` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Название точки',
  `address` varchar(500) DEFAULT NULL COMMENT 'Адрес точки',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Телефон точки',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Активна ли точка',
  `color` varchar(7) NOT NULL DEFAULT '#2b2b2b' COMMENT 'Цвет точки в графике',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Дата создания',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Дата обновления'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Таблица точек (кафе)';

--
-- Дамп данных таблицы `locations`
--

INSERT INTO `locations` (`id`, `name`, `address`, `phone`, `is_active`, `color`, `created_at`, `updated_at`) VALUES
(4, '5е Авеню', 'Москва, улица Маршала Бирюзова, 32, 123060', '+79042366776', 1, '#2b2b2b', '2026-02-06 18:04:13', '2026-02-06 18:04:13'),
(5, 'Атлантик Сити', 'Санкт-Петербург, улица Савушкина, 126', '+79117938390', 1, '#2b2b2b', '2026-02-06 18:06:33', '2026-02-06 18:06:33'),
(6, 'Бухарестская', 'Санкт-Петербург, Бухарестская улица, 32, 192071', '+79322650030', 1, '#2b2b2b', '2026-02-06 18:07:48', '2026-02-06 18:07:48'),
(7, 'Гороховая', 'Санкт-Петербург, Гороховая улица, 12', '+79111480096', 1, '#2b2b2b', '2026-02-06 18:09:07', '2026-02-06 18:09:07'),
(8, 'Жемчужная Плаза', 'Санкт-Петербург, Петергофское шоссе, 51, 198322', '+79817390843', 1, '#2b2b2b', '2026-02-06 18:10:22', '2026-02-06 18:10:22'),
(9, 'Кронштадт', 'Цитадельское шоссе, 18Е', '+79117712372', 0, '#2b2b2b', '2026-02-06 18:33:08', '2026-02-06 18:33:08'),
(10, 'Легенда', 'Санкт-Петербург, Комендантский проспект, 54', '+79817325263', 1, '#2b2b2b', '2026-02-06 18:34:25', '2026-02-06 18:34:25'),
(11, 'Леомолл', 'Санкт-Петербург, Планерная улица, 59', '+79312499890', 1, '#2b2b2b', '2026-02-06 18:36:03', '2026-02-06 18:36:03'),
(12, 'Ломоносова 1', 'Санкт-Петербург, Наб. канала Грибоедова, 30-32Ч', '+79211829794', 1, '#2b2b2b', '2026-02-06 18:38:07', '2026-02-06 18:42:34'),
(13, 'Ломоносова 12/66', 'Санкт-Петербург, Улица Ломоносова, 12/66', '+79119750048', 1, '#2b2b2b', '2026-02-06 18:39:07', '2026-02-06 18:42:28'),
(14, 'Лондон Молл', 'Санкт-Петербург, Улица Коллонтай, 3Б', '+79119884995', 1, '#2b2b2b', '2026-02-06 18:41:01', '2026-02-06 18:42:22'),
(15, 'Марата', 'Санкт-Петербург, Улица Марата, 3', '+79112570669', 1, '#2b2b2b', '2026-02-06 18:42:14', '2026-02-06 18:42:14'),
(16, 'Меркурий', 'Санкт-Петербург, улица Савушкина, 141', '+79812647472', 1, '#2b2b2b', '2026-02-06 18:43:46', '2026-02-06 18:43:46'),
(17, 'Прометей', 'Санкт-Петербург, проспект Просвещения, 80, к. 1', '+79811898896', 1, '#2b2b2b', '2026-02-06 18:45:15', '2026-02-06 18:45:15'),
(18, 'Стачек', 'Санкт-Петербург, проспект Стачек, 99', '+79217874599', 1, '#2b2b2b', '2026-02-06 18:46:02', '2026-02-06 18:46:02'),
(19, 'ТРК Лето', 'Санкт-Петербург, Пулковское шоссе, 25, к. 1', '+79811996683', 1, '#2b2b2b', '2026-02-06 18:46:53', '2026-02-06 18:46:53'),
(20, 'ТЦ Радуга', 'Санкт-Петербург, проспект Космонавтов, 14', '+79817319493', 1, '#2b2b2b', '2026-02-06 18:47:43', '2026-02-06 18:47:43'),
(21, 'Чкаловская', 'Санкт-Петербург, Чкаловский проспект, 5', '+79650036674', 1, '#2b2b2b', '2026-02-06 18:48:35', '2026-02-06 18:48:35');

-- --------------------------------------------------------

--
-- Структура таблицы `manager_locations`
--

CREATE TABLE `manager_locations` (
  `id` int(11) UNSIGNED NOT NULL,
  `manager_id` int(11) UNSIGNED NOT NULL COMMENT 'ID управляющего (пользователь с должностью manager)',
  `location_id` int(11) UNSIGNED NOT NULL COMMENT 'ID точки',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Дата создания'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Связь управляющих с точками';

--
-- Дамп данных таблицы `manager_locations`
--

INSERT INTO `manager_locations` (`id`, `manager_id`, `location_id`, `created_at`) VALUES
(8, 14, 7, '2026-02-06 19:17:12'),
(9, 14, 10, '2026-02-06 19:17:12'),
(12, 14, 11, '2026-02-06 19:17:38');

-- --------------------------------------------------------

--
-- Структура таблицы `position_rates`
--

CREATE TABLE `position_rates` (
  `id` int(11) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL COMMENT 'Код должности (enum из users.position)',
  `name` varchar(255) NOT NULL COMMENT 'Название должности',
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Ставка, руб/час',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ставки по должностям';

--
-- Дамп данных таблицы `position_rates`
--

INSERT INTO `position_rates` (`id`, `code`, `name`, `hourly_rate`, `created_at`, `updated_at`) VALUES
(1, 'manager', 'Управляющий', 350.00, '2026-02-05 13:29:38', '2026-02-05 13:29:38'),
(2, 'location_manager', 'Менеджер точки', 300.00, '2026-02-05 13:29:38', '2026-02-05 13:29:38'),
(3, 'senior_teamaker', 'Старший тимейкер', 300.00, '2026-02-05 13:29:38', '2026-02-05 13:29:38'),
(4, 'teamaker', 'Тимейкер', 250.00, '2026-02-05 13:29:38', '2026-02-05 13:29:38'),
(5, 'trainee', 'Стажёр', 200.00, '2026-02-05 13:29:38', '2026-02-05 13:29:38');

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Название товара',
  `sku` varchar(100) DEFAULT NULL COMMENT 'Артикул',
  `unit` varchar(50) NOT NULL DEFAULT 'шт' COMMENT 'Единица измерения',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Справочник товаров для заказов поставок';

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `name`, `sku`, `unit`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Тапиока Коробка 18 шт.', 'tapioka-box', 'шт', 1, '2026-02-06 19:49:14', '2026-02-06 19:49:14'),
(2, 'Джус-Боллы Манго', 'juicy-balls-mango', 'шт', 1, '2026-02-06 19:49:14', '2026-02-06 19:49:14'),
(3, 'Джус-Боллы Апельсин', 'juicy-balls-orange', 'шт', 1, '2026-02-06 19:51:34', '2026-02-06 19:51:34'),
(4, 'Джус-Боллы Виноград', 'juicy-balls-grape', 'шт', 1, '2026-02-06 19:51:34', '2026-02-06 19:51:34'),
(5, 'Джус-Боллы Клубника', 'juicy-balls-strawberry', 'шт', 1, '2026-02-06 19:52:44', '2026-02-06 19:52:44'),
(6, 'Джус-Боллы Маракуйя', 'juicy-balls-passionfruit', 'шт', 1, '2026-02-06 19:52:44', '2026-02-06 19:52:44'),
(7, 'Джус-Боллы Йогурт', 'juicy-balls-yogurt', 'шт', 1, '2026-02-06 19:53:50', '2026-02-06 19:53:50'),
(8, 'Джус-Боллы Персик', 'juicy-balls-peach', 'шт', 1, '2026-02-06 19:53:50', '2026-02-06 19:53:50'),
(9, 'Джус-Боллы Зеленый Виноград', 'juicy-balls-green-grape', 'шт', 1, '2026-02-06 19:55:12', '2026-02-06 19:55:12'),
(10, 'Таро порошок', 'taro-powder', 'шт', 1, '2026-02-06 19:55:12', '2026-02-06 19:55:12'),
(11, 'Кокосовое желе', 'nata-de-coco', 'шт', 1, '2026-02-06 19:55:59', '2026-02-06 19:55:59'),
(12, 'Агар-агар Сакура', 'agar-agar', 'шт', 1, '2026-02-06 19:55:59', '2026-02-06 19:55:59'),
(13, 'Клубничное пюре', 'strawberry-jam', 'шт', 1, '2026-02-06 19:56:52', '2026-02-06 19:56:52'),
(14, 'Апельсиновое пюре', 'orange-jam', 'шт', 1, '2026-02-06 19:56:52', '2026-02-06 19:56:52'),
(15, 'Грейпфрутовое пюре', 'grapefuit-jam', 'шт', 1, '2026-02-06 19:58:14', '2026-02-06 19:58:14'),
(16, 'Гуава пюре', 'guava-jam', 'шт', 1, '2026-02-06 19:58:14', '2026-02-06 19:58:14'),
(17, 'Сироп маракуйя', 'syrup-passionfruit', 'шт', 1, '2026-02-06 19:59:24', '2026-02-06 19:59:24'),
(18, 'Гранатовое пюре', 'pomegranate-jam', 'шт', 1, '2026-02-06 19:59:24', '2026-02-06 19:59:24'),
(19, 'Манговое пюре', 'mango-jam', 'шт', 1, '2026-02-06 20:00:20', '2026-02-06 20:00:20'),
(20, 'Пюре алоэ и ромашка', 'aloe-chamomile-jam', 'шт', 1, '2026-02-06 20:00:20', '2026-02-06 20:00:20'),
(21, 'Сироп Клубника', 'strawberry-syrup', 'шт', 1, '2026-02-06 20:00:50', '2026-02-06 20:00:50'),
(22, 'Сироп Блю Кюрасао', 'blue-curacao-syrup', 'шт', 1, '2026-02-06 20:01:56', '2026-02-06 20:01:56'),
(23, 'Сироп Карамель', 'caramel-syrup', 'шт', 1, '2026-02-06 20:01:56', '2026-02-06 20:01:56'),
(24, 'Сироп Гренадин', 'grenadin-syrup', 'шт', 1, '2026-02-06 20:02:38', '2026-02-06 20:02:38'),
(25, 'Сироп Фисташка', 'pistachio-syrup', 'шт', 1, '2026-02-06 20:02:38', '2026-02-06 20:02:38'),
(26, 'Сироп Лесной Орех', 'hazelnut-syrup', 'шт', 1, '2026-02-06 20:03:50', '2026-02-06 20:03:50'),
(27, 'Шоколадный соус', 'chocolate-sauce', 'шт', 1, '2026-02-06 20:03:50', '2026-02-06 20:03:50'),
(28, 'Сироп Мята', 'mint-syrup', 'шт', 1, '2026-02-06 20:04:47', '2026-02-06 20:04:47'),
(29, 'Карамельный соус', 'caramel-sauce', 'шт', 1, '2026-02-06 20:04:47', '2026-02-06 20:04:47'),
(30, 'Фисташковая Паста', 'pistachio-paste', 'шт', 1, '2026-02-06 20:05:52', '2026-02-06 20:05:52'),
(31, 'Сухое молоко', 'powdered-milk', 'шт', 1, '2026-02-06 20:05:52', '2026-02-06 20:05:52'),
(32, 'Тростниковый Сахар', 'black-sugar', 'шт', 1, '2026-02-06 20:06:39', '2026-02-06 20:06:39'),
(33, 'Сырный порошок', 'cheese-powder', 'шт', 1, '2026-02-06 20:06:39', '2026-02-06 20:06:39'),
(34, 'Косточки Маракуйя', 'passionfruit-seeds', 'шт', 1, '2026-02-06 20:07:57', '2026-02-06 20:07:57'),
(35, 'Чай Улун Камелия', 'kamelia-tea', 'шт', 1, '2026-02-06 20:07:57', '2026-02-06 20:07:57'),
(36, 'Жасминовый чай', 'jasmin-tea', 'шт', 1, '2026-02-06 20:08:54', '2026-02-06 20:08:54'),
(37, 'Чай Лапсанг Сушонг', 'black-tea', 'шт', 1, '2026-02-06 20:08:54', '2026-02-06 20:08:54'),
(38, 'Фруктоза жидкая', 'fructose', 'шт', 1, '2026-02-06 20:10:01', '2026-02-06 20:10:01'),
(39, 'Матча Премиум', 'matcha-powdered', 'шт', 1, '2026-02-06 20:10:01', '2026-02-06 20:10:01'),
(40, 'Черная карамель', 'brown-caramel-syrup', 'шт', 1, '2026-02-06 20:10:57', '2026-02-06 20:10:57'),
(41, 'Лимонный концентрат', 'lemon-concentrate', 'шт', 1, '2026-02-06 20:10:57', '2026-02-06 20:10:57'),
(42, 'Смесь для вафель \"Сытная\"', 'rich-waffle-mix', 'шт', 1, '2026-02-06 20:12:36', '2026-02-06 20:12:36'),
(43, 'Смесь для вафель \"Сладкая\"', 'sweet-waffle-mix', 'шт', 1, '2026-02-06 20:12:36', '2026-02-06 20:12:36'),
(44, 'Коллаген', 'collagen-powder', 'шт', 1, '2026-02-06 20:14:00', '2026-02-06 20:14:00'),
(45, 'Б-Комплекс', 'b-complex-powder', 'шт', 1, '2026-02-06 20:14:00', '2026-02-06 20:14:00'),
(46, 'Кофе Зёрна', 'coffee-seeds', 'шт', 1, '2026-02-06 20:14:47', '2026-02-06 20:14:47'),
(47, 'Горячий молочный шоколад', 'cacao-powder', 'шт', 1, '2026-02-06 20:14:47', '2026-02-06 20:14:47'),
(48, 'Орео печенье крошка', 'oreo-crispy', 'шт', 1, '2026-02-06 20:15:39', '2026-02-06 20:15:39'),
(49, 'Карамельное печенье крошка', 'caramel-crispy', 'шт', 1, '2026-02-06 20:15:39', '2026-02-06 20:15:39'),
(50, 'Кокосовая вода', 'coconut-water', 'шт', 1, '2026-02-06 20:16:11', '2026-02-06 20:16:11');

-- --------------------------------------------------------

--
-- Структура таблицы `profile_card_template`
--

CREATE TABLE `profile_card_template` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `preview_image` varchar(255) DEFAULT NULL,
  `css_class` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(10) UNSIGNED NOT NULL,
  `updated_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `profile_card_template`
--

INSERT INTO `profile_card_template` (`id`, `name`, `code`, `description`, `preview_image`, `css_class`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Test Gold', 'test_gold', 'Тестовая карточка профиля', NULL, 'profile-card-test-gold', 1, 1772482284, 1772482284);

-- --------------------------------------------------------

--
-- Структура таблицы `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh` varchar(255) NOT NULL,
  `auth` varchar(255) NOT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `resource_cells`
--

CREATE TABLE `resource_cells` (
  `id` int(11) UNSIGNED NOT NULL,
  `row_id` int(11) UNSIGNED NOT NULL,
  `column_id` int(11) UNSIGNED NOT NULL,
  `value` text DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ячейки матрицы ресурсов (с версионностью)';

-- --------------------------------------------------------

--
-- Структура таблицы `resource_cell_history`
--

CREATE TABLE `resource_cell_history` (
  `id` int(11) UNSIGNED NOT NULL,
  `cell_id` int(11) UNSIGNED NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `version` int(11) NOT NULL,
  `changed_by` int(11) UNSIGNED DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='История изменений ячеек матрицы ресурсов';

-- --------------------------------------------------------

--
-- Структура таблицы `resource_columns`
--

CREATE TABLE `resource_columns` (
  `id` int(11) UNSIGNED NOT NULL,
  `matrix_id` int(11) UNSIGNED NOT NULL,
  `label` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Колонки матрицы ресурсов';

-- --------------------------------------------------------

--
-- Структура таблицы `resource_matrices`
--

CREATE TABLE `resource_matrices` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Матрицы ресурсов/возможностей';

--
-- Дамп данных таблицы `resource_matrices`
--

INSERT INTO `resource_matrices` (`id`, `name`, `code`, `description`) VALUES
(1, 'Матрица ресурсов', 'default', 'Таблица возможностей/ресурсов');

-- --------------------------------------------------------

--
-- Структура таблицы `resource_rows`
--

CREATE TABLE `resource_rows` (
  `id` int(11) UNSIGNED NOT NULL,
  `matrix_id` int(11) UNSIGNED NOT NULL,
  `label` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Строки матрицы ресурсов';

-- --------------------------------------------------------

--
-- Структура таблицы `schedule_shifts`
--

CREATE TABLE `schedule_shifts` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `location_id` int(11) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `time_start` time DEFAULT NULL,
  `time_end` time DEFAULT NULL,
  `hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_night` tinyint(1) NOT NULL DEFAULT 0,
  `is_overtime` tinyint(1) NOT NULL DEFAULT 0,
  `is_day_off` tinyint(1) NOT NULL DEFAULT 0,
  `comment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='График смен сотрудников по точкам';

--
-- Дамп данных таблицы `schedule_shifts`
--

INSERT INTO `schedule_shifts` (`id`, `user_id`, `location_id`, `date`, `time_start`, `time_end`, `hours`, `is_night`, `is_overtime`, `is_day_off`, `comment`, `created_at`, `updated_at`) VALUES
(21, 16, 7, '2026-02-05', '10:00:00', '22:00:00', 12.00, 0, 0, 0, NULL, '2026-02-06 19:28:58', '2026-02-06 19:28:58'),
(22, 17, 7, '2026-02-22', NULL, NULL, 0.00, 0, 0, 1, NULL, '2026-02-18 19:18:19', '2026-02-18 19:18:19'),
(23, 16, 7, '2026-02-22', NULL, NULL, 0.00, 0, 0, 1, NULL, '2026-02-18 19:18:22', '2026-02-18 19:18:22'),
(24, 16, 7, '2026-02-23', NULL, NULL, 0.00, 0, 0, 1, NULL, '2026-02-24 12:08:22', '2026-02-24 12:08:22'),
(25, 16, 7, '2026-02-24', NULL, NULL, 0.00, 0, 0, 1, NULL, '2026-02-24 12:08:25', '2026-02-24 12:08:25'),
(26, 18, 7, '2026-02-24', NULL, NULL, 0.00, 0, 0, 1, NULL, '2026-02-24 12:08:26', '2026-02-24 12:08:26'),
(27, 18, 7, '2026-02-23', NULL, NULL, 0.00, 0, 0, 1, NULL, '2026-02-24 12:08:28', '2026-02-24 12:08:28'),
(28, 17, 7, '2026-02-23', NULL, NULL, 0.00, 0, 0, 1, NULL, '2026-02-24 12:08:37', '2026-02-24 12:08:37');

-- --------------------------------------------------------

--
-- Структура таблицы `supply_orders`
--

CREATE TABLE `supply_orders` (
  `id` int(11) UNSIGNED NOT NULL,
  `location_id` int(11) UNSIGNED NOT NULL,
  `created_by` int(11) UNSIGNED NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Заказы поставок по точкам';

--
-- Дамп данных таблицы `supply_orders`
--

INSERT INTO `supply_orders` (`id`, `location_id`, `created_by`, `status`, `comment`, `created_at`) VALUES
(3, 7, 13, 'new', NULL, '2026-02-06 19:49:53'),
(4, 5, 13, 'new', NULL, '2026-02-06 20:17:11'),
(5, 10, 14, 'new', NULL, '2026-02-06 20:52:45'),
(7, 7, 17, 'new', NULL, '2026-02-24 11:26:20'),
(8, 7, 13, 'new', NULL, '2026-02-27 21:34:46'),
(9, 7, 13, 'new', NULL, '2026-02-27 21:36:52'),
(10, 7, 13, 'new', NULL, '2026-02-27 21:36:57'),
(11, 7, 13, 'new', NULL, '2026-02-27 21:37:25');

-- --------------------------------------------------------

--
-- Структура таблицы `supply_order_items`
--

CREATE TABLE `supply_order_items` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `quantity` decimal(10,3) NOT NULL DEFAULT 0.000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Позиции заказов поставок';

--
-- Дамп данных таблицы `supply_order_items`
--

INSERT INTO `supply_order_items` (`id`, `order_id`, `product_id`, `quantity`) VALUES
(1, 3, 2, 0.060),
(2, 4, 12, 1.180),
(3, 4, 19, 0.050),
(4, 4, 48, 0.150),
(5, 4, 23, 0.040),
(6, 4, 38, 0.040),
(7, 5, 36, 0.070),
(8, 5, 30, 0.060),
(9, 5, 37, 0.040),
(10, 7, 12, 1.000),
(11, 8, 12, 2.000),
(12, 8, 45, 3.000),
(13, 9, 12, 2.000),
(14, 9, 45, 3.000),
(15, 10, 12, 2.000),
(16, 10, 45, 3.000),
(17, 11, 12, 2.000),
(18, 11, 45, 3.000);

-- --------------------------------------------------------

--
-- Структура таблицы `tech_cards`
--

CREATE TABLE `tech_cards` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Название позиции',
  `category` varchar(100) DEFAULT NULL COMMENT 'Категория (напитки, десерты, блюда и т.д.)',
  `prep_time` int(11) UNSIGNED DEFAULT NULL COMMENT 'Время приготовления (минуты)',
  `description` text DEFAULT NULL COMMENT 'Описание / технология приготовления',
  `serving` varchar(255) DEFAULT NULL COMMENT 'Вариант подачи',
  `image` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Технологические карты';

--
-- Дамп данных таблицы `tech_cards`
--

INSERT INTO `tech_cards` (`id`, `name`, `category`, `prep_time`, `description`, `serving`, `image`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Дексик', 'Котик', 60, 'Найти на авито, взять, покормить, полюбить', 'Красиво обернуть в корм', 'cards/qWajXhsfbeR6.jpg', 1, 13, '2026-02-27 23:41:15', '2026-02-27 23:41:15'),
(1001, 'Клубника-маракуйя вайб', 'Вайбы', 5, 'Чайный напиток с клубникой и маракуйей. Готовится в шейкере, доводится до нужной температуры за счёт льда.', 'Стакан / хайбол, лёд по стандарту', NULL, 1, NULL, '2026-03-02 22:39:41', '2026-03-02 22:39:41'),
(1002, 'Киви-маракуйя вайб', 'Вайбы', 5, 'Чайный напиток с киви и маракуйей. Готовится в шейкере, доводится до нужной температуры за счёт льда.', 'Стакан / хайбол, лёд по стандарту', NULL, 1, NULL, '2026-03-02 22:39:41', '2026-03-02 22:39:41'),
(1003, 'Апельсиновый вайб', 'Вайбы', 5, 'Цитрусовый чайный напиток с апельсином. Взбивается в шейкере и подаётся со слайсами цитрусов.', 'Стакан / хайбол, лёд по стандарту', NULL, 1, NULL, '2026-03-02 22:39:41', '2026-03-02 22:39:41'),
(1004, 'Манго маракуйя Mix', 'Вайбы', 5, 'Напиток на основе зелёного чая и премикса манго-маракуйя. Готовится в шейкере.', 'Стакан / хайбол, лёд по стандарту', NULL, 1, NULL, '2026-03-02 22:39:41', '2026-03-02 22:39:41'),
(1005, 'Грейпфрутовый вайб', 'Вайбы', 5, 'Чайный напиток с грейпфрутом. Доводится до нужной температуры за счёт воды или льда.', 'Стакан / хайбол, лёд по стандарту', NULL, 1, NULL, '2026-03-02 22:39:41', '2026-03-02 22:39:41'),
(1006, 'Гуава Вишня с кокосовым', 'Вайбы', 5, 'Напиток с премиксом гуава-вишня и кокосовым желе. Готовится в шейкере.', 'Стакан / хайбол, лёд по стандарту', NULL, 1, NULL, '2026-03-02 22:39:41', '2026-03-02 22:39:41'),
(1007, 'Лайм лимон ти', 'Вайбы', 5, 'Цитрусовый чайный напиток с лаймом и лимоном. Лимон мадлится, напиток взбивается в шейкере.', 'Стакан / хайбол, лёд по стандарту', NULL, 1, NULL, '2026-03-02 22:39:41', '2026-03-02 22:39:41'),
(1008, 'Алоэ Ромашка', 'Вайбы', 5, 'Напиток на основе чая камомил с алоэ и кокосовым соусом. Взбивается и переливается в стакан.', 'Стакан / хайбол, лёд по стандарту', NULL, 1, NULL, '2026-03-02 22:39:41', '2026-03-02 22:39:41'),
(1009, 'Гуава гранат Mix', 'Вайбы', 5, 'Напиток с премиксом гуава-гранат. Лимон мадлится в стакане, напиток взбивается в шейкере.', 'Стакан / хайбол, лёд по стандарту', NULL, 1, NULL, '2026-03-02 22:39:41', '2026-03-02 22:39:41');

-- --------------------------------------------------------

--
-- Структура таблицы `tech_card_ingredients`
--

CREATE TABLE `tech_card_ingredients` (
  `id` int(11) UNSIGNED NOT NULL,
  `card_id` int(11) UNSIGNED NOT NULL,
  `size_code` varchar(20) DEFAULT NULL COMMENT 'Размер: S, M, L и т.п. (NULL = общий для всех)',
  `size_id` int(11) UNSIGNED DEFAULT NULL,
  `ingredient_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Количество',
  `unit` varchar(50) NOT NULL DEFAULT 'г' COMMENT 'Единица: г, мл, шт и т.д.',
  `price_per_unit` decimal(10,2) DEFAULT NULL COMMENT 'Цена за единицу (для расчёта себестоимости)',
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ингредиенты ТТК';

--
-- Дамп данных таблицы `tech_card_ingredients`
--

INSERT INTO `tech_card_ingredients` (`id`, `card_id`, `size_code`, `size_id`, `ingredient_name`, `quantity`, `unit`, `price_per_unit`, `sort_order`) VALUES
(1, 1, NULL, NULL, 'Молоко сухое', 30.00, 'г', NULL, 0),
(2, 1, NULL, NULL, 'Сгущенка', 10.00, 'г', NULL, 1),
(3, 1, NULL, NULL, 'Чай', 150.00, 'г', NULL, 2),
(4, 1, NULL, NULL, 'Фруктоза', 15.00, 'г', NULL, 3),
(5, 1, NULL, NULL, 'Тапиока', 40.00, 'г', NULL, 4),
(6, 1001, 'M', NULL, 'Чай зелёный', 150.00, 'мл', NULL, 10),
(7, 1001, 'L', NULL, 'Чай зелёный', 200.00, 'мл', NULL, 11),
(8, 1001, 'M', NULL, 'Фруктоза', 15.00, 'мл', NULL, 12),
(9, 1001, 'L', NULL, 'Фруктоза', 20.00, 'мл', NULL, 13),
(10, 1001, 'M', NULL, 'Пюре маракуйя', 5.00, 'г', NULL, 14),
(11, 1001, 'L', NULL, 'Пюре маракуйя', 10.00, 'г', NULL, 15),
(12, 1001, 'M', NULL, 'Пюре клубники', 5.00, 'г', NULL, 16),
(13, 1001, 'L', NULL, 'Пюре клубники', 10.00, 'г', NULL, 17),
(14, 1001, NULL, NULL, 'Косточки маракуйи', 15.00, 'г', NULL, 18),
(15, 1001, NULL, NULL, 'Лимон (слайс / долька)', 1.00, 'шт', NULL, 19),
(16, 1002, 'M', NULL, 'Чай зелёный / камелия', 150.00, 'мл', NULL, 10),
(17, 1002, 'L', NULL, 'Чай зелёный / камелия', 200.00, 'мл', NULL, 11),
(18, 1002, 'M', NULL, 'Фруктоза', 15.00, 'мл', NULL, 12),
(19, 1002, 'L', NULL, 'Фруктоза', 20.00, 'мл', NULL, 13),
(20, 1002, 'M', NULL, 'Пюре маракуйя', 5.00, 'г', NULL, 14),
(21, 1002, 'L', NULL, 'Пюре маракуйя', 10.00, 'г', NULL, 15),
(22, 1002, 'M', NULL, 'Пюре киви', 5.00, 'г', NULL, 16),
(23, 1002, 'L', NULL, 'Пюре киви', 10.00, 'г', NULL, 17),
(24, 1002, NULL, NULL, 'Лимон (слайс / долька)', 1.00, 'шт', NULL, 18),
(25, 1003, 'M', NULL, 'Чай зелёный', 150.00, 'мл', NULL, 10),
(26, 1003, 'L', NULL, 'Чай зелёный', 200.00, 'мл', NULL, 11),
(27, 1003, 'M', NULL, 'Фруктоза', 15.00, 'мл', NULL, 12),
(28, 1003, 'L', NULL, 'Фруктоза', 20.00, 'мл', NULL, 13),
(29, 1003, 'M', NULL, 'Пюре апельсина', 25.00, 'мл', NULL, 14),
(30, 1003, 'L', NULL, 'Пюре апельсина', 30.00, 'мл', NULL, 15),
(31, 1003, NULL, NULL, 'Слайс апельсина крупный', 2.00, 'шт', NULL, 16),
(32, 1004, 'M', NULL, 'Чай зелёный', 150.00, 'мл', NULL, 10),
(33, 1004, 'L', NULL, 'Чай зелёный', 200.00, 'мл', NULL, 11),
(34, 1004, 'M', NULL, 'Фруктоза', 15.00, 'мл', NULL, 12),
(35, 1004, 'L', NULL, 'Фруктоза', 20.00, 'мл', NULL, 13),
(36, 1004, 'M', NULL, 'Премикс манго-маракуйя', 25.00, 'мл', NULL, 14),
(37, 1004, 'L', NULL, 'Премикс манго-маракуйя', 35.00, 'мл', NULL, 15),
(38, 1005, 'M', NULL, 'Чай зелёный', 150.00, 'мл', NULL, 10),
(39, 1005, 'L', NULL, 'Чай зелёный', 200.00, 'мл', NULL, 11),
(40, 1005, 'M', NULL, 'Фруктоза', 15.00, 'мл', NULL, 12),
(41, 1005, 'L', NULL, 'Фруктоза', 20.00, 'мл', NULL, 13),
(42, 1005, 'M', NULL, 'Пюре грейпфрута', 25.00, 'мл', NULL, 14),
(43, 1005, 'L', NULL, 'Пюре грейпфрута', 35.00, 'мл', NULL, 15),
(44, 1005, NULL, NULL, 'Грейпфрут (слайс)', 1.00, 'шт', NULL, 16),
(45, 1006, 'M', NULL, 'Чай зелёный', 150.00, 'мл', NULL, 10),
(46, 1006, 'M', NULL, 'Фруктоза', 15.00, 'мл', NULL, 11),
(47, 1006, 'M', NULL, 'Премикс гуава-вишня', 25.00, 'мл', NULL, 12),
(48, 1006, 'M', NULL, 'Кокосовое желе', 40.00, 'г', NULL, 13),
(49, 1007, 'M', NULL, 'Чай зелёный / дробим фрукты', 150.00, 'мл', NULL, 10),
(50, 1007, 'L', NULL, 'Чай зелёный / дробим фрукты', 200.00, 'мл', NULL, 11),
(51, 1007, 'M', NULL, 'Лимон', 1.00, 'шт', NULL, 12),
(52, 1007, 'L', NULL, 'Лимон', 2.00, 'шт', NULL, 13),
(53, 1007, 'M', NULL, 'Лайм', 1.00, 'шт', NULL, 14),
(54, 1007, 'L', NULL, 'Лайм', 2.00, 'шт', NULL, 15),
(55, 1008, 'M', NULL, 'Чай камомил', 150.00, 'мл', NULL, 10),
(56, 1008, 'L', NULL, 'Чай камомил', 200.00, 'мл', NULL, 11),
(57, 1008, 'M', NULL, 'Фруктоза', 15.00, 'мл', NULL, 12),
(58, 1008, 'L', NULL, 'Фруктоза', 20.00, 'мл', NULL, 13),
(59, 1008, 'M', NULL, 'Кокосовый соус', 30.00, 'мл', NULL, 14),
(60, 1008, 'L', NULL, 'Кокосовый соус', 40.00, 'мл', NULL, 15),
(61, 1008, 'M', NULL, 'Алоэ кусочки', 25.00, 'г', NULL, 16),
(62, 1008, 'L', NULL, 'Алоэ кусочки', 35.00, 'г', NULL, 17),
(63, 1009, 'S', NULL, 'Чай зелёный / камелия', 100.00, 'мл', NULL, 10),
(64, 1009, 'M', NULL, 'Чай зелёный / камелия', 150.00, 'мл', NULL, 11),
(65, 1009, 'L', NULL, 'Чай зелёный / камелия', 200.00, 'мл', NULL, 12),
(66, 1009, 'S', NULL, 'Премикс гуава-гранат', 20.00, 'мл', NULL, 13),
(67, 1009, 'M', NULL, 'Премикс гуава-гранат', 30.00, 'мл', NULL, 14),
(68, 1009, 'L', NULL, 'Премикс гуава-гранат', 40.00, 'мл', NULL, 15),
(69, 1009, 'S', NULL, 'Лимон (мадлится в стакане)', 1.00, 'шт', NULL, 16),
(70, 1009, 'M', NULL, 'Лимон (мадлится в стакане)', 1.00, 'шт', NULL, 17),
(71, 1009, 'L', NULL, 'Лимон (мадлится в стакане)', 2.00, 'шт', NULL, 18);

-- --------------------------------------------------------

--
-- Структура таблицы `tech_card_sizes`
--

CREATE TABLE `tech_card_sizes` (
  `id` int(11) UNSIGNED NOT NULL,
  `card_id` int(11) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL COMMENT 'Код размера: S, M, L, XL и т.п.',
  `label` varchar(50) NOT NULL COMMENT 'Отображаемое имя: S, M, Литр, 350 мл',
  `volume_ml` int(11) UNSIGNED DEFAULT NULL COMMENT 'Объём в мл (если актуально)',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Размер по умолчанию для карточки'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Размеры техкарт (S, M, L и т.п.)';

-- --------------------------------------------------------

--
-- Структура таблицы `training_materials`
--

CREATE TABLE `training_materials` (
  `id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `content` longtext NOT NULL COMMENT 'HTML-контент',
  `test_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'Привязка к тесту (опционально)',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Теоретические материалы';

--
-- Дамп данных таблицы `training_materials`
--

INSERT INTO `training_materials` (`id`, `title`, `category`, `content`, `test_id`, `sort_order`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'декстер телрия', 'котик', 'бла бла бла', 1, 0, 1, 13, '2026-02-28 23:52:50', '2026-02-28 23:52:50');

-- --------------------------------------------------------

--
-- Структура таблицы `training_questions`
--

CREATE TABLE `training_questions` (
  `id` int(11) UNSIGNED NOT NULL,
  `test_id` int(11) UNSIGNED NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Порядок вопроса',
  `question_text` text NOT NULL COMMENT 'Текст вопроса',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Варианты ответов: [{"id":1,"text":"...","is_correct":true}, ...]' CHECK (json_valid(`options`)),
  `correct_answer` varchar(50) DEFAULT NULL COMMENT 'ID правильного ответа (для одиночного выбора — индекс или ключ)',
  `points` int(11) NOT NULL DEFAULT 1 COMMENT 'Баллы за правильный ответ',
  `image` varchar(500) DEFAULT NULL COMMENT 'Изображение к вопросу',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Вопросы тестов';

--
-- Дамп данных таблицы `training_questions`
--

INSERT INTO `training_questions` (`id`, `test_id`, `sort_order`, `question_text`, `options`, `correct_answer`, `points`, `image`, `created_at`, `updated_at`) VALUES
(1, 1, 0, '1', '\"[{\\\"id\\\":\\\"1\\\",\\\"text\\\":\\\"1\\\",\\\"is_correct\\\":false},{\\\"id\\\":\\\"2\\\",\\\"text\\\":\\\"1\\\",\\\"is_correct\\\":false},{\\\"id\\\":\\\"3\\\",\\\"text\\\":\\\"1\\\",\\\"is_correct\\\":false},{\\\"id\\\":\\\"4\\\",\\\"text\\\":\\\"0\\\",\\\"is_correct\\\":true}]\"', '4', 1, NULL, '2026-02-27 23:09:37', '2026-02-27 23:09:37'),
(2, 1, 1, '2', '\"[{\\\"id\\\":\\\"1\\\",\\\"text\\\":\\\"2\\\",\\\"is_correct\\\":false},{\\\"id\\\":\\\"2\\\",\\\"text\\\":\\\"2\\\",\\\"is_correct\\\":false},{\\\"id\\\":\\\"3\\\",\\\"text\\\":\\\"0\\\",\\\"is_correct\\\":true}]\"', '3', 1, NULL, '2026-02-27 23:10:01', '2026-02-27 23:10:01'),
(3, 1, 2, '3', '\"[{\\\"id\\\":\\\"1\\\",\\\"text\\\":\\\"0\\\",\\\"is_correct\\\":true},{\\\"id\\\":\\\"2\\\",\\\"text\\\":\\\"3\\\",\\\"is_correct\\\":false},{\\\"id\\\":\\\"3\\\",\\\"text\\\":\\\"3\\\",\\\"is_correct\\\":false}]\"', '1', 1, NULL, '2026-02-27 23:10:01', '2026-02-27 23:10:01');

-- --------------------------------------------------------

--
-- Структура таблицы `training_results`
--

CREATE TABLE `training_results` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `test_id` int(11) UNSIGNED NOT NULL,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Набранный балл (%)',
  `points_earned` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Набранные баллы',
  `points_max` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Максимум баллов в тесте',
  `time_spent` int(11) UNSIGNED DEFAULT NULL COMMENT 'Время прохождения (секунды)',
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `answers_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Ответы по вопросам (для разбора)' CHECK (json_valid(`answers_data`)),
  `started_at` datetime NOT NULL,
  `finished_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Результаты прохождения тестов';

--
-- Дамп данных таблицы `training_results`
--

INSERT INTO `training_results` (`id`, `user_id`, `test_id`, `score`, `points_earned`, `points_max`, `time_spent`, `passed`, `answers_data`, `started_at`, `finished_at`) VALUES
(1, 17, 1, 100.00, 3, 3, 14, 1, '\"{\\\"1\\\":{\\\"question_id\\\":1,\\\"user_answer\\\":\\\"4\\\",\\\"correct\\\":true},\\\"2\\\":{\\\"question_id\\\":2,\\\"user_answer\\\":\\\"3\\\",\\\"correct\\\":true},\\\"3\\\":{\\\"question_id\\\":3,\\\"user_answer\\\":\\\"1\\\",\\\"correct\\\":true}}\"', '2026-02-27 20:10:16', '2026-02-27 20:10:30'),
(2, 17, 1, 33.33, 1, 3, 9, 0, '\"{\\\"1\\\":{\\\"question_id\\\":1,\\\"user_answer\\\":\\\"3\\\",\\\"correct\\\":false},\\\"2\\\":{\\\"question_id\\\":2,\\\"user_answer\\\":\\\"3\\\",\\\"correct\\\":true},\\\"3\\\":{\\\"question_id\\\":3,\\\"user_answer\\\":\\\"2\\\",\\\"correct\\\":false}}\"', '2026-02-27 20:10:59', '2026-02-27 20:11:08'),
(3, 13, 1, 100.00, 3, 3, 21, 1, '\"{\\\"1\\\":{\\\"question_id\\\":1,\\\"user_answer\\\":\\\"4\\\",\\\"correct\\\":true},\\\"2\\\":{\\\"question_id\\\":2,\\\"user_answer\\\":\\\"3\\\",\\\"correct\\\":true},\\\"3\\\":{\\\"question_id\\\":3,\\\"user_answer\\\":\\\"1\\\",\\\"correct\\\":true}}\"', '2026-02-27 20:42:54', '2026-02-27 20:43:15'),
(4, 13, 1, 66.67, 2, 3, 5, 0, '\"{\\\"1\\\":{\\\"question_id\\\":1,\\\"user_answer\\\":\\\"4\\\",\\\"correct\\\":true},\\\"2\\\":{\\\"question_id\\\":2,\\\"user_answer\\\":\\\"3\\\",\\\"correct\\\":true},\\\"3\\\":{\\\"question_id\\\":3,\\\"user_answer\\\":\\\"2\\\",\\\"correct\\\":false}}\"', '2026-02-27 20:43:22', '2026-02-27 20:43:28'),
(5, 13, 1, 100.00, 3, 3, 6, 1, '\"{\\\"1\\\":{\\\"question_id\\\":1,\\\"user_answer\\\":\\\"4\\\",\\\"correct\\\":true},\\\"2\\\":{\\\"question_id\\\":2,\\\"user_answer\\\":\\\"3\\\",\\\"correct\\\":true},\\\"3\\\":{\\\"question_id\\\":3,\\\"user_answer\\\":\\\"1\\\",\\\"correct\\\":true}}\"', '2026-02-28 20:51:23', '2026-02-28 20:51:31'),
(6, 13, 1, 100.00, 3, 3, 5, 1, '\"{\\\"1\\\":{\\\"question_id\\\":1,\\\"user_answer\\\":\\\"4\\\",\\\"correct\\\":true},\\\"2\\\":{\\\"question_id\\\":2,\\\"user_answer\\\":\\\"3\\\",\\\"correct\\\":true},\\\"3\\\":{\\\"question_id\\\":3,\\\"user_answer\\\":\\\"1\\\",\\\"correct\\\":true}}\"', '2026-03-01 18:20:14', '2026-03-01 18:20:19');

-- --------------------------------------------------------

--
-- Структура таблицы `training_tests`
--

CREATE TABLE `training_tests` (
  `id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Название теста',
  `category` varchar(100) DEFAULT NULL COMMENT 'Категория (охрана труда, меню, стандарты и т.д.)',
  `description` text DEFAULT NULL COMMENT 'Описание теста',
  `time_limit` int(11) UNSIGNED DEFAULT NULL COMMENT 'Лимит времени на весь тест (секунды), NULL = без лимита',
  `question_time_limit` int(11) UNSIGNED DEFAULT NULL COMMENT 'Лимит времени на один вопрос (секунды), NULL = без лимита',
  `pass_score` decimal(5,2) NOT NULL DEFAULT 70.00 COMMENT 'Проходной балл (%)',
  `image` varchar(500) DEFAULT NULL COMMENT 'Изображение теста (URL или путь)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Тесты/викторины';

--
-- Дамп данных таблицы `training_tests`
--

INSERT INTO `training_tests` (`id`, `title`, `category`, `description`, `time_limit`, `question_time_limit`, `pass_score`, `image`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'тестовый тест', 'Тест', 'Тестовый тест для тестеров тестового теста', 360, 60, 80.00, '', 1, 13, '2026-02-27 23:08:28', '2026-02-27 23:08:28');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `phone` varchar(20) NOT NULL COMMENT 'Номер телефона',
  `email` varchar(255) NOT NULL COMMENT 'Электронная почта',
  `password_hash` varchar(255) NOT NULL COMMENT 'Хеш пароля',
  `position` enum('manager','location_manager','senior_teamaker','teamaker','trainee') NOT NULL COMMENT 'Должность',
  `location_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'ID точки закрепления (NULL для управляющих)',
  `avatar` varchar(500) DEFAULT NULL COMMENT 'Путь к аватарке',
  `first_name` varchar(100) DEFAULT NULL COMMENT 'Имя',
  `last_name` varchar(100) DEFAULT NULL COMMENT 'Фамилия',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Активен ли пользователь',
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Дата создания',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Дата обновления',
  `auth_key` varchar(32) DEFAULT NULL,
  `birthday` date DEFAULT NULL COMMENT 'День рождения',
  `telegram` varchar(100) DEFAULT NULL COMMENT 'Telegram',
  `certification_date` date DEFAULT NULL COMMENT 'Дата аттестации'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Пользователи системы. Управляющий (manager) может управлять несколькими точками через таблицу manager_locations. Менеджер точки (location_manager) закреплен за одной точкой через location_id. Остальные сотрудники также могут быть закреплены за точкой через location_id.';

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `phone`, `email`, `password_hash`, `position`, `location_id`, `avatar`, `first_name`, `last_name`, `is_active`, `is_admin`, `created_at`, `updated_at`, `auth_key`, `birthday`, `telegram`, `certification_date`) VALUES
(12, '+7 (999) 111-11-11', 'manager@cafe.ru', '$2y$13$CYmP8kc2k9kVuROKoQpHlOI4XesqplVJj4FVlFijNJlzIKzeL7atK', 'manager', NULL, NULL, 'Иван', 'Петров', 1, 0, '2026-02-01 19:52:53', '2026-02-01 19:52:53', '_UaxFUmmqeV8mWP94mxyu62L0fcYZ8lZ', NULL, NULL, NULL),
(13, '+7 (999) 000-00-01', 'admin', '$2y$12$1pPg38OvGfVUEBWBqpizt.cN9qUACYwuxwjDCSPGm.VUFUTJeJbru', 'manager', NULL, '/uploads/avatars/13_1772480563.png', 'Администратор', 'Системы', 1, 1, '2026-02-01 20:04:00', '2026-03-02 19:42:43', 'e002e7e62eb3cb489a4514c23b6f2bd0', NULL, NULL, NULL),
(14, '+79999999999', 'annasorokina@gmail.com', '$2y$13$l3x5hacB3U0vFXjF2RPGxuhMbzmgQOmB0/dseHcegrLzhz4rD3pe.', 'manager', NULL, NULL, 'Сорокина', 'Анна', 1, 0, '2026-02-06 18:59:25', '2026-02-06 18:59:25', 'HCv-sFb4vv4miyCJeWLMO3kqvXYr8Nm-', NULL, NULL, NULL),
(15, '+79118887766', 'alinaberlina@gmail.com', '$2y$13$6RlypR/ZURy7PVucy5hAju5YgSvJhg.c881k9xnq9jJdm7LTOiyem', 'manager', NULL, NULL, 'Алина', 'Берлина', 1, 0, '2026-02-06 19:01:34', '2026-02-06 19:01:34', 'eDRCHu0ykaEpvntUHsklJkGYAR9yd-w2', NULL, NULL, NULL),
(16, '+79992223344', 'kirstein@gmail.com', '$2y$13$DoGqmItBQ7GiPrdbF31jS.tQA/U9DJFghb2WgI3HOXiXUtjBvCPtS', 'location_manager', 7, NULL, 'Евгений', 'Кирштейн', 1, 0, '2026-02-06 19:20:33', '2026-02-06 19:20:33', 'uYHaEOG-je_1XKysp_ISKiAUmTGz8qwi', NULL, NULL, NULL),
(17, '+79994445566', 'vlasenko@gmail.com', '$2y$13$8tXiSw..2zPh/69dzcBh9.r2bfr0Au8NDjKmTkzMz1o370kl7iNny', 'location_manager', 7, NULL, 'Арина', 'Власенко', 1, 0, '2026-02-06 19:21:22', '2026-02-06 19:21:22', 'UDqjTfAC8WYW0rGdKHFVsIPUxm5QlhH3', NULL, NULL, NULL),
(18, '+7845227462', 'prihodko@gmail.com', '$2y$13$nbpKfXgcEznf6nUo8V.3UuJ4/Q8RzOcIbBFbcU0GxLXLwdFA7aiUm', 'teamaker', 7, NULL, 'Ксюша', 'Приходько', 1, 0, '2026-02-06 19:22:15', '2026-02-06 19:22:15', 'X1ylyPKcWynoVvkIh26hq66sU9ohZM-t', NULL, NULL, NULL),
(19, '+76543214567', 'ermak@gmail.com', '$2y$13$FQx3xE95am0IITczp7siYer7AW8A/3/7/TQI1uhdR385x8BZiew1m', 'location_manager', 11, NULL, 'Виктория', 'Ермак', 1, 0, '2026-02-06 19:23:00', '2026-02-06 19:23:00', 'MISFAEe4VkT-YjhQr17jdyKeh83BI6ev', NULL, NULL, NULL),
(20, '+79995552233', 'andreeva@gmail.com', '$2y$13$s2HhwbPmgJD6LRyDL2/6eOwrq6568J/f9cIGpKvmZyAC5DZ0/Ceu6', 'senior_teamaker', 11, NULL, 'Виктория', 'Андреева', 1, 0, '2026-02-06 19:23:52', '2026-02-06 19:23:52', 'gMqNncIJjNkhR8maiLZKuQPiFKDiVfsu', NULL, NULL, NULL),
(21, '+76542227766', 'konovalova@gmail.com', '$2y$13$lkVXhZkSD1GAFy0G/K2tt.e7jTXHHnekl0DYjF6AjM3LwbVlg8dBC', 'teamaker', 11, NULL, 'Алина', 'Коновалова', 1, 0, '2026-02-06 19:24:47', '2026-02-06 19:24:47', 'fBvKMxBzqE8PsTQcRWdBv23K6f95cPlD', NULL, NULL, NULL),
(22, '+78884443377', 'evdokimova@gmail.com', '$2y$13$Jjf1RtwrTZd5L0mU.GQWieBc/zE56zMV6ug/nK.VZk3cyQz1O/wXW', 'location_manager', 10, NULL, 'Ксюша', 'Евдокимова', 1, 0, '2026-02-06 19:25:49', '2026-02-06 19:25:49', 'j6QsdeCoOor7IGC7_fV-o3TGlyaPQc5x', NULL, NULL, NULL),
(23, '+79991234567', 'gunykina@gmail.com', '$2y$13$PReiT2kw0OV0tlMtr2ahkO2stePOUyWBviuEQrJHsokL35h.cCOuu', 'senior_teamaker', 10, NULL, 'Кира', 'Гунькина', 1, 0, '2026-02-06 19:26:37', '2026-02-06 19:26:37', 'p80hCPuVP7gCTIvLI_0k75wYUo-dhRbF', NULL, NULL, NULL),
(24, '+79326774387', 'shidlovskaya@gmail.com', '$2y$13$h78mC/hiB55Fl/LdrrVvn.uQsMI/zoN27VGCWv1TJ/avfsB1eFy.C', 'trainee', 10, NULL, 'Екатерина', 'Шидловская', 1, 0, '2026-02-06 19:28:17', '2026-02-06 19:28:17', 'NxBD9uKCohfJ9lZW1_VkAiuoTfb1sEuK', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_availability`
--

CREATE TABLE `user_availability` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `weekday` tinyint(4) NOT NULL COMMENT '1=Пн ... 7=Вс',
  `time_start` time DEFAULT NULL,
  `time_end` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `user_availability`
--

INSERT INTO `user_availability` (`id`, `user_id`, `weekday`, `time_start`, `time_end`) VALUES
(6, 16, 1, '10:00:00', '22:00:00'),
(7, 16, 2, '09:00:00', '21:30:00'),
(8, 16, 4, '10:00:00', '22:00:00'),
(9, 16, 5, '09:00:00', '21:00:00'),
(15, 17, 4, '10:00:00', '22:00:00'),
(16, 17, 5, '10:00:00', '22:00:00'),
(17, 17, 6, '10:00:00', '22:00:00'),
(18, 17, 7, '09:00:00', '16:00:00');

-- --------------------------------------------------------

--
-- Структура таблицы `user_availability_by_date`
--

CREATE TABLE `user_availability_by_date` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `time_start` time DEFAULT NULL,
  `time_end` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_availability_by_date`
--

INSERT INTO `user_availability_by_date` (`id`, `user_id`, `date`, `time_start`, `time_end`, `created_at`) VALUES
(1, 17, '2026-02-23', '09:00:00', '21:00:00', '2026-02-24 12:19:31'),
(2, 17, '2026-02-24', '09:00:00', '22:00:00', '2026-02-24 12:19:39'),
(3, 17, '2026-02-26', '10:00:00', '22:00:00', '2026-02-24 12:19:50'),
(4, 17, '2026-02-27', '11:00:00', '16:00:00', '2026-02-24 12:19:57'),
(5, 17, '2026-03-01', '09:00:00', '21:00:00', '2026-02-24 12:23:36'),
(6, 17, '2026-03-02', '10:00:00', '22:00:00', '2026-02-24 12:23:43'),
(7, 17, '2026-03-04', '16:00:00', '22:00:00', '2026-02-24 12:23:59'),
(8, 17, '2026-03-05', '09:00:00', '22:00:00', '2026-02-24 12:24:06'),
(9, 17, '2026-03-07', '16:00:00', '22:00:00', '2026-02-24 12:24:15'),
(10, 16, '2026-03-02', '10:00:00', '22:00:00', '2026-02-24 12:25:09'),
(11, 16, '2026-03-03', '09:00:00', '21:00:00', '2026-02-24 12:25:14'),
(12, 16, '2026-03-05', '10:00:00', '22:00:00', '2026-02-24 12:25:19'),
(13, 16, '2026-03-06', '09:00:00', '21:00:00', '2026-02-24 12:25:23'),
(14, 16, '2026-03-07', '11:00:00', '21:00:00', '2026-02-24 12:25:31'),
(15, 18, '2026-03-01', '09:00:00', '22:00:00', '2026-02-24 12:26:26'),
(16, 18, '2026-03-02', '10:00:00', '22:00:00', '2026-02-24 12:26:31'),
(17, 18, '2026-03-03', '10:00:00', '22:00:00', '2026-02-24 12:26:39'),
(18, 18, '2026-03-04', '09:00:00', '21:00:00', '2026-02-24 12:26:43'),
(19, 18, '2026-03-06', '10:00:00', '22:00:00', '2026-02-24 12:26:50'),
(20, 18, '2026-03-07', '11:00:00', '22:00:00', '2026-02-24 12:26:56'),
(21, 17, '2026-03-08', '09:00:00', '16:00:00', '2026-02-24 12:41:51'),
(22, 16, '2026-03-08', '10:00:00', '22:00:00', '2026-02-24 12:42:42');

-- --------------------------------------------------------

--
-- Структура таблицы `user_profile_card`
--

CREATE TABLE `user_profile_card` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED NOT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `unlocked_at` int(10) UNSIGNED DEFAULT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `daily_reports`
--
ALTER TABLE `daily_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_daily_location_date` (`location_id`,`report_date`),
  ADD KEY `idx_daily_reports_location_date` (`location_id`,`report_date`),
  ADD KEY `idx_daily_reports_manager` (`manager_id`);

--
-- Индексы таблицы `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Индексы таблицы `manager_locations`
--
ALTER TABLE `manager_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_manager_location` (`manager_id`,`location_id`),
  ADD KEY `idx_manager_id` (`manager_id`),
  ADD KEY `idx_location_id` (`location_id`);

--
-- Индексы таблицы `position_rates`
--
ALTER TABLE `position_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_position_rates_code` (`code`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_is_active` (`is_active`);

--
-- Индексы таблицы `profile_card_template`
--
ALTER TABLE `profile_card_template`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_profile_card_template_code` (`code`);

--
-- Индексы таблицы `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_push_subscriptions_user_id` (`user_id`);

--
-- Индексы таблицы `resource_cells`
--
ALTER TABLE `resource_cells`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_resource_cell_row_column` (`row_id`,`column_id`),
  ADD KEY `idx_resource_cells_row` (`row_id`),
  ADD KEY `idx_resource_cells_column` (`column_id`),
  ADD KEY `idx_resource_cells_updated_by` (`updated_by`);

--
-- Индексы таблицы `resource_cell_history`
--
ALTER TABLE `resource_cell_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resource_cell_history_cell` (`cell_id`),
  ADD KEY `idx_resource_cell_history_user` (`changed_by`);

--
-- Индексы таблицы `resource_columns`
--
ALTER TABLE `resource_columns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resource_columns_matrix` (`matrix_id`);

--
-- Индексы таблицы `resource_matrices`
--
ALTER TABLE `resource_matrices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_resource_matrices_code` (`code`);

--
-- Индексы таблицы `resource_rows`
--
ALTER TABLE `resource_rows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resource_rows_matrix` (`matrix_id`);

--
-- Индексы таблицы `schedule_shifts`
--
ALTER TABLE `schedule_shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_schedule_shifts_user_location_date` (`user_id`,`location_id`,`date`),
  ADD KEY `idx_schedule_shifts_location_date` (`location_id`,`date`),
  ADD KEY `idx_schedule_shifts_user_date` (`user_id`,`date`),
  ADD KEY `idx_schedule_shifts_flags` (`is_night`,`is_overtime`,`is_day_off`);

--
-- Индексы таблицы `supply_orders`
--
ALTER TABLE `supply_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_supply_orders_location` (`location_id`),
  ADD KEY `idx_supply_orders_created_by` (`created_by`);

--
-- Индексы таблицы `supply_order_items`
--
ALTER TABLE `supply_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_supply_order_items_order` (`order_id`),
  ADD KEY `idx_supply_order_items_product` (`product_id`);

--
-- Индексы таблицы `tech_cards`
--
ALTER TABLE `tech_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tech_cards_category` (`category`),
  ADD KEY `idx_tech_cards_is_active` (`is_active`);

--
-- Индексы таблицы `tech_card_ingredients`
--
ALTER TABLE `tech_card_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tech_card_ingredients_card_id` (`card_id`),
  ADD KEY `idx_tech_card_ingredients_size_id` (`size_id`);

--
-- Индексы таблицы `tech_card_sizes`
--
ALTER TABLE `tech_card_sizes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tech_card_sizes_card_id` (`card_id`);

--
-- Индексы таблицы `training_materials`
--
ALTER TABLE `training_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_training_materials_test_id` (`test_id`),
  ADD KEY `idx_training_materials_category` (`category`);

--
-- Индексы таблицы `training_questions`
--
ALTER TABLE `training_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_training_questions_test_id` (`test_id`);

--
-- Индексы таблицы `training_results`
--
ALTER TABLE `training_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_training_results_user_id` (`user_id`),
  ADD KEY `idx_training_results_test_id` (`test_id`),
  ADD KEY `idx_training_results_finished_at` (`finished_at`),
  ADD KEY `idx_training_results_score` (`score`);

--
-- Индексы таблицы `training_tests`
--
ALTER TABLE `training_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_training_tests_category` (`category`),
  ADD KEY `idx_training_tests_is_active` (`is_active`),
  ADD KEY `idx_training_tests_created_by` (`created_by`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_phone` (`phone`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_position` (`position`),
  ADD KEY `idx_location_id` (`location_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Индексы таблицы `user_availability`
--
ALTER TABLE `user_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_availability_user_weekday` (`user_id`,`weekday`);

--
-- Индексы таблицы `user_availability_by_date`
--
ALTER TABLE `user_availability_by_date`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_date` (`user_id`,`date`),
  ADD KEY `idx_user_availability_by_date_user_id` (`user_id`),
  ADD KEY `idx_user_availability_by_date_date` (`date`);

--
-- Индексы таблицы `user_profile_card`
--
ALTER TABLE `user_profile_card`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_profile_card_user_id` (`user_id`),
  ADD KEY `idx_user_profile_card_template_id` (`template_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `daily_reports`
--
ALTER TABLE `daily_reports`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT для таблицы `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT для таблицы `manager_locations`
--
ALTER TABLE `manager_locations`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `position_rates`
--
ALTER TABLE `position_rates`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT для таблицы `profile_card_template`
--
ALTER TABLE `profile_card_template`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `resource_cells`
--
ALTER TABLE `resource_cells`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `resource_cell_history`
--
ALTER TABLE `resource_cell_history`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `resource_columns`
--
ALTER TABLE `resource_columns`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `resource_matrices`
--
ALTER TABLE `resource_matrices`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `resource_rows`
--
ALTER TABLE `resource_rows`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `schedule_shifts`
--
ALTER TABLE `schedule_shifts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT для таблицы `supply_orders`
--
ALTER TABLE `supply_orders`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `supply_order_items`
--
ALTER TABLE `supply_order_items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `tech_cards`
--
ALTER TABLE `tech_cards`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1010;

--
-- AUTO_INCREMENT для таблицы `tech_card_ingredients`
--
ALTER TABLE `tech_card_ingredients`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT для таблицы `tech_card_sizes`
--
ALTER TABLE `tech_card_sizes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `training_materials`
--
ALTER TABLE `training_materials`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `training_questions`
--
ALTER TABLE `training_questions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `training_results`
--
ALTER TABLE `training_results`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `training_tests`
--
ALTER TABLE `training_tests`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT для таблицы `user_availability`
--
ALTER TABLE `user_availability`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `user_availability_by_date`
--
ALTER TABLE `user_availability_by_date`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT для таблицы `user_profile_card`
--
ALTER TABLE `user_profile_card`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `daily_reports`
--
ALTER TABLE `daily_reports`
  ADD CONSTRAINT `fk_daily_reports_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_daily_reports_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `manager_locations`
--
ALTER TABLE `manager_locations`
  ADD CONSTRAINT `fk_manager_locations_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_manager_locations_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD CONSTRAINT `fk_push_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `resource_cells`
--
ALTER TABLE `resource_cells`
  ADD CONSTRAINT `fk_resource_cells_column` FOREIGN KEY (`column_id`) REFERENCES `resource_columns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resource_cells_row` FOREIGN KEY (`row_id`) REFERENCES `resource_rows` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resource_cells_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `resource_cell_history`
--
ALTER TABLE `resource_cell_history`
  ADD CONSTRAINT `fk_resource_cell_history_cell` FOREIGN KEY (`cell_id`) REFERENCES `resource_cells` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resource_cell_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `resource_columns`
--
ALTER TABLE `resource_columns`
  ADD CONSTRAINT `fk_resource_columns_matrix` FOREIGN KEY (`matrix_id`) REFERENCES `resource_matrices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `resource_rows`
--
ALTER TABLE `resource_rows`
  ADD CONSTRAINT `fk_resource_rows_matrix` FOREIGN KEY (`matrix_id`) REFERENCES `resource_matrices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `schedule_shifts`
--
ALTER TABLE `schedule_shifts`
  ADD CONSTRAINT `fk_schedule_shifts_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_shifts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `supply_orders`
--
ALTER TABLE `supply_orders`
  ADD CONSTRAINT `fk_supply_orders_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_supply_orders_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `supply_order_items`
--
ALTER TABLE `supply_order_items`
  ADD CONSTRAINT `fk_supply_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `supply_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_supply_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tech_card_ingredients`
--
ALTER TABLE `tech_card_ingredients`
  ADD CONSTRAINT `fk_tech_card_ingredients_card` FOREIGN KEY (`card_id`) REFERENCES `tech_cards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tech_card_ingredients_size` FOREIGN KEY (`size_id`) REFERENCES `tech_card_sizes` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tech_card_sizes`
--
ALTER TABLE `tech_card_sizes`
  ADD CONSTRAINT `fk_tech_card_sizes_card` FOREIGN KEY (`card_id`) REFERENCES `tech_cards` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `training_materials`
--
ALTER TABLE `training_materials`
  ADD CONSTRAINT `fk_training_materials_test` FOREIGN KEY (`test_id`) REFERENCES `training_tests` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `training_questions`
--
ALTER TABLE `training_questions`
  ADD CONSTRAINT `fk_training_questions_test` FOREIGN KEY (`test_id`) REFERENCES `training_tests` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `training_results`
--
ALTER TABLE `training_results`
  ADD CONSTRAINT `fk_training_results_test` FOREIGN KEY (`test_id`) REFERENCES `training_tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_training_results_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_availability`
--
ALTER TABLE `user_availability`
  ADD CONSTRAINT `fk_user_availability_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_availability_by_date`
--
ALTER TABLE `user_availability_by_date`
  ADD CONSTRAINT `fk_user_availability_by_date_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_profile_card`
--
ALTER TABLE `user_profile_card`
  ADD CONSTRAINT `fk_user_profile_card_template` FOREIGN KEY (`template_id`) REFERENCES `profile_card_template` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_profile_card_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

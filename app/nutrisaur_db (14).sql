-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 26, 2025 at 04:45 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nutrisaur_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_food_recommendations`
--

CREATE TABLE `ai_food_recommendations` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `food_name` varchar(255) NOT NULL,
  `food_emoji` varchar(10) DEFAULT NULL,
  `food_description` text DEFAULT NULL,
  `ai_reasoning` text DEFAULT NULL,
  `nutritional_priority` varchar(100) DEFAULT 'general',
  `ingredients` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `nutritional_impact_score` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fcm_tokens`
--

CREATE TABLE `fcm_tokens` (
  `id` int(11) NOT NULL,
  `fcm_token` varchar(500) NOT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `user_barangay` varchar(255) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_used` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fcm_tokens`
--

INSERT INTO `fcm_tokens` (`id`, `fcm_token`, `device_name`, `user_email`, `user_barangay`, `app_version`, `platform`, `is_active`, `last_used`, `created_at`, `updated_at`) VALUES
(93, 'fOlBmdnBS6StEDxO0vVF64:APA91bH2661ip-pWOvWyv3AO3VeQD4shuj1fwPediUj3IoWO11A3jLJ1vCaJt_-NNZWCInEyKQ4TJUIg8TVc0SMiEtmk-reSVbYnJi73OOjMUGkHFrUTWLY', 'M2101K6P', 'jdjd@jdjd.k', 'Cupang North', '1.0', 'android', 1, NULL, '2025-08-18 14:06:15', '2025-08-20 03:46:12'),
(94, 'cY8LXQZIROiZ2eeGYkpUlZ:APA91bFlmoPatvlIATHy4boBf9hpwCHcMzdqgUVSSIQhpfhqH95rt5iaON6B_Hq4TR3yYEIGVySIKKgm_ZPmov8lg4__OeVErM57X29j820q6lP6oeLy4vQ', 'RMX2001', 'ramziene123@gmail.com', 'Poblacion', '1.0', 'android', 1, NULL, '2025-08-20 00:46:28', '2025-08-20 00:46:28');

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `notification_type` varchar(100) DEFAULT NULL,
  `target_type` enum('all','municipality','barangay','specific') DEFAULT NULL,
  `target_value` varchar(255) DEFAULT NULL,
  `tokens_sent` int(11) DEFAULT NULL,
  `success` tinyint(1) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_logs`
--

INSERT INTO `notification_logs` (`id`, `event_id`, `notification_type`, `target_type`, `target_value`, `tokens_sent`, `success`, `error_message`, `created_at`) VALUES
(1, 75, 'imported_event', 'barangay', 'Sample Location', 0, 0, 'No FCM tokens found for location', '2025-08-18 11:51:48'),
(2, 76, 'new_event', 'barangay', 'A. Rivera (Pob.)', 0, 0, 'No FCM tokens found for location', '2025-08-18 11:52:44'),
(3, 0, 'test', 'all', 'all', 1, 1, NULL, '2025-08-18 12:04:06'),
(4, 77, 'new_event', 'barangay', 'A. Rivera (Pob.)', 0, 0, 'No FCM tokens found for location', '2025-08-18 12:10:16'),
(5, 78, 'new_event', 'barangay', 'A. Rivera (Pob.)', 0, 0, 'No FCM tokens found for location', '2025-08-18 12:24:26'),
(6, 79, 'new_event', 'barangay', 'A. Rivera (Pob.)', 0, 0, 'No FCM tokens found for location', '2025-08-18 12:38:15'),
(7, 80, 'new_event', 'barangay', 'A. Rivera (Pob.)', 1, 0, NULL, '2025-08-18 12:43:27'),
(8, 81, 'new_event', 'barangay', 'A. Rivera (Pob.)', 1, 0, NULL, '2025-08-18 12:53:57'),
(9, 82, 'new_event', 'barangay', 'A. Rivera (Pob.)', 1, 0, NULL, '2025-08-18 12:54:33'),
(10, 83, 'new_event', 'barangay', 'A. Rivera (Pob.)', 1, 0, NULL, '2025-08-18 12:58:40'),
(11, 84, 'new_event', 'barangay', 'A. Rivera (Pob.)', 1, 0, NULL, '2025-08-18 13:03:23'),
(12, 85, 'new_event', 'barangay', 'A. Rivera (Pob.)', 13, 0, NULL, '2025-08-18 13:20:03'),
(13, 86, 'new_event', 'barangay', 'A. Rivera (Pob.)', 1, 1, NULL, '2025-08-18 14:23:10'),
(14, 87, 'new_event', 'barangay', 'Alion', 1, 1, NULL, '2025-08-18 14:25:28'),
(15, 88, 'new_event', 'barangay', 'A. Rivera (Pob.)', 1, 1, NULL, '2025-08-18 14:39:20'),
(16, 89, 'new_event', 'barangay', 'A. Rivera (Pob.)', 1, 1, NULL, '2025-08-19 16:20:39'),
(17, 90, 'new_event', 'barangay', 'A. Rivera (Pob.)', 0, 0, 'No FCM tokens found for location', '2025-08-19 17:52:25'),
(18, 91, 'imported_event', 'barangay', 'Sample Location', 0, 0, 'No FCM tokens found for location', '2025-08-19 18:58:30'),
(19, 92, 'imported_event', 'barangay', 'Sample Location', 0, 0, 'No FCM tokens found for location', '2025-08-19 19:15:02'),
(20, 93, 'imported_event', 'barangay', 'Sample Location', 0, 0, 'No FCM tokens found for location', '2025-08-19 19:15:56'),
(21, 94, 'new_event', 'barangay', 'Lamao', 0, 0, 'No FCM tokens found for location', '2025-08-19 19:16:14'),
(22, 95, 'new_event', 'barangay', 'MUNICIPALITY_BAGAC', 0, 0, 'No FCM tokens found for location', '2025-08-19 19:16:54'),
(23, 96, 'new_event', 'barangay', 'MUNICIPALITY_ABUCAY', 0, 0, 'No FCM tokens found for location', '2025-08-19 19:17:28'),
(24, 98, 'new_event', 'all', 'all', 0, 0, 'No FCM tokens found for location', '2025-08-19 21:22:50'),
(25, 99, 'new_event', 'all', 'all', 0, 0, 'No FCM tokens found for location', '2025-08-19 21:23:09'),
(26, 100, 'new_event', 'barangay', 'A. Rivera (Pob.)', 1, 1, NULL, '2025-08-19 22:15:11'),
(27, 101, 'imported_event', 'barangay', 'Sample Location', 0, 0, 'No FCM tokens found for location', '2025-08-20 00:36:20'),
(28, 102, 'imported_event', 'barangay', 'Sample Location', 0, 0, 'No FCM tokens found for location', '2025-08-20 00:36:54'),
(29, 103, 'new_event', 'barangay', 'A. Rivera (Pob.)', 1, 1, NULL, '2025-08-20 00:38:32'),
(30, 104, 'new_event', 'all', 'all', 1, 1, NULL, '2025-08-20 04:01:03');

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_goals`
--

CREATE TABLE `nutrition_goals` (
  `goal_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `daily_calories` int(11) DEFAULT 2000,
  `daily_protein_g` int(11) DEFAULT 50,
  `daily_carbs_g` int(11) DEFAULT 250,
  `daily_fat_g` int(11) DEFAULT 70,
  `daily_water_ml` int(11) DEFAULT 2000,
  `nutrition_risk_score` int(11) DEFAULT 0,
  `start_date` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_time` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `organizer` varchar(100) DEFAULT NULL,
  `created_at` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `title`, `type`, `description`, `date_time`, `location`, `organizer`, `created_at`) VALUES
(102, 'Sample Event', 'Workshop', 'Sample description', '2025-08-27 08:36:00', 'Sample Location', 'Sample Organizer', 1755650214000),
(103, 'yhvghefws', 'Workshop', 'dwdwdwd', '2025-09-05 08:38:00', 'A. Rivera (Pob.)', 'dawdaw', 20250820083832),
(104, 'Emergency Malnutrition Intervention Program', 'Webinar', 'Immediate intervention for high-risk individuals with risk scores ≥50. Includes therapeutic feeding, medical monitoring, and family counseling.', '2025-08-27 04:00:00', '', 'sKevin', 20250820120101);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `created_at`, `last_login`, `is_active`) VALUES
(334, 'Kevin', 'kevinpingol123@gmail.com', '$2y$10$kCS2f2HMP4DrBI.8q4qRjOUAshCe/nHT9TbSiaTF1FK/34RATVypK', '2025-08-15 16:41:32', '2025-08-22 08:03:31', 1),
(335, 'sKevin', 'kevinpingols123@gmail.com', '$2y$10$4q97cGTt8OyhHilWTLgHreVPZPAkm.i3k6VC590rY/X3uRtiikNUK', '2025-08-20 03:35:38', '2025-08-20 03:35:40', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_events`
--

CREATE TABLE `user_events` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `program_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('joined','left','pending') DEFAULT 'joined'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `event_id` int(11) NOT NULL,
  `notification_type` varchar(50) DEFAULT 'email',
  `status` enum('pending','read','sent') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `height` float DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `bmi` float DEFAULT NULL,
  `muac` float DEFAULT NULL,
  `swelling` varchar(10) DEFAULT NULL,
  `weight_loss` varchar(20) DEFAULT NULL,
  `dietary_diversity` int(11) DEFAULT NULL,
  `feeding_behavior` varchar(20) DEFAULT NULL,
  `physical_thin` tinyint(1) DEFAULT 0,
  `physical_shorter` tinyint(1) DEFAULT 0,
  `physical_weak` tinyint(1) DEFAULT 0,
  `physical_none` tinyint(1) DEFAULT 0,
  `physical_signs` text DEFAULT NULL,
  `has_recent_illness` tinyint(1) DEFAULT 0,
  `has_eating_difficulty` tinyint(1) DEFAULT 0,
  `has_food_insecurity` tinyint(1) DEFAULT 0,
  `has_micronutrient_deficiency` tinyint(1) DEFAULT 0,
  `has_functional_decline` tinyint(1) DEFAULT 0,
  `goal` varchar(255) DEFAULT NULL,
  `risk_score` int(11) DEFAULT NULL,
  `screening_answers` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `diet_prefs` text DEFAULT NULL,
  `avoid_foods` text DEFAULT NULL,
  `barangay` varchar(255) DEFAULT NULL,
  `income` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_email`, `username`, `name`, `birthday`, `age`, `gender`, `height`, `weight`, `bmi`, `muac`, `swelling`, `weight_loss`, `dietary_diversity`, `feeding_behavior`, `physical_thin`, `physical_shorter`, `physical_weak`, `physical_none`, `physical_signs`, `has_recent_illness`, `has_eating_difficulty`, `has_food_insecurity`, `has_micronutrient_deficiency`, `has_functional_decline`, `goal`, `risk_score`, `screening_answers`, `allergies`, `diet_prefs`, `avoid_foods`, `barangay`, `income`, `created_at`, `updated_at`) VALUES
(518, 'udhd@udjd.kene', 'jrhd', NULL, '2003-08-20', 0, 'boy', 171, 50, 0, 0, 'no', '>10%', 5, 'poor appetite', 1, 0, 0, 0, NULL, 0, 0, 0, 0, 0, NULL, 66, '{\"action\":\"save_screening\",\"email\":\"udhd@udjd.kene\",\"username\":\"udhd@udjd.kene\",\"risk_score\":66,\"birthday\":\"2003-08-20\",\"age\":0,\"gender\":\"boy\",\"weight\":50,\"height\":171,\"bmi\":0,\"muac\":\"\",\"swelling\":\"no\",\"weight_loss\":\"\\u003E10%\",\"dietary_diversity\":5,\"feeding_behavior\":\"poor appetite\",\"physical_thin\":true,\"physical_shorter\":false,\"physical_weak\":false,\"physical_none\":false,\"has_recent_illness\":false,\"has_eating_difficulty\":false,\"has_food_insecurity\":false,\"has_micronutrient_deficiency\":false,\"has_functional_decline\":false,\"barangay\":\"A. Rivera (Pob.)\",\"income\":\"Below PHP 12,030\\/month (Below poverty line)\"}', NULL, NULL, NULL, 'A. Rivera (Pob.)', 'Below PHP 12,030/month (Below poverty line)', '2025-08-20 03:37:33', '2025-08-20 03:38:00'),
(520, 'jdjd@jdjd.k', 'jdjdjd', NULL, '2003-08-20', 0, 'girl', 180, 150, 0, 0, 'no', '<5% or none', 5, 'good appetite', 1, 0, 0, 0, NULL, 0, 0, 0, 0, 0, NULL, 13, '{\"action\":\"save_screening\",\"email\":\"jdjd@jdjd.k\",\"username\":\"jdjd@jdjd.k\",\"risk_score\":13,\"birthday\":\"2003-08-20\",\"age\":0,\"gender\":\"girl\",\"weight\":150,\"height\":180,\"bmi\":0,\"muac\":\"\",\"swelling\":\"no\",\"weight_loss\":\"\\u003C5% or none\",\"dietary_diversity\":5,\"feeding_behavior\":\"good appetite\",\"physical_thin\":true,\"physical_shorter\":false,\"physical_weak\":false,\"physical_none\":false,\"has_recent_illness\":false,\"has_eating_difficulty\":false,\"has_food_insecurity\":false,\"has_micronutrient_deficiency\":false,\"has_functional_decline\":false,\"barangay\":\"Cupang North\",\"income\":\"Below PHP 12,030\\/month (Below poverty line)\"}', NULL, NULL, NULL, 'Cupang North', 'Below PHP 12,030/month (Below poverty line)', '2025-08-20 03:42:30', '2025-08-20 03:46:12'),
(522, 'udhdh@udje.jdjd', 'hdnd', NULL, NULL, NULL, '', 0, 0, 0, 0, 'no', '<5% or none', 0, 'good appetite', 0, 0, 0, 0, NULL, 0, 0, 0, 0, 0, NULL, 0, '[]', NULL, NULL, NULL, '', '', '2025-08-20 03:59:36', '2025-08-20 03:59:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_admin_active` (`is_active`);

--
-- Indexes for table `ai_food_recommendations`
--
ALTER TABLE `ai_food_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ai_food_user_email` (`user_email`),
  ADD KEY `idx_ai_food_priority` (`nutritional_priority`),
  ADD KEY `idx_ai_food_created_at` (`created_at`);

--
-- Indexes for table `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fcm_token` (`fcm_token`),
  ADD KEY `idx_user_barangay` (`user_barangay`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `nutrition_goals`
--
ALTER TABLE `nutrition_goals`
  ADD PRIMARY KEY (`goal_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD KEY `idx_programs_datetime` (`date_time`),
  ADD KEY `idx_programs_type` (`type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_created_at` (`created_at`),
  ADD KEY `idx_users_active` (`is_active`);

--
-- Indexes for table `user_events`
--
ALTER TABLE `user_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_event` (`user_email`,`program_id`),
  ADD KEY `user_email` (`user_email`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `idx_user_events_status` (`status`),
  ADD KEY `idx_user_events_joined_at` (`joined_at`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_email` (`user_email`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_email` (`user_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_food_recommendations`
--
ALTER TABLE `ai_food_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `nutrition_goals`
--
ALTER TABLE `nutrition_goals`
  MODIFY `goal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=336;

--
-- AUTO_INCREMENT for table `user_events`
--
ALTER TABLE `user_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=523;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_food_recommendations`
--
ALTER TABLE `ai_food_recommendations`
  ADD CONSTRAINT `fk_ai_food_user_email` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `nutrition_goals`
--
ALTER TABLE `nutrition_goals`
  ADD CONSTRAINT `nutrition_goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_events`
--
ALTER TABLE `user_events`
  ADD CONSTRAINT `fk_user_events_email` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_events_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `fk_user_notifications_email` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

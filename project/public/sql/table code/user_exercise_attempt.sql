-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: db_server
-- Generation Time: Jun 01, 2026 at 08:11 AM
-- Server version: 9.3.0
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `IndiGo`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_exercise_attempt`
--

CREATE TABLE `user_exercise_attempt` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `exercise_id` int UNSIGNED NOT NULL,
  `answer_given` text,
  `is_correct` tinyint(1) NOT NULL,
  `time_taken_ms` int UNSIGNED DEFAULT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user_exercise_attempt`
--
ALTER TABLE `user_exercise_attempt`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_exercise` (`user_id`,`exercise_id`),
  ADD KEY `exercise_id` (`exercise_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user_exercise_attempt`
--
ALTER TABLE `user_exercise_attempt`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_exercise_attempt`
--
ALTER TABLE `user_exercise_attempt`
  ADD CONSTRAINT `user_exercise_attempt_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_exercise_attempt_ibfk_2` FOREIGN KEY (`exercise_id`) REFERENCES `exercise` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: db_server
-- Generation Time: Jun 01, 2026 at 06:33 AM
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
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` int DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `language_id` int NOT NULL,
  `ui_language_code` char(5) NOT NULL DEFAULT 'en',
  `xp` int UNSIGNED NOT NULL DEFAULT '0',
  `streak_count` int UNSIGNED NOT NULL DEFAULT '0',
  `streak_longest` int UNSIGNED NOT NULL DEFAULT '0',
  `hearts` tinyint NOT NULL DEFAULT '5',
  `daily_goal_minutes` tinyint NOT NULL DEFAULT '10',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password_hash`, `email`, `reset_token`, `reset_token_expiry`, `avatar_url`, `language_id`, `ui_language_code`, `xp`, `streak_count`, `streak_longest`, `hearts`, `daily_goal_minutes`, `created_at`, `last_login`) VALUES
(29, 'admin', '$2y$10$BbXQgYbOviy6VTAfBMp7P.6ZdZMtE62HSNw0QU80z5w2YBOvnfGEK', NULL, NULL, NULL, NULL, 2, 'en', 0, 0, 0, 5, 10, '2026-05-26 12:09:08', '2026-05-26 12:09:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

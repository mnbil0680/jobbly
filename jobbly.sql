-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2026 at 07:07 AM
-- Server version: 8.0.44
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jobbly`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`) VALUES
(1, 'Software Engineering', 'software-engineering'),
(2, 'Marketing', 'marketing'),
(3, 'Healthcare', 'healthcare'),
(4, 'Sales', 'sales');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'Unknown',
  `poster_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `job_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'open',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `company_name`, `poster_id`, `category_id`, `title`, `description`, `location`, `job_type`, `salary_min`, `salary_max`, `currency`, `status`, `created_at`, `expires_at`) VALUES
(1, 'ManTech', 'jobicy_142313', 1, 'Senior SAP Integration Developer', '', 'Unknown', 'Full-time', 0.00, 0.00, 'USD', 'open', '2026-04-23 03:28:38', NULL),
(2, 'Live the Dash Travel', 'jsearch_rapidapi_QzN_pvBka-UeiDQ9AAAAAA==', 1, 'Virtual Cruise & Tour Agent', '', 'Unknown', 'Full-time', 0.00, 0.00, 'USD', 'open', '2026-04-23 03:28:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_skills`
--

CREATE TABLE `job_skills` (
  `id` int NOT NULL,
  `job_id` int NOT NULL,
  `skill_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `is_required` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_jobs`
--

CREATE TABLE `saved_jobs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `job_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_jobs`
--

INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`) VALUES
(9, 1, 1),
(6, 1, 2),
(10, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `email` varchar(191) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `profile_photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cv_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `details`, `profile_photo`, `cv_path`, `created_at`) VALUES
(1, 'Mohamed Nabil', 'mnbil0680@gmail.com', '$2y$10$QBWArM7NPFW/65wK1XYcie7jrv4PIpXqLR4/2SIKD3DIPfMc2NNsG', 'i am a good man', 'assets/uploads/photo_1_1776910289.png', 'assets/uploads/cv_1_1776910303.pdf', '2026-04-23 04:09:17'),
(2, 'Shenawy El Gammed', 'Shenawy@gmail.com', '$2y$10$SuHu0258Xg5cuKKB1MkzVeh0gTbnnzUZA.NghK8wxqdLnjLfo50Gm', 'Shenawy El Gammed in \nIS Department', 'assets/uploads/photo_2_1776913437.jpg', 'assets/uploads/cv_2_1776913447.pdf', '2026-04-23 05:03:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_company` (`company_name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `job_skills`
--
ALTER TABLE `job_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_job` (`user_id`,`job_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `job_skills`
--
ALTER TABLE `job_skills`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `JOBS_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `job_skills`
--
ALTER TABLE `job_skills`
  ADD CONSTRAINT `JOB_SKILLS_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD CONSTRAINT `SAVED_JOBS_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `SAVED_JOBS_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2026 at 11:42 AM
-- Server version: 8.0.44
-- PHP Version: 8.2.12


SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- Table structure for table `categories`
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`id`, `name`, `slug`) VALUES
(1, 'Software Engineering', 'software-engineering'),
(2, 'Marketing', 'marketing'),
(3, 'Healthcare', 'healthcare'),
(4, 'Sales', 'sales');

-- Table structure for table `jobs`
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) DEFAULT 'Unknown',
  `poster_id` varchar(255) DEFAULT NULL,
  `category_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `location` varchar(255) DEFAULT NULL,
  `job_type` varchar(100) DEFAULT NULL,
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'open',
  `apply_url` varchar(2048) NOT NULL DEFAULT '',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_poster_id` (`poster_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `jobs` (`id`, `company_name`, `poster_id`, `category_id`, `title`, `description`, `location`, `job_type`, `salary_min`, `salary_max`, `currency`, `status`, `created_at`, `apply_url`) VALUES
(1, 'ManTech', 'jobicy_142313', 1, 'Senior SAP Integration Developer', '', 'Unknown', 'Full-time', 0.00, 0.00, 'USD', 'open', '2026-04-23 03:28:38', ''),
(2, 'Live the Dash Travel', 'jsearch_rapidapi_QzN_pvBka-UeiDQ9AAAAAA==', 1, 'Virtual Cruise & Tour Agent', '', 'Unknown', 'Full-time', 0.00, 0.00, 'USD', 'open', '2026-04-23 03:28:38', ''),
(3, 'TELUS Digital', 'remotive_2089990', 1, 'Content Reviewer - US', 'Check content quality', 'USA', 'part_time', 0.00, 0.00, 'USD', 'open', '2026-04-23 07:26:23', 'https://example.com/apply');

-- Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT 'user',
  `email` varchar(191) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `details` text,
  `profile_photo` varchar(255) DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `details`) VALUES
(1, 'Mohamed Nabil', 'mnbil0680@gmail.com', '$2y$10$QBWArM7NPFW/65wK1XYcie7jrv4PIpXqLR4/2SIKD3DIPfMc2NNsG', 'i am a good man'),
(2, 'Shenawy El Gammed', 'Shenawy@gmail.com', '$2y$10$SuHu0258Xg5cuKKB1MkzVeh0gTbnnzUZA.NghK8wxqdLnjLfo50Gm', 'Developer');

-- Table structure for table `saved_jobs`
CREATE TABLE IF NOT EXISTS `saved_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `job_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_job` (`user_id`,`job_id`),
  CONSTRAINT `FK_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`) VALUES
(9, 1, 1),
(6, 1, 2),
(10, 2, 1);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

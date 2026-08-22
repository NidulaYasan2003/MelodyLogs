-- MelodyLogs Database Schema & Starter Seeds
-- Author: MelodyLogs Team
-- Target: MySQL / MariaDB (UTF-8 mb4)

CREATE DATABASE IF NOT EXISTS `melodylogs_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `melodylogs_db`;


-- Table structure for `users`

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL DEFAULT '',
    `google_id` VARCHAR(255) DEFAULT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'user',
    `vocal_type` VARCHAR(50) NOT NULL DEFAULT 'Vocalist',
    `bio` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table structure for `posts`

CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'Vocal Technique',
    `summary` VARCHAR(350) NOT NULL,
    `content` LONGTEXT NOT NULL,
    `cover_image_url` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `view_count` INT DEFAULT 0,
    CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_posts_user` (`user_id`),
    INDEX `idx_posts_category` (`category`),
    INDEX `idx_posts_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `vocal_type`, `bio`) VALUES
(1, 'Admin', 'admin@melodylogs.com', '$2y$10$gS9q3FTXmn5G9wsRwNGz0uf/.rtzJVcy5/WK9dG2CG3QC5UPCFHMy', 'superadmin', 'Platform Administrator', 'Platform Administrator and Head Curator of MelodyLogs.')
ON DUPLICATE KEY UPDATE `id`=`id`;

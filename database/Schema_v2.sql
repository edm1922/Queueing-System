-- phpMyAdmin SQL Dump
-- Queue Management System - Manpower Agency Edition v2.0
-- Complete Database Schema with All Enhancements

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Service Groups table (v3 - grouping services into categories)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `service_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Known Companies table (v3 - autocomplete for kiosk)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `known_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_company_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Drop existing tables (for fresh installation only)
-- In production, use ALTER TABLE commands instead
-- --------------------------------------------------------

DROP TABLE IF EXISTS `redistribution_logs`;
DROP TABLE IF EXISTS `counter_service_assignments`;
DROP TABLE IF EXISTS `display_announcements`;
DROP TABLE IF EXISTS `service_types`;
DROP TABLE IF EXISTS `queue_sequences`;
DROP TABLE IF EXISTS `daily_metrics`;
DROP TABLE IF EXISTS `auth_logs`;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `counters`;
DROP TABLE IF EXISTS `display_settings`;

-- --------------------------------------------------------
-- Users table for authentication
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','supervisor','staff') DEFAULT 'staff',
  `window_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `password_reset_required` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default admin user (password: admin123)
INSERT INTO `users` (`username`, `password_hash`, `display_name`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@agency.com', 'admin');

-- --------------------------------------------------------
-- User sessions table
-- --------------------------------------------------------

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Authentication logs
-- --------------------------------------------------------

CREATE TABLE `auth_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `event_type` (`event_type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Service types table
-- --------------------------------------------------------

CREATE TABLE `service_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `queue_prefix` varchar(3) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `group_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Manpower Agency Services
INSERT INTO `service_types` (`name`, `code`, `description`, `queue_prefix`) VALUES
('Insurance', 'insurance', 'UCBP Insurance services, claims, inquiries', 'I'),
('Benefits', 'benefits', 'SSS Benefits applications and inquiries', 'I'),
('ID Renewal', 'id_renewal', 'ID card renewal, updates, replacements', 'R'),
('ATM Claim', 'atm_renewal', 'ATM card renewal, PIN issues, replacements', 'R'),
('Other', 'other', 'General inquiries and other services', 'O'),
('Custom', 'custom', 'Custom inquiry or concern', 'CS');

-- --------------------------------------------------------
-- Queue sequences for atomic queue number generation
-- --------------------------------------------------------

CREATE TABLE `queue_sequences` (
  `prefix` varchar(3) NOT NULL,
  `current_value` int(11) NOT NULL DEFAULT 0,
  `queue_date` date NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `queue_sequences` (`prefix`, `current_value`, `queue_date`) VALUES
('I', 0, CURDATE()),
('R', 0, CURDATE()),
('O', 0, CURDATE());

-- --------------------------------------------------------
-- Counters (Windows) table
-- --------------------------------------------------------

CREATE TABLE `counters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(50) DEFAULT NULL,
  `description` varchar(200) DEFAULT NULL,
  `custom_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `window_number` int(11) DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 1,
  `status_text` varchar(20) DEFAULT 'Online',
  `avg_service_time` int(11) DEFAULT 0,
  `customers_served` int(11) DEFAULT 0,
  `last_status_change` timestamp NULL DEFAULT NULL,
  `current_customer_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `current_customer_id` (`current_customer_id`),
  KEY `idx_is_online` (`is_online`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Manpower Agency Windows
INSERT INTO `counters` (`id`, `name`, `display_name`, `window_number`, `is_online`, `current_customer_id`) VALUES
(1, 'Window 1', 'Insurance & Benefits', 1, 1, NULL),
(2, 'Window 2', 'ID & ATM Renewals', 2, 1, NULL);

-- --------------------------------------------------------
-- Counter service assignments
-- --------------------------------------------------------

CREATE TABLE `counter_service_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `counter_id` int(11) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`counter_id`, `service_type`),
  KEY `counter_id` (`counter_id`),
  KEY `service_type` (`service_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Primary assignments
INSERT INTO `counter_service_assignments` (`counter_id`, `service_type`, `is_primary`, `display_order`) VALUES
(1, 'insurance', 1, 1),
(1, 'benefits', 1, 2),
(2, 'id_renewal', 1, 1),
(2, 'atm_renewal', 1, 2);

-- --------------------------------------------------------
-- Customers table
-- --------------------------------------------------------

CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_number` varchar(20) NOT NULL,
  `queue_date` date NOT NULL,
  `name` varchar(100) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `purpose` varchar(50) DEFAULT NULL,
  `status` enum('waiting','serving','completed','cancelled','skipped','no-show') DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `called_at` timestamp NULL DEFAULT NULL,
  `served_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `service_duration` int(11) DEFAULT NULL,
  `wait_duration` int(11) DEFAULT NULL,
  `counter_id` int(11) DEFAULT NULL,
  `is_redistributed` tinyint(1) DEFAULT 0,
  `is_follow_up` tinyint(1) DEFAULT 0,
  `follow_up_marked_by` int(11) DEFAULT NULL,
  `follow_up_completed_by` int(11) DEFAULT NULL,
  `follow_up_resolved_at` timestamp NULL DEFAULT NULL,
  `follow_up_rejected_at` timestamp NULL DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `custom_description` varchar(255) DEFAULT NULL,
  `forwarded_to_counter_id` int(11) DEFAULT NULL,
  `forwarded_by_user_id` int(11) DEFAULT NULL,
  `forward_remark` text DEFAULT NULL,
  `forwarded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_queue_number_date` (`queue_number`, `queue_date`),
  KEY `idx_status` (`status`),
  KEY `idx_service_type` (`service_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status_created` (`status`, `created_at`),
  KEY `idx_status_service` (`status`, `service_type`),
  KEY `idx_follow_up_marked_by` (`follow_up_marked_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Display settings table
-- --------------------------------------------------------

CREATE TABLE `display_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(100) DEFAULT 'Service Center',
  `branch_name` varchar(100) DEFAULT 'Main Office',
  `address` varchar(255) DEFAULT NULL,
  `welcome_message` text DEFAULT NULL,
  `refresh_interval` int(11) DEFAULT 10,
  `video_url` varchar(500) DEFAULT NULL,
  `video_type` enum('youtube','local','none') DEFAULT 'none',
  `video_volume` int(11) DEFAULT 50,
  `poster_enabled` tinyint(1) DEFAULT 0,
  `poster_interval` int(11) DEFAULT 10,
  `poster_duration` int(11) DEFAULT 10,
  `poster_images` text DEFAULT NULL,
  `poster_announcements` text DEFAULT NULL,
  `video_title` varchar(255) DEFAULT 'Citizen Services Overview',
  `video_sponsor` varchar(255) DEFAULT 'Public Affairs Office',
  `video_cta` varchar(255) DEFAULT NULL,
  `auto_play_video` tinyint(1) DEFAULT 1,
  `display_layout` enum('queue_only','video_queue','queue_video') DEFAULT 'video_queue',
  `cutoff_time` varchar(10) DEFAULT '17:00',
  `active_announcement` text DEFAULT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `theme_color` varchar(20) DEFAULT '#1e3a5f',
  `force_refresh_token` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `display_settings` (`id`, `company_name`, `branch_name`, `welcome_message`, `refresh_interval`, `video_url`, `video_type`, `video_title`, `video_sponsor`, `display_layout`) VALUES
(1, 'Manpower Agency', 'Quezon City — Main Hall', 'Welcome! Please have your queue ticket ready.', 10, 'dQw4w9WgXcQ', 'youtube', 'Citizen Services Overview', 'Public Affairs Office', 'video_queue');

-- --------------------------------------------------------
-- Display announcements table
-- --------------------------------------------------------

CREATE TABLE `display_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','urgent') DEFAULT 'info',
  `font_family` varchar(50) DEFAULT 'Inter',
  `priority` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_preset` tinyint(1) DEFAULT 0,
  `counter_id` int(11) DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `display_duration` int(11) DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Preset announcements for manpower agency
INSERT INTO `display_announcements` (`title`, `message`, `type`, `priority`, `is_active`, `is_preset`) VALUES
('Documents Required', 'Please prepare your documents before approaching the counter', 'info', 5, 1, 1),
('SSS Reminder', 'SSS benefits applications require: Valid ID, 2x2 photos, and accomplished form', 'info', 4, 1, 1),
('Insurance Claim', 'UCBP Insurance claims require: Valid ID, claim form, and supporting documents', 'info', 4, 1, 1),
('ID Renewal Notice', 'ID renewal takes 5-7 working days. Please bring original valid ID.', 'info', 3, 1, 1),
('ATM Notice', 'ATM renewal takes 5-7 working days. Your new card will be delivered via mail.', 'info', 3, 1, 1),
('High Volume', 'Due to high volume, expected wait time may exceed 30 minutes. Thank you for your patience.', 'warning', 8, 1, 1),
('Closing Soon', 'We are now closing. Last number for today will be announced shortly.', 'warning', 9, 1, 1);

-- --------------------------------------------------------
-- Redistribution logs
-- --------------------------------------------------------

CREATE TABLE `redistribution_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` enum('counter_offline','counter_online','manual_override') NOT NULL,
  `counter_id` int(11) NOT NULL,
  `affected_services` json DEFAULT NULL,
  `reassigned_customers` int(11) DEFAULT 0,
  `reassigned_to_counter_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `counter_id` (`counter_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Daily metrics for reporting
-- --------------------------------------------------------

CREATE TABLE `daily_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `service_type` varchar(50) DEFAULT NULL,
  `total_served` int(11) DEFAULT 0,
  `total_waited_seconds` int(11) DEFAULT 0,
  `total_service_seconds` int(11) DEFAULT 0,
  `avg_wait_seconds` int(11) DEFAULT 0,
  `avg_service_seconds` int(11) DEFAULT 0,
  `max_wait_seconds` int(11) DEFAULT 0,
  `max_service_seconds` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date_service` (`date`, `service_type`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
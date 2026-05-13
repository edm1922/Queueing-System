-- phpMyAdmin SQL Dump
-- Queue Management System - Enhancement Migration
-- Version: 2.0 - Manpower Agency Edition
-- Features: Dynamic counter redistribution, announcements, video integration

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Drop existing tables if they exist (for fresh installation)
-- NOTE: In production, use ALTER TABLE instead of DROP

DROP TABLE IF EXISTS `redistribution_logs`;
DROP TABLE IF EXISTS `counter_service_assignments`;
DROP TABLE IF EXISTS `display_announcements`;
DROP TABLE IF EXISTS `service_types`;
DROP TABLE IF EXISTS `queue_sequences`;

-- --------------------------------------------------------
-- Create service_types table (master list of services)
-- --------------------------------------------------------

CREATE TABLE `service_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `queue_prefix` char(1) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for service_types (Manpower Agency services)
--

INSERT INTO `service_types` (`name`, `code`, `description`, `queue_prefix`) VALUES
('Insurance', 'insurance', 'UCBP Insurance services, claims, inquiries', 'I'),
('Benefits', 'benefits', 'SSS Benefits applications and inquiries', 'I'),
('ID Renewal', 'id_renewal', 'ID card renewal, updates, replacements', 'R'),
('ATM Renewal', 'atm_renewal', 'ATM card renewal, PIN issues, replacements', 'R');

-- --------------------------------------------------------
-- Create queue_sequences table (atomic queue number generation)
-- --------------------------------------------------------

CREATE TABLE `queue_sequences` (
  `prefix` char(1) NOT NULL,
  `current_value` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Initialize sequences for each prefix
--

INSERT INTO `queue_sequences` (`prefix`, `current_value`) VALUES
('I', 0),
('R', 0);

-- --------------------------------------------------------
-- Create counter_service_assignments table
-- Maps services to counters with primary/fallback designation
-- --------------------------------------------------------

CREATE TABLE `counter_service_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `counter_id` int(11) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 1 COMMENT '1 = original assignment, 0 = fallback',
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`counter_id`, `service_type`),
  KEY `counter_id` (`counter_id`),
  KEY `service_type` (`service_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create display_announcements table
-- Custom and preset announcements for public display
-- --------------------------------------------------------

CREATE TABLE `display_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','urgent') DEFAULT 'info',
  `priority` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_preset` tinyint(1) DEFAULT 0,
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Insert preset announcements for manpower agency
--

INSERT INTO `display_announcements` (`title`, `message`, `type`, `priority`, `is_active`, `is_preset`) VALUES
('Documents Required', 'Please prepare your documents before approaching the counter', 'info', 5, 1, 1),
('SSS Reminder', 'SSS benefits applications require: Valid ID, 2x2 photos, and accomplished form', 'info', 4, 1, 1),
('Insurance Claim', 'UCBP Insurance claims require: Valid ID, claim form, and supporting documents', 'info', 4, 1, 1),
('ID Renewal Notice', 'ID renewal takes 5-7 working days. Please bring original valid ID.', 'info', 3, 1, 1),
('ATM Notice', 'ATM renewal takes 5-7 working days. Your new card will be delivered via mail.', 'info', 3, 1, 1),
('High Volume', 'Due to high volume, expected wait time may exceed 30 minutes. Thank you for your patience.', 'warning', 8, 1, 1),
('Closing Soon', 'We are now closing. Last number for today will be announced shortly.', 'warning', 9, 1, 1);

-- --------------------------------------------------------
-- Create redistribution_logs table
-- Audit trail for counter online/offline events
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
-- Modify existing counters table
-- Add new columns for enhanced counter management
-- --------------------------------------------------------

ALTER TABLE `counters`
  ADD COLUMN `display_name` varchar(50) DEFAULT NULL AFTER `name`,
  ADD COLUMN `window_number` int(11) DEFAULT NULL AFTER `display_name`,
  ADD COLUMN `avg_service_time` int(11) DEFAULT 0 AFTER `is_online`,
  ADD COLUMN `customers_served` int(11) DEFAULT 0 AFTER `avg_service_time`,
  ADD COLUMN `last_status_change` timestamp NULL DEFAULT NULL AFTER `customers_served`;

-- --------------------------------------------------------
-- Modify existing customers table
-- Add columns for service time tracking and metrics
-- --------------------------------------------------------

ALTER TABLE `customers`
  ADD COLUMN `served_at` timestamp NULL DEFAULT NULL AFTER `called_at`,
  ADD COLUMN `service_duration` int(11) DEFAULT NULL AFTER `completed_at`,
  ADD COLUMN `wait_duration` int(11) DEFAULT NULL AFTER `service_duration`,
  ADD COLUMN `counter_id` int(11) DEFAULT NULL AFTER `service_type`,
  ADD COLUMN `is_redistributed` tinyint(1) DEFAULT 0 AFTER `counter_id`;

-- --------------------------------------------------------
-- Modify existing display_settings table
-- Add columns for video integration and layout
-- --------------------------------------------------------

ALTER TABLE `display_settings`
  ADD COLUMN `video_url` varchar(500) DEFAULT NULL AFTER `refresh_interval`,
  ADD COLUMN `video_type` enum('youtube','local','none') DEFAULT 'none' AFTER `video_url`,
  ADD COLUMN `video_volume` int(11) DEFAULT 50 AFTER `video_type`,
  ADD COLUMN `auto_play_video` tinyint(1) DEFAULT 1 AFTER `video_volume`,
  ADD COLUMN `display_layout` enum('queue_only','video_queue','queue_video') DEFAULT 'video_queue' AFTER `auto_play_video`,
  ADD COLUMN `active_announcement` text DEFAULT NULL AFTER `display_layout`;

-- --------------------------------------------------------
-- Add indexes for performance
-- --------------------------------------------------------

ALTER TABLE `customers`
  ADD INDEX `idx_status` (`status`),
  ADD INDEX `idx_service_type` (`service_type`),
  ADD INDEX `idx_created_at` (`created_at`),
  ADD INDEX `idx_status_created` (`status`, `created_at`),
  ADD INDEX `idx_status_service` (`status`, `service_type`);

ALTER TABLE `counters`
  ADD INDEX `idx_is_online` (`is_online`);

-- --------------------------------------------------------
-- Initialize counter_service_assignments
-- Window 1 = Insurance & Benefits (I prefix)
-- Window 2 = ID Renewal & ATM Renewal (R prefix)
-- --------------------------------------------------------

INSERT INTO `counter_service_assignments` (`counter_id`, `service_type`, `is_primary`, `display_order`) VALUES
(1, 'insurance', 1, 1),
(1, 'benefits', 1, 2),
(2, 'id_renewal', 1, 1),
(2, 'atm_renewal', 1, 2);

-- --------------------------------------------------------
-- Update counters with display names
-- --------------------------------------------------------

UPDATE `counters` SET `display_name` = 'Insurance & Benefits' WHERE `id` = 1;
UPDATE `counters` SET `display_name` = 'ID & ATM Renewals' WHERE `id` = 2;
UPDATE `counters` SET `window_number` = 1 WHERE `id` = 1;
UPDATE `counters` SET `window_number` = 2 WHERE `id` = 2;

-- --------------------------------------------------------
-- Update display_settings with defaults
-- --------------------------------------------------------

UPDATE `display_settings` SET `display_layout` = 'video_queue' WHERE `id` = 1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
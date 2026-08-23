-- Migration v3: Service Groups, Company Name, Purpose
-- Run this ONCE against your existing queuing_system database

-- 1. Create service_groups table
CREATE TABLE IF NOT EXISTS `service_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Add group_id to service_types
ALTER TABLE `service_types`
  ADD COLUMN IF NOT EXISTS `group_id` int(11) DEFAULT NULL AFTER `is_active`;

-- 3. Add company_name and purpose to customers
ALTER TABLE `customers`
  ADD COLUMN IF NOT EXISTS `company_name` varchar(255) DEFAULT NULL AFTER `service_type`;

ALTER TABLE `customers`
  ADD COLUMN IF NOT EXISTS `purpose` varchar(50) DEFAULT NULL AFTER `company_name`;

-- 4. Create known_companies table for autocomplete
CREATE TABLE IF NOT EXISTS `known_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_company_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- v5: Add remark column to customers table for operator notes
ALTER TABLE `customers` ADD COLUMN `remark` text DEFAULT NULL AFTER `is_follow_up`;

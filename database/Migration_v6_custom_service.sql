-- v6: Add custom_description column for Custom service type
ALTER TABLE `customers` ADD COLUMN `custom_description` varchar(255) DEFAULT NULL AFTER `remark`;

-- Add the Custom service type (if not already present)
INSERT IGNORE INTO `service_types` (`name`, `code`, `description`, `queue_prefix`, `is_active`) VALUES ('Custom', 'custom', 'Custom inquiry or concern', 'C', 1);

-- Ensure queue_sequence exists for prefix C
INSERT IGNORE INTO `queue_sequences` (`prefix`, `current_value`) VALUES ('C', 0);

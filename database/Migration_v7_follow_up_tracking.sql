ALTER TABLE customers
  ADD COLUMN `follow_up_marked_by` INT(11) DEFAULT NULL AFTER `is_follow_up`,
  ADD COLUMN `follow_up_resolved_at` TIMESTAMP NULL DEFAULT NULL AFTER `follow_up_marked_by`,
  ADD COLUMN `follow_up_rejected_at` TIMESTAMP NULL DEFAULT NULL AFTER `follow_up_resolved_at`;

ALTER TABLE customers ADD KEY `idx_follow_up_marked_by` (`follow_up_marked_by`);

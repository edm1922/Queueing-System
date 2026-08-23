-- Migration v9: Add queue_date to customers, scope unique key per day
-- queue_number uniqueness was global, but daily reset causes collisions with yesterday's data

ALTER TABLE customers ADD COLUMN `queue_date` date DEFAULT NULL AFTER `queue_number`;
UPDATE customers SET queue_date = DATE(DATE_ADD(COALESCE(created_at, NOW()), INTERVAL 8 HOUR));
ALTER TABLE customers MODIFY COLUMN `queue_date` date NOT NULL;
ALTER TABLE customers DROP INDEX queue_number;
ALTER TABLE customers ADD UNIQUE KEY `uq_queue_number_date` (`queue_number`, `queue_date`);

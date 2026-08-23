-- v8: Change custom queue prefix from 'C' to 'CS' (multi-char support)
ALTER TABLE `service_types` MODIFY `queue_prefix` varchar(3) NOT NULL;
ALTER TABLE `queue_sequences` MODIFY `prefix` varchar(3) NOT NULL;
UPDATE `service_types` SET `queue_prefix` = 'CS' WHERE `code` = 'custom';

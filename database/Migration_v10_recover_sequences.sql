-- Recover queue_sequences to match actual max ticket numbers
-- Match prefix exactly by requiring prefix + exactly 3 digits
UPDATE queue_sequences qs
  JOIN (
    SELECT qs2.prefix,
           COALESCE(MAX(CAST(SUBSTRING(c.queue_number, LENGTH(qs2.prefix)+1) AS UNSIGNED)), 0) as max_ticket
    FROM queue_sequences qs2
    LEFT JOIN customers c ON LENGTH(c.queue_number) = LENGTH(qs2.prefix) + 3
                         AND c.queue_number LIKE CONCAT(qs2.prefix, '%')
                         AND c.queue_date = qs2.queue_date
    GROUP BY qs2.prefix
  ) actual ON actual.prefix = qs.prefix
SET qs.current_value = GREATEST(actual.max_ticket, qs.current_value)
WHERE actual.max_ticket > qs.current_value;

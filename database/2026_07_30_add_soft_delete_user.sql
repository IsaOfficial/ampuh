-- Soft delete support for pegawai records.
-- Run this migration before deploying the related application code.

SET @deleted_at_exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user'
    AND COLUMN_NAME = 'deleted_at'
);

SET @sql := IF(@deleted_at_exists = 0,
  'ALTER TABLE `user` ADD COLUMN `deleted_at` DATETIME NULL AFTER `created_at`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @deleted_by_exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user'
    AND COLUMN_NAME = 'deleted_by'
);

SET @sql := IF(@deleted_by_exists = 0,
  'ALTER TABLE `user` ADD COLUMN `deleted_by` INT NULL AFTER `deleted_at`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @delete_reason_exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user'
    AND COLUMN_NAME = 'delete_reason'
);

SET @sql := IF(@delete_reason_exists = 0,
  'ALTER TABLE `user` ADD COLUMN `delete_reason` VARCHAR(255) NULL AFTER `deleted_by`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_deleted_at_exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user'
    AND INDEX_NAME = 'idx_user_deleted_at'
);

SET @sql := IF(@idx_deleted_at_exists = 0,
  'ALTER TABLE `user` ADD INDEX `idx_user_deleted_at` (`deleted_at`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_deleted_by_exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user'
    AND INDEX_NAME = 'idx_user_deleted_by'
);

SET @sql := IF(@idx_deleted_by_exists = 0,
  'ALTER TABLE `user` ADD INDEX `idx_user_deleted_by` (`deleted_by`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

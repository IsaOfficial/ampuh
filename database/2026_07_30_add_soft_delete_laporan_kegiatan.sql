DELIMITER $$

CREATE PROCEDURE add_column_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND COLUMN_NAME = p_column_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN `', p_column_name, '` ', p_column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE add_index_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_index_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND INDEX_NAME = p_index_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD ', p_index_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

CALL add_column_if_missing('laporan_kegiatan', 'deleted_at', 'DATETIME NULL AFTER rejection_note');
CALL add_column_if_missing('laporan_kegiatan', 'deleted_by', 'INT NULL AFTER deleted_at');
CALL add_column_if_missing('laporan_kegiatan', 'delete_reason', 'VARCHAR(255) NULL AFTER deleted_by');

CALL add_index_if_missing('laporan_kegiatan', 'idx_laporan_kegiatan_deleted_at', 'INDEX `idx_laporan_kegiatan_deleted_at` (`deleted_at`)');
CALL add_index_if_missing('laporan_kegiatan', 'idx_laporan_kegiatan_deleted_by', 'INDEX `idx_laporan_kegiatan_deleted_by` (`deleted_by`)');

DROP PROCEDURE add_column_if_missing;
DROP PROCEDURE add_index_if_missing;

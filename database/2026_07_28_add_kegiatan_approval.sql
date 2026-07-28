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

CALL add_column_if_missing('laporan_kegiatan', 'approval_status', "ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER bukti");
CALL add_column_if_missing('laporan_kegiatan', 'approved_by', 'INT NULL AFTER approval_status');
CALL add_column_if_missing('laporan_kegiatan', 'approved_at', 'DATETIME NULL AFTER approved_by');
CALL add_column_if_missing('laporan_kegiatan', 'verification_token', 'VARCHAR(64) NULL AFTER approved_at');
CALL add_column_if_missing('laporan_kegiatan', 'document_hash', 'VARCHAR(64) NULL AFTER verification_token');
CALL add_column_if_missing('laporan_kegiatan', 'approval_revoked_at', 'DATETIME NULL AFTER document_hash');
CALL add_column_if_missing('laporan_kegiatan', 'signature_note', 'VARCHAR(255) NULL AFTER approval_revoked_at');
CALL add_column_if_missing('laporan_kegiatan', 'rejection_note', 'VARCHAR(255) NULL AFTER signature_note');

CALL add_index_if_missing('laporan_kegiatan', 'uq_laporan_kegiatan_verification_token', 'UNIQUE INDEX `uq_laporan_kegiatan_verification_token` (`verification_token`)');
CALL add_index_if_missing('laporan_kegiatan', 'idx_laporan_kegiatan_approval_status', 'INDEX `idx_laporan_kegiatan_approval_status` (`approval_status`)');
CALL add_index_if_missing('laporan_kegiatan', 'idx_laporan_kegiatan_approved_by', 'INDEX `idx_laporan_kegiatan_approved_by` (`approved_by`)');

DROP PROCEDURE add_column_if_missing;
DROP PROCEDURE add_index_if_missing;
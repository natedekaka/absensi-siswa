-- Add remember_token columns (idempotent — skip if already exists)

SET @db = 'absensi_siswa';

-- Add remember_token column if not exists
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
               WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'remember_token');
SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) NULL AFTER role',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add remember_expires column if not exists
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
               WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'remember_expires');
SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN remember_expires DATETIME NULL AFTER remember_token',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE users ADD INDEX IF NOT EXISTS idx_remember_token (remember_token);

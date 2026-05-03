ALTER TABLE users 
ADD COLUMN remember_token VARCHAR(255) NULL AFTER role,
ADD COLUMN remember_expires DATETIME NULL AFTER remember_token;

ALTER TABLE users ADD INDEX IF NOT EXISTS idx_remember_token (remember_token);

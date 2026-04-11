-- Add barcode column to siswa table
ALTER TABLE siswa ADD COLUMN barcode VARCHAR(50) NULL UNIQUE AFTER nisn;

-- Generate barcode from existing NIS if barcode is null
UPDATE siswa SET barcode = nis WHERE barcode IS NULL;

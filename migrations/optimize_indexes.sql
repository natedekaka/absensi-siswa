-- Optimasi index untuk performa (idempotent)
-- Menambahkan composite index untuk query paling sering digunakan
-- Gunakan: mysql -u root -p absensi_siswa < optimize_indexes.sql

-- ============================================================
-- HAPUS DUPLICATE INDEX (jalan di MariaDB 10.x+)
-- ============================================================
ALTER TABLE absensi DROP INDEX IF EXISTS siswa_id;
ALTER TABLE absensi DROP INDEX IF EXISTS idx_siswa_id;
ALTER TABLE absensi DROP INDEX IF EXISTS semester_id;
ALTER TABLE absensi DROP INDEX IF EXISTS idx_tanggal;
ALTER TABLE absensi_mapel DROP INDEX IF EXISTS mapel_id;
ALTER TABLE absensi_mapel DROP INDEX IF EXISTS idx_mapel_id;

-- ============================================================
-- TAMBAH COMPOSITE INDEX
-- ============================================================

-- absensi: index untuk query: WHERE siswa_id IN (...) AND tanggal = ? AND semester_id = ?
ALTER TABLE absensi ADD INDEX IF NOT EXISTS idx_siswa_tgl_smt (siswa_id, tanggal, semester_id);

-- absensi_mapel: index untuk query batch mapel
ALTER TABLE absensi_mapel ADD INDEX IF NOT EXISTS idx_mapel_batch (kelas_id, mapel_id, tanggal, semester_id, siswa_id);

-- siswa: index untuk filter kelas + status
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_kelas_status (kelas_id, status);

-- semester: index untuk query semester aktif
ALTER TABLE semester ADD INDEX IF NOT EXISTS idx_semester_aktif_cepat (is_active, tahun_ajaran_id, semester);

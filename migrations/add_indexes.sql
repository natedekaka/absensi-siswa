-- Index untuk performa aplikasi absensi siswa
-- Jalankan di phpMyAdmin atau via command line
-- Error "Duplicate key" dapat diabaikan

-- Index pada tabel absensi
ALTER TABLE absensi ADD INDEX IF NOT EXISTS idx_tanggal (tanggal);
ALTER TABLE absensi ADD INDEX IF NOT EXISTS idx_siswa_id (siswa_id);
ALTER TABLE absensi ADD INDEX IF NOT EXISTS idx_semester_id (semester_id);

-- Index pada tabel siswa
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_kelas_id (kelas_id);
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_status (status);

-- Index pada tabel kelas
ALTER TABLE kelas ADD INDEX IF NOT EXISTS idx_nama_kelas (nama_kelas);

-- Index pada tabel semester
ALTER TABLE semester ADD INDEX IF NOT EXISTS idx_is_active (is_active);
ALTER TABLE semester ADD INDEX IF NOT EXISTS idx_tahun_ajaran (tahun_ajaran_id);

-- Index pada tabel users
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_username (username);
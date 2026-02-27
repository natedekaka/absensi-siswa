-- Database Schema untuk Aplikasi Absensi Siswa dengan Tahun Ajaran & Semester
-- Jalankan file ini di phpMyAdmin atau MySQL

-- ==================== TABEL BARU ====================

-- Tabel tahun_ajaran
CREATE TABLE IF NOT EXISTS tahun_ajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL, -- contoh: 2025/2026
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel semester
CREATE TABLE IF NOT EXISTS semester (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tahun_ajaran_id INT NOT NULL,
    semester INT NOT NULL, -- 1 atau 2
    nama VARCHAR(50) NOT NULL, -- contoh: Semester 1, Semester 2
    tgl_mulai DATE NOT NULL,
    tgl_selesai DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id) ON DELETE CASCADE
);

-- ==================== UPDATE TABEL ABSENSI ====================

-- 1. Tambah kolom semester_id jika belum ada
ALTER TABLE absensi ADD COLUMN semester_id INT NULL;

-- 2. Tambah foreign key (jika belum ada)
-- ALTER TABLE absensi ADD FOREIGN KEY (semester_id) REFERENCES semester(id);

-- 3. Tambah index untuk performa query
ALTER TABLE absensi ADD INDEX idx_semester (semester_id);
ALTER TABLE absensi ADD INDEX idx_tanggal_semester (tanggal, semester_id);

-- ==================== DATA CONTOH ====================

-- Insert data tahun ajaran contoh
INSERT INTO tahun_ajaran (nama, is_active) VALUES 
('2025/2026', 1),
('2026/2027', 0);

-- Insert data semester contoh (pastikan ID tahun ajaran sesuai)
INSERT INTO semester (tahun_ajaran_id, semester, nama, tgl_mulai, tgl_selesai, is_active) VALUES 
(1, 1, 'Semester 1 - 2025/2026', '2025-07-14', '2025-12-20', 1),
(1, 2, 'Semester 2 - 2025/2026', '2026-01-05', '2026-06-30', 0);

-- ==================== CATATAN ====================
-- Jika tabel absensi sudah ada data, perlu update semester_id untuk data yang ada:
-- UPDATE absensi a SET a.semester_id = (SELECT id FROM semester WHERE is_active = 1 LIMIT 1);

-- RBAC: Perluas role users + tabel relasi
-- Created: 2026-06-07

-- 1. Perluas ENUM role users
ALTER TABLE users MODIFY COLUMN role ENUM('admin','guru','wali_kelas','orang_tua') NOT NULL DEFAULT 'guru';

-- 2. Tambah kolom wali_kelas_id ke tabel kelas (FK ke users.id)
ALTER TABLE kelas ADD COLUMN wali_kelas_id INT NULL AFTER wali_kelas;
ALTER TABLE kelas ADD INDEX idx_wali_kelas_id (wali_kelas_id);

-- 3. Tabel relasi guru dengan kelas (seorang guru bisa ngajar banyak kelas)
CREATE TABLE IF NOT EXISTS guru_kelas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    kelas_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_guru_kelas (user_id, kelas_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel relasi orang tua dengan siswa (1 orang tua bisa punya banyak anak)
CREATE TABLE IF NOT EXISTS siswa_orang_tua (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    siswa_id INT NOT NULL,
    hubungan VARCHAR(50) DEFAULT 'ayah',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_orang_tua_siswa (user_id, siswa_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

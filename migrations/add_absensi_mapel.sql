-- ==========================================================================
-- Absensi Mata Pelajaran — Guru mencatat kehadiran siswa di kelasnya
-- Sama seperti absensi (piket) tapi per-guru & per-kelas
-- ==========================================================================

CREATE TABLE IF NOT EXISTS absensi_mapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    user_id INT NOT NULL,
    kelas_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('Hadir','Sakit','Izin','Alfa','Terlambat') NOT NULL,
    semester_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semester(id) ON DELETE SET NULL,
    UNIQUE KEY unique_per_guru (user_id, siswa_id, kelas_id, tanggal),
    KEY idx_tanggal_semester (tanggal, semester_id),
    KEY idx_kelas_tanggal (kelas_id, tanggal),
    KEY idx_user_tanggal (user_id, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

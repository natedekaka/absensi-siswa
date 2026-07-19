-- ==========================================================================
-- Tabel Mata Pelajaran + relasi ke guru_kelas
-- ==========================================================================

CREATE TABLE IF NOT EXISTS mapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_mapel VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE guru_kelas
    ADD COLUMN mapel_id INT DEFAULT NULL AFTER kelas_id,
    ADD FOREIGN KEY (mapel_id) REFERENCES mapel(id) ON DELETE SET NULL;

-- Isi mapel SMA (Kurikulum Merdeka)
INSERT IGNORE INTO mapel (id, nama_mapel) VALUES
    (1, 'Pendidikan Agama & Budi Pekerti'),
    (2, 'Pendidikan Pancasila'),
    (3, 'Bahasa Indonesia'),
    (4, 'Matematika Wajib'),
    (5, 'Matematika Minat'),
    (6, 'Bahasa Inggris'),
    (7, 'PJOK'),
    (8, 'Sejarah'),
    (9, 'Seni Budaya'),
    (10, 'Prakarya & Kewirausahaan'),
    (11, 'Biologi'),
    (12, 'Fisika'),
    (13, 'Kimia'),
    (14, 'Ekonomi'),
    (15, 'Sosiologi'),
    (16, 'Geografi'),
    (17, 'Informatika'),
    (19, 'Bahasa Sunda'),
    (20, 'Bahasa Jepang');

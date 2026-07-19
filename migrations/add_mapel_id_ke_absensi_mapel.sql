-- ==========================================================================
-- Menambahkan kolom mapel_id ke tabel absensi_mapel
-- Setiap absensi mapel terkait dengan satu mata pelajaran
-- ==========================================================================

ALTER TABLE absensi_mapel
    ADD COLUMN mapel_id INT DEFAULT NULL AFTER kelas_id,
    ADD FOREIGN KEY (mapel_id) REFERENCES mapel(id) ON DELETE SET NULL;

-- Hapus unique key lama (tidak termasuk mapel_id)
ALTER TABLE absensi_mapel DROP INDEX unique_per_guru;

-- Unique key baru dengan mapel_id
-- Guru bisa absen mapel berbeda di kelas yang sama pada hari yang sama
ALTER TABLE absensi_mapel
    ADD UNIQUE KEY unique_per_guru_mapel (user_id, siswa_id, kelas_id, mapel_id, tanggal);

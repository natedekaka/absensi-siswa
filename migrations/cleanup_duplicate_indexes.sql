-- Cleanup duplicate/redundant indexes for performance
-- Menghapus index yang duplikat atau redundan untuk mempercepat INSERT/UPDATE
-- dan menghemat memory InnoDB buffer pool

-- Tabel absensi
-- siswa_id: duplicate of idx_siswa_id (same column)
ALTER TABLE absensi DROP INDEX IF EXISTS siswa_id;
-- semester_id: duplicate of idx_semester_id (same column)
ALTER TABLE absensi DROP INDEX IF EXISTS semester_id;
-- idx_tanggal: redundant because idx_tanggal_semester(tanggal, semester_id) covers it
ALTER TABLE absensi DROP INDEX IF EXISTS idx_tanggal;

-- Tabel absensi_mapel
-- mapel_id: redundant because unique_per_guru_mapel already covers mapel_id
-- (Note: siswa_id and semester_id indexes are kept because they're needed by FK constraints)
ALTER TABLE absensi_mapel DROP INDEX IF EXISTS mapel_id;

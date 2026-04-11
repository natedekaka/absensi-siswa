-- Tambah kolom status dan tingkat di tabel siswa untuk mendukung manajemen kenaikan kelas
ALTER TABLE siswa ADD COLUMN status ENUM('aktif', 'alumni') DEFAULT 'aktif' AFTER jenis_kelamin;
ALTER TABLE siswa ADD COLUMN tingkat INT DEFAULT NULL AFTER status;
ALTER TABLE siswa ADD COLUMN tahun_lulus YEAR DEFAULT NULL AFTER tingkat;

-- Update tingkat berdasarkan kelas yang ada
UPDATE siswa s 
JOIN kelas k ON s.kelas_id = k.id 
SET s.tingkat = CASE 
    WHEN k.nama_kelas LIKE 'X-%' THEN 10
    WHEN k.nama_kelas LIKE 'XI-%' THEN 11
    WHEN k.nama_kelas LIKE 'XII-%' THEN 12
    WHEN k.nama_kelas LIKE '12-%' THEN 12
    WHEN k.nama_kelas LIKE '11-%' THEN 11
    WHEN k.nama_kelas LIKE '10-%' THEN 10
    ELSE NULL
END;

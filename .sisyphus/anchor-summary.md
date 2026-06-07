# Session Summary — Anchor Point

## Overview
Aplikasi PHP absensi siswa dengan role-based access (admin, guru, wali_kelas). Awalnya absensi reguler (piket) per kelas. Kemudian developed fitur absensi mapel (guru mapel mengabsen per kelas + mapel).

---

## Done

### 1. Setup & Infrastructure
- Docker, migrasi, autentikasi (login, logout, forgot password, remember me)
- CSRF protection, role middleware
- Konfigurasi sekolah (logo, nama, warna) + upload
- Dark mode, responsive layout

### 2. Absensi Piket (reguler)
- CRUD absensi harian (Hadir/Terlambat/Sakit/Izin/Alfa) via `absensi/index.php`
- Scan barcode via `absensi/barcode.php`
- Riwayat siswa dengan heatmap, progress bar, grafik
- Rekap per kelas, export CSV/PDF

### 3. Manajemen Data
- Siswa (CRUD, import CSV, barcode)
- Kelas (CRUD)
- Users (CRUD untuk admin/guru/wali_kelas)
- Orang Tua siswa
- Kenaikan kelas
- Tahun Ajaran

### 4. Role Guru — hanya lihat kelas sendiri
- Guru mapel bisa akses Absen Mapel & Rekap Mapel
- Wali_kelas bisa akses Absensi reguler (piket) + lihat rekap
- **Admin-only**: Absensi reguler (piket), Scan Barcode

### 5. Bugfixes
- Kalender tidak muncul saat klik tanggal range rekap → fix jQuery `datepicker` tidak diinit
- Border merah `ring-red-500` tidak muncul → fix dengan class `border-red-500` saja
- Placeholder/gambar icon user di form siswa bertumpuk dengan teks input → CSS `form-input-icon` + ganti `pl-10` jadi `form-input-icon`
- Upload logo error forbidden (nginx) → `chown` uploads directory di container
- Rekap dashboard error "Column 'id' in WHERE is ambiguous" → fix dengan alias `k.id`

### 6. Absensi Mapel — Fitur Baru
- **Table baru**: `absensi_mapel` (id, siswa_id, kelas_id, mapel_id, user_id, tanggal, status, created_at)
- **Table baru**: `mapel` (id, nama_mapel)
- **Alter**: `guru_kelas` tambah kolom `mapel_id` (ganti UNIQUE ke (user_id, kelas_id, mapel_id))
- Halaman absensi: `absensi/mapel.php` — guru pilih kelas + mapel, absen siswa
- Proses simpan: `absensi/proses_mapel.php`
- Get siswa AJAX: `absensi/get_siswa_mapel.php`
- Rekap: `rekap/mapel.php` — filter by kelas + mapel, lihat statistik
- **Guru mapel hanya lihat kelas+mapel yang diassign**

### 7. Manajemen Mapel — `mapel/index.php`
- CRUD mata pelajaran (admin only)
- Sidebar link added

### 8. Atur Guru Kelas — `kelas/guru.php`
- **Seleksi guru untuk kelas, sekarang dengan pilihan Mapel**
- Admin centang guru + pilih mapel dari dropdown (TomSelect/select native)
- Validation: guru yang dicentang wajib pilih mapel
- Data disimpan ke `guru_kelas` dengan `mapel_id`

### 9. Fix Alur Guru Mapel — `kelas/guru.php`
- Sebelumnya: pilih kelas dulu, baru ceklis guru → kacau karena guru bisa di banyak kelas
- Sekarang: **pilih guru dulu** → tampilkan semua kelas + mapel yang diassign ke guru itu
- Submit via `<form>` biasa (bukan JS manual)

### 10. Fix Rekap Mapel — `rekap/mapel.php` & `export_mapel.php`
- `total_hari` dihitung pakai `COUNT(DISTINCT a.tanggal)` — bukan `COUNT(*)`
- Ditambah **view per siswa** kalau user sudah pilih kelas + mapel (tabel detail per siswa)
- Export mengikuti logic yang sama

### 11. Fix Proses Absensi Mapel — `absensi/proses_mapel.php`
- Dari `SELECT → if exists UPDATE else INSERT` di-loop
- Jadi satu query: `INSERT ... ON DUPLICATE KEY UPDATE` — lebih cepat, atomic

### 12. Role Guru — Sembunyikan Menu — sidebar `layout.php`
- **"Rekap Absensi"** (rekap reguler/piket) disembunyikan dari guru
- **"Kartu Siswa"** (menu siswa) disembunyikan dari guru
- Guru hanya akses menu yang relevan: Absen Mapel, Rekap Mapel

### 13. Riwayat Siswa untuk Guru — `siswa/riwayat.php` & `export_riwayat.php`
- Guru (bukan admin) lihat data **`absensi_mapel`** (bukan `absensi` reguler)
- Guru hanya lihat siswa di **kelas yang diassign** ke mereka via `guru_kelas`
- Export (CSV/PDF) mengikuti data yang sesuai role

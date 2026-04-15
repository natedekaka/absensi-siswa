# Aplikasi Absensi Siswa

Sistem absensi siswa berbasis web untuk mengelola kehadiran siswa secara digital.

## Fitur

### Manajemen Data
- **Manajemen Siswa**: Tambah, edit, hapus, import/export data siswa
- **Manajemen Kelas**: Kelola data kelas dan distribusi siswa
- **Manajemen Tahun Ajaran**: Kelola tahun ajaran dan semester
- **Kenaikan Kelas**: Fitur kenaikan kelas dan kelulusan
- **Profil Sekolah**: Konfigurasi nama sekolah dan logo

### Absensi
- **Input Absensi**: Catat kehadiran siswa (Hadir, Sakit, Alfa, Izin, Terlambat)
- **Absensi Barcode/QR**: Scan barcode atau QR code kartu siswa
  - Filter tanggal & semester
  - Notifikasi suara saat absensi berhasil
  - Counter jumlah absensi hari ini
  - Riwayat scan harian

### Laporan & Export
- **Dashboard Statistik**: Grafik kehadiran (line, pie, bar)
  - Filter periode (7/30/90 hari atau custom)
  - Filter semester
- **Rekap Absensi**: Laporan per kelas dengan Export PDF/Excel
- **Riwayat Absensi**: Riwayat kehadiran per siswa

### Generate Kartu
- **Generate Barcode/QR**: Buat barcode atau QR code kartu siswa
  - Print kartu siswa massal

## Tech Stack

- **Backend**: PHP native
- **Database**: MySQL/MariaDB
- **Frontend**: Bootstrap, Chart.js, vanilla JS
- **Scanner**: html5-qrcode (scan barcode/QR via kamera)
- **Server**: Apache (PHP Built-in untuk development)

## Requirements

- PHP 8.2+
- MySQL/MariaDB 10+
- Podman/Docker (untuk container)

## Cara Install

### 1. Clone Repository

```bash
git clone https://github.com/natedekaka/absensi-siswa.git
cd absensi-siswa
```

### 2. Menggunakan Docker/Podman

```bash
# Jalankan container
podman-compose up -d

# Import database
podman exec -i absensi-siswa_db_1 mysql -u root -prootpass absensi_siswa < absensi_siswa_backup_20260411.sql
```

**Catatan**: Gunakan `podman-compose up -d` (bukan down) agar data database tersimpan.

### 3. Tanpa Docker

1. Install PHP dan MySQL/MariaDB
2. Buat database: `CREATE DATABASE absensi_siswa;`
3. Import `absensi_siswa_backup_20260411.sql`
4. Edit `core/Database.php` sesuai konfigurasi lokal
5. Jalankan: `php -S localhost:8080`

## Konfigurasi Database

Edit file `core/Database.php`:

```php
private $host = 'localhost';        // host database
private $user = 'root';             // username
private $pass = '';                 // password
private $db = 'absensi_siswa';      // nama database
```

## Akses Aplikasi

- **Web**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
  - Server: `db`
  - Username: `root`
  - Password: `rootpass`

## Default Login

- **Username**: admin
- **Password**: (cek di database tabel `users`)

## Port

| Service        | Port |
|----------------|------|
| Web App        | 8080 |
| phpMyAdmin     | 8081 |
| Database (ext) | 3306 |

## Struktur Folder

```
absensi-siswa/
├── absensi/          # Modul absensi (termasuk barcode scanner)
├── assets/           # CSS, uploads, assets lain
├── core/             # Konfigurasi core (Database, init)
├── dashboard/        # Halaman dashboard dengan statistik
├── kelas/            # Modul manajemen kelas
├── siswa/            # Modul manajemen siswa (termasuk generate barcode)
├── rekap/            # Laporan rekap absensi & export
├── views/            # Template/layout
├── migrations/       # SQL migrations
├── absensi_siswa_backup_20260411.sql  # Backup database
└── docker-compose.yml
```

## Cara Penggunaan

### Absensi Manual
1. Buka menu Absensi
2. Pilih tanggal & semester
3. Pilih kelas
4. Klik nama siswa & pilih status kehadiran

### Absensi Barcode/QR
1. Buka menu Absensi Barcode
2. Klik "Mulai Scan" untuk scan kamera, atau input manual NIS
3. Pilih status kehadiran
4. Klik "Simpan Absensi"

### Generate Kartu Siswa
1. Buka menu Kartu Siswa
2. Pilih jenis (Barcode/QR Code)
3. Filter kelas jika perlu
4. Klik "Print Semua" untuk print kartu

## Backup Database

```bash
podman exec -i absensi-siswa_db_1 mysqldump -u root -prootpass absensi_siswa > backup_baru.sql
```

---

## Panduan Tahun Ajaran & Semester

### Alur Ganti Tahun Ajaran

```
TAHUN AJARAN LAMA ──► TAHUN AJARAN BARU
        │                      │
        ▼                      ▼
┌───────────────┐       ┌───────────────┐
│ 1. Kelulusan │       │ 5. Buat TA   │
│    Kelas 12  │       │    Baru      │
└───────────────┘       └───────────────┘
        │                      │
        ▼                      ▼
┌───────────────┐       ┌───────────────┐
│ 2. Naik Level│       │ 6. Aktifkan │
│  10→11,11→12│       │    Semester │
└───────────────┘       └───────────────┘
        │
        ▼
┌───────────────┐
│ 3. Redistribusi│
│   Kelas baru │
└───────────────┘
```

### Ringkasan Urutan (WAJIB urutan benar)

| Urutan | Proses | Menu | Keterangan |
|--------|-------|------|----------|
| 1 | **Kelulusan** | Kenaikan Kelas → Kelulusan | Kelas 12 → alumni |
| 2 | **Naik Tingkat** | Kenaikan Kelas → Naik Tingkat | X→XI, XI→XII |
| 3 | **Redistribusi** | Kenaikan Kelas → Redistribusi | Bagi ke kelas/jurusan baru |
| 4 | **Tahun Ajaran Baru** | Tahun Ajaran → Tambah | Buat TA baru |
| 5 | **Aktifkan Semester** | Tahun Ajaran | Aktifkan semester |

---

## Panduan Redistribusi Kelas

Redistribusi = Memindahkan siswa ke kelas/jurusan baru.

### Cara 1: Export-Edit-Import CSV (Recommended)

#### Step 1: Export
```
Menu: Kenaikan Kelas → Export Siswa per Tingkat
1. Pilih tingkat (10 atau 11)
2. Klik "Export CSV"
3. Simpan file
```

#### Step 2: Edit di Excel
```
1. Buka file CSV
2. Edit kolom:
   - Nama Kelas Baru: XI IPA 1
   - ID Kelas Baru: 15 (lihat di menu Kelas)
3. Format delimiter: titik koma (;)
```

Contoh CSV:
```csv
NIS;NISN;Nama;Kelas Lama;Nama Kelas Baru;ID Kelas Baru
12345;0001;BUDI S;X IPA 1;XI IPA 1;15
12346;0002;ANI W;X IPS 1;XI IPS 1;16
```

#### Step 3: Import
```
Menu: Kenaikan Kelas → Import Redistribusi
1. Pilih file CSV
2. Klik Import
```

### Cara 2: Lewat Menu Kelas
```
Menu: Kelas → Edit → Klik siswa → Edit → Ganti kelas
```

### Cara 3: Lewat phpMyAdmin
```sql
-- Lihat ID kelas
SELECT id, nama_kelas FROM kelas;

-- Pindahkan siswa
UPDATE siswa SET kelas_id = 15 WHERE nis = '12345';
```

---

## Troubleshooting

| Masalah | Solusi |
|----------|-------|
| Can't connect DB | Tunggu 10 detik, refresh |
| Import CSV gagal | Delimiter harus `;` |
| ID kelas salah | Cek di menu Kelas |
| Absensi tidak simpan | Semester harus aktif |
| PDF export error | Semester harus di-set |

## Lisensi

MIT License
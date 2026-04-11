# Aplikasi Absensi Siswa

Sistem absensi siswa berbasis web untuk mengelola kehadiran siswa secara digital.

## Fitur

- **Manajemen Siswa**: Tambah, edit, hapus, import/export data siswa
- **Manajemen Kelas**: Kelola data kelas dan distribusi siswa
- **Absensi**: Catat kehadiran siswa (Hadir, Sakit, Alfa, Izin, Terlambat)
- **Rekap Absensi**: Laporan rekapitulasi absensi per kelas
- **Manajemen Tahun Ajaran**: Kelola tahun ajaran dan semester
- **Kenaikan Kelas**: Fitur kenaikan kelas dan kelulusan
- **Barcode Scanner**: Absensi menggunakan barcode
- **Profil Sekolah**: Konfigurasi nama sekolah dan logo

## Tech Stack

- **Backend**: PHP native
- **Database**: MySQL/MariaDB
- **Frontend**: Bootstrap, vanilla JS
- **Server**: Apache (PHP Built-in untuk development)

## Requirements

- PHP 8.2+
- MySQL/MariaDB 10+
- Podman/Docker (untuk container)
- Composer (jika ada dependensi)

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
podman exec -i absensi-siswa-db mysql -u root -prootpass absensi_siswa < absensi_siswa_backup.sql
```

### 3. Tanpa Docker

1. Install PHP dan MySQL/MariaDB
2. Buat database: `CREATE DATABASE absensi_siswa;`
3. Import `absensi_siswa_backup.sql`
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
- **Password**: (cek di database)

## Port

| Service        | Port |
|----------------|------|
| Web App        | 8080 |
| phpMyAdmin     | 8081 |
| Database (ext) | 3306 |

## Struktur Folder

```
absensi-siswa/
├── absensi/          # Modul absensi
├── assets/           # CSS, uploads, assets lain
├── core/             # Konfigurasi core (Database, init)
├── dashboard/        # Halaman dashboard
├── kelas/            # Modul manajemen kelas
├── siswa/            # Modul manajemen siswa
├── views/            # Template/layout
├── migrations/      # SQL migrations
├── absensi_siswa_backup.sql  # Backup database
└── docker-compose.yml
```

##Lisensi

MIT License
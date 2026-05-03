# 🎓 Sistem Absensi Siswa

Aplikasi web berbasis PHP untuk mencatat dan mengelola absensi siswa harian. Dilengkapi dengan fitur ekspor, visualisasi data, dan antarmuka yang responsif.

![PHP Version](https://img.shields.io/badge/PHP-8.2-blue.svg)
![MySQL](https://img.shields.io/badge/Database-MySQL-orange.svg)
![License](https://img.shields.io/badge/License-MIT-green.svg)

---

## 📋 Daftar Isi

- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Cara Menggunakan](#cara-menggunakan)
- [Struktur Folder](#struktur-folder)
- [Migrasi Database](#migrasi-database)
- [Kontribusi](#kontribusi)
- [Lisensi](#lisensi)

---

## ✨ Fitur

### 1. **Manajemen Absensi**
   - Input absensi harian (Hadir, Terlambat, Sakit, Izin, Alfa)
   - Scan barcode untuk input cepat
   - Filter berdasarkan kelas, semester, dan tanggal

### 2. **Rekap & Laporan**
   - Rekap absensi per kelas/semester
   - Riwayat absensi per siswa
   - **Progress Bar** kehadiran dengan kode warna
   - **Calendar Heatmap** visualisasi GitHub-style
   - Export ke **Excel (CSV)** dan **PDF**

### 3. **Searchable Dropdown**
   - Pencarian siswa real-time dengan TomSelect
   - Menampilkan nama siswa + kelas

### 4. **Filter Semester**
   - Pilihan semester dengan auto-set tanggal
   - Periode semester otomatis terisi

### 5. **Bulk Delete**
   - Hapus masal data absensi dengan checkbox
   - Konfirmasi sebelum menghapus

### 6. **Autentikasi & Keamanan**
   - Login dengan Remember Me
   - Forgot Password dengan token reset
   - CSRF Protection pada semua form
   - Password hashing dengan bcrypt

### 7. **UI/UX Modern**
   - Dark Mode toggle
   - Responsive (Mobile & Desktop)
   - PWA Support (Progressive Web App)
   - Stat cards dengan gradient

---

## 🛠️ Teknologi

| Komponen | Teknologi |
|-----------|-------------|
| **Backend** | PHP 8.2, MySQLi (PDO Wrapper) |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) |
| **CSS Framework** | Bootstrap 5.3 |
| **Libraries** | TomSelect, Chart.js, Font Awesome |
| **Database** | MySQL/MariaDB |
| **PWA** | Service Worker, Manifest.json |

---

## 📝 Persyaratan

- PHP >= 8.0
- MySQL >= 5.7 atau MariaDB >= 10.3
- Web Server (Apache/Nginx) atau PHP built-in server
- Docker (opsional, untuk development)

---

## 🚀 Instalasi

### Metode 1: Local Development

1. **Clone repository:**
   ```bash
   git clone https://github.com/username/absensi-siswa.git
   cd absensi-siswa
   ```

2. **Setup Database:**
   ```bash
   # Login ke MySQL
   mysql -u root -p
   
   # Buat database
   CREATE DATABASE absensi_siswa;
   EXIT;
   ```

3. **Copy file environment:**
   ```bash
   cp .env.example .env
   ```

4. **Edit konfigurasi `.env`:**
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=absensi_siswa
   DB_USER=root
   DB_PASS=
   BASE_URL=/
   APP_ENV=development
   APP_SECRET=your_random_secret_key_here
   ```

5. **Jalankan migrasi:**
   ```bash
   php migrate.php
   ```

6. **Start web server:**
   ```bash
   php -S localhost:8000
   ```

7. **Akses aplikasi:**
   - Buka browser: `http://localhost:8000`

---

### Metode 2: Docker (Rekomendasi)

1. **Pastikan Docker terinstal**

2. **Jalankan dengan docker-compose:**
   ```bash
   docker-compose up -d
   ```

3. **Akses aplikasi:**
   - Web: `http://localhost:8080`
   - phpMyAdmin: `http://localhost:8081`

**Catatan:** Docker otomatis menjalankan migrasi saat container pertama kali dinyalakan.

---

## ⚙️ Konfigurasi

### Environment Variables (`.env`)

| Variabel | Deskripsi | Default |
|-----------|-------------|---------|
| `DB_HOST` | Host database | `127.0.0.1` |
| `DB_PORT` | Port database | `3306` |
| `DB_NAME` | Nama database | `absensi_siswa` |
| `DB_USER` | Username database | `root` |
| `DB_PASS` | Password database | (kosong) |
| `BASE_URL` | Base URL aplikasi | `/` |
| `APP_ENV` | Environment (`development`/`production`/`docker`) | `development` |
| `APP_SECRET` | Secret key untuk CSRF & session | (wajib diubah) |
| `UPLOAD_MAX_SIZE` | Maksimal ukuran upload | `2M` |
| `ALLOWED_EXTENSIONS` | Ekstensi file yang diizinkan | `jpg,jpeg,png,pdf` |

---

## 📖 Cara Menggunakan

### 1. **Login**
   - Akses `http://localhost:8080/login.php`
   - Default: Admin perlu dibuat manual via phpMyAdmin
   - Fitur "Remember Me" tersedia
   - Lupa pasword? Klik "Lupa Pasword" → cek email (konfigurasi SMTP diperlukan)

### 2. **Input Absensi Harian**
   - Masuk ke menu **Absensi > Input Absensi**
   - Pilih **Semester** dan **Kelas**
   - Pilih **Tanggal**
   - Klik siswa → pilih status (Hadir/Terlambat/Sakit/Izin/Alfa)
   - Klik **Simpan Absensi**

### 3. **Scan Barcode**
   - Masuk ke menu **Absensi > Scan Barcode**
   - Izinkan akses kamera
   - Arahkan ke barcode siswa
   - Pilih status → **Simpan**

### 4. **Rekap Absensi**
   - Masuk ke menu **Rekap**
   - Pilih **Kelas** dan **Periode Tanggal**
   - Klik **Filter**
   - Lihat statistik per kelas

### 5. **Riwayat Siswa (Fitur Baru!)**
   - Masuk ke menu **Siswa > Riwayat**
   - **Cari siswa** dengan kotak pencarian (TomSelect)
   - Pilih **Semester** → tanggal otomatis terisi
   - Lihat:
     - **Progress Bar** kehadiran (%)
     - **Calendar Heatmap** (Hijau=Hadir, Kuning=Terlambat, Biru=Sakit/Izin, Merah=Alfa)
     - **Grafik Tren** kehadiran
     - **Tabel Detail** dengan fitur **Hapus Masal**
   - **Export** ke Excel atau PDF

### 6. **Manajemen Siswa**
   - Tambah siswa baru (individu atau import CSV)
   - Edit data siswa
   - Hapus siswa

### 7. **Konfigurasi Sekolah**
   - Atur nama sekolah
   - Upload logo
   - Pilih warna tema (Primary & Sekunder)

---

## 📂 Struktur Folder

```
absensi-siswa/
├── core/
│   ├── init.php              # Inisialisasi (CSRF, helper functions)
│   ├── config.php            # Konfigurasi environment
│   ├── Database.php         # MySQLi wrapper (singleton)
│   ├── DatabasePDO.php      # PDO wrapper (optional)
│   └── App.php               # Router & helper functions
├── controllers/
│   ├── HomeController.php
│   ├── SiswaController.php
│   └── AbsensiController.php
├── views/
│   ├── layout.php           # Main layout (navbar, sidebar, footer)
│   ├── login.php
│   └── dashboard.php
├── siswa/
│   ├── index.php           # List siswa
│   ├── tambah.php         # Form tambah siswa
│   ├── edit.php            # Form edit siswa
│   ├── import.php         # Import CSV
│   ├── export.php         # Export CSV
│   ├── hapus.php          # Hapus siswa
│   ├── hapus_batch.php    # Hapus masal siswa
│   ├── barcode.php        # Barcode generator
│   └── riwayat.php       # Riwayat absensi ( heatmap, progress bar)
├── absensi/
│   ├── index.php           # Input absensi
│   ├── proses.php         # Proses simpan absensi
│   ├── barcode.php        # Scan barcode
│   ├── get_siswa.php     # AJAX get siswa by kelas
│   └── proses_barcode.php # Proses simpan dari barcode
├── rekap/
│   ├── kelas.php          # Rekap per kelas
│   ├── siswa.php         # Rekap per siswa
│   └── export.php        # Export rekap
├── kelas/
│   ├── index.php           # List kelas
│   ├── tambah.php
│   └── edit.php
├── migrations/
│   ├── add_konfigurasi_sekolah.sql
│   ├── add_kolom_siswa.sql
│   ├── add_barcode_siswa.sql
│   ├── add_indexes.sql
│   └── add_remember_token.sql
├── assets/
│   ├── css/
│   │   ├── style.css              # Main stylesheet
│   │   └── color-override.css     # Dark mode overrides
│   ├── js/
│   ├── img/
│   └── uploads/
├── dashboard/
│   └── index.php            # Dashboard dengan statistik
├── forgot_password.php
├── reset_password.php
├── migrate.php              # CLI migration tool
├── manifest.json            # PWA manifest
├── service-worker.js        # PWA service worker
├── docker-compose.yml      # Docker configuration
├── .env.example            # Contoh file environment
└── README.md               # Dokumentasi ini
```

---

## 🗃️ Migrasi Database

Jalankan migrasi untuk membuat tabel database:

```bash
# Jalankan semua migrasi
php migrate.php

# Jalankan ulang dari awal (hati-hati, akan menghapus semua tabel!)
php migrate.php --fresh
```

**Tabel yang dibuat:**
- `siswa` - Data siswa
- `kelas` - Data kelas
- `semester` - Data semester
- `tahun_ajaran` - Data tahun ajaran
- `absensi` - Data absensi
- `users` - Data pengguna (admin)
- `konfigurasi_sekolah` - Pengaturan sekolah

---

## 🤝 Kontribusi

Kontribusi sangat diterima!

1. Fork repository ini
2. Buat branch fitur (`git checkout -b fitur/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin fitur/AmazingFeature`)
5. Buat Pull Request

---

## 📄 Lisensi

Proyek ini dilisensi di bawah **MIT License** - lihat file `LICENSE` untuk detail.

---

## 📧️ Support & Kontak

- **Issues:** Gunakan [GitHub Issues](https://github.com/username/absensi-siswa/issues) untuk bug reports
- **Diskusi:** Gunakan [GitHub Discussions](https://github.com/username/absensi-siswa/discussions)

---

## 🙏 Acknovledgments

- **Bootstrap** - CSS Framework
- **TomSelect** - Searchable dropdown
- **Chart.js** - Data visualization
- **Font Awesome** - Icons
- **Docker** - Containerization

---

**Dibuat dengan ❤️ oleh Tim Pengembang Absensi Siswa**

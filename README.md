# 🎓 Sistem Absensi Siswa

Aplikasi web berbasis PHP untuk mencatat dan mengelola absensi siswa. Mendukung **absensi piket (reguler)** dan **absensi mata pelajaran (mapel)** dengan role-based access untuk Admin, Guru, dan Wali Kelas. Dilengkapi fitur ekspor, visualisasi data, dan antarmuka responsif.

![PHP Version](https://img.shields.io/badge/PHP-8.2-blue.svg)
![MySQL](https://img.shields.io/badge/Database-MySQL-orange.svg)
![Tailwind CSS](https://img.shields.io/badge/CSS-Tailwind%20v4-38BDF8.svg)
![License](https://img.shields.io/badge/License-MIT-green.svg)

---

## 📋 Daftar Isi

- [Fitur](#fitur)
- [Role & Hak Akses](#role--hak-akses)
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

### 1. **Manajemen Absensi Piket (Reguler)**
   - Input absensi harian (Hadir, Terlambat, Sakit, Izin, Alfa) per kelas
   - Scan barcode untuk input cepat
   - Filter berdasarkan kelas, semester, dan tanggal
   - *Akses: Admin & Wali Kelas*

### 2. **Absensi Mata Pelajaran (Mapel)**
   - Guru mapel mengabsen siswa per kelas + mata pelajaran
   - Pilih semester → kelas → mapel → tanggal
   - Status absensi per siswa (Hadir/Terlambat/Sakit/Izin/Alfa)
   - Guru hanya melihat kelas + mapel yang diassign
   - *Akses: Admin & Guru*

### 3. **Rekap & Laporan**
   - Rekap absensi piket per kelas/semester
   - Rekap absensi mapel per kelas + mapel
   - Riwayat absensi per siswa (piket & mapel, sesuai role)
   - **Progress Bar** kehadiran dengan kode warna
   - **Calendar Heatmap** visualisasi GitHub-style
   - **Grafik Tren** kehadiran
   - Export ke **Excel (CSV)** dan **PDF**

### 4. **Role-Based Access Control (RBAC)**
   - **Admin**: akses penuh (absensi piket, scan barcode, manajemen data, dll)
   - **Guru**: hanya lihat kelas+mapel yang diassign, akses Absen Mapel & Rekap Mapel
   - **Wali Kelas**: akses absensi piket + rekap untuk kelas yang diampu

### 5. **Manajemen Data**
   - **Siswa**: CRUD, import CSV, export, generate barcode
   - **Kelas**: CRUD
   - **Users**: CRUD untuk admin/guru/wali_kelas
   - **Mata Pelajaran (Mapel)**: CRUD
   - **Orang Tua**: hubungkan orang tua ke siswa
   - **Tahun Ajaran**: kelola tahun ajaran aktif
   - **Kenaikan Kelas & Kelulusan**: naikkan siswa ke kelas berikutnya, kelulusan, redistribusi

### 6. **Atur Guru Kelas + Mapel**
   - Admin pilih guru → centang kelas yang diampu → pilih mapel (untuk guru mapel)
   - Data disimpan ke tabel `guru_kelas` dengan `mapel_id`

### 7. **Searchable Dropdown**
   - Pencarian siswa real-time dengan TomSelect
   - Menampilkan nama siswa + kelas

### 8. **Filter Semester**
   - Pilihan semester dengan auto-set tanggal
   - Periode semester otomatis terisi

### 9. **Bulk Delete**
   - Hapus masal data absensi dengan checkbox
   - Konfirmasi sebelum menghapus

### 10. **Autentikasi & Keamanan**
   - Login dengan Remember Me
   - Forgot Password dengan token reset
   - CSRF Protection pada semua form
   - Password hashing dengan bcrypt

### 11. **Konfigurasi Sekolah**
   - Atur nama sekolah
   - Upload logo
   - Pilih warna tema (Primary & Sekunder)

### 12. **UI/UX Modern**
   - Dark Mode toggle
   - Responsive (Mobile & Desktop)
   - PWA Support (Progressive Web App)
   - Stat cards dengan gradient

---

## 👥 Role & Hak Akses

| Menu | Admin | Guru | Wali Kelas |
|------|-------|------|------------|
| Dashboard | ✅ | ✅ | ✅ |
| Absensi Piket | ✅ | — | ✅ |
| Scan Barcode | ✅ | — | — |
| Absensi Mapel | ✅ | ✅ | — |
| Manajemen Siswa | ✅ | — | — |
| Riwayat Siswa | ✅ | ✅ (mapel only) | ✅ (piket) |
| Manajemen Kelas | ✅ | — | — |
| Rekap Piket | ✅ | — | ✅ |
| Rekap Mapel | ✅ | ✅ | — |
| Manajemen User | ✅ | — | — |
| Manajemen Mapel | ✅ | — | — |
| Atur Guru Kelas | ✅ | — | — |
| Tahun Ajaran | ✅ | — | — |
| Kenaikan Kelas | ✅ | — | — |
| Konfigurasi Sekolah | ✅ | — | — |

---

## 🛠️ Teknologi

| Komponen | Teknologi |
|-----------|-------------|
| **Backend** | PHP 8.2, MySQLi (singleton wrapper) |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) |
| **CSS Framework** | Tailwind CSS v4 (custom design system) |
| **Libraries** | TomSelect, Chart.js, Font Awesome 6, jsPDF, SheetJS |
| **Database** | MySQL/MariaDB |
| **PWA** | Service Worker, Manifest.json |

---

## 📝 Persyaratan

- PHP >= 8.0
- MySQL >= 5.7 atau MariaDB >= 10.3
- Web Server (Apache/Nginx) atau PHP built-in server
- Docker / Podman (opsional, untuk development & production)
- Node.js (untuk build CSS dengan Tailwind CLI)

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

5. **Install dependencies & build CSS:**
   ```bash
   npm install
   npx tailwindcss -i assets/css/main.css -o assets/css/app.css
   ```

6. **Jalankan migrasi:**
   ```bash
   php migrate.php
   ```

7. **Start web server:**
   ```bash
   php -S localhost:8000
   ```

8. **Akses aplikasi:**
   - Buka browser: `http://localhost:8000`

---

### Metode 2: Docker / Podman (Rekomendasi)

1. **Pastikan Docker atau Podman terinstal**

2. **Jalankan dengan docker-compose / podman-compose:**
   ```bash
   # Docker
   docker-compose up -d

   # Podman
   podman-compose up -d
   ```

3. **Akses aplikasi:**
   - Web: `http://localhost:8082`
   - phpMyAdmin: `http://localhost:8083`

**Catatan:** Container otomatis menjalankan migrasi, kompresi gzip, dan OPcache tuning saat pertama kali dinyalakan.

---

### Metode 3: Deploy ke Production Server

1. **Clone repository:**
   ```bash
   git clone https://github.com/username/absensi-siswa.git
   cd absensi-siswa
   ```

2. **Build CSS (butuh Node.js):**
   ```bash
   npm install
   npx @tailwindcss/cli -i assets/css/main.css -o assets/css/app.css --minify
   ```

3. **Setup Database:**
   ```bash
   mysql -u root -p -e "CREATE DATABASE absensi_siswa"
   ```

4. **Buat `.env`:**
   ```bash
   cp .env.example .env
   nano .env
   ```
   Sesuaikan konfigurasi database dan `APP_ENV=production`.

5. **Jalankan migrasi:**
   ```bash
   php migrate.php
   ```

6. **Set permission:**
   ```bash
   chmod -R 755 .
   chmod -R 777 assets/uploads
   ```

7. **Config Apache** (`/etc/apache2/sites-available/absensi.conf`):
   ```apache
   <VirtualHost *:80>
       ServerName absensi.domain.com
       DocumentRoot /var/www/absensi-siswa

       <Directory /var/www/absensi-siswa>
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/absensi-error.log
       CustomLog ${APACHE_LOG_DIR}/absensi-access.log combined
   </VirtualHost>
   ```
   ```bash
   a2ensite absensi.conf
   a2enmod rewrite
   systemctl restart apache2
   ```

8. **Config Nginx** (alternatif, `/etc/nginx/sites-available/absensi`):
   ```nginx
   server {
       listen 80;
       server_name absensi.domain.com;
       root /var/www/absensi-siswa;
       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           include snippets/fastcgi-php.conf;
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
       }

       location ~ /\.ht {
           deny all;
       }

       location /assets/ {
           expires 1y;
           add_header Cache-Control "public, immutable";
       }
   }
   ```
   ```bash
   ln -s /etc/nginx/sites-available/absensi /etc/nginx/sites-enabled/
   nginx -t && systemctl restart nginx
   ```

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
   - Admin perlu dibuat pertama kali via phpMyAdmin
   - Fitur "Remember Me" tersedia
   - Lupa password? Klik "Lupa Password" → cek email (konfigurasi SMTP diperlukan)

### 2. **Input Absensi Piket (Reguler)**
   - Masuk ke menu **Absensi > Input Absensi**
   - Pilih **Semester** dan **Kelas**
   - Pilih **Tanggal**
   - Klik siswa → pilih status (Hadir/Terlambat/Sakit/Izin/Alfa)
   - Klik **Simpan Absensi**
   - *Menu ini hanya untuk Admin & Wali Kelas*

### 3. **Input Absensi Mapel**
   - Masuk ke menu **Absensi > Absen Mapel**
   - Pilih **Semester, Kelas, Mata Pelajaran, Tanggal**
   - Data siswa akan tampil otomatis
   - Klik siswa → pilih status (Hadir/Terlambat/Sakit/Izin/Alfa)
   - Klik **Simpan Absensi**
   - Guru hanya melihat kelas & mapel yang diassign ke dirinya
   - *Menu ini untuk Admin & Guru*

### 4. **Scan Barcode**
   - Masuk ke menu **Absensi > Scan Barcode**
   - Izinkan akses kamera
   - Arahkan ke barcode siswa
   - Pilih status → **Simpan**
   - *Menu ini hanya untuk Admin*

### 5. **Rekap Absensi Piket**
   - Masuk ke menu **Rekap > Rekap Absensi**
   - Pilih **Kelas** dan **Periode Tanggal**
   - Klik **Filter**
   - Lihat statistik per kelas
   - *Menu ini untuk Admin & Wali Kelas*

### 6. **Rekap Absensi Mapel**
   - Masuk ke menu **Rekap > Rekap Mapel**
   - Pilih **Semester, Kelas, Mata Pelajaran**
   - Lihat statistik kehadiran per siswa
   - Klik kelas + mapel untuk lihat detail per siswa
   - Export Excel & PDF tersedia
   - *Menu ini untuk Admin & Guru*

### 7. **Riwayat Siswa**
   - Masuk ke menu **Siswa > Riwayat**
   - **Cari siswa** dengan kotak pencarian (TomSelect)
   - Pilih **Semester** → tanggal otomatis terisi
   - Lihat:
     - **Progress Bar** kehadiran (%)
     - **Calendar Heatmap** (Hijau=Hadir, Kuning=Terlambat, Biru=Sakit/Izin, Merah=Alfa)
     - **Grafik Tren** kehadiran
     - **Tabel Detail** dengan fitur **Hapus Masal**
   - **Export** ke Excel atau PDF
   - Guru melihat data absensi mapel, Admin & Wali Kelas melihat data absensi piket

### 8. **Manajemen Data**
   - **Siswa**: Tambah/edit/hapus/import CSV/export/generate barcode
   - **Kelas**: Tambah/edit/hapus
   - **Mata Pelajaran**: Tambah/edit/hapus
   - **Users**: Kelola akun admin, guru, wali_kelas
   - **Tahun Ajaran**: Kelola tahun ajaran + set aktif
   - **Kenaikan Kelas**: Naikkan siswa, kelulusan, redistribusi siswa

### 9. **Atur Guru Kelas & Mapel**
   - Masuk ke **Kelas > Atur Guru**
   - Pilih guru dari daftar
   - Centang kelas yang diampu guru tersebut
   - Untuk guru mapel: pilih mata pelajaran dari dropdown
   - Klik **Simpan**

### 10. **Konfigurasi Sekolah**
   - Masuk ke menu **Pengaturan > Profil Sekolah**
   - Atur nama sekolah, alamat
   - Upload logo
   - Pilih warna tema (Primary & Sekunder)

---

## 📂 Struktur Folder

```
absensi-siswa/
├── core/
│   ├── init.php              # Inisialisasi (CSRF, helper functions, RBAC)
│   ├── config.php            # Konfigurasi environment
│   ├── Database.php         # MySQLi wrapper (singleton)
│   └── App.php               # Router & helper functions
├── views/
│   └── layout.php           # Main layout (navbar, sidebar, footer)
├── dashboard/
│   └── index.php            # Dashboard dengan statistik
├── absensi/
│   ├── index.php           # Input absensi piket
│   ├── proses.php          # Proses simpan absensi piket
│   ├── barcode.php         # Scan barcode
│   ├── proses_barcode.php  # Proses simpan dari barcode
│   ├── get_siswa.php       # AJAX get siswa by kelas (piket)
│   ├── mapel.php           # Input absensi mapel
│   ├── proses_mapel.php    # Proses simpan absensi mapel
│   └── get_siswa_mapel.php # AJAX get siswa by kelas + mapel
├── rekap/
│   ├── kelas.php           # Rekap piket per kelas
│   ├── export.php          # Export rekap piket
│   ├── mapel.php           # Rekap absensi mapel
│   └── export_mapel.php    # Export rekap mapel
├── siswa/
│   ├── index.php           # List siswa
│   ├── tambah.php          # Form tambah siswa
│   ├── edit.php            # Form edit siswa
│   ├── import.php          # Import CSV
│   ├── export.php          # Export CSV
│   ├── hapus.php           # Hapus siswa
│   ├── hapus_batch.php     # Hapus masal siswa
│   ├── bulk_delete.php     # Hapus masal riwayat absensi
│   ├── barcode.php         # Barcode generator
│   ├── orang_tua.php       # Kelola orang tua siswa
│   ├── riwayat.php         # Riwayat absensi (heatmap, progress bar, grafik)
│   └── export_riwayat.php  # Export riwayat siswa
├── kelas/
│   ├── index.php           # List kelas
│   ├── tambah.php          # Tambah kelas
│   ├── edit.php            # Edit kelas
│   └── guru.php            # Atur guru + mapel per kelas
├── mapel/
│   └── index.php           # CRUD mata pelajaran
├── users/
│   └── index.php           # Manajemen user (admin/guru/wali_kelas)
├── kenaikan/
│   ├── index.php           # Kenaikan kelas
│   ├── kelulusan.php       # Kelulusan siswa
│   └── redistribusi.php    # Redistribusi siswa antar kelas
├── tahun_ajaran/
│   └── index.php           # CRUD tahun ajaran
├── migrations/
│   ├── add_konfigurasi_sekolah.sql
│   ├── add_kolom_siswa.sql
│   ├── add_barcode_siswa.sql
│   ├── add_indexes.sql
│   ├── add_remember_token.sql
│   ├── add_rbac.sql
│   ├── add_absensi_mapel.sql
│   ├── add_mapel.sql
│   └── add_mapel_id_ke_absensi_mapel.sql
├── assets/
│   ├── css/
│   │   ├── main.css              # Source CSS (Tailwind v4 + custom design)
│   │   └── app.css               # Compiled CSS
│   ├── js/
│   ├── img/
│   └── uploads/
├── propos_sekolah.php        # Konfigurasi sekolah (logo, warna, nama)
├── forgot_password.php
├── reset_password.php
├── migrate.php               # CLI migration tool
├── manifest.json             # PWA manifest
├── service-worker.js         # PWA service worker
├── package.json              # Node.js dependencies (Tailwind CSS)
├── docker-compose.yml        # Docker configuration
├── .env.example              # Contoh file environment
└── README.md                 # Dokumentasi ini
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

| Tabel | Deskripsi |
|-------|-----------|
| `siswa` | Data siswa |
| `kelas` | Data kelas |
| `semester` | Data semester |
| `tahun_ajaran` | Data tahun ajaran |
| `absensi` | Data absensi piket (reguler) |
| `absensi_mapel` | Data absensi mata pelajaran |
| `mapel` | Data mata pelajaran |
| `guru_kelas` | Relasi guru → kelas (+ mapel_id untuk guru mapel) |
| `users` | Data pengguna (admin, guru, wali_kelas) |
| `siswa_orang_tua` | Relasi siswa → orang tua |
| `konfigurasi_sekolah` | Pengaturan sekolah (logo, nama, warna tema) |

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

## 🙏 Acknowledgments

- **Tailwind CSS** - CSS Framework
- **TomSelect** - Searchable dropdown
- **Chart.js** - Data visualization
- **Font Awesome** - Icons
- **SheetJS (xlsx)** - Export Excel
- **jsPDF** - Export PDF
- **Docker** - Containerization

---

**Dibuat dengan ❤️ oleh MGMP Informatika Sanaci**

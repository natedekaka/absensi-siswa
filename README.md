# 🎓 Sistem Absensi Siswa — Panduan Penggunaan

Aplikasi absensi untuk sekolah berbasis web. Mendukung absensi piket (reguler) dan absensi mata pelajaran (mapel), scan barcode, rekap visual, dan manajemen data siswa.

> **Role di aplikasi ini:** Admin • Guru • Wali Kelas • Orang Tua

---

## 📋 Daftar Isi

- [Login & Pertama Kali](#-login--pertama-kali)
- [Dashboard](#-dashboard)
- [Absensi Harian (Piket)](#-absensi-harian-piket)
- [Absensi Mapel (Mata Pelajaran)](#-absensi-mapel-mata-pelajaran)
- [Copy Absensi Kemarin](#-copy-absensi-kemarin)
- [Scan Barcode](#-scan-barcode)
- [Rekap & Laporan](#-rekap--laporan)
- [Riwayat Absensi Siswa](#-riwayat-absensi-siswa)
- [Manajemen Data (Admin)](#-manajemen-data-admin)
- [Kenaikan Kelas & Promosi](#-kenaikan-kelas--promosi)
- [Import Siswa Baru](#-import-siswa-baru)
- [Role & Hak Akses](#-role--hak-akses)
- [FAQ](#-faq)

---

## 🔐 Login & Pertama Kali

1. Buka aplikasi di browser (kiri sekolah masing-masing)
2. Masukkan **Username** dan **Password** dari admin
3. Centang **Remember Me** agar tidak perlu login ulang
4. Klik **Masuk**

![Login page concept]

**Lupa password?** Klik "Lupa Password" — masukkan email — cek inbox untuk link reset.

> **Admin pertama** harus dibuat langsung ke database.

### Setelah Login Pertama Kali

Admin sebaiknya melakukan ini dulu:

1. **Profil Sekolah** (sidebar → Pengaturan → Profil Sekolah)
   - Isi nama sekolah
   - Upload logo
   - Pilih warna tema (primary & sekunder)
2. **Tahun Ajaran** (sidebar → Pengaturan → Tahun Ajaran)
   - Tambah tahun ajaran baru → klik **Aktifkan**
3. **Kelas** (sidebar → Data → Kelas)
   - Tambah semua kelas yang ada
4. **Mata Pelajaran** (sidebar → Pengaturan → Mata Pelajaran)
   - Tambah semua mapel yang diajarkan
5. **Pengguna** (sidebar → Pengaturan → Pengguna)
   - Tambah akun Guru dan Wali Kelas
6. **Atur Guru Kelas** (sidebar → Pengaturan → Guru Kelas)
   - Pilih guru → centang kelas yang diampu
   - Untuk guru mapel: pilih juga mata pelajarannya
7. **Siswa** (sidebar → Data → Siswa → Import CSV)

---

## 📊 Dashboard

Halaman utama setelah login. Menampilkan:

| Kartu Statistik | Keterangan |
|---|---|
| Total Siswa | Semua siswa aktif |
| Total Kelas | Jumlah kelas terdaftar |
| Total Guru | Jumlah guru & wali kelas |
| Hari Ini | Tanggal + jam real-time |
| **Kehadiran Hari Ini** | Pie chart: Hadir, Terlambat, Sakit, Izin, Alfa |
| **Tren Bulanan** | Line chart kehadiran 30 hari terakhir |

Role non-admin melihat data sesuai aksesnya.

---

## 📝 Absensi Harian (Piket)

> **Akses:** Admin, Wali Kelas

Untuk absensi rutin setiap hari per kelas.

### Langkah-langkah:

1. **Buka menu** `Absensi` di sidebar
2. **Pilih filter:**
   - **Tanggal** — otomatis hari ini
   - **Semester** — otomatis semester aktif
   - **Kelas** — pilih kelas yang akan diabsen
3. Tabel siswa akan muncul otomatis
4. Klik **radio button** status per siswa:

| Status | Warna | Arti |
|---|---|---|
| Hadir | 🟢 Hijau | Siswa hadir tepat waktu |
| Terlambat | 🟡 Kuning | Hadir tapi terlambat |
| Sakit | 🔵 Biru | Tidak hadir karena sakit |
| Izin | 🟣 Ungu | Tidak hadir karena izin |
| Alfa | 🔴 Merah | Tidak hadir tanpa keterangan |

### ✨ Set Semua Hadir

Klik checkbox **"Semua"** di pojok kiri header tabel untuk set semua siswa ke **Hadir** dalam 1 klik. Centang sekali — semua berubah jadi Hijau.

### ✨ Auto-Save

Setiap kali kamu klik status siswa, data **otomatis tersimpan** setelah jeda 400ms. Tidak perlu klik tombol simpan. Indikator "Menyimpan..." muncul di pojok kanan atas, lalu hilang saat selesai.

### ✨ Copy Absensi Kemarin

Tombol **"Copy Kemarin"** akan muncul setelah kelas dipilih. Fungsinya menyalin data absensi dari hari sebelumnya (sistem akan cari data mundur hingga 7 hari, otomatis skip hari Minggu) dan menerapkannya ke hari ini. Auto-save langsung jalan.

### Filter Semua Kelas (*Admin only*)

Pilih opsi **"Semua Kelas"** di dropdown kelas untuk menampilkan absensi gabungan semua kelas dalam satu tabel.

---

## 📚 Absensi Mapel (Mata Pelajaran)

> **Akses:** Admin, Guru

Guru mengabsen siswa sesuai mata pelajaran yang diampu.

### Langkah-langkah:

1. Buka menu **Absen Mapel** di sidebar
2. Pilih filter:
   - **Semester**
   - **Kelas**
   - **Mata Pelajaran**
   - **Tanggal**
3. Tabel siswa tampil otomatis
4. Klik status per siswa (Hadir/Terlambat/Sakit/Izin/Alfa)

### Yang perlu diketahui:
- Guru hanya melihat kelas & mapel yang **diassign** ke dirinya (atur di menu Guru Kelas oleh Admin)
- Auto-save juga aktif (sama seperti absensi piket)
- Tombol "Copy Kemarin" juga tersedia
- Checkbox "Semua" juga tersedia

---

## 📋 Copy Absensi Kemarin

Fitur ini ada di halaman **Absensi** dan **Absen Mapel**.

### Cara kerja:
1. Pilih kelas
2. Tombol **"Copy Kemarin"** akan muncul di pojok kanan atas
3. Klik tombol — sistem mencari data absensi dari hari sebelumnya
4. **Maju mundur hingga 7 hari** — kalau kemarin libur/Minggu, sistem cari hari sebelumnya
5. **Hari Minggu otomatis dilewati**
6. Data ditemukan → diterapkan ke form → auto-save langsung jalan
7. Muncul notifikasi hijau: *"Data absensi kemarin berhasil disalin"*

### Kapan fitur ini berguna:
- Hari Senin — copy dari Jumat sebelumnya (skip Sabtu-Minggu)
- Setelah libur panjang — copy dari hari terakhir sebelum libur
- Update manual: copy dulu lalu ubah beberapa siswa yang berbeda

---

## 📷 Scan Barcode

> **Akses:** Admin only

Untuk absensi cepat dengan kamera. Setiap siswa punya barcode unik (NIS).

### Langkah-langkah:

1. Buka menu **Scan Barcode** di sidebar
2. Izinkan akses kamera saat diminta browser
3. Arahkan kamera ke barcode/kartu siswa
4. Barcode terbaca — data siswa muncul
5. Pilih status (Hadir/Terlambat/Sakit/Izin/Alfa)
6. Klik **Simpan**
7. Langsung siap scan siswa berikutnya

### Cetak Kartu Barcode:
1. Buka menu **Kartu Siswa** (sidebar → Data → Kartu Siswa)
2. Centang siswa yang ingin dicetak
3. Klik **Cetak Barcode**

---

## 📈 Rekap & Laporan

> **Akses:** Admin, Wali Kelas (Rekap Piket) • Admin, Guru (Rekap Mapel)

### Rekap Absensi (Piket)

1. Buka menu **Rekap Absensi** di sidebar
2. Pilih **Kelas** dan **Rentang Tanggal**
3. Klik **Filter**
4. Lihat tabel rekap per siswa dengan jumlah:
   - Total hari
   - Hadir • Terlambat • Sakit • Izin • Alfa
5. **Export Excel** atau **Export PDF** untuk unduh

### Rekap Mapel

1. Buka menu **Rekap Mapel** di sidebar
2. Pilih **Kelas** → lihat daftar mapel
3. Klik mapel untuk lihat detail rekap per siswa
4. Export Excel & PDF juga tersedia

---

## 👤 Riwayat Absensi Siswa

> **Akses:** Semua role (data sesuai akses masing-masing)

Lihat riwayat lengkap seorang siswa dalam 3 tampilan:

1. **Progress Bar** — persentase kehadiran (hijau jika > 75%)
2. **Calendar Heatmap** — kalender warna dengan kode:
   - 🟩 Hijau = Hadir
   - 🟨 Kuning = Terlambat
   - 🟦 Biru = Sakit / Izin
   - 🟥 Merah = Alfa
   - ⬜ Abu-abu = Tanpa keterangan / libur
3. **Grafik Tren** — line chart kehadiran per minggu
4. **Tabel Detail** — daftar tanggal + status lengkap

### Cara:
1. Buka menu **Riwayat Absensi** di sidebar
2. Cari siswa dengan kotak pencarian (ketik nama, otomatis muncul saran)
3. Pilih semester
4. Semua tampilan terisi otomatis

### Fitur tambahan:
- **Hapus Masal** — centang beberapa baris → klik Hapus
- **Export Excel** — unduh data tabel
- **Export PDF** — unduh laporan lengkap dengan grafik

---

## ⚙️ Manajemen Data (Admin)

Semua menu admin ada di sidebar bagian **Data** dan **Pengaturan**.

### Siswa
| Menu | Fungsi |
|---|---|
| Siswa → Daftar | Lihat, cari, edit, hapus siswa |
| Siswa → Tambah | Form tambah 1 siswa |
| Siswa → Import CSV | Import banyak siswa dari file Excel/CSV (lihat panduan khusus di bawah) |
| Siswa → Export | Download semua data siswa |
| Siswa → Kartu Siswa | Cetak barcode |

### Kelas
| Menu | Fungsi |
|---|---|
| Kelas → Daftar | Tambah/edit/hapus kelas |
| Kelas → Atur Guru | Assign guru dan wali kelas ke kelas + mapel |

### Atur Guru Kelas & Mapel
1. Buka menu **Guru Kelas** di sidebar
2. Pilih guru dari daftar
3. Centang kelas yang diampu guru tersebut
4. Untuk **guru mata pelajaran**: pilih mapel di dropdown samping kelas
5. Klik **Simpan**

### Pengguna (User)

Mengelola akun login:
- **Admin** — akses penuh semua fitur
- **Guru** — hanya bisa absen mapel + rekap mapel
- **Wali Kelas** — hanya bisa absen piket + rekap piket untuk kelasnya
- **Orang Tua** — hanya bisa lihat riwayat anaknya

### Konfigurasi Sekolah
- Nama sekolah, logo, warna tema (primary & sekunder)
- Warna tema dipakai di sidebar, tombol, dan aksen aplikasi

---

## 🎓 Kenaikan Kelas & Promosi

> **Akses:** Admin only

Setiap akhir tahun ajaran, ada 3 tahap:

### Tahap 1: Luluskan Kelas 12 (Kelulusan)

1. Buka menu **Kenaikan Kelas** di sidebar
2. Klik tab **Kelulusan**
3. Masukkan **Tahun Lulus**
4. Klik **Proses Kelulusan**
5. Semua siswa XII otomatis jadi **alumni** (status berubah, tingkat jadi NULL)

### Tahap 2: Promosi XI→XII dan X→XI (Promosi)

> **Fitur baru:** Promosi 1 langkah via Export→Excel→Import

Karena data naik kelas diatur manual oleh **Guru BK**, flow-nya:

1. Buka menu **Kenaikan Kelas** → **Promosi**
2. Pilih **X → XI** atau **XI → XII**
3. Klik **Export CSV** — download file berisi:
   - Data siswa lengkap (NIS, NISN, Nama, Kelas Asal, ID Kelas Asal)
   - **Kolom kosong**: Kelas Tujuan + ID Kelas Tujuan (untuk diisi BK)
   - Referensi daftar kelas tujuan di bagian bawah file
4. **BK edit file di Excel:**
   - Isi kolom **ID Kelas Tujuan** (nomor ID dari tabel referensi)
   - Simpan sebagai CSV
5. **Upload** file hasil editan BK → klik **Import & Promosikan**
6. Sistem otomatis:
   - ✅ Pindahkan siswa ke kelas baru
   - ✅ Update tingkat (X→11, XI→12)
   - ✅ Generate barcode untuk yang belum punya

**Contoh isian Excel:**
```
NIS;NISN;Nama;Kelas Asal;ID Asal;Kelas Tujuan;ID Tujuan
1234;0051234567;Adi Saputra;XI-1;21;XII-1;464
```

### Tahap 3: Import Siswa Baru Kelas 10

Gunakan menu **Siswa → Import CSV** — lihat panduan di bawah.

---

### Cara Lama (Alternatif): Kenaikan Manual

Jika tetap ingin pakai cara lama:

1. Buka menu **Kenaikan Kelas** → **Kenaikan**
2. Pilih **tingkat asal** dan **tingkat tujuan**
3. Mapping setiap kelas asal → kelas tujuan
4. Klik **Naikkan Kelas**

Cara ini juga update tingkat tapi **tidak** generate barcode. Direkomendasikan untuk situasi darurat/sederhana.

---

## 📥 Import Siswa Baru

> **Akses:** Admin only

Untuk import data dalam jumlah banyak sekaligus (kelas 10 baru, pindahan, dll).

### Format File CSV:

| Kolom | Nama | Wajib | Contoh |
|---|---|---|---|
| 1 | NIS | ✅ | 2526001 |
| 2 | NISN | ✅ | 0123456789 |
| 3 | Nama | ✅ | Adi Saputra |
| 4 | Kelas ID | ✅ | 1 (lihat tabel referensi) |
| 5 | Jenis Kelamin | ✅ | Laki-laki / Perempuan |

### Langkah-langkah:

1. Buka menu **Siswa** → **Import CSV**
2. Klik **Template Umum** atau **Template Khusus Kelas X** untuk download format
3. Isi data di Excel/LibreOffice — kolom 4 (Kelas ID) lihat tabel referensi di halaman
4. Simpan sebagai **CSV UTF-8**
5. Upload file → klik **Import Data**

### ✨ Yang otomatis dilakukan sistem:
- **Tingkat** terdeteksi otomatis dari nama kelas (X→10, XI→11, XII→12)
- **Barcode** otomatis digenerate dari NIS
- Data yang NIS sudah ada akan di-update (beserta tingkatnya)
- Siswa lama yang belum punya barcode akan dilengkapi

### Template Khusus Kelas X
Template ini sudah menyertakan daftar kelas X dan ID-nya di bagian bawah file. Cocok untuk import siswa baru PPDB.

---

## 👥 Role & Hak Akses

| Menu | Admin | Guru | Wali Kelas | Orang Tua |
|---|---|---|---|---|
| Dashboard | ✅ | ✅ | ✅ | ✅ |
| Absensi Piket | ✅ | — | ✅ | — |
| Scan Barcode | ✅ | — | — | — |
| Absen Mapel | ✅ | ✅ | — | — |
| Siswa (CRUD) | ✅ | — | — | — |
| Riwayat Siswa | ✅ | ✅ (mapel) | ✅ (piket) | ✅ (anak) |
| Kelas (CRUD) | ✅ | — | — | — |
| Rekap Piket | ✅ | — | ✅ | — |
| Rekap Mapel | ✅ | ✅ | — | — |
| User Management | ✅ | — | — | — |
| Mata Pelajaran | ✅ | — | — | — |
| Guru Kelas | ✅ | — | — | — |
| Tahun Ajaran | ✅ | — | — | — |
| Kenaikan Kelas | ✅ | — | — | — |
| Profil Sekolah | ✅ | — | — | — |

---

## ❓ FAQ

### Data absensi tidak muncul setelah pilih kelas?
Pastikan kelas sudah dipilih, tanggal sudah benar, dan ada siswa di kelas tersebut. Coba refresh halaman.

### Auto-save kok tidak jalan?
Fitur auto-save aktif secara default. Cek koneksi internet. Indikator "Menyimpan..." harus muncul saat klik status. Jika tidak, coba clear cache browser.

### Copy Kemarin tidak muncul?
Tombol muncul setelah kelas dan tanggal dipilih. Pastikan tanggal sebelumnya ada data absensi.

### Import CSV error "Data tidak valid"?
- Pastikan pakai separator **;** (titik koma), bukan koma
- Pastikan kolom Kelas ID diisi dengan **angka** (ID dari tabel referensi)
- Pastikan file ber-ekstensi .csv
- Cek tidak ada baris kosong di tengah data

### Barcode tidak terbaca kamera?
- Pastikan ruangan cukup terang
- Pastikan barcode dicetak dengan kontras tinggi (hitam di atas putih)
- Coba zoom kamera dengan mendekatkan/menjauhkan kartu
- Untuk sementara: bisa input manual via menu Absensi

### Guru tidak bisa melihat kelas/mapel?
Cek di menu **Guru Kelas** — pastikan guru sudah di-assign ke kelas dan mapel yang benar.

### Lupa password admin?
Minta admin lain untuk reset di menu Pengguna, atau reset via database langsung.

### Data absensi terhapus?
Hubungi admin. Data absensi bisa di-restore dari backup database.

---

© 2026 Sistem Absensi Siswa

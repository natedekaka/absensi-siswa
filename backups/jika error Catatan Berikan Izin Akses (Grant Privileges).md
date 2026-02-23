Fatal error: Uncaught mysqli_sql_exception: Access denied for user 'user'@'%' to database 'absensi_db3' in /var/www/html/absensi-siswa/config.php:9 Stack trace: #0 /var/www/html/absensi-siswa/config.php(9): mysqli->__construct('db', 'user', Object(SensitiveParameterValue), 'absensi_db3') #1 /var/www/html/absensi-siswa/proses_login.php(5): require('/var/www/html/a...') #2 {main} thrown in /var/www/html/absensi-siswa/config.php on line 9



Error `mysqli_sql_exception: Access denied` ini biasanya berarti koneksi PHP kamu berhasil mencapai server database, tetapi ditolak karena **hak akses (privileges)** yang tidak sesuai.

Masalahnya ada pada user `'user'@'%'` yang tidak memiliki izin untuk mengakses database bernama `absensi_db3`. Berikut adalah langkah-langkah solusinya:

------

### 1. Berikan Izin Akses (Grant Privileges)

Jika kamu menggunakan MySQL/MariaDB (misal via Terminal atau phpMyAdmin), kamu perlu memberikan izin secara eksplisit kepada user tersebut. Jalankan perintah SQL berikut sebagai **root**:

SQL

```
-- Memberikan semua akses ke database absensi_db3 untuk user tersebut
GRANT ALL PRIVILEGES ON absensi_db3.* TO 'user'@'%';

-- Refresh izin agar langsung berlaku
FLUSH PRIVILEGES;
```

> **Catatan:** Jika database `absensi_db3` belum ada, buat dulu dengan perintah: `CREATE DATABASE absensi_db3;`

### 2. Cek Nama Database di `config.php`

Pastikan tidak ada salah ketik pada baris 9 di file `config.php`. Berdasarkan error log kamu, PHP mencoba mengakses database: `absensi_db3`.

- Apakah nama databasenya benar-benar `absensi_db3`?
- Apakah mungkin seharusnya `absensi_db` atau nama lain?

### 3. Cek Host Database

Di stack trace terlihat kamu menggunakan `'db'` sebagai host (kemungkinan besar kamu menggunakan **Docker**).

- Pastikan user `'user'` sudah dibuat di container database tersebut.
- Pastikan user tersebut dibuat untuk host `%` (remote) atau host `db`, bukan cuma `localhost`.

### 4. Skema Hubungan Koneksi

Berikut adalah gambaran sederhana bagaimana PHP berkomunikasi dengan database dan di mana letak kegagalannya:

------


<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// Hanya admin yang bisa melakukan aksi ini
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

require_once '../config.php';

// Pastikan ada ID kelas yang dikirimkan
if (!isset($_GET['kelas_id']) || empty($_GET['kelas_id'])) {
    header("Location: index.php");
    exit;
}

$kelas_id = $_GET['kelas_id'];

// Mulai transaksi untuk memastikan semua operasi berhasil atau gagal bersamaan
$koneksi->begin_transaction();

try {
    // 1. Hapus semua data absensi terkait siswa di kelas ini
    $sql_absensi = "
        DELETE a FROM absensi a
        JOIN siswa s ON a.siswa_id = s.id
        WHERE s.kelas_id = ?
    ";
    $stmt_absensi = $koneksi->prepare($sql_absensi);
    $stmt_absensi->bind_param("i", $kelas_id);
    $stmt_absensi->execute();

    // 2. Hapus semua siswa dari kelas yang dipilih
    $sql_siswa = "DELETE FROM siswa WHERE kelas_id = ?";
    $stmt_siswa = $koneksi->prepare($sql_siswa);
    $stmt_siswa->bind_param("i", $kelas_id);
    $stmt_siswa->execute();

    // Commit transaksi jika semua query berhasil
    $koneksi->commit();
    header("Location: index.php?success=hapus_kelas");
    exit;

} catch (mysqli_sql_exception $exception) {
    // Rollback transaksi jika ada kesalahan
    $koneksi->rollback();
    // Redirect dengan pesan error
    header("Location: index.php?error=db_error");
    exit;
}

$koneksi->close();
?>
<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: ../dashboard/");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

if (!isset($_GET['kelas_id']) || empty($_GET['kelas_id'])) {
    header("Location: index.php");
    exit;
}

$kelas_id = $_GET['kelas_id'];
$koneksi = conn();

$koneksi->begin_transaction();

try {
    $sql_absensi = "DELETE a FROM absensi a JOIN siswa s ON a.siswa_id = s.id WHERE s.kelas_id = ?";
    $stmt_absensi = $koneksi->prepare($sql_absensi);
    $stmt_absensi->bind_param("i", $kelas_id);
    $stmt_absensi->execute();

    $sql_siswa = "DELETE FROM siswa WHERE kelas_id = ?";
    $stmt_siswa = $koneksi->prepare($sql_siswa);
    $stmt_siswa->bind_param("i", $kelas_id);
    $stmt_siswa->execute();

    $koneksi->commit();
    header("Location: index.php?success=1");
    exit;

} catch (Exception $e) {
    $koneksi->rollback();
    header("Location: index.php?error=1");
    exit;
}

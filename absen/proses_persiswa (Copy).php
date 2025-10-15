<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $siswa_id = $_POST['siswa_id'];
    $tanggal = $_POST['tanggal'];
    $status = $_POST['status'];

    // Cek apakah sudah ada data absensi
    $check = $koneksi->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ?");
    $check->bind_param("is", $siswa_id, $tanggal);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Update jika sudah ada
        $stmt = $koneksi->prepare("UPDATE absensi SET status = ? WHERE siswa_id = ? AND tanggal = ?");
        $stmt->bind_param("sis", $status, $siswa_id, $tanggal);
    } else {
        // Insert jika belum ada
        $stmt = $koneksi->prepare("INSERT INTO absensi (siswa_id, tanggal, status) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $siswa_id, $tanggal, $status);
    }

    $stmt->execute();
    header("Location: absensi_persiswa.php?success=1");
    exit;
}
?>
<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Hapus data absensi terkait siswa terlebih dahulu
    $koneksi->query("DELETE FROM absensi WHERE siswa_id = $id");
    
    // Hapus siswa
    $stmt = $koneksi->prepare("DELETE FROM siswa WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=1");
        exit;
    } else {
        die("Error: " . $stmt->error);
    }
} else {
    header("Location: index.php");
    exit;
}
?>

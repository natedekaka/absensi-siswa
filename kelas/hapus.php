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
    
    // Periksa apakah ada siswa di kelas ini
    $cek = $koneksi->query("SELECT COUNT(*) as total FROM siswa WHERE kelas_id = $id");
    $row = $cek->fetch_assoc();
    
    if ($row['total'] > 0) {
        // Jika ada siswa, tidak bisa dihapus
        session_start();
        $_SESSION['error'] = "Kelas tidak bisa dihapus karena masih memiliki siswa.";
        header("Location: index.php");
        exit;
    }
    
    // Hapus kelas
    $stmt = $koneksi->prepare("DELETE FROM kelas WHERE id = ?");
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

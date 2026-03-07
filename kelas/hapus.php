<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $cek = conn()->query("SELECT COUNT(*) as total FROM siswa WHERE kelas_id = $id");
    $row = $cek->fetch_assoc();
    
    if ($row['total'] > 0) {
        $_SESSION['error'] = "Kelas tidak bisa dihapus karena masih memiliki siswa.";
        header("Location: index.php");
        exit;
    }
    
    $stmt = conn()->prepare("DELETE FROM kelas WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Kelas berhasil dihapus!";
        header("Location: index.php");
        exit;
    } else {
        die("Error: " . $stmt->error);
    }
} else {
    header("Location: index.php");
    exit;
}

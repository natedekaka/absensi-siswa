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
    
    conn()->query("DELETE FROM absensi WHERE siswa_id = $id");
    
    $stmt = conn()->prepare("DELETE FROM siswa WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Siswa berhasil dihapus!";
        header("Location: index.php");
        exit;
    } else {
        die("Error: " . $stmt->error);
    }
} else {
    header("Location: index.php");
    exit;
}

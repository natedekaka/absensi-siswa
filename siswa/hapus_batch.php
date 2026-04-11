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

$delete_type = $_POST['delete_type'] ?? '';
$deleted = 0;

if ($delete_type === 'selected') {
    $siswa_ids = $_POST['siswa_ids'] ?? '';
    
    if (empty($siswa_ids)) {
        $_SESSION['error'] = "Tidak ada siswa yang dipilih";
        header("Location: index.php");
        exit;
    }
    
    $ids = array_filter(array_map('intval', explode(',', $siswa_ids)), function($id) {
        return $id > 0;
    });
    
    if (empty($ids)) {
        $_SESSION['error'] = "Tidak ada siswa yang dipilih";
        header("Location: index.php");
        exit;
    }
    
    $koneksi = conn();
    $koneksi->begin_transaction();
    
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        
        $stmt_absensi = $koneksi->prepare("DELETE FROM absensi WHERE siswa_id IN ($placeholders)");
        $stmt_absensi->bind_param($types, ...$ids);
        $stmt_absensi->execute();
        
        $stmt_siswa = $koneksi->prepare("DELETE FROM siswa WHERE id IN ($placeholders)");
        $stmt_siswa->bind_param($types, ...$ids);
        $stmt_siswa->execute();
        
        $deleted = $stmt_siswa->affected_rows;
        $koneksi->commit();
        
        $_SESSION['success'] = "Berhasil menghapus $deleted siswa";
    } catch (Exception $e) {
        $koneksi->rollback();
        $_SESSION['error'] = "Gagal menghapus siswa: " . $e->getMessage();
    }
    
} elseif ($delete_type === 'kelas') {
    $kelas_id = (int)$_POST['kelas_id'];
    
    if ($kelas_id <= 0) {
        $_SESSION['error'] = "Kelas tidak valid";
        header("Location: index.php");
        exit;
    }
    
    $koneksi = conn();
    $koneksi->begin_transaction();
    
    try {
        $stmt_absensi = $koneksi->prepare("DELETE a FROM absensi a JOIN siswa s ON a.siswa_id = s.id WHERE s.kelas_id = ?");
        $stmt_absensi->bind_param("i", $kelas_id);
        $stmt_absensi->execute();
        
        $stmt_siswa = $koneksi->prepare("DELETE FROM siswa WHERE kelas_id = ?");
        $stmt_siswa->bind_param("i", $kelas_id);
        $stmt_siswa->execute();
        
        $deleted = $stmt_siswa->affected_rows;
        $koneksi->commit();
        
        $_SESSION['success'] = "Berhasil menghapus $deleted siswa di kelas tersebut";
    } catch (Exception $e) {
        $koneksi->rollback();
        $_SESSION['error'] = "Gagal menghapus siswa: " . $e->getMessage();
    }
    
} elseif ($delete_type === 'all') {
    $koneksi = conn();
    $koneksi->begin_transaction();
    
    try {
        $koneksi->query("DELETE FROM absensi WHERE siswa_id IN (SELECT id FROM siswa WHERE status = 'aktif' OR status IS NULL)");
        $koneksi->query("DELETE FROM siswa WHERE status = 'aktif' OR status IS NULL");
        
        $deleted = $koneksi->affected_rows;
        $koneksi->commit();
        
        $_SESSION['success'] = "Berhasil menghapus semua siswa ($deleted siswa)";
    } catch (Exception $e) {
        $koneksi->rollback();
        $_SESSION['error'] = "Gagal menghapus siswa: " . $e->getMessage();
    }
    
} else {
    $_SESSION['error'] = "Jenis penghapusan tidak valid";
}

header("Location: index.php");
exit;

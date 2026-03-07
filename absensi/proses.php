<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/Database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];
    $semester_id = $_POST['semester_id'];
    $statuses = $_POST['status'] ?? [];
    
    $saved = 0;
    foreach ($statuses as $siswa_id => $status) {
        $check = conn()->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ? AND semester_id = ?");
        $check->bind_param("isi", $siswa_id, $tanggal, $semester_id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $stmt = conn()->prepare("UPDATE absensi SET status = ?, semester_id = ? WHERE siswa_id = ? AND tanggal = ?");
            $stmt->bind_param("siis", $status, $semester_id, $siswa_id, $tanggal);
        } else {
            $stmt = conn()->prepare("INSERT INTO absensi (siswa_id, tanggal, status, semester_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $siswa_id, $tanggal, $status, $semester_id);
        }
        
        $stmt->execute();
        $saved++;
    }
    
    $_SESSION['success'] = "Berhasil menyimpan $saved absensi!";
    header("Location: index.php");
    exit;
}

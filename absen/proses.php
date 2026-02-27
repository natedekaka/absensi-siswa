<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];
    $semester_id = $_POST['semester_id'];
    $statuses = $_POST['status'];
    
    foreach ($statuses as $siswa_id => $status) {
        // Cek apakah sudah ada absensi untuk siswa di tanggal ini berdasarkan semester
        $check = $koneksi->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ? AND semester_id = ?");
        $check->bind_param("isi", $siswa_id, $tanggal, $semester_id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            // Update jika sudah ada
            $stmt = $koneksi->prepare("UPDATE absensi SET status = ?, semester_id = ? WHERE siswa_id = ? AND tanggal = ?");
            $stmt->bind_param("siis", $status, $semester_id, $siswa_id, $tanggal);
        } else {
            // Insert jika belum ada
            $stmt = $koneksi->prepare("INSERT INTO absensi (siswa_id, tanggal, status, semester_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $siswa_id, $tanggal, $status, $semester_id);
        }
        
        $stmt->execute();
    }
    
    header("Location: index.php?success=1");
    exit;
}
?>

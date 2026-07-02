<?php
session_start();
require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/Database.php';

if (!has_role('admin', 'guru', 'wali_kelas')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$semester_id = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : 0;
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$type = isset($_GET['type']) ? $_GET['type'] : 'piket';
$mapel_id = isset($_GET['mapel_id']) ? (int)$_GET['mapel_id'] : 0;
$user_id = (int)($_SESSION['user']['id'] ?? 0);

// Get yesterday (or last weekday with data)
$kemarin = date('Y-m-d', strtotime($tanggal . ' -1 day'));

// Check if yesterday has data, otherwise try the day before (skip weekends)
$tgl_coba = $kemarin;
$data = [];
$found = false;

for ($i = 0; $i < 7; $i++) {
    $hari = date('N', strtotime($tgl_coba)); // 1=Monday, 7=Sunday
    // Skip Sunday
    if ($hari == 7) {
        $tgl_coba = date('Y-m-d', strtotime($tgl_coba . ' -1 day'));
        continue;
    }
    
    if ($type === 'mapel' && $mapel_id > 0) {
        $q = conn()->prepare("
            SELECT siswa_id, status FROM absensi_mapel 
            WHERE tanggal = ? AND semester_id = ? AND kelas_id = ? AND mapel_id = ? AND user_id = ?
        ");
        $q->bind_param("siiii", $tgl_coba, $semester_id, $kelas_id, $mapel_id, $user_id);
    } else {
        $q = conn()->prepare("
            SELECT siswa_id, status FROM absensi 
            WHERE tanggal = ? AND semester_id = ?
        ");
        $q->bind_param("si", $tgl_coba, $semester_id);
    }
    
    $q->execute();
    $result = $q->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[$row['siswa_id']] = $row['status'];
        }
        $found = true;
        break;
    }
    
    $tgl_coba = date('Y-m-d', strtotime($tgl_coba . ' -1 day'));
}

echo json_encode([
    'success' => $found,
    'data' => $data,
    'tanggal_sumber' => $found ? $tgl_coba : null,
    'message' => $found ? 'Data dari ' . $tgl_coba : 'Tidak ada data absensi sebelumnya dalam 7 hari terakhir'
]);

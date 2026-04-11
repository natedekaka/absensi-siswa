<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/Database.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'No input received']);
        exit;
    }
    
    $csrf_token = $input['csrf_token'] ?? '';
    $tanggal = $input['tanggal'] ?? date('Y-m-d');
    $semester_id = (int)($input['semester_id'] ?? 0);
    $statuses = $input['status'] ?? [];
    
    if (!verify_csrf($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid!']);
        exit;
    }
    
    if (!$semester_id) {
        echo json_encode(['success' => false, 'message' => 'Semester harus dipilih!']);
        exit;
    }
    
    $semester = conn()->query("SELECT * FROM semester WHERE id = $semester_id")->fetch_assoc();
    if (!$semester) {
        echo json_encode(['success' => false, 'message' => 'Semester tidak ditemukan!']);
        exit;
    }
    
    $tgl_mulai = $semester['tgl_mulai'];
    $tgl_selesai = $semester['tgl_selesai'];
    
    if ($tanggal < $tgl_mulai || $tanggal > $tgl_selesai) {
        echo json_encode(['success' => false, 'message' => 'Tanggal ' . date('d M Y', strtotime($tanggal)) . ' tidak sesuai dengan periode semester ' . $semester['nama'] . ' (' . date('d M Y', strtotime($tgl_mulai)) . ' - ' . date('d M Y', strtotime($tgl_selesai)) . ')!']);
        exit;
    }
    
    if (empty($statuses)) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada data absensi untuk disimpan!']);
        exit;
    }
    
    $saved = 0;
    foreach ($statuses as $siswa_id => $status) {
        $siswa_id = (int)$siswa_id;
        if ($siswa_id <= 0) continue;
        $status = in_array($status, ['Hadir', 'Sakit', 'Izin', 'Alfa', 'Terlambat']) ? $status : 'Hadir';
        
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
    
    echo json_encode(['success' => true, 'message' => "Berhasil menyimpan $saved absensi!"]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

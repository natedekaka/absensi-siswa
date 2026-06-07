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

$user_id = (int)($_SESSION['user']['id'] ?? 0);

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'No input received']);
        exit;
    }
    
    $csrf_token = $input['csrf_token'] ?? '';
    $tanggal = $input['tanggal'] ?? date('Y-m-d');
    $semester_id = (int)($input['semester_id'] ?? 0);
    $kelas_id = (int)($input['kelas_id'] ?? 0);
    $mapel_id = (int)($input['mapel_id'] ?? 0);
    $statuses = $input['status'] ?? [];
    
    if (!verify_csrf($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid!']);
        exit;
    }
    
    if (!$semester_id) {
        echo json_encode(['success' => false, 'message' => 'Semester harus dipilih!']);
        exit;
    }
    
    if (!$kelas_id) {
        echo json_encode(['success' => false, 'message' => 'Kelas harus dipilih!']);
        exit;
    }

    if (!$mapel_id) {
        echo json_encode(['success' => false, 'message' => 'Mata pelajaran harus dipilih!']);
        exit;
    }

    // Verify teacher is assigned to this class + mapel (unless admin)
    if (!has_role('admin')) {
        $check = conn()->prepare("SELECT 1 FROM guru_kelas WHERE user_id = ? AND kelas_id = ? AND mapel_id = ?");
        $check->bind_param("iii", $user_id, $kelas_id, $mapel_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak terdaftar sebagai pengajar mapel ini di kelas ini!']);
            exit;
        }
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
    $stmt = conn()->prepare("
        INSERT INTO absensi_mapel (siswa_id, user_id, kelas_id, mapel_id, tanggal, status, semester_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), semester_id = VALUES(semester_id)
    ");
    
    foreach ($statuses as $siswa_id => $status) {
        $siswa_id = (int)$siswa_id;
        if ($siswa_id <= 0) continue;
        $status = in_array($status, ['Hadir', 'Sakit', 'Izin', 'Alfa', 'Terlambat']) ? $status : 'Hadir';
        
        $stmt->bind_param("iiiissi", $siswa_id, $user_id, $kelas_id, $mapel_id, $tanggal, $status, $semester_id);
        $stmt->execute();
        $saved++;
    }
    
    echo json_encode(['success' => true, 'message' => "Berhasil menyimpan $saved absensi mapel!"]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

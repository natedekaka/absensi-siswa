<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();

$base_path = __DIR__ . '/../';
require_once $base_path . 'core/init.php';
require_once $base_path . 'core/Database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $siswa_id = (int)($_POST['siswa_id'] ?? 0);
    $barcode = isset($_POST['barcode']) ? $_POST['barcode'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'hadir';

    if (empty($siswa_id) || empty($barcode) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }

    $valid_status = ['hadir', 'sakit', 'izin', 'alfa'];
    if (!in_array($status, $valid_status)) {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        exit;
    }

    $conn = conn();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
        exit;
    }

    $tanggal = date('Y-m-d');

    // Get any semester
    $semester = $conn->query("SELECT id FROM semester WHERE is_active = 1 LIMIT 1");
    if (!$semester || $semester->num_rows === 0) {
        $semester = $conn->query("SELECT id FROM semester ORDER BY id DESC LIMIT 1");
    }
    $semesterId = null;
    if ($semester && $semester->num_rows > 0) {
        $semesterData = $semester->fetch_assoc();
        $semesterId = $semesterData['id'];
    }

    // Convert status to uppercase
    $status_map = [
        'hadir' => 'Hadir',
        'sakit' => 'Sakit', 
        'izin' => 'Izin',
        'alfa' => 'Alfa'
    ];
    $status_final = $status_map[$status] ?? 'Hadir';

    // Check if already exists
    $checkSql = "SELECT id FROM absensi WHERE siswa_id = " . (int)$siswa_id . " AND tanggal = '$tanggal'";
    if ($semesterId) {
        $checkSql .= " AND semester_id = " . (int)$semesterId;
    } else {
        $checkSql .= " AND semester_id IS NULL";
    }
    $checkResult = $conn->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Siswa sudah melakukan absensi hari ini']);
        exit;
    }

    // Insert new attendance (without created_at if column doesn't exist)
    $semesterPart = $semesterId ? (int)$semesterId : "NULL";
    $insertSql = "INSERT INTO absensi (siswa_id, semester_id, tanggal, status) VALUES (" . 
        (int)$siswa_id . ", $semesterPart, '$tanggal', '$status_final')";

    if ($conn->query($insertSql)) {
        echo json_encode(['success' => true, 'message' => 'Absensi berhasil disimpan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan absensi: ' . $conn->error]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

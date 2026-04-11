<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();

$base_path = __DIR__ . '/../';
require_once $base_path . 'core/init.php';
require_once $base_path . 'core/Database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

$barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';

if (empty($barcode)) {
    echo json_encode(['success' => false, 'message' => 'Barcode tidak boleh kosong']);
    exit;
}

try {
    $conn = conn();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
        exit;
    }

    // Check if barcode column exists, if not use nis
    $columnCheck = $conn->query("SHOW COLUMNS FROM siswa LIKE 'barcode'");
    $barcodeColumn = $columnCheck && $columnCheck->num_rows > 0 ? 'barcode' : 'nis';

    // Search by barcode or nis or nisn
    $searchTerm = $conn->real_escape_string($barcode);
    $result = $conn->query("
        SELECT s.*, k.nama_kelas as kelas_nama 
        FROM siswa s 
        LEFT JOIN kelas k ON s.kelas_id = k.id 
        WHERE s.{$barcodeColumn} = '$searchTerm' OR s.nis = '$searchTerm' OR s.nisn = '$searchTerm'
        LIMIT 1
    ");

    if (!$result || $result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Siswa tidak ditemukan. Periksa kembali barcode/NIS yang diinput.']);
        exit;
    }

    $siswa = $result->fetch_assoc();

    // Check if already absented today
    $today = date('Y-m-d');

    // Get semester
    $semester = $conn->query("SELECT id FROM semester WHERE is_active = 1 LIMIT 1");
    if (!$semester || $semester->num_rows === 0) {
        $semester = $conn->query("SELECT id FROM semester ORDER BY id DESC LIMIT 1");
    }
    $semesterId = null;
    if ($semester && $semester->num_rows > 0) {
        $semesterData = $semester->fetch_assoc();
        $semesterId = $semesterData['id'];
    }

    $absenCheckSql = "SELECT * FROM absensi WHERE siswa_id = " . (int)$siswa['id'] . " AND tanggal = '$today'";
    if ($semesterId) {
        $absenCheckSql .= " AND semester_id = " . (int)$semesterId;
    } else {
        $absenCheckSql .= " AND semester_id IS NULL";
    }
    $absenResult = $conn->query($absenCheckSql);

    $sudah_absen = false;
    $absensi = null;
    $status_display = '';
    if ($absenResult && $absenResult->num_rows > 0) {
        $sudah_absen = true;
        $absensi = $absenResult->fetch_assoc();
        $status_map = [
            'Hadir' => 'Hadir',
            'Sakit' => 'Sakit',
            'Izin' => 'Izin',
            'Alfa' => 'Alfa',
            'Terlambat' => 'Terlambat'
        ];
        $status_display = $status_map[$absensi['status']] ?? $absensi['status'];
    }

    echo json_encode([
        'success' => true,
        'siswa' => [
            'id' => $siswa['id'],
            'nama' => $siswa['nama'],
            'nis' => $siswa['nis'],
            'nisn' => $siswa['nisn'],
            'kelas_id' => $siswa['kelas_id'],
            'kelas_nama' => $siswa['kelas_nama'],
            'jenis_kelamin' => $siswa['jenis_kelamin']
        ],
        'sudah_absen' => $sudah_absen,
        'absensi' => $absensi,
        'status_display' => $status_display
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

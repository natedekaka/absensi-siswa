<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: riwayat.php');
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
$siswa_id = isset($_POST['siswa_id']) ? (int)$_POST['siswa_id'] : 0;
$tgl_awal = $_POST['tgl_awal'] ?? '';
$tgl_akhir = $_POST['tgl_akhir'] ?? '';
$ids = $_POST['ids'] ?? [];

if (!verify_csrf($csrf_token)) {
    $_SESSION['error'] = 'Token keamanan tidak valid!';
    header("Location: riwayat.php?siswa_id=$siswa_id&tgl_awal=$tgl_awal&tgl_akhir=$tgl_akhir");
    exit;
}

if (empty($ids) || $siswa_id <= 0) {
    $_SESSION['error'] = 'Tidak ada data yang dipilih!';
    header("Location: riwayat.php?siswa_id=$siswa_id&tgl_awal=$tgl_awal&tgl_akhir=$tgl_akhir");
    exit;
}

$deleted = 0;
foreach ($ids as $id) {
    $id = (int)$id;
    if ($id <= 0) continue;
    
    $stmt = conn()->prepare("DELETE FROM absensi WHERE id = ? AND siswa_id = ?");
    $stmt->bind_param("ii", $id, $siswa_id);
    if ($stmt->execute()) {
        $deleted++;
    }
}

$_SESSION['success'] = "Berhasil menghapus $deleted data absensi!";
header("Location: riwayat.php?siswa_id=$siswa_id&tgl_awal=$tgl_awal&tgl_akhir=$tgl_akhir");
exit;

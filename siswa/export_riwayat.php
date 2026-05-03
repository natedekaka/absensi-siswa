<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    die('Unauthorized');
}

require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/Database.php';

$siswa_id = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : 0;
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');
$format = $_GET['format'] ?? 'excel';

if ($siswa_id <= 0) {
    die('Siswa tidak valid');
}

$siswa = conn()->query("SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.id = $siswa_id")->fetch_assoc();
if (!$siswa) {
    die('Siswa tidak ditemukan');
}

$semester_aktif = conn()->query("SELECT id FROM semester WHERE is_active = 1 LIMIT 1")->fetch_assoc();
$semester_id = $semester_aktif['id'] ?? null;
$where_semester = $semester_id ? "AND semester_id = " . (int)$semester_id : "";

$absensi = conn()->query("
    SELECT * FROM absensi 
    WHERE siswa_id = $siswa_id AND tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir' $where_semester
    ORDER BY tanggal ASC
");

$days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];

if ($format === 'excel') {
    exportExcel($siswa, $absensi, $tgl_awal, $tgl_akhir, $days);
} elseif ($format === 'pdf') {
    exportPDF($siswa, $absensi, $tgl_awal, $tgl_akhir, $days);
} else {
    die('Format tidak valid');
}

function exportExcel($siswa, $absensi, $tgl_awal, $tgl_akhir, $days) {
    $filename = 'Riwayat_Absensi_' . preg_replace('/[^a-zA-Z0-9]/', '_', $siswa['nama']) . '_' . date('Ymd') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    fputcsv($output, ['Riwayat Absensi Siswa']);
    fputcsv($output, ['Nama', $siswa['nama']]);
    fputcsv($output, ['Kelas', $siswa['nama_kelas'] ?? 'Tidak ada kelas']);
    fputcsv($output, ['Periode', date('d/m/Y', strtotime($tgl_awal)) . ' - ' . date('d/m/Y', strtotime($tgl_akhir))]);
    fputcsv($output, []);
    
    fputcsv($output, ['No', 'Tanggal', 'Hari', 'Status']);
    
    $no = 1;
    while ($row = $absensi->fetch_assoc()) {
        $hari = $days[date('l', strtotime($row['tanggal']))] ?? '';
        fputcsv($output, [$no++, date('d/m/Y', strtotime($row['tanggal'])), $hari, $row['status']]);
    }
    
    fclose($output);
    exit;
}

function exportPDF($siswa, $absensi, $tgl_awal, $tgl_akhir, $days) {
    $filename = 'Riwayat_Absensi_' . preg_replace('/[^a-zA-Z0-9]/', '_', $siswa['nama']) . '_' . date('Ymd') . '.html';
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Riwayat Absensi - ' . htmlspecialchars($siswa['nama']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; border-bottom: 2px solid #3B82F6; padding-bottom: 10px; }
            .info { margin: 20px 0; }
            .info-item { margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #3B82F6; color: white; padding: 10px; text-align: left; }
            td { padding: 8px; border-bottom: 1px solid #ddd; }
            .hadir { color: #10b981; font-weight: bold; }
            .terlambat { color: #f59e0b; font-weight: bold; }
            .sakit, .izin { color: #3b82f6; font-weight: bold; }
            .alfa { color: #ef4444; font-weight: bold; }
            @media print {
                body { margin: 0; }
                button { display: none; }
            }
        </style>
    </head>
    <body>
        <button onclick="window.print()" style="padding: 10px 20px; background: #3B82F6; color: white; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 20px;">
            Print / Save as PDF
        </button>
        
        <h1>Riwayat Absensi Siswa</h1>
        
        <div class="info">
            <div class="info-item"><strong>Nama:</strong> ' . htmlspecialchars($siswa['nama']) . '</div>
            <div class="info-item"><strong>Kelas:</strong> ' . htmlspecialchars($siswa['nama_kelas'] ?? 'Tidak ada kelas') . '</div>
            <div class="info-item"><strong>Periode:</strong> ' . date('d/m/Y', strtotime($tgl_awal)) . ' - ' . date('d/m/Y', strtotime($tgl_akhir)) . '</div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Hari</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';
    
    $no = 1;
    $absensi->data_seek(0);
    while ($row = $absensi->fetch_assoc()) {
        $hari = $days[date('l', strtotime($row['tanggal']))] ?? '';
        $status_class = strtolower($row['status']);
        $html .= '
                <tr>
                    <td>' . $no++ . '</td>
                    <td>' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>
                    <td>' . $hari . '</td>
                    <td class="' . $status_class . '">' . htmlspecialchars($row['status']) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <script>
            // Auto-trigger print dialog after 500ms
            setTimeout(function() {
                window.print();
            }, 500);
        </script>
    </body>
    </html>';
    
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

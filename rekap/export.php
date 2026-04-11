<?php
session_start();
if (!isset($_SESSION['user'])) {
    die('Akses ditolak.');
}

require_once '../core/init.php';
require_once '../core/Database.php';

$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');
$type = $_GET['type'] ?? 'pdf';

$tgl_awal = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_awal) ? $tgl_awal : date('Y-m-01');
$tgl_akhir = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_akhir) ? $tgl_akhir : date('Y-m-t');

$tahun_ajaran_aktif = conn()->query("SELECT id FROM tahun_ajaran WHERE is_active = 1")->fetch_assoc();
$ta_id = $tahun_ajaran_aktif['id'] ?? 0;

if ($ta_id > 0) {
    $semester = conn()->query("SELECT * FROM semester WHERE semester IN (1, 2) AND tahun_ajaran_id = $ta_id ORDER BY semester ASC");
} else {
    $semester = conn()->query("SELECT * FROM semester WHERE semester IN (1, 2) ORDER BY tahun_ajaran_id DESC, semester ASC LIMIT 2");
}
$semester_dates = [];
while ($s = $semester->fetch_assoc()) {
    $semester_dates[$s['semester']] = $s;
}

if (!$kelas_id) {
    die('Kelas harus dipilih.');
}

$stmt = conn()->prepare("SELECT nama_kelas, wali_kelas FROM kelas WHERE id = ?");
$stmt->bind_param("i", $kelas_id);
$stmt->execute();
$kelas = $stmt->get_result()->fetch_assoc();

if (!$kelas) {
    die('Kelas tidak ditemukan.');
}

function getDateRangeForSemester($semester_num, $semester_dates, $tgl_awal, $tgl_akhir) {
    if (!isset($semester_dates[$semester_num])) {
        return null;
    }
    $s = $semester_dates[$semester_num];
    $smt_mulai = $s['tgl_mulai'];
    $smt_selesai = $s['tgl_selesai'];
    
    $range_awal = max($tgl_awal, $smt_mulai);
    $range_akhir = min($tgl_akhir, $smt_selesai);
    
    if ($range_awal > $range_akhir) {
        return null;
    }
    
    return ['awal' => $range_awal, 'akhir' => $range_akhir, 'id' => $s['id']];
}

function getSiswaDataByDateRange($kelas_id, $tgl_awal, $tgl_akhir, $semester_id) {
    $stmt = conn()->prepare("
        SELECT s.id, s.nama, s.nis, s.jenis_kelamin,
            COALESCE(SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END), 0) as hadir,
            COALESCE(SUM(CASE WHEN a.status = 'Terlambat' THEN 1 ELSE 0 END), 0) as terlambat,
            COALESCE(SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END), 0) as sakit,
            COALESCE(SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END), 0) as izin,
            COALESCE(SUM(CASE WHEN a.status = 'Alfa' THEN 1 ELSE 0 END), 0) as alfa
        FROM siswa s
        LEFT JOIN absensi a ON s.id = a.siswa_id 
            AND a.tanggal BETWEEN ? AND ? AND a.semester_id = ?
        WHERE s.kelas_id = ? AND (s.status = 'aktif' OR s.status IS NULL)
        GROUP BY s.id, s.nama, s.nis, s.jenis_kelamin
        ORDER BY s.nama ASC
    ");
    $stmt->bind_param("ssii", $tgl_awal, $tgl_akhir, $semester_id, $kelas_id);
    $stmt->execute();
    return $stmt->get_result();
}

$smt1_range = getDateRangeForSemester(1, $semester_dates, $tgl_awal, $tgl_akhir);
$smt2_range = getDateRangeForSemester(2, $semester_dates, $tgl_awal, $tgl_akhir);

$siswa_smt1 = $smt1_range ? getSiswaDataByDateRange($kelas_id, $smt1_range['awal'], $smt1_range['akhir'], $smt1_range['id']) : null;
$siswa_smt2 = $smt2_range ? getSiswaDataByDateRange($kelas_id, $smt2_range['awal'], $smt2_range['akhir'], $smt2_range['id']) : null;

$data_smt1 = [];
if ($siswa_smt1 && $siswa_smt1->num_rows > 0) {
    while ($row = $siswa_smt1->fetch_assoc()) {
        $data_smt1[$row['id']] = $row;
    }
}

$data_smt2 = [];
if ($siswa_smt2 && $siswa_smt2->num_rows > 0) {
    while ($row = $siswa_smt2->fetch_assoc()) {
        $data_smt2[$row['id']] = $row;
    }
}

$all_siswa_stmt = conn()->prepare("SELECT id, nis, nama, jenis_kelamin FROM siswa WHERE kelas_id = ? AND (status = 'aktif' OR status IS NULL) ORDER BY nama ASC");
$all_siswa_stmt->bind_param("i", $kelas_id);
$all_siswa_stmt->execute();
$all_siswa = $all_siswa_stmt->get_result();

$total_hari = (strtotime($tgl_akhir) - strtotime($tgl_awal)) / (60*60*24) + 1;
$hari_smt1 = $smt1_range ? (strtotime($smt1_range['akhir']) - strtotime($smt1_range['awal'])) / (60*60*24) + 1 : 0;
$hari_smt2 = $smt2_range ? (strtotime($smt2_range['akhir']) - strtotime($smt2_range['awal'])) / (60*60*24) + 1 : 0;

if ($type === 'excel' || $type === 'xlsx') {
    $filename = 'rekap_absensi_' . str_replace(' ', '_', $kelas['nama_kelas']) . '_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['REKAP ABSENSI SISWA - SEMESTER 1 & 2'], ';');
    fputcsv($output, ['Kelas: ' . $kelas['nama_kelas']], ';');
    fputcsv($output, ['Periode: ' . date('d/m/Y', strtotime($tgl_awal)) . ' - ' . date('d/m/Y', strtotime($tgl_akhir))], ';');
    fputcsv($output, [], ';');
    
    $header1 = ['', '', '', '', 'SEMESTER 1', '', '', '', '', '', 'SEMESTER 2', '', '', '', '', ''];
    fputcsv($output, $header1, ';');
    
    $header2 = ['No', 'NIS', 'Nama Siswa', 'JK', 'Hadir', 'Telat', 'Sakit', 'Izin', 'Alfa', '%', 'Hadir', 'Telat', 'Sakit', 'Izin', 'Alfa', '%'];
    fputcsv($output, $header2, ';');
    
    $no = 1;
    while ($row = $all_siswa->fetch_assoc()) {
        $d1 = isset($data_smt1[$row['id']]) ? $data_smt1[$row['id']] : ['hadir'=>0,'terlambat'=>0,'sakit'=>0,'izin'=>0,'alfa'=>0];
        $d2 = isset($data_smt2[$row['id']]) ? $data_smt2[$row['id']] : ['hadir'=>0,'terlambat'=>0,'sakit'=>0,'izin'=>0,'alfa'=>0];
        
        $persen1 = $hari_smt1 > 0 ? round(($d1['hadir'] / $hari_smt1) * 100, 1) : 0;
        $persen2 = $hari_smt2 > 0 ? round(($d2['hadir'] / $hari_smt2) * 100, 1) : 0;
        
        fputcsv($output, [
            $no,
            $row['nis'],
            $row['nama'],
            substr($row['jenis_kelamin'], 0, 1),
            $d1['hadir'],
            $d1['terlambat'],
            $d1['sakit'],
            $d1['izin'],
            $d1['alfa'],
            $persen1 . '%',
            $d2['hadir'],
            $d2['terlambat'],
            $d2['sakit'],
            $d2['izin'],
            $d2['alfa'],
            $persen2 . '%'
        ], ';');
        $no++;
    }
    
    fclose($output);
    exit;
}

if ($type === 'pdf') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi - <?= htmlspecialchars($kelas['nama_kelas']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin-bottom: 5px; }
        .header p { font-size: 12px; color: #666; margin: 0; }
        table { font-size: 10px; width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: center; }
        th { background-color: #128C7E; color: white; }
        .smt-header { background-color: #25D366; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn-print { position: fixed; top: 20px; right: 20px; }
    </style>
</head>
<body>
    <div class="no-print btn-print">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak / Save as PDF
        </button>
        <a href="?kelas_id=<?= $kelas_id ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&type=excel" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>
    
    <div class="header">
        <h1>LAPORAN REKAP ABSENSI SISWA</h1>
        <p><strong>Kelas: <?= htmlspecialchars($kelas['nama_kelas']) ?></strong> | Wali Kelas: <?= htmlspecialchars($kelas['wali_kelas'] ?? '-') ?></p>
        <p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="vertical-align: middle;">No</th>
                <th rowspan="2" style="vertical-align: middle;">NIS</th>
                <th rowspan="2" style="vertical-align: middle;">Nama Siswa</th>
                <th rowspan="2" style="vertical-align: middle;">JK</th>
                <th colspan="5" class="smt-header">SEMESTER 1 (<?= $hari_smt1 ?> hari)</th>
                <th colspan="5" class="smt-header">SEMESTER 2 (<?= $hari_smt2 ?> hari)</th>
            </tr>
            <tr>
                <th>H</th><th>T</th><th>S</th><th>I</th><th>A</th><th>%</th>
                <th>H</th><th>T</th><th>S</th><th>I</th><th>A</th><th>%</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $all_siswa->data_seek(0);
            while ($row = $all_siswa->fetch_assoc()):
                $d1 = isset($data_smt1[$row['id']]) ? $data_smt1[$row['id']] : ['hadir'=>0,'terlambat'=>0,'sakit'=>0,'izin'=>0,'alfa'=>0];
                $d2 = isset($data_smt2[$row['id']]) ? $data_smt2[$row['id']] : ['hadir'=>0,'terlambat'=>0,'sakit'=>0,'izin'=>0,'alfa'=>0];
                
                $persen1 = $hari_smt1 > 0 ? round(($d1['hadir'] / $hari_smt1) * 100, 1) : 0;
                $persen2 = $hari_smt2 > 0 ? round(($d2['hadir'] / $hari_smt2) * 100, 1) : 0;
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nis']) ?></td>
                <td style="text-align: left;"><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= substr($row['jenis_kelamin'], 0, 1) ?></td>
                <td><?= $d1['hadir'] ?></td>
                <td><?= $d1['terlambat'] ?></td>
                <td><?= $d1['sakit'] ?></td>
                <td><?= $d1['izin'] ?></td>
                <td><?= $d1['alfa'] ?></td>
                <td><strong><?= $persen1 ?>%</strong></td>
                <td><?= $d2['hadir'] ?></td>
                <td><?= $d2['terlambat'] ?></td>
                <td><?= $d2['sakit'] ?></td>
                <td><?= $d2['izin'] ?></td>
                <td><?= $d2['alfa'] ?></td>
                <td><strong><?= $persen2 ?>%</strong></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 20px; text-align: right; font-size: 11px; color: #666;">
        Dicetak: <?= date('d/m/Y H:i') ?>
    </div>
</body>
</html>
    <?php
    exit;
}

die('Jenis ekspor tidak valid.');

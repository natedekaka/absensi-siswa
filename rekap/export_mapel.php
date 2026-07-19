<?php
session_start();
require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/Database.php';

if (!has_role('admin', 'guru', 'wali_kelas')) {
    die('Akses ditolak.');
}

$user_id = (int)($_SESSION['user']['id'] ?? 0);

$guru_id = isset($_GET['guru_id']) ? (int)$_GET['guru_id'] : 0;
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$mapel_id = isset($_GET['mapel_id']) ? (int)$_GET['mapel_id'] : 0;
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');
$type = $_GET['type'] ?? 'pdf';

$tgl_awal = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_awal) ? $tgl_awal : date('Y-m-01');
$tgl_akhir = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_akhir) ? $tgl_akhir : date('Y-m-t');

// Non-admin only sees their own data
if (!has_role('admin')) {
    $guru_id = $user_id;
}

function getRekapMapel($guru_id, $kelas_id, $mapel_id, $tgl_awal, $tgl_akhir) {
    $where = " WHERE a.tanggal BETWEEN ? AND ?";
    $params = [$tgl_awal, $tgl_akhir];
    $types = "ss";

    if ($guru_id > 0) {
        $where .= " AND a.user_id = ?";
        $params[] = $guru_id;
        $types .= "i";
    }
    if ($kelas_id > 0) {
        $where .= " AND a.kelas_id = ?";
        $params[] = $kelas_id;
        $types .= "i";
    }
    if ($mapel_id > 0) {
        $where .= " AND a.mapel_id = ?";
        $params[] = $mapel_id;
        $types .= "i";
    }

    $stmt = conn()->prepare("
        SELECT 
            u.nama AS nama_guru,
            k.nama_kelas,
            mp.nama_mapel,
            COUNT(DISTINCT a.tanggal) AS total_hari
        FROM absensi_mapel a
        JOIN users u ON a.user_id = u.id
        JOIN kelas k ON a.kelas_id = k.id
        JOIN mapel mp ON a.mapel_id = mp.id
        $where
        GROUP BY a.user_id, a.kelas_id, a.mapel_id, u.nama, k.nama_kelas, mp.nama_mapel
        ORDER BY u.nama, k.nama_kelas, mp.nama_mapel
    ");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

function getRekapMapelSiswa($guru_id, $kelas_id, $mapel_id, $tgl_awal, $tgl_akhir) {
    $where = " WHERE a.tanggal BETWEEN ? AND ?";
    $params = [$tgl_awal, $tgl_akhir];
    $types = "ss";

    if ($guru_id > 0) {
        $where .= " AND a.user_id = ?";
        $params[] = $guru_id;
        $types .= "i";
    }
    if ($kelas_id > 0) {
        $where .= " AND a.kelas_id = ?";
        $params[] = $kelas_id;
        $types .= "i";
    }
    if ($mapel_id > 0) {
        $where .= " AND a.mapel_id = ?";
        $params[] = $mapel_id;
        $types .= "i";
    }

    $stmt = conn()->prepare("
        SELECT 
            s.nis,
            s.nama AS nama_siswa,
            COUNT(DISTINCT a.tanggal) AS total_pertemuan,
            COUNT(*) AS total_tercatat,
            SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN a.status = 'Terlambat' THEN 1 ELSE 0 END) AS terlambat,
            SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) AS izin,
            SUM(CASE WHEN a.status = 'Alfa' THEN 1 ELSE 0 END) AS alfa
        FROM absensi_mapel a
        JOIN siswa s ON a.siswa_id = s.id
        $where
        GROUP BY a.siswa_id, s.nis, s.nama
        ORDER BY s.nama
    ");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

$show_siswa = ($kelas_id > 0);
$rekap = null;
$rekap_siswa = null;
if ($show_siswa) {
    $rekap_siswa = getRekapMapelSiswa($guru_id, $kelas_id, $mapel_id, $tgl_awal, $tgl_akhir);
} else {
    $rekap = getRekapMapel($guru_id, $kelas_id, $mapel_id, $tgl_awal, $tgl_akhir);
}

// ======================== EXCEL (CSV) ========================
if ($type === 'excel' || $type === 'xlsx') {
    $filename = 'rekap_absensi_mapel_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

    fputcsv($output, ['REKAP ABSENSI MATA PELAJARAN'], ';');
    fputcsv($output, ['Periode: ' . date('d/m/Y', strtotime($tgl_awal)) . ' - ' . date('d/m/Y', strtotime($tgl_akhir))], ';');
    fputcsv($output, [], ';');

    if ($show_siswa && $rekap_siswa && $rekap_siswa->num_rows > 0) {
        // Per-siswa export
        fputcsv($output, ['No', 'NIS', 'Nama Siswa', 'Total Pertemuan', 'Hadir', 'Telat', 'Sakit', 'Izin', 'Alfa', '% Hadir'], ';');
        $no = 1;
        while ($row = $rekap_siswa->fetch_assoc()) {
            $persen = $row['total_tercatat'] > 0 ? round(($row['hadir'] / $row['total_tercatat']) * 100, 1) : 0;
            fputcsv($output, [
                $no++, $row['nis'], $row['nama_siswa'],
                $row['total_pertemuan'], $row['hadir'], $row['terlambat'], $row['sakit'],
                $row['izin'], $row['alfa'], $persen . '%'
            ], ';');
        }
    } elseif ($rekap && $rekap->num_rows > 0) {
        // Summary export
        fputcsv($output, ['No', 'Kelas', 'Mapel', 'Total Hari'], ';');
        $no = 1;
        while ($row = $rekap->fetch_assoc()) {
            fputcsv($output, [
                $no++, $row['nama_kelas'], $row['nama_mapel'],
                $row['total_hari']
            ], ';');
        }
    }

    fclose($output);
    exit;
}

// ======================== PRINT / PDF ========================
$sekolah = getKonfigurasiSekolah(conn());
$sekolah_nama = $sekolah['nama_sekolah'] ?? 'LAPORAN REKAP ABSENSI MAPEL';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi Mapel</title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin: 0 0 5px; }
        .header p { font-size: 12px; color: #666; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #333; padding: 5px 6px; text-align: center; }
        th { background-color: #7C3AED; color: white; font-weight: 600; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn-bar { position: fixed; top: 20px; right: 20px; display: flex; gap: 8px; }
        .btn { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; }
        .btn-print { background: #7C3AED; color: white; }
        .btn-excel { background: #10B981; color: white; }
        .footer { margin-top: 20px; text-align: right; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <div class="no-print btn-bar">
        <button class="btn btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak / PDF
        </button>
        <a href="export_mapel.php?guru_id=<?= $guru_id ?>&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&type=excel" class="btn btn-excel" style="text-decoration:none;">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>

    <div class="header">
        <h1><?= htmlspecialchars($sekolah_nama) ?></h1>
        <p><strong>REKAP ABSENSI MATA PELAJARAN</strong></p>
        <p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
        <?php if ($kelas_id): ?>
            <?php $kelas = conn()->query("SELECT nama_kelas FROM kelas WHERE id = $kelas_id")->fetch_assoc(); ?>
            <p>Kelas: <?= htmlspecialchars($kelas['nama_kelas'] ?? '') ?></p>
        <?php endif; ?>
        <?php if ($mapel_id): ?>
            <?php $mapel = conn()->query("SELECT nama_mapel FROM mapel WHERE id = $mapel_id")->fetch_assoc(); ?>
            <p>Mapel: <?= htmlspecialchars($mapel['nama_mapel'] ?? '') ?></p>
        <?php endif; ?>
        <hr style="border:1px solid #000;margin:10px 0;">
    </div>

    <?php if ($show_siswa && $rekap_siswa && $rekap_siswa->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Total Pertemuan</th>
                <th>Hadir</th>
                <th>Telat</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alfa</th>
                <th>% Hadir</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while ($row = $rekap_siswa->fetch_assoc()):
                $persen = $row['total_tercatat'] > 0 ? round(($row['hadir'] / $row['total_tercatat']) * 100, 1) : 0;
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nis']) ?></td>
                <td style="text-align:left;"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                <td><?= $row['total_pertemuan'] ?></td>
                <td style="font-weight:600;color:#059669;"><?= $row['hadir'] ?></td>
                <td style="color:#d97706;"><?= $row['terlambat'] ?></td>
                <td style="color:#6b7280;"><?= $row['sakit'] ?></td>
                <td style="color:#2563eb;"><?= $row['izin'] ?></td>
                <td style="color:#dc2626;"><?= $row['alfa'] ?></td>
                <td><strong><?= $persen ?>%</strong></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php elseif ($rekap && $rekap->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kelas</th>
                <th>Mapel</th>
                <th>Total Hari</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while ($row = $rekap->fetch_assoc()): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                <td><?= htmlspecialchars($row['nama_mapel']) ?></td>
                <td><?= $row['total_hari'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="text-align:center;padding:20px;color:#999;">Tidak ada data absensi mapel untuk periode ini.</p>
    <?php endif; ?>

    <div class="footer">
        Dicetak: <?= date('d/m/Y H:i') ?>
    </div>
</body>
</html>
<?php
exit;

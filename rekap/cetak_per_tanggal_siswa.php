<?php
session_start();

// Cek login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';

// Ambil parameter dari URL
$kelas_id = filter_input(INPUT_GET, 'kelas_id', FILTER_SANITIZE_STRING);
$periode = filter_input(INPUT_GET, 'periode', FILTER_SANITIZE_STRING);
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_SANITIZE_STRING);

if ($periode == 'bulan') {
    $bulan = filter_input(INPUT_GET, 'bulan', FILTER_SANITIZE_STRING);
    $tahun = filter_input(INPUT_GET, 'tahun', FILTER_SANITIZE_STRING);
    $tgl_awal = "$tahun-$bulan-01";
    $tgl_akhir = date('Y-m-t', strtotime($tgl_awal));
} else {
    $tgl_awal = filter_input(INPUT_GET, 'tgl_awal', FILTER_SANITIZE_STRING);
    $tgl_akhir = filter_input(INPUT_GET, 'tgl_akhir', FILTER_SANITIZE_STRING);
}

// Validasi parameter
if (!$kelas_id || !strtotime($tgl_awal) || !strtotime($tgl_akhir)) {
    echo "<p>Tanggal atau kelas tidak valid.</p>";
    exit;
}
if (strtotime($tgl_awal) > strtotime($tgl_akhir)) {
    echo "<p>Tanggal awal tidak boleh lebih besar dari tanggal akhir.</p>";
    exit;
}

$semua_kelas = ($kelas_id === 'all');
$nama_kelas = '';
if (!$semua_kelas) {
    $stmt_kelas = $koneksi->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt_kelas->bind_param("i", $kelas_id);
    $stmt_kelas->execute();
    $stmt_kelas->bind_result($nama_kelas);
    $stmt_kelas->fetch();
    $stmt_kelas->close();
}

// Ambil daftar tanggal dalam periode
$dates = [];
$current_date = strtotime($tgl_awal);
$end_date = strtotime($tgl_akhir);
while ($current_date <= $end_date) {
    $dates[] = date('Y-m-d', $current_date);
    $current_date = strtotime('+1 day', $current_date);
}

// Ambil data siswa
if ($semua_kelas) {
    $stmt_siswa = $koneksi->prepare("
        SELECT s.id, s.nama, s.jenis_kelamin, k.nama_kelas 
        FROM siswa s 
        JOIN kelas k ON s.kelas_id = k.id 
        ORDER BY k.nama_kelas, s.nama
    ");
} else {
    $stmt_siswa = $koneksi->prepare("SELECT id, nama, jenis_kelamin FROM siswa WHERE kelas_id = ? ORDER BY nama");
    $stmt_siswa->bind_param("i", $kelas_id);
}
$stmt_siswa->execute();
$result_siswa = $stmt_siswa->get_result();

$data_siswa = [];
$rekap_total = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];

// Kumpulkan data absensi per siswa
while ($row = $result_siswa->fetch_assoc()) {
    $siswa_id = $row['id'];
    $absensi = array_fill_keys($dates, '-');
    $rekap = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];

    $stmt_absen = $koneksi->prepare("
        SELECT tanggal, status 
        FROM absensi 
        WHERE siswa_id = ? AND tanggal BETWEEN ? AND ?
    ");
    $stmt_absen->bind_param("iss", $siswa_id, $tgl_awal, $tgl_akhir);
    $stmt_absen->execute();
    $result_absen = $stmt_absen->get_result();

    while ($absen = $result_absen->fetch_assoc()) {
        $tanggal = $absen['tanggal'];
        $status = $absen['status'];
        if (in_array($tanggal, $dates)) {
            $absensi[$tanggal] = substr($status, 0, 1);
            $rekap[$status]++;
            $rekap_total[$status]++;
        }
    }
    $stmt_absen->close();

    $data_siswa[] = [
        'id' => $siswa_id,
        'nama' => htmlspecialchars($row['nama']),
        'kelas' => $semua_kelas ? htmlspecialchars($row['nama_kelas']) : '',
        'jk' => htmlspecialchars($row['jenis_kelamin']),
        'absensi' => $absensi,
        'hadir' => $rekap['Hadir'],
        'terlambat' => $rekap['Terlambat'],
        'sakit' => $rekap['Sakit'],
        'izin' => $rekap['Izin'],
        'alfa' => $rekap['Alfa'],
        'total' => array_sum($rekap)
    ];
}
$stmt_siswa->close();

// Urutkan data
$sort_field = match($sort_by) {
    'hadir' => 'hadir',
    'terlambat' => 'terlambat',
    'sakit' => 'sakit',
    'izin' => 'izin',
    'alfa' => 'alfa',
    default => 'nama'
};
usort($data_siswa, function($a, $b) use ($sort_field) {
    if ($sort_field == 'nama') {
        return strcmp($a['nama'], $b['nama']);
    }
    return $b[$sort_field] <=> $a[$sort_field];
});
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekap Absensi Siswa Per Tanggal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
        }
        .table th, .table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 12px;
        }
        .table th {
            background-color: #e9ecef;
        }
        .table th.rotate {
            height: 120px;
            white-space: nowrap;
        }
        .table th.rotate > div {
            transform: rotate(-45deg);
            width: 30px;
        }
        .header-img {
            max-width: 100px;
        }
        @page {
            size: landscape;
            margin: 1cm;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="text-center mb-4">
            <h2>Rekap Absensi Siswa Per Tanggal</h2>
            <h4><?= $semua_kelas ? "Semua Kelas" : "Kelas: " . htmlspecialchars($nama_kelas) ?></h4>
            <p><strong>Periode:</strong> <?= date('d M Y', strtotime($tgl_awal)) ?> s.d. <?= date('d M Y', strtotime($tgl_akhir)) ?></p>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <?php if ($semua_kelas): ?>
                        <th rowspan="2">Kelas</th>
                    <?php endif; ?>
                    <th rowspan="2">Nama Siswa</th>
                    <th rowspan="2">Jenis Kelamin</th>
                    <th colspan="<?= count($dates) ?>">Tanggal</th>
                    <th rowspan="2">Hadir</th>
                    <th rowspan="2">Terlambat</th>
                    <th rowspan="2">Sakit</th>
                    <th rowspan="2">Izin</th>
                    <th rowspan="2">Alfa</th>
                    <th rowspan="2">Total</th>
                </tr>
                <tr>
                    <?php foreach ($dates as $date): ?>
                        <th class="rotate"><div><?= date('d M', strtotime($date)) ?></div></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($data_siswa as $d): ?>
                    <tr>
                        <td><?= $no ?></td>
                        <?php if ($semua_kelas): ?>
                            <td><?= $d['kelas'] ?></td>
                        <?php endif; ?>
                        <td style="text-align: left;"><?= $d['nama'] ?></td>
                        <td><?= $d['jk'] ?></td>
                        <?php foreach ($dates as $date): ?>
                            <td><?= $d['absensi'][$date] ?></td>
                        <?php endforeach; ?>
                        <td><?= $d['hadir'] ?></td>
                        <td><?= $d['terlambat'] ?></td>
                        <td><?= $d['sakit'] ?></td>
                        <td><?= $d['izin'] ?></td>
                        <td><?= $d['alfa'] ?></td>
                        <td><strong><?= $d['total'] ?></strong></td>
                    </tr>
                    <?php $no++; endforeach; ?>
                <tr class="fw-bold">
                    <td></td>
                    <?php if ($semua_kelas): ?>
                        <td></td>
                    <?php endif; ?>
                    <td>Total</td>
                    <td></td>
                    <?php foreach ($dates as $date): ?>
                        <?php
                        $total_per_tanggal = 0;
                        foreach ($data_siswa as $d) {
                            if ($d['absensi'][$date] != '-') $total_per_tanggal++;
                        }
                        ?>
                        <td><?= $total_per_tanggal ?></td>
                    <?php endforeach; ?>
                    <td><?= $rekap_total['Hadir'] ?></td>
                    <td><?= $rekap_total['Terlambat'] ?></td>
                    <td><?= $rekap_total['Sakit'] ?></td>
                    <td><?= $rekap_total['Izin'] ?></td>
                    <td><?= $rekap_total['Alfa'] ?></td>
                    <td><strong><?= array_sum($rekap_total) ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div class="no-print mt-3">
            <a href="javascript:window.print()" class="btn btn-primary">Cetak</a>
            <a href="rekap_per_tanggal_siswa.php?<?= $params ?>" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</body>
</html>
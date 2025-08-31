<?php
session_start();

// Cek login (opsional: bisa dihapus jika ingin bisa dicetak tanpa login)
// Tapi jika ingin aman, tetap cek
if (!isset($_SESSION['user'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

require_once '../config.php';

// Ambil parameter dari URL
$kelas_id = $_GET['kelas_id'] ?? '';
$tgl_awal = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';
$sort_by = $_GET['sort_by'] ?? '';

// Validasi input
if (empty($kelas_id) || empty($tgl_awal) || empty($tgl_akhir)) {
    die("<h3 class='text-danger'>Parameter tidak lengkap.</h3>");
}

if (!strtotime($tgl_awal) || !strtotime($tgl_akhir)) {
    die("<h3 class='text-danger'>Tanggal tidak valid.</h3>");
}

if (strtotime($tgl_awal) > strtotime($tgl_akhir)) {
    die("<h3 class='text-danger'>Tanggal awal tidak boleh lebih besar dari tanggal akhir.</h3>");
}

// Inisialisasi rekap
$rekapKelas = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];
$semua_kelas = ($kelas_id === 'all');
$nama_kelas = '';

// Ambil nama kelas jika bukan "semua kelas"
if (!$semua_kelas) {
    $stmt_kelas = $koneksi->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt_kelas->bind_param("i", $kelas_id);
    $stmt_kelas->execute();
    $stmt_kelas->bind_result($nama_kelas);
    $stmt_kelas->fetch();
    $stmt_kelas->close();
}

// Judul laporan
$judul_laporan = $semua_kelas ? "Rekap Semua Kelas" : "Rekap Kelas: " . htmlspecialchars($nama_kelas);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekap Absensi</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { -webkit-print-color-adjust: exact; }
            @page { margin: 1cm; }
            .table th, .table td { padding: 0.5rem !important; font-size: 12px; }
            .table { font-size: 12px; }
            .text-center { text-align: center; }
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .info {
            margin-bottom: 15px;
        }
        .table th {
            background-color: #f0f0f0 !important;
            color: #000 !important;
            text-align: center;
        }
        .table tbody td {
            vertical-align: middle;
            text-align: center;
        }
        .fw-bold {
            font-weight: bold;
        }
    </style>
    <script>
        function cetak() {
            window.print();
        }
    </script>
</head>
<body onload="cetak()">
    <div class="container-fluid">
        <!-- Tombol cetak hanya muncul di layar -->
        <div class="text-end mb-3 no-print">
            <button onclick="cetak()" class="btn btn-primary">🖨️ Cetak</button>
            <a href="javascript:window.history.back()" class="btn btn-secondary">‹ Kembali</a>
        </div>

        <!-- Kop Laporan -->
        <div class="header">
            <h3><strong>ABSENSI SISWA</strong></h3>
            <h4><?= $judul_laporan ?></h4>
            <p><strong>Periode:</strong> <?= date('d M Y', strtotime($tgl_awal)) ?> s.d. <?= date('d M Y', strtotime($tgl_akhir)) ?></p>
        </div>

        <?php
        // Ambil data siswa
        if ($semua_kelas) {
            $stmt_siswa = $koneksi->prepare("
                SELECT s.id, s.nama, s.jenis_kelamin, k.nama_kelas 
                FROM siswa s 
                JOIN kelas k ON s.kelas_id = k.id 
                ORDER BY k.nama_kelas, s.nama
            ");
        } else {
            $stmt_siswa = $koneksi->prepare("SELECT id, nama, jenis_kelamin FROM siswa WHERE kelas_id = ?");
            $stmt_siswa->bind_param("i", $kelas_id);
        }

        $stmt_siswa->execute();
        $result_siswa = $stmt_siswa->get_result();
        $data_siswa = [];

        // Mapping sort_by ke field array
        $sort_field = match($sort_by) {
            'hadir' => 'hadir',
            'terlambat' => 'terlambat',
            'sakit' => 'sakit',
            'izin' => 'izin',
            'alfa' => 'alfa',
            default => null
        };

        // Kumpulkan data absensi
        while ($row = $result_siswa->fetch_assoc()) {
            $siswa_id = $row['id'];
            $rekap = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];

            $stmt_absen = $koneksi->prepare("
                SELECT status FROM absensi 
                WHERE siswa_id = ? 
                  AND tanggal BETWEEN ? AND ?
            ");
            $stmt_absen->bind_param("iss", $siswa_id, $tgl_awal, $tgl_akhir);
            $stmt_absen->execute();
            $result_absen = $stmt_absen->get_result();

            while ($absen = $result_absen->fetch_assoc()) {
                $status = $absen['status'];
                if (isset($rekap[$status])) {
                    $rekap[$status]++;
                    $rekapKelas[$status]++;
                }
            }

            $data_siswa[] = [
                'nama' => htmlspecialchars($row['nama']),
                'kelas' => $semua_kelas ? htmlspecialchars($row['nama_kelas']) : '',
                'jk' => htmlspecialchars($row['jenis_kelamin']),
                'hadir' => $rekap['Hadir'],
                'terlambat' => $rekap['Terlambat'],
                'sakit' => $rekap['Sakit'],
                'izin' => $rekap['Izin'],
                'alfa' => $rekap['Alfa'],
                'total' => array_sum($rekap)
            ];

            $stmt_absen->close();
        }
        $stmt_siswa->close();

        // Urutkan data jika diperlukan
        if ($sort_field) {
            usort($data_siswa, function($a, $b) use ($sort_field) {
                return $b[$sort_field] <=> $a[$sort_field]; // descending
            });
        }
        ?>

        <!-- Tabel Rekap -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <?php if ($semua_kelas): ?>
                        <th>Kelas</th>
                    <?php endif; ?>
                    <th>Nama Siswa</th>
                    <th>Jenis Kelamin</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Sakit</th>
                    <th>Izin</th>
                    <th>Alfa</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; ?>
                <?php foreach ($data_siswa as $d): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <?php if ($semua_kelas): ?>
                            <td><?= $d['kelas'] ?></td>
                        <?php endif; ?>
                        <td><?= $d['nama'] ?></td>
                        <td><?= $d['jk'] ?></td>
                        <td><?= $d['hadir'] ?></td>
                        <td><?= $d['terlambat'] ?></td>
                        <td><?= $d['sakit'] ?></td>
                        <td><?= $d['izin'] ?></td>
                        <td><?= $d['alfa'] ?></td>
                        <td><strong><?= $d['total'] ?></strong></td>
                    </tr>
                <?php endforeach; ?>

                <!-- Baris Total -->
                <tr class="fw-bold">
                    <td></td>
                    <?php if ($semua_kelas): ?>
                        <td></td>
                    <?php endif; ?>
                    <td>Total</td>
                    <td></td>
                    <td><?= $rekapKelas['Hadir'] ?></td>
                    <td><?= $rekapKelas['Terlambat'] ?></td>
                    <td><?= $rekapKelas['Sakit'] ?></td>
                    <td><?= $rekapKelas['Izin'] ?></td>
                    <td><?= $rekapKelas['Alfa'] ?></td>
                    <td><strong><?= array_sum($rekapKelas) ?></strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Ringkasan Statistik -->
        <div class="row mt-4">
            <div class="col-12">
                <h5>Ringkasan Kehadiran:</h5>
                <ul>
                    <li><strong>Hadir:</strong> <?= $rekapKelas['Hadir'] ?></li>
                    <li><strong>Terlambat:</strong> <?= $rekapKelas['Terlambat'] ?></li>
                    <li><strong>Sakit:</strong> <?= $rekapKelas['Sakit'] ?></li>
                    <li><strong>Izin:</strong> <?= $rekapKelas['Izin'] ?></li>
                    <li><strong>Alfa:</strong> <?= $rekapKelas['Alfa'] ?></li>
                </ul>
            </div>
        </div>

        <div class="text-center text-muted mt-5">
            <p>Dicetak pada: <?= date('d M Y H:i') ?> | Oleh: <?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Admin') ?></p>
        </div>
    </div>

    <!-- Optional: Bootstrap JS jika ingin interaksi (tapi tidak perlu untuk cetak) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
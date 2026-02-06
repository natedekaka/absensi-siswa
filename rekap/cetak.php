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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS UNTUK TAMPILAN LAYAR (PREVIEW) */
        body {
            background-color: #f4f7f9;
            font-family: Arial, sans-serif;
        }
        .container-fluid {
            max-width: 21cm; /* Simulasi lebar kertas A4 */
            background-color: #fff;
            padding: 1.5cm;
            margin: 20px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            min-height: 29.7cm;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .table th {
            background-color: #343a40 !important;
            color: white !important;
            text-align: center;
        }

        /* CSS KHUSUS UNTUK CETAK (PRINT) */
        @media print {
            .no-print { display: none !important; } /* Sembunyikan tombol cetak */
            
            body { 
                background-color: #fff !important;
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important;
                margin: 0;
            }
            
            .container-fluid {
                width: 100% !important;
                max-width: none !important;
                padding: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
            }

            @page { 
                size: A4;
                margin: 1.5cm; /* Jarak aman pinggir kertas */
            }

            /* Perbaikan Garis Tabel agar muncul saat diprint */
            .table { 
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10pt; 
            }
            .table-bordered th, .table-bordered td {
                border: 1px solid #000 !important; /* Garis hitam pekat */
                padding: 6px !important;
            }
            
            /* HEADER TABEL: Muncul lagi di setiap halaman baru jika data panjang */
            thead { display: table-header-group; } 
            
            /* CEGAH POTONGAN: Mencegah satu baris terbelah di dua halaman */
            tr { page-break-inside: avoid; }
            
            .summary-box {
                border: 1px solid #000 !important;
                background-color: #f7f7f7 !important;
                page-break-inside: avoid; /* Kotak ringkasan tidak terpotong */
            }
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
        <div class="text-end mb-4 no-print">
            <button onclick="cetak()" class="btn btn-primary shadow-sm me-2"><i class="fas fa-print me-1"></i> Cetak</button>
            <a href="javascript:window.history.back()" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
        </div>

        <div class="header">
            <h3 class="mb-0"><strong>LAPORAN ABSENSI SISWA</strong></h3>
            <h4 class="mt-2 mb-1"><?= $judul_laporan ?></h4>
            <p class="m-0">Periode: <?= date('d F Y', strtotime($tgl_awal)) ?> s.d. <?= date('d F Y', strtotime($tgl_akhir)) ?></p>
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

        <table class="table table-bordered table-striped mt-4">
            <thead>
                <tr>
                    <th rowspan="2" class="align-middle">No</th>
                    <?php if ($semua_kelas): ?>
                        <th rowspan="2" class="align-middle">Kelas</th>
                    <?php endif; ?>
                    <th rowspan="2" class="align-middle">Nama Siswa</th>
                    <th rowspan="2" class="align-middle">J/K</th>
                    <th colspan="5" class="text-center">Status Kehadiran</th>
                    <th rowspan="2" class="align-middle">Total</th>
                </tr>
                <tr>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Sakit</th>
                    <th>Izin</th>
                    <th>Alfa</th>
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
                        <td class="text-start"><?= $d['nama'] ?></td>
                        <td><?= $d['jk'] ?></td>
                        <td><?= $d['hadir'] ?></td>
                        <td><?= $d['terlambat'] ?></td>
                        <td><?= $d['sakit'] ?></td>
                        <td><?= $d['izin'] ?></td>
                        <td><?= $d['alfa'] ?></td>
                        <td class="fw-bold"><?= $d['total'] ?></td>
                    </tr>
                <?php endforeach; ?>

                <tr class="fw-bold table-dark">
                    <td></td>
                    <?php if ($semua_kelas): ?>
                        <td></td>
                    <?php endif; ?>
                    <td class="text-end">Total</td>
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

        <div class="row mt-5">
            <div class="col-12 summary-box">
                <h5 class="mb-3">Ringkasan Kehadiran Keseluruhan:</h5>
                <ul class="summary-list row">
                    <li class="col-6 col-md-4"><strong>Hadir:</strong> <?= $rekapKelas['Hadir'] ?></li>
                    <li class="col-6 col-md-4"><strong>Terlambat:</strong> <?= $rekapKelas['Terlambat'] ?></li>
                    <li class="col-6 col-md-4"><strong>Sakit:</strong> <?= $rekapKelas['Sakit'] ?></li>
                    <li class="col-6 col-md-4"><strong>Izin:</strong> <?= $rekapKelas['Izin'] ?></li>
                    <li class="col-6 col-md-4"><strong>Alfa:</strong> <?= $rekapKelas['Alfa'] ?></li>
                </ul>
            </div>
        </div>

        <div class="text-center text-muted mt-5">
            <hr>
            <p class="m-0">Dicetak pada: <?= date('d M Y H:i') ?> | Oleh: <?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Admin') ?></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
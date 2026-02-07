<?php
session_start();
// Cek login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';

// Ambil parameter dari URL
$kelas_id = filter_input(INPUT_GET, 'kelas_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$periode = filter_input(INPUT_GET, 'periode', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if ($periode == 'bulan') {
    $bulan = filter_input(INPUT_GET, 'bulan', FILTER_SANITIZE_NUMBER_INT);
    $tahun = filter_input(INPUT_GET, 'tahun', FILTER_SANITIZE_NUMBER_INT);
    $tgl_awal = "$tahun-$bulan-01";
    $tgl_akhir = date('Y-m-t', strtotime($tgl_awal));
} else {
    $tgl_awal = filter_input(INPUT_GET, 'tgl_awal', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $tgl_akhir = filter_input(INPUT_GET, 'tgl_akhir', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
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
    $kelas_id_int = (int)$kelas_id;
    $stmt_kelas = $koneksi->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt_kelas->bind_param("i", $kelas_id_int);
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
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #000;
        }

        .container {
            max-width: 100%;
            padding: 0;
        }

        /* ========== PRINT STYLES - A4/F4 Landscape Presisi ========== */
        @media print {
            body {
                background-color: #fff;
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .print-container {
                width: 100%;
                min-height: 100vh;
                margin: 0;
                padding: 0;
                font-size: 11px;
            }

            /* Header Kop Surat */
            .kop-surat {
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
                margin-bottom: 15px;
                text-align: center;
            }

            .kop-surat h1 {
                margin: 0;
                font-size: 18pt;
                font-weight: bold;
                line-height: 1.2;
            }

            .kop-surat h2 {
                margin: 5px 0;
                font-size: 14pt;
                font-weight: bold;
                line-height: 1.2;
            }

            .kop-surat p {
                margin: 3px 0;
                font-size: 11pt;
                line-height: 1.2;
            }

            .kop-surat .sekolah {
                font-size: 14pt;
                font-weight: bold;
                margin-bottom: 5px;
            }

            .kop-surat .alamat {
                font-size: 11pt;
                margin-bottom: 5px;
            }

            .kop-surat .nomor {
                font-size: 11pt;
                margin: 0;
            }

            /* Tabel Print Optimasi */
            .table-print {
                width: 100%;
                border-collapse: collapse;
                font-size: 10pt;
                table-layout: fixed;
            }

            .table-print th,
            .table-print td {
                border: 1px solid #000;
                padding: 4px 2px;
                text-align: center;
                vertical-align: middle;
                word-wrap: break-word;
                overflow: hidden;
            }

            .table-print thead th {
                background-color: #f2f2f2;
                font-weight: bold;
                padding: 5px 2px;
                font-size: 10pt;
            }

            .table-print th.rotate {
                height: 70px;
                white-space: nowrap;
                padding: 0;
                width: 28px;
            }

            .table-print th.rotate > div {
                transform: rotate(-45deg);
                width: 70px;
                margin-left: -15px;
                margin-top: 15px;
                font-weight: normal;
                font-size: 9pt;
            }

            .table-print td.no {
                width: 25px;
                font-weight: bold;
            }

            /* PERUBAHAN: Meningkatkan lebar kolom nama menjadi 220px */
            .table-print td.nama {
                text-align: left;
                font-weight: bold;
                width: 220px;
                padding-left: 8px;
            }

            .table-print td.jk {
                width: 25px;
            }

            .table-print td.kelas {
                width: 60px;
            }

            .table-print td.absensi {
                width: 22px;
                font-weight: normal;
                font-size: 9pt;
            }

            .table-print td.rekap {
                width: 30px;
                font-weight: bold;
                font-size: 10pt;
            }

            .table-print tbody tr:nth-child(even) {
                background-color: #f9f9f9;
            }

            .table-print tfoot td {
                background-color: #e9e9e9;
                font-weight: bold;
                border: 2px solid #000 !important;
            }

            .table-print tfoot th {
                background-color: #e9e9e9;
                font-weight: bold;
                border: 2px solid #000 !important;
            }

            /* Header total di bagian atas */
            .header-total {
                background-color: #f2f2f2;
                font-weight: bold;
                border: 1px solid #000;
                padding: 3px;
                font-size: 10pt;
            }

            /* Footer */
            .footer {
                margin-top: 40px;
                text-align: right;
                font-size: 11pt;
                page-break-before: always;
            }

            .ttd-box {
                width: 250px;
                margin-left: auto;
                text-align: center;
            }

            .ttd-box p {
                margin: 30px 0 5px 0;
                font-size: 12pt;
            }

            .ttd-box .ttd-label {
                font-weight: bold;
                font-size: 11pt;
            }
        }

        /* ========== SCREEN STYLES ========== */
        @media screen {
            .action-buttons {
                margin: 20px 0;
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .preview-note {
                background-color: #fff3cd;
                border: 1px solid #ffc107;
                padding: 15px;
                margin: 20px 0;
                border-radius: 5px;
            }

            .alert {
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Tombol Aksi - Hanya di Layar -->
        <div class="action-buttons no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Cetak
            </button>
            <button onclick="exportPDF()" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </button>
            <a href="rekap_per_tanggal_siswa.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- Preview Note -->
        <div class="preview-note no-print">
            <strong>Catatan:</strong> Untuk hasil cetak yang presisi di kertas A4/F4 landscape:
            <ol>
                <li>Klik tombol "Cetak"</li>
                <li>Pilih ukuran kertas: <strong>A4</strong> atau <strong>F4</strong></li>
                <li>Atur orientasi: <strong>Landscape</strong></li>
                <li>Margin: <strong>Default</strong> atau <strong>Minimum</strong></li>
                <li>Scaling: <strong>100%</strong> (jangan pilih "Fit to page")</li>
            </ol>
        </div>

        <!-- Konten Print -->
        <div class="print-container">
            <!-- Kop Surat -->
            <div class="kop-surat">
                <div class="sekolah">SMA NEGERI 6 CIMAH</div>
                <div class="alamat">Jalan Melong Raya No.172</div>
                <div class="nomor">Nomor: 081/SP/02.02.04/2026</div>
                <h1>REKAP ABSENSI SISWA PER TANGGAL</h1>
                <h2><?= $semua_kelas ? "SEMUA KELAS" : "KELAS: " . strtoupper(htmlspecialchars($nama_kelas)) ?></h2>
                <p><strong>Periode:</strong> <?= date('d F Y', strtotime($tgl_awal)) ?> s.d. <?= date('d F Y', strtotime($tgl_akhir)) ?></p>
                <p><strong>Jumlah Hari:</strong> <?= count($dates) ?> hari</p>
            </div>

            <!-- Tabel Absensi -->
            <table class="table-print">
                <thead>
                    <tr>
                        <th rowspan="2" class="no">No</th>
                        <?php if ($semua_kelas): ?>
                            <th rowspan="2" class="kelas">Kelas</th>
                        <?php endif; ?>
                        <th rowspan="2" class="nama">Nama Siswa</th>
                        <th rowspan="2" class="jk">JK</th>
                        <th colspan="<?= count($dates) ?>">Tanggal</th>
                        <th rowspan="2" class="rekap">H</th>
                        <th rowspan="2" class="rekap">T</th>
                        <th rowspan="2" class="rekap">S</th>
                        <th rowspan="2" class="rekap">I</th>
                        <th rowspan="2" class="rekap">A</th>
                        <th rowspan="2" class="rekap">Jml</th>
                    </tr>
                    <tr>
                        <?php foreach ($dates as $date): ?>
                            <th class="rotate absensi">
                                <div><?= date('d', strtotime($date)) ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($data_siswa as $d): ?>
                        <tr>
                            <td class="no"><?= $no ?></td>
                            <?php if ($semua_kelas): ?>
                                <td class="kelas"><?= $d['kelas'] ?></td>
                            <?php endif; ?>
                            <td class="nama"><?= $d['nama'] ?></td>
                            <td class="jk"><?= strtoupper(substr($d['jk'], 0, 1)) ?></td>
                            <?php foreach ($dates as $date): ?>
                                <td class="absensi"><?= $d['absensi'][$date] !== '-' ? $d['absensi'][$date] : '' ?></td>
                            <?php endforeach; ?>
                            <td class="rekap"><?= $d['hadir'] ?></td>
                            <td class="rekap"><?= $d['terlambat'] ?></td>
                            <td class="rekap"><?= $d['sakit'] ?></td>
                            <td class="rekap"><?= $d['izin'] ?></td>
                            <td class="rekap"><?= $d['alfa'] ?></td>
                            <td class="rekap"><?= $d['total'] ?></td>
                        </tr>
                    <?php $no++; endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="<?= 3 + ($semua_kelas ? 1 : 0) ?>" style="text-align: center; font-weight: bold;">TOTAL</td>
                        <?php foreach ($dates as $date): ?>
                            <?php
                            $total_per_tanggal = 0;
                            foreach ($data_siswa as $d) {
                                if ($d['absensi'][$date] != '-') $total_per_tanggal++;
                            }
                            ?>
                            <td class="rekap"><?= $total_per_tanggal ?></td>
                        <?php endforeach; ?>
                        <td class="rekap"><?= $rekap_total['Hadir'] ?></td>
                        <td class="rekap"><?= $rekap_total['Terlambat'] ?></td>
                        <td class="rekap"><?= $rekap_total['Sakit'] ?></td>
                        <td class="rekap"><?= $rekap_total['Izin'] ?></td>
                        <td class="rekap"><?= $rekap_total['Alfa'] ?></td>
                        <td class="rekap"><?= array_sum($rekap_total) ?></td>
                    </tr>
                    <tr>
                        <th colspan="<?= 3 + ($semua_kelas ? 1 : 0) ?>" class="header-total">Keterangan</th>
                        <th colspan="<?= count($dates) ?>" class="header-total">H = Hadir, T = Terlambat, S = Sakit, I = Izin, A = Alfa</th>
                        <th colspan="6" class="header-total"></th>
                    </tr>
                </tfoot>
            </table>

            <!-- Footer TTD -->
            <div class="footer">
                <div class="ttd-box">
                    <p><?= date('d F Y') ?></p>
                    <p class="ttd-label">Kepala Sekolah</p>
                    <br><br><br><br>
                    <p>_________________________</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Export PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function exportPDF() {
            const element = document.querySelector('.print-container');
            const opt = {
                margin: 5,
                filename: 'Rekap_Absensi_<?= $semua_kelas ? "Semua_Kelas" : str_replace(" ", "_", $nama_kelas) ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'landscape',
                    compress: true
                }
            };

            // Show loading
            alert('Sedang memproses PDF...\nSilakan tunggu beberapa saat.');

            // Generate PDF
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
<?php
session_start();
// Cek login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';
require_once '../includes/header.php';
// Ambil bulan dan tahun saat ini sebagai default
$current_month = date('m');
$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi Siswa Per Tanggal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table th, .table td {
            white-space: nowrap;
            text-align: center;
        }
        .table th.rotate {
            height: 140px;
            white-space: nowrap;
        }
        .table th.rotate > div {
            transform: rotate(-45deg);
            width: 30px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h2>Rekap Absensi Siswa Per Tanggal</h2>
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <label><strong>Kelas</strong></label>
                <select name="kelas_id" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <option value="all" <?= (isset($_GET['kelas_id']) && $_GET['kelas_id'] == 'all') ? 'selected' : '' ?>>
                        🔍 Semua Kelas
                    </option>
                    <?php
                    $stmt_kelas = $koneksi->prepare("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
                    $stmt_kelas->execute();
                    $result_kelas = $stmt_kelas->get_result();
                    while ($row = $result_kelas->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= ($row['id'] == ($_GET['kelas_id'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['nama_kelas']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label><strong>Periode</strong></label>
                <select name="periode" class="form-select" required>
                    <option value="bulan" <?= (($_GET['periode'] ?? 'bulan') == 'bulan') ? 'selected' : '' ?>>Bulan</option>
                    <option value="semester" <?= (($_GET['periode'] ?? '') == 'semester') ? 'selected' : '' ?>>Semester</option>
                </select>
            </div>
            <div class="col-md-3" id="bulan-tahun" <?= (($_GET['periode'] ?? 'bulan') == 'semester') ? 'style="display:none;"' : '' ?>>
                <label><strong>Bulan dan Tahun</strong></label>
                <div class="row g-2">
                    <div class="col">
                        <select name="bulan" class="form-select">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= (($_GET['bulan'] ?? $current_month) == str_pad($m, 2, '0', STR_PAD_LEFT)) ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col">
                        <select name="tahun" class="form-select">
                            <?php for ($y = $current_year - 5; $y <= $current_year + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= (($_GET['tahun'] ?? $current_year) == $y) ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-3" id="periode-tanggal" <?= (($_GET['periode'] ?? 'bulan') == 'bulan') ? 'style="display:none;"' : '' ?>>
                <label><strong>Rentang Tanggal</strong></label>
                <div class="row g-2">
                    <div class="col">
                        <input type="date" name="tgl_awal" class="form-control" value="<?= $_GET['tgl_awal'] ?? date('Y-m-01') ?>">
                    </div>
                    <div class="col">
                        <input type="date" name="tgl_akhir" class="form-control" value="<?= $_GET['tgl_akhir'] ?? date('Y-m-t') ?>">
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <label><strong>Urutkan Berdasarkan</strong></label>
                <select name="sort_by" class="form-select">
                    <option value="">Default (Nama)</option>
                    <option value="hadir" <?= ($_GET['sort_by'] ?? '') == 'hadir' ? 'selected' : '' ?>>Hadir</option>
                    <option value="terlambat" <?= ($_GET['sort_by'] ?? '') == 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                    <option value="sakit" <?= ($_GET['sort_by'] ?? '') == 'sakit' ? 'selected' : '' ?>>Sakit</option>
                    <option value="izin" <?= ($_GET['sort_by'] ?? '') == 'izin' ? 'selected' : '' ?>>Izin</option>
                    <option value="alfa" <?= ($_GET['sort_by'] ?? '') == 'alfa' ? 'selected' : '' ?>>Alfa</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
                <a href="?" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <?php if (isset($_GET['kelas_id']) && (isset($_GET['bulan']) || isset($_GET['tgl_awal']))): ?>
            <?php
            $kelas_id = $_GET['kelas_id'];
            $periode = $_GET['periode'] ?? 'bulan';
            $semua_kelas = ($kelas_id === 'all');
            $nama_kelas = '';

            // Tentukan rentang tanggal - PERBAIKAN FILTER DEPRECATED
            if ($periode == 'bulan') {
                $bulan = filter_input(INPUT_GET, 'bulan', FILTER_SANITIZE_NUMBER_INT);
                $tahun = filter_input(INPUT_GET, 'tahun', FILTER_SANITIZE_NUMBER_INT);
                $tgl_awal = "$tahun-$bulan-01";
                $tgl_akhir = date('Y-m-t', strtotime($tgl_awal));
            } else {
                $tgl_awal = filter_input(INPUT_GET, 'tgl_awal', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $tgl_akhir = filter_input(INPUT_GET, 'tgl_akhir', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            }

            // Validasi tanggal
            if (!strtotime($tgl_awal) || !strtotime($tgl_akhir)) {
                echo "<div class='alert alert-danger'>Tanggal tidak valid.</div>";
                exit;
            }

            if (strtotime($tgl_awal) > strtotime($tgl_akhir)) {
                echo "<div class='alert alert-danger'>Tanggal awal tidak boleh lebih besar dari tanggal akhir.</div>";
                exit;
            }

            // Ambil nama kelas
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
                $kelas_id_int = (int)$kelas_id;
                $stmt_siswa = $koneksi->prepare("SELECT id, nama, jenis_kelamin FROM siswa WHERE kelas_id = ? ORDER BY nama");
                $stmt_siswa->bind_param("i", $kelas_id_int);
            }
            $stmt_siswa->execute();
            $result_siswa = $stmt_siswa->get_result();
            $data_siswa = [];
            $rekap_total = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];

            // Kumpulkan data absensi per siswa
            while ($row = $result_siswa->fetch_assoc()) {
                $siswa_id = $row['id'];
                $absensi = array_fill_keys($dates, '-'); // Default: tanda '-' jika tidak ada data
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
                        $absensi[$tanggal] = substr($status, 0, 1); // Ambil huruf pertama: H, T, S, I, A
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

            // Ambil sort_by
            $sort_by = $_GET['sort_by'] ?? '';
            $sort_field = match($sort_by) {
                'hadir' => 'hadir',
                'terlambat' => 'terlambat',
                'sakit' => 'sakit',
                'izin' => 'izin',
                'alfa' => 'alfa',
                default => 'nama'
            };

            // Urutkan data
            usort($data_siswa, function($a, $b) use ($sort_field) {
                if ($sort_field == 'nama') {
                    return strcmp($a['nama'], $b['nama']);
                }
                return $b[$sort_field] <=> $a[$sort_field]; // descending untuk lainnya
            });

            // Tampilkan judul
            echo "<h4>" . ($semua_kelas ? "Rekap Semua Kelas" : "Rekap Kelas: " . htmlspecialchars($nama_kelas)) . "</h4>";
            echo "<p><strong>Periode:</strong> " . date('d M Y', strtotime($tgl_awal)) . " s.d. " . date('d M Y', strtotime($tgl_akhir)) . "</p>";

            // Tampilkan tabel
            echo "<div class='table-responsive'>";
            echo "<table id='tabel-rekap' class='table table-bordered table-striped table-hover mt-3'>";
            echo "<thead class='table-primary'>";
            echo "<tr>";
            echo "<th rowspan='2'>No</th>";
            if ($semua_kelas) echo "<th rowspan='2'>Kelas</th>";
            echo "<th rowspan='2'>Nama Siswa</th>";
            echo "<th rowspan='2'>Jenis Kelamin</th>";
            echo "<th colspan='" . count($dates) . "'>Tanggal</th>";
            echo "<th rowspan='2'>Hadir</th>";
            echo "<th rowspan='2'>Terlambat</th>";
            echo "<th rowspan='2'>Sakit</th>";
            echo "<th rowspan='2'>Izin</th>";
            echo "<th rowspan='2'>Alfa</th>";
            echo "<th rowspan='2'>Total</th>";
            echo "</tr>";
            echo "<tr>";
            foreach ($dates as $date) {
                echo "<th class='rotate'><div>" . date('d M', strtotime($date)) . "</div></th>";
            }
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            $no = 1;
            foreach ($data_siswa as $d) {
                echo "<tr>";
                echo "<td>$no</td>";
                if ($semua_kelas) echo "<td>{$d['kelas']}</td>";
                echo "<td>{$d['nama']}</td>";
                echo "<td>{$d['jk']}</td>";
                foreach ($dates as $date) {
                    echo "<td>{$d['absensi'][$date]}</td>";
                }
                echo "<td>{$d['hadir']}</td>";
                echo "<td>{$d['terlambat']}</td>";
                echo "<td>{$d['sakit']}</td>";
                echo "<td>{$d['izin']}</td>";
                echo "<td>{$d['alfa']}</td>";
                echo "<td><strong>{$d['total']}</strong></td>";
                echo "</tr>";
                $no++;
            }

            // Baris total
            echo "<tr class='table-primary fw-bold'>";
            echo "<td></td>";
            if ($semua_kelas) echo "<td></td>";
            echo "<td>Total</td><td></td>";
            foreach ($dates as $date) {
                $total_per_tanggal = 0;
                foreach ($data_siswa as $d) {
                    if ($d['absensi'][$date] != '-') $total_per_tanggal++;
                }
                echo "<td>$total_per_tanggal</td>";
            }
            echo "<td>{$rekap_total['Hadir']}</td>";
            echo "<td>{$rekap_total['Terlambat']}</td>";
            echo "<td>{$rekap_total['Sakit']}</td>";
            echo "<td>{$rekap_total['Izin']}</td>";
            echo "<td>{$rekap_total['Alfa']}</td>";
            echo "<td><strong>" . array_sum($rekap_total) . "</strong></td>";
            echo "</tr>";
            echo "</tbody></table>";
            echo "</div>";

            // Grafik Pie
            echo '<div class="chart-container">';
            echo '<canvas id="chart-kelas"></canvas>';
            echo '</div>';

            // Chart.js Script
            echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var ctx = document.getElementById("chart-kelas").getContext("2d");
                    new Chart(ctx, {
                        type: "pie",
                        data: {
                            labels: ["Hadir", "Terlambat", "Sakit", "Izin", "Alfa"],
                            datasets: [{
                                data: [' .
                                    $rekap_total['Hadir'] . ',' .
                                    $rekap_total['Terlambat'] . ',' .
                                    $rekap_total['Sakit'] . ',' .
                                    $rekap_total['Izin'] . ',' .
                                    $rekap_total['Alfa'] .
                                '],
                                backgroundColor: ["#4CAF50", "#FFC107", "#2196F3", "#9C27B0", "#F44336"],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: "Rekap Kehadiran ' . ($semua_kelas ? 'Semua Kelas' : addslashes(htmlspecialchars($nama_kelas))) . '",
                                    font: { size: 16 }
                                },
                                legend: { position: "right" },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            var total = context.dataset.data.reduce((a,b)=>a+b,0);
                                            var value = context.raw;
                                            var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                            return context.label + ": " + value + " (" + percentage + "%)";
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
            </script>';

            // Tombol Cetak
            $params = http_build_query([
                'kelas_id' => $kelas_id,
                'periode' => $periode,
                'bulan' => $periode == 'bulan' ? $bulan : '',
                'tahun' => $periode == 'bulan' ? $tahun : '',
                'tgl_awal' => $periode == 'semester' ? $tgl_awal : '',
                'tgl_akhir' => $periode == 'semester' ? $tgl_akhir : '',
                'sort_by' => $sort_by
            ]);
            echo "<a href='cetak_per_tanggal_siswa.php?$params' class='btn btn-success mt-3' target='_blank'>
                <i class='bi bi-printer'></i> Cetak Rekap
            </a>";
        ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle input berdasarkan periode
            $('select[name="periode"]').change(function() {
                var periode = $(this).val();
                if (periode == 'bulan') {
                    $('#bulan-tahun').show();
                    $('#periode-tanggal').hide();
                    $('#periode-tanggal input').val('');
                } else {
                    $('#bulan-tahun').hide();
                    $('#periode-tanggal').show();
                    $('#bulan-tahun select').val('');
                }
            });

            // DataTables
            const sort_by = '<?= $sort_by; ?>';
            let sortColumnIndex = -1;
            if (sort_by === 'hadir') sortColumnIndex = <?= $semua_kelas ? count($dates) + 4 : count($dates) + 3 ?>;
            if (sort_by === 'terlambat') sortColumnIndex = <?= $semua_kelas ? count($dates) + 5 : count($dates) + 4 ?>;
            if (sort_by === 'sakit') sortColumnIndex = <?= $semua_kelas ? count($dates) + 6 : count($dates) + 5 ?>;
            if (sort_by === 'izin') sortColumnIndex = <?= $semua_kelas ? count($dates) + 7 : count($dates) + 6 ?>;
            if (sort_by === 'alfa') sortColumnIndex = <?= $semua_kelas ? count($dates) + 8 : count($dates) + 7 ?>;

            const datatableOptions = {
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                },
                pageLength: 25,
                scrollX: true,
                columnDefs: [
                    { orderable: false, targets: [0] }, // Non-sortable: Kolom No
                    { orderable: false, targets: [-1] } // Non-sortable: Kolom Total
                ]
            };

            if (sortColumnIndex > -1) {
                datatableOptions.order = [[sortColumnIndex, "desc"]];
            }

            $('#tabel-rekap').DataTable(datatableOptions);
        });
    </script>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
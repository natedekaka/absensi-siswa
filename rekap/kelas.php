<?php
session_start();

// Cek login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';
require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi Per Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <h2>Rekap Absensi Per Kelas</h2>

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
            <label><strong>Tanggal Awal</strong></label>
            <input type="date" name="tgl_awal" class="form-control" required
                   value="<?= $_GET['tgl_awal'] ?? date('Y-m-01') ?>">
        </div>

        <div class="col-md-3">
            <label><strong>Tanggal Akhir</strong></label>
            <input type="date" name="tgl_akhir" class="form-control" required
                   value="<?= $_GET['tgl_akhir'] ?? date('Y-m-t') ?>">
        </div>

        <div class="col-md-3">
            <label><strong>Urutkan Berdasarkan</strong></label>
            <select name="sort_by" class="form-select">
                <option value="">Default (No)</option>
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

    <?php if (isset($_GET['kelas_id'])): ?>
        <?php
        $kelas_id = $_GET['kelas_id'];
        $tgl_awal = htmlspecialchars($_GET['tgl_awal'] ?? '');
        $tgl_akhir = htmlspecialchars($_GET['tgl_akhir'] ?? '');

        // Validasi tanggal
        if (!strtotime($tgl_awal) || !strtotime($tgl_akhir)) {
            echo "<div class='alert alert-danger'>Tanggal tidak valid.</div>";
            exit;
        }

        if (strtotime($tgl_awal) > strtotime($tgl_akhir)) {
            echo "<div class='alert alert-danger'>Tanggal awal tidak boleh lebih besar dari tanggal akhir.</div>";
            exit;
        }

        // Inisialisasi rekap
        $rekapKelas = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];
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

        // Tampilkan judul
        echo "<h4>" . ($semua_kelas ? "Rekap Semua Kelas" : "Rekap Kelas: " . htmlspecialchars($nama_kelas)) . "</h4>";
        echo "<p><strong>Periode:</strong> " . date('d M Y', strtotime($tgl_awal)) . " s.d. " . date('d M Y', strtotime($tgl_akhir)) . "</p>";

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

        // Ambil sort_by
        $sort_by = $_GET['sort_by'] ?? '';
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

        // Urutkan data jika perlu
        if ($sort_field) {
            usort($data_siswa, function($a, $b) use ($sort_field) {
                return $b[$sort_field] <=> $a[$sort_field]; // descending
            });
        }
        
        // Tentukan indeks kolom untuk DataTables
        $columnIndex = 0;
        if ($sort_by == 'hadir') $columnIndex = $semua_kelas ? 4 : 3;
        if ($sort_by == 'terlambat') $columnIndex = $semua_kelas ? 5 : 4;
        if ($sort_by == 'sakit') $columnIndex = $semua_kelas ? 6 : 5;
        if ($sort_by == 'izin') $columnIndex = $semua_kelas ? 7 : 6;
        if ($sort_by == 'alfa') $columnIndex = $semua_kelas ? 8 : 7;

        // Tampilkan tabel
        echo "<table id='tabel-rekap' class='table table-bordered table-striped table-hover mt-3'>";
        echo "<thead class='table-primary'>
                <tr>
                    <th>No</th>";
        if ($semua_kelas) echo "<th>Kelas</th>";
        echo "<th>Nama Siswa</th>
                    <th>Jenis Kelamin</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Sakit</th>
                    <th>Izin</th>
                    <th>Alfa</th>
                    <th>Total</th>
                </tr>
              </thead>";
        echo "<tbody>";

        $no = 1;
        foreach ($data_siswa as $d) {
            echo "<tr>";
            echo "<td>{$no}</td>";
            if ($semua_kelas) echo "<td>{$d['kelas']}</td>";
            echo "<td>{$d['nama']}</td>";
            echo "<td>{$d['jk']}</td>";
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
        echo "<td>{$rekapKelas['Hadir']}</td>";
        echo "<td>{$rekapKelas['Terlambat']}</td>";
        echo "<td>{$rekapKelas['Sakit']}</td>";
        echo "<td>{$rekapKelas['Izin']}</td>";
        echo "<td>{$rekapKelas['Alfa']}</td>";
        echo "<td><strong>" . array_sum($rekapKelas) . "</strong></td>";
        echo "</tr>";

        echo "</tbody></table>";

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
                            $rekapKelas['Hadir'] . ',' . 
                            $rekapKelas['Terlambat'] . ',' . 
                            $rekapKelas['Sakit'] . ',' . 
                            $rekapKelas['Izin'] . ',' . 
                            $rekapKelas['Alfa'] . 
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
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'sort_by' => $sort_by
        ]);
        echo "<a href='cetak.php?$params' class='btn btn-success mt-3' target='_blank'>
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
    // Tentukan indeks kolom yang akan diurutkan berdasarkan parameter sort_by
    const isAllKelas = <?= json_encode($semua_kelas); ?>;
    const sort_by = '<?= $sort_by; ?>';
    let sortColumnIndex = -1;
    
    // Mapping antara nilai sort_by dengan indeks kolom tabel
    if (sort_by === 'hadir') sortColumnIndex = isAllKelas ? 4 : 3;
    if (sort_by === 'terlambat') sortColumnIndex = isAllKelas ? 5 : 4;
    if (sort_by === 'sakit') sortColumnIndex = isAllKelas ? 6 : 5;
    if (sort_by === 'izin') sortColumnIndex = isAllKelas ? 7 : 6;
    if (sort_by === 'alfa') sortColumnIndex = isAllKelas ? 8 : 7;

    const datatableOptions = {
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
        },
        pageLength: 25,
        columnDefs: [
            { orderable: false, targets: [0] }, // Non-sortable: Kolom No
            { orderable: false, targets: [-1] } // Non-sortable: Kolom Total
        ]
    };
    
    // Terapkan pengurutan awal jika sort_by dipilih
    if (sortColumnIndex > -1) {
        datatableOptions.order = [[sortColumnIndex, "desc"]];
    }
    
    $('#tabel-rekap').DataTable(datatableOptions);
});
</script>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>
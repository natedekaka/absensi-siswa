<?php
session_start();

// Cek login
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config.php';
require_once '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Kehadiran Keseluruhan Periode</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .table-rekap th, .table-rekap td {
            text-align: center;
            vertical-align: middle;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <h2>Rekap Kehadiran Keseluruhan Periode</h2>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-6">
            <label><strong>Tanggal Awal</strong></label>
            <input type="date" name="tgl_awal" class="form-control" required
                   value="<?= $_GET['tgl_awal'] ?? date('Y-m-01', strtotime('-5 months')) ?>">
        </div>
        <div class="col-md-6">
            <label><strong>Tanggal Akhir</strong></label>
            <input type="date" name="tgl_akhir" class="form-control" required
                   value="<?= $_GET['tgl_akhir'] ?? date('Y-m-t') ?>">
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Tampilkan Rekap</button>
            <a href="?" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <?php if (isset($_GET['tgl_awal']) && isset($_GET['tgl_akhir'])): ?>
        <?php
        $tgl_awal = filter_input(INPUT_GET, 'tgl_awal', FILTER_SANITIZE_STRING);
        $tgl_akhir = filter_input(INPUT_GET, 'tgl_akhir', FILTER_SANITIZE_STRING);

        // Validasi tanggal
        if (!strtotime($tgl_awal) || !strtotime($tgl_akhir) || strtotime($tgl_awal) > strtotime($tgl_akhir)) {
            echo "<div class='alert alert-danger'>Rentang tanggal tidak valid.</div>";
            exit;
        }

        echo "<h4>Rekap Periode: " . date('d M Y', strtotime($tgl_awal)) . " s.d. " . date('d M Y', strtotime($tgl_akhir)) . "</h4>";

        // Query untuk menghitung total hari efektif (jumlah absensi unik)
        $stmt_total_hari = $koneksi->prepare("
            SELECT COUNT(DISTINCT tanggal) AS total_hari 
            FROM absensi 
            WHERE tanggal BETWEEN ? AND ?
        ");
        $stmt_total_hari->bind_param("ss", $tgl_awal, $tgl_akhir);
        $stmt_total_hari->execute();
        $total_hari = $stmt_total_hari->get_result()->fetch_assoc()['total_hari'] ?? 0;
        $stmt_total_hari->close();

        // Query untuk mengambil rekap absensi per kelas pada periode yang dipilih
        $stmt_rekap = $koneksi->prepare("
            SELECT
                k.nama_kelas,
                COUNT(s.id) AS TotalSiswa,
                SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) AS Hadir,
                SUM(CASE WHEN a.status = 'Terlambat' THEN 1 ELSE 0 END) AS Terlambat,
                SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) AS Sakit,
                SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) AS Izin
            FROM kelas k
            LEFT JOIN siswa s ON k.id = s.kelas_id
            LEFT JOIN absensi a ON s.id = a.siswa_id AND a.tanggal BETWEEN ? AND ?
            GROUP BY k.id
            ORDER BY k.nama_kelas
        ");
        $stmt_rekap->bind_param("ss", $tgl_awal, $tgl_akhir);
        $stmt_rekap->execute();
        $result_rekap = $stmt_rekap->get_result();

        $grand_total = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0, 'TotalSiswa' => 0, 'TotalAbsensi' => 0];
        $data_tabel = [];

        // Hitung total Alfa dan rekap grand total
        while ($row = $result_rekap->fetch_assoc()) {
            $total_absensi_siswa_diabsen = $row['Hadir'] + $row['Terlambat'] + $row['Sakit'] + $row['Izin'];
            $alfa_calc = ($row['TotalSiswa'] * $total_hari) - $total_absensi_siswa_diabsen;
            $row['Alfa'] = $alfa_calc > 0 ? $alfa_calc : 0;
            $row['TotalAbsensi'] = $total_absensi_siswa_diabsen + $row['Alfa'];

            $data_tabel[] = $row;
            
            $grand_total['Hadir'] += $row['Hadir'];
            $grand_total['Terlambat'] += $row['Terlambat'];
            $grand_total['Sakit'] += $row['Sakit'];
            $grand_total['Izin'] += $row['Izin'];
            $grand_total['Alfa'] += $row['Alfa'];
            $grand_total['TotalSiswa'] += $row['TotalSiswa'];
            $grand_total['TotalAbsensi'] += $row['TotalAbsensi'];
        }
        
        ?>

        <table id="tabel-rekap-periode" class="table table-bordered table-striped table-hover mt-3 table-rekap">
            <thead class="table-primary">
                <tr>
                    <th>No</th>
                    <th>Kelas</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Sakit</th>
                    <th>Izin</th>
                    <th>Alfa</th>
                    <th>Total Absensi</th>
                    <th>Total Siswa</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($data_tabel as $row):
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['nama_kelas']) ?></td>
                    <td><?= $row['Hadir'] ?></td>
                    <td><?= $row['Terlambat'] ?></td>
                    <td><?= $row['Sakit'] ?></td>
                    <td><?= $row['Izin'] ?></td>
                    <td><?= $row['Alfa'] ?></td>
                    <td><?= $row['TotalAbsensi'] ?></td>
                    <td><?= $row['TotalSiswa'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-primary fw-bold">
                    <td colspan="2" class="text-end">Total Keseluruhan</td>
                    <td><?= $grand_total['Hadir'] ?></td>
                    <td><?= $grand_total['Terlambat'] ?></td>
                    <td><?= $grand_total['Sakit'] ?></td>
                    <td><?= $grand_total['Izin'] ?></td>
                    <td><?= $grand_total['Alfa'] ?></td>
                    <td><?= $grand_total['TotalAbsensi'] ?></td>
                    <td><?= $grand_total['TotalSiswa'] ?></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="chart-container">
            <canvas id="chart-rekap"></canvas>
        </div>
        
        <?php
        $params = http_build_query(['tgl_awal' => $tgl_awal, 'tgl_akhir' => $tgl_akhir]);
        echo "<a href='cetak_rekap_periode.php?$params' class='btn btn-success mt-3' target='_blank'>
                <i class='bi bi-printer'></i> Cetak Rekap Periode
              </a>";
        ?>

    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    $('#tabel-rekap-periode').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
        },
        "paging": false,
        "searching": false,
        "info": false
    });
    
    // Data untuk chart
    const labels = ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alfa'];
    const data = {
        labels: labels,
        datasets: [{
            label: 'Total Kehadiran Keseluruhan',
            data: [
                <?= $grand_total['Hadir'] ?>,
                <?= $grand_total['Terlambat'] ?>,
                <?= $grand_total['Sakit'] ?>,
                <?= $grand_total['Izin'] ?>,
                <?= $grand_total['Alfa'] ?>
            ],
            backgroundColor: [
                '#28a745', '#ffc107', '#17a2b8', '#6c757d', '#dc3545'
            ],
            hoverOffset: 4
        }]
    };
    
    // Konfigurasi chart
    const config = {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Total Rekap Kehadiran Seluruh Sekolah'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var value = context.raw;
                            var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return context.label + ": " + value + " (" + percentage + "%)";
                        }
                    }
                }
            }
        }
    };
    
    // Render chart
    new Chart(
        document.getElementById('chart-rekap'),
        config
    );
});
</script>

<?php require_once '../../includes/footer.php'; ?>
</body>
</html>
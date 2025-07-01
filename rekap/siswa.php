<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';
require_once '../includes/header.php';
require_once '../includes/functions.php';
?>
<h2>Rekap Absensi Per Siswa</h2>
<div class="card mb-4">
    <div class="card-body">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="bulan-tab" data-bs-toggle="tab" data-bs-target="#bulan" type="button" role="tab">Per Bulan</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rentang-tab" data-bs-toggle="tab" data-bs-target="#rentang" type="button" role="tab">Rentang Tanggal</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="semester-tab" data-bs-toggle="tab" data-bs-target="#semester" type="button" role="tab">Per Semester</button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <!-- Tab Per Bulan -->
            <div class="tab-pane fade show active" id="bulan" role="tabpanel">
                <form method="GET" class="row g-3 mt-3">
                    <input type="hidden" name="mode" value="bulan">
                    <div class="col-md-6">
                        <label>Cari Nama Siswa</label>
                        <input type="text" name="cari" class="form-control" 
                               placeholder="Masukkan nama siswa" value="<?= $_GET['cari'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Bulan</label>
                        <select name="bulan" class="form-select" required>
                            <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?= $i ?>" <?= ($i == ($_GET['bulan'] ?? date('n'))) ? 'selected' : '' ?>>
                                <?= date('F', mktime(0,0,0,$i,1)) ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tahun</label>
                        <input type="number" name="tahun" class="form-control" 
                               value="<?= $_GET['tahun'] ?? date('Y') ?>" required>
                    </div>
                    <div class="col-md-12 mt-4">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <a href="siswa.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
            <!-- Tab Rentang Tanggal -->
            <div class="tab-pane fade" id="rentang" role="tabpanel">
                <form method="GET" class="row g-3 mt-3">
                    <input type="hidden" name="mode" value="rentang">
                    <div class="col-md-6">
                        <label>Cari Nama Siswa</label>
                        <input type="text" name="cari" class="form-control" 
                               placeholder="Masukkan nama siswa" value="<?= $_GET['cari'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Dari Tanggal</label>
                        <input type="date" name="dari" class="form-control" 
                               value="<?= $_GET['dari'] ?? date('Y-m-01') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="sampai" class="form-control" 
                               value="<?= $_GET['sampai'] ?? date('Y-m-t') ?>" required>
                    </div>
                    <div class="col-md-12 mt-4">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <a href="siswa.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
            <!-- Tab Per Semester -->
            <div class="tab-pane fade" id="semester" role="tabpanel">
                <form method="GET" class="row g-3 mt-3">
                    <input type="hidden" name="mode" value="semester">
                    <div class="col-md-6">
                        <label>Cari Nama Siswa</label>
                        <input type="text" name="cari" class="form-control" 
                               placeholder="Masukkan nama siswa" value="<?= $_GET['cari'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Semester</label>
                        <select name="semester" class="form-select" required>
                            <option value="1" <?= (($_GET['semester'] ?? '1') == '1') ? 'selected' : '' ?>>Semester 1</option>
                            <option value="2" <?= (($_GET['semester'] ?? '2') == '2') ? 'selected' : '' ?>>Semester 2</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" class="form-control" 
                               value="<?= $_GET['tahun_ajaran'] ?? date('Y') . '/' . (date('Y')+1) ?>" required
                               placeholder="Contoh: 2023/2024">
                    </div>
                    <div class="col-md-12 mt-4">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <a href="siswa.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_GET['mode'])) {
    $mode = $_GET['mode'];
    $cari = $_GET['cari'] ?? '';
    
    // Query ambil semua siswa dengan kelas dan jenis kelamin
    $query = "SELECT siswa.id, siswa.nama, siswa.jenis_kelamin, kelas.nama_kelas 
              FROM siswa 
              JOIN kelas ON siswa.kelas_id = kelas.id";
    
    if (!empty($cari)) {
        $cari = $koneksi->real_escape_string($cari);
        $query .= " WHERE siswa.nama LIKE '%$cari%'";
    }
    
    $siswa_result = $koneksi->query($query);
    
    if ($siswa_result->num_rows > 0) {
        while($siswa = $siswa_result->fetch_assoc()) {
            $siswa_id = $siswa['id'];
            $rekap = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];
            
            // Proses berdasarkan mode
            if ($mode == 'bulan') {
                $bulan = $_GET['bulan'];
                $tahun = $_GET['tahun'];
                $absensi = getAbsensiBySiswa($siswa_id);
                while($absen = $absensi->fetch_assoc()) {
                    if (date('n', strtotime($absen['tanggal'])) == $bulan && 
                        date('Y', strtotime($absen['tanggal'])) == $tahun) {
                        $rekap[$absen['status']]++;
                    }
                }
                $periode = date('F Y', mktime(0,0,0,$bulan,1,$tahun));
                
            } elseif ($mode == 'rentang') {
                $dari = $_GET['dari'];
                $sampai = $_GET['sampai'];
                $stmt = $koneksi->prepare("SELECT status FROM absensi 
                                          WHERE siswa_id = ? 
                                          AND tanggal BETWEEN ? AND ?");
                $stmt->bind_param("iss", $siswa_id, $dari, $sampai);
                $stmt->execute();
                $absensi = $stmt->get_result();
                while($absen = $absensi->fetch_assoc()) {
                    $rekap[$absen['status']]++;
                }
                $periode = date('d M Y', strtotime($dari)) . ' - ' . date('d M Y', strtotime($sampai));
                
            } elseif ($mode == 'semester') {
                $semester = $_GET['semester'];
                $tahun_ajaran = $_GET['tahun_ajaran'];
                list($tahun_awal, $tahun_akhir) = explode('/', $tahun_ajaran);
                
                if ($semester == 1) {
                    $dari = $tahun_awal . '-07-01';
                    $sampai = $tahun_awal . '-12-31';
                } else {
                    $dari = $tahun_akhir . '-01-01';
                    $sampai = $tahun_akhir . '-06-30';
                }
                
                $stmt = $koneksi->prepare("SELECT status FROM absensi 
                                          WHERE siswa_id = ? 
                                          AND tanggal BETWEEN ? AND ?");
                $stmt->bind_param("iss", $siswa_id, $dari, $sampai);
                $stmt->execute();
                $absensi = $stmt->get_result();
                while($absen = $absensi->fetch_assoc()) {
                    $rekap[$absen['status']]++;
                }
                $periode = 'Semester ' . $semester . ' TA ' . $tahun_ajaran;
            }
            
            // Tampilkan hasil rekap
            echo '<div class="card mb-4">';
            echo '<div class="card-header">';
            echo '<h4>Rekap Absensi: ' . htmlspecialchars($siswa['nama']) . ' (' . htmlspecialchars($siswa['nama_kelas']) . ')</h4>';
            echo '</div>';
            echo '<div class="card-body">';
            echo '<p><strong>Jenis Kelamin:</strong> ' . htmlspecialchars($siswa['jenis_kelamin']) . '</p>';
            echo '<p>Periode: ' . $periode . '</p>';
            
            // Tabel Rekap
            echo '<table class="table table-bordered">';
            echo '<tr><th>Hadir</th><th>Terlambat</th><th>Sakit</th><th>Izin</th><th>Alfa</th><th>Total</th></tr>';
            echo '<tr>';
            echo '<td>' . $rekap['Hadir'] . '</td>';
            echo '<td>' . $rekap['Terlambat'] . '</td>';
            echo '<td>' . $rekap['Sakit'] . '</td>';
            echo '<td>' . $rekap['Izin'] . '</td>';
            echo '<td>' . $rekap['Alfa'] . '</td>';
            echo '<td>' . array_sum($rekap) . '</td>';
            echo '</tr>';
            echo '</table>';
            
            // Grafik Pie
            echo '<div class="chart-container" style="height: 300px;">';
            echo '<canvas id="chart-' . $siswa_id . '"></canvas>';
            echo '</div>';
            
            // Skrip untuk grafik
            echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var ctx = document.getElementById("chart-' . $siswa_id . '").getContext("2d");
                var myChart = new Chart(ctx, {
                    type: "pie",
                    data: {
                        labels: ["Hadir", "Terlambat", "Sakit", "Izin", "Alfa"],
                        datasets: [{
                            data: [' . 
                                $rekap['Hadir'] . ',' . 
                                $rekap['Terlambat'] . ',' . 
                                $rekap['Sakit'] . ',' . 
                                $rekap['Izin'] . ',' . 
                                $rekap['Alfa'] . 
                            '],
                            backgroundColor: [
                                "#4CAF50",  // Hijau untuk Hadir
                                "#FFC107",  // Kuning untuk Terlambat
                                "#2196F3",  // Biru untuk Sakit
                                "#9C27B0",  // Ungu untuk Izin
                                "#F44336"   // Merah untuk Alfa
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: "Persentase Kehadiran",
                                font: { size: 16 }
                            },
                            legend: { position: "right" },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        var label = context.label || "";
                                        var value = context.raw || 0;
                                        var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        var percentage = Math.round((value / total) * 100) + "%";
                                        return label + ": " + value + " (" + percentage + ")";
                                    }
                                }
                            }
                        }
                    }
                });
            });
            </script>';
            
            // Tombol cetak
            $cetak_params = "type=siswa&id=$siswa_id&mode=$mode";
            echo '<a href="cetak.php?' . $cetak_params . '" 
                  class="btn btn-success" target="_blank">Cetak</a>';
            
            echo '</div></div>';
        }
    } else {
        echo '<div class="alert alert-warning">Tidak ditemukan siswa dengan nama "' . htmlspecialchars($cari) . '"</div>';
    }
}
?>

<?php require_once '../includes/footer.php'; ?>
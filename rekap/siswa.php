<?php
// Tambahkan baris ini untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// ... sisanya kode

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
                <button class="nav-link active" id="bulan-tab" data-bs-toggle="tab" data-bs-target="#bulan" type="button" role="tab">Rentang Tanggal</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rentang-tab" data-bs-toggle="tab" data-bs-target="#rentang" type="button" role="tab">Rentang Tanggal Spesifik</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="semester-tab" data-bs-toggle="tab" data-bs-target="#semester" type="button" role="tab">Per Semester</button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <!-- Tab Rentang Tanggal -->
            <div class="tab-pane fade show active" id="bulan" role="tabpanel">
                <form method="GET" class="row g-3 mt-3">
                    <input type="hidden" name="mode" value="bulan">
                    <div class="col-md-4">
                        <label>Cari Nama Siswa</label>
                        <input type="text" name="cari" class="form-control" 
                               placeholder="Masukkan nama siswa" value="<?= $_GET['cari'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Semester</label>
                        <select name="semester_id" class="form-select" required>
                            <option value="">Pilih Semester</option>
                            <?php
                            $semester_list = $koneksi->query("SELECT * FROM semester ORDER BY is_active DESC, tahun_ajaran_id DESC, semester ASC");
                            while ($row = $semester_list->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>" <?= ($row['id'] == ($_GET['semester_id'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['nama']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Dari Tanggal</label>
                        <input type="date" name="dari_tanggal" class="form-control" 
                               value="<?= $_GET['dari_tanggal'] ?? date('Y-m-01') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="sampai_tanggal" class="form-control" 
                               value="<?= $_GET['sampai_tanggal'] ?? date('Y-m-t') ?>" required>
                    </div>
                    <div class="col-md-12 mt-4">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <a href="siswa.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
            <!-- Tab Rentang Tanggal Spesifik -->
            <div class="tab-pane fade" id="rentang" role="tabpanel">
                <form method="GET" class="row g-3 mt-3">
                    <input type="hidden" name="mode" value="rentang">
                    <div class="col-md-4">
                        <label>Cari Nama Siswa</label>
                        <input type="text" name="cari" class="form-control" 
                               placeholder="Masukkan nama siswa" value="<?= $_GET['cari'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Semester</label>
                        <select name="semester_id" class="form-select" required>
                            <option value="">Pilih Semester</option>
                            <?php
                            $semester_list = $koneksi->query("SELECT * FROM semester ORDER BY is_active DESC, tahun_ajaran_id DESC, semester ASC");
                            while ($row = $semester_list->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>" <?= ($row['id'] == ($_GET['semester_id'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['nama']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Dari Tanggal</label>
                        <input type="date" name="dari" class="form-control" 
                               value="<?= $_GET['dari'] ?? date('Y-m-01') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="sampai" class="form-control" 
                               value="<?= $_GET['sampai'] ?? date('Y-m-t') ?>" required>
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
                    <div class="col-md-6">
                        <label>Semester</label>
                        <select name="semester_id" class="form-select" required>
                            <?php
                            $semester_list = $koneksi->query("SELECT * FROM semester ORDER BY is_active DESC, tahun_ajaran_id DESC, semester ASC");
                            while ($row = $semester_list->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>" <?= ($row['id'] == ($_GET['semester_id'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['nama']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
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
            $detail = ['Terlambat' => [], 'Sakit' => [], 'Izin' => [], 'Alfa' => []];
            
            // Proses berdasarkan mode
            if ($mode == 'bulan') {
                $dari_tanggal = $_GET['dari_tanggal'];
                $sampai_tanggal = $_GET['sampai_tanggal'];
                $semester_id = $_GET['semester_id'];
                
                // Tentukan tanggal mulai dan akhir
                $tanggal_mulai = $dari_tanggal;
                $tanggal_akhir = $sampai_tanggal;
                
                // Query untuk mengambil data absensi dalam rentang tanggal
                $stmt = $koneksi->prepare("SELECT status, tanggal FROM absensi 
                                          WHERE siswa_id = ? 
                                          AND tanggal BETWEEN ? AND ?
                                          AND semester_id = ?");
                $stmt->bind_param("issi", $siswa_id, $tanggal_mulai, $tanggal_akhir, $semester_id);
                $stmt->execute();
                $absensi = $stmt->get_result();
                
                while($absen = $absensi->fetch_assoc()) {
                    $rekap[$absen['status']]++;
                    
                    // Simpan detail tanggal untuk status non-hadir
                    if ($absen['status'] != 'Hadir') {
                        $detail[$absen['status']][] = $absen['tanggal'];
                    }
                }
                
                // Format periode untuk ditampilkan
                $periode = date('d F Y', strtotime($dari_tanggal)) . ' - ' . date('d F Y', strtotime($sampai_tanggal));
                
            } elseif ($mode == 'rentang') {
                $dari = $_GET['dari'];
                $sampai = $_GET['sampai'];
                $semester_id = $_GET['semester_id'];
                $tanggal_mulai = $dari;
                $tanggal_akhir = $sampai;
                
                $stmt = $koneksi->prepare("SELECT status, tanggal FROM absensi 
                                          WHERE siswa_id = ? 
                                          AND tanggal BETWEEN ? AND ?
                                          AND semester_id = ?");
                $stmt->bind_param("issi", $siswa_id, $dari, $sampai, $semester_id);
                $stmt->execute();
                $absensi = $stmt->get_result();
                while($absen = $absensi->fetch_assoc()) {
                    $rekap[$absen['status']]++;
                    
                    // Simpan detail tanggal untuk status non-hadir
                    if ($absen['status'] != 'Hadir') {
                        $detail[$absen['status']][] = $absen['tanggal'];
                    }
                }
                $periode = date('d M Y', strtotime($dari)) . ' - ' . date('d M Y', strtotime($sampai));
                
            } elseif ($mode == 'semester') {
                $semester_id = $_GET['semester_id'];
                
                // Get semester info from database
                $semester_info = $koneksi->query("SELECT * FROM semester WHERE id = $semester_id")->fetch_assoc();
                
                $tanggal_mulai = $semester_info['tgl_mulai'];
                $tanggal_akhir = $semester_info['tgl_selesai'];
                
                $stmt = $koneksi->prepare("SELECT status, tanggal FROM absensi 
                                          WHERE siswa_id = ? 
                                          AND tanggal BETWEEN ? AND ?
                                          AND semester_id = ?");
                $stmt->bind_param("issi", $siswa_id, $tanggal_mulai, $tanggal_akhir, $semester_id);
                $stmt->execute();
                $absensi = $stmt->get_result();
                while($absen = $absensi->fetch_assoc()) {
                    $rekap[$absen['status']]++;
                    
                    // Simpan detail tanggal untuk status non-hadir
                    if ($absen['status'] != 'Hadir') {
                        $detail[$absen['status']][] = $absen['tanggal'];
                    }
                }
                $periode = $semester_info['nama'];
            }
            
            // Hitung total dan persentase
            $total = array_sum($rekap);
            $persentase = [];
            if ($total > 0) {
                foreach ($rekap as $status => $jumlah) {
                    $persentase[$status] = round(($jumlah / $total) * 100, 2);
                }
            } else {
                foreach ($rekap as $status => $jumlah) {
                    $persentase[$status] = 0;
                }
            }
            
            // Tampilkan hasil rekap
            echo '<div class="card mb-4">';
            echo '<div class="card-header">';
            echo '<h4>Rekap Absensi: ' . htmlspecialchars($siswa['nama']) . ' (' . htmlspecialchars($siswa['nama_kelas']) . ')</h4>';
            echo '</div>';
            echo '<div class="card-body">';
            echo '<p><strong>Jenis Kelamin:</strong> ' . htmlspecialchars($siswa['jenis_kelamin']) . '</p>';
            echo '<p><strong>Periode:</strong> ' . $periode . '</p>';
            echo '<p><strong>Tanggal Mulai Rekap:</strong> ' . date('d F Y', strtotime($tanggal_mulai)) . '</p>';
            echo '<p><strong>Tanggal Akhir Rekap:</strong> ' . date('d F Y', strtotime($tanggal_akhir)) . '</p>';
            
            // Grafik Pie
            echo '<div class="row mt-4">';
            echo '<div class="col-md-6">';
            echo '<h5>Grafik Persentase Kehadiran</h5>';
            echo '<div class="chart-container" style="height: 300px;">';
            echo '<canvas id="chart-' . $siswa_id . '"></canvas>';
            echo '</div>';
            echo '</div>';
            
            // Tabel Detail Grafik
            echo '<div class="col-md-6">';
            echo '<h5>Detail Persentase Kehadiran</h5>';
            echo '<table class="table table-bordered">';
            echo '<thead><tr><th>Status</th><th>Jumlah</th><th>Persentase</th></tr></thead>';
            echo '<tbody>';
            
            // Tampilkan data untuk setiap status
            foreach (['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alfa'] as $status) {
                $color = '';
                if ($status == 'Hadir') $color = '#4CAF50';
                elseif ($status == 'Terlambat') $color = '#FFC107';
                elseif ($status == 'Sakit') $color = '#2196F3';
                elseif ($status == 'Izin') $color = '#9C27B0';
                elseif ($status == 'Alfa') $color = '#F44336';
                
                echo '<tr>';
                echo '<td><span style="display:inline-block;width:12px;height:12px;background-color:' . $color . ';margin-right:5px;"></span>' . $status . '</td>';
                echo '<td>' . $rekap[$status] . '</td>';
                echo '<td>' . $persentase[$status] . '%</td>';
                echo '</tr>';
            }
            
            echo '<tr class="table-active">';
            echo '<td><strong>Total</strong></td>';
            echo '<td><strong>' . $total . '</strong></td>';
            echo '<td><strong>100%</strong></td>';
            echo '</tr>';
            echo '</tbody></table>';
            echo '</div>';
            echo '</div>';
            
            // Tabel Detail Tanggal
            echo '<h5 class="mt-4">Histori Kehadiran</h5>';
            echo '<table class="table table-bordered">';
            echo '<thead><tr><th>Status</th><th>Tanggal</th></tr></thead>';
            echo '<tbody>';
            
            // Tampilkan detail untuk setiap status non-hadir
            foreach (['Terlambat', 'Sakit', 'Izin', 'Alfa'] as $status) {
                if (!empty($detail[$status])) {
                    $first_row = true;
                    foreach ($detail[$status] as $tanggal) {
                        echo '<tr>';
                        if ($first_row) {
                            $rowspan = count($detail[$status]);
                            echo '<td rowspan="' . $rowspan . '">' . $status . '</td>';
                            $first_row = false;
                        }
                        echo '<td>' . date('d F Y', strtotime($tanggal)) . '</td>';
                        echo '</tr>';
                    }
                }
            }
            
            // Jika tidak ada data kehadiran non-hadir
            if (empty($detail['Terlambat']) && empty($detail['Sakit']) && 
                empty($detail['Izin']) && empty($detail['Alfa'])) {
                echo '<tr><td colspan="2" class="text-center">Tidak ada histori kehadiran untuk periode ini</td></tr>';
            }
            
            echo '</tbody></table>';
            
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
            
            // Tombol cetak - gunakan format yang compatible dengan cetak.php
            $semester_id_cetak = $_GET['semester_id'] ?? '';
            $tgl_awal_cetak = '';
            $tgl_akhir_cetak = '';
            
            if ($mode == 'semester') {
                $semester_info = $koneksi->query("SELECT * FROM semester WHERE id = $semester_id_cetak")->fetch_assoc();
                $tgl_awal_cetak = $semester_info['tgl_mulai'] ?? '';
                $tgl_akhir_cetak = $semester_info['tgl_selesai'] ?? '';
            } elseif ($mode == 'bulan') {
                $tgl_awal_cetak = $_GET['dari_tanggal'] ?? '';
                $tgl_akhir_cetak = $_GET['sampai_tanggal'] ?? '';
            } elseif ($mode == 'rentang') {
                $tgl_awal_cetak = $_GET['dari'] ?? '';
                $tgl_akhir_cetak = $_GET['sampai'] ?? '';
            }
            
            // Redirect ke cetak.php dengan format yang benar
            $cetak_url = "cetak.php?kelas_id={$siswa_id}&semester_id={$semester_id_cetak}&tgl_awal={$tgl_awal_cetak}&tgl_akhir={$tgl_akhir_cetak}&mode=siswa";
            
            echo '<a href="' . $cetak_url . '" 
                  class="btn btn-success" target="_blank">Cetak</a>';
            
            echo '</div></div>';
        }
    } else {
        echo '<div class="alert alert-warning">Tidak ditemukan siswa dengan nama "' . htmlspecialchars($cari) . '"</div>';
    }
}
?>

<?php require_once '../includes/footer.php'; ?>
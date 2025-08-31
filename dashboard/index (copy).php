<?php
// Set zona waktu (sesuaikan dengan lokasi Anda)
date_default_timezone_set('Asia/Jakarta');
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../includes/functions.php';
check_login();
include_once '../config.php';

// Inisialisasi statistik
$statistics = [
    'siswa' => 0,
    'kelas' => 0,
    'absen_hari_ini' => 0,
    'absen_minggu_ini' => 0
];

// Hitung jumlah siswa
$sql = "SELECT COUNT(*) AS total FROM siswa";
$result = $koneksi->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $statistics['siswa'] = $row['total'];
}

// Hitung jumlah kelas
$sql = "SELECT COUNT(*) AS total FROM kelas";
$result = $koneksi->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $statistics['kelas'] = $row['total'];
}

// Hitung absensi hari ini
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) AS total FROM absensi WHERE tanggal = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param('s', $today);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $statistics['absen_hari_ini'] = $row['total'];
}

// Hitung absensi minggu ini
$start_week = date('Y-m-d', strtotime('monday this week'));
$end_week = date('Y-m-d', strtotime('sunday this week'));
$sql = "SELECT COUNT(*) AS total FROM absensi WHERE tanggal BETWEEN ? AND ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param('ss', $start_week, $end_week);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $statistics['absen_minggu_ini'] = $row['total'];
}

// Ambil 10 absensi terbaru
$sql = "SELECT a.id, a.tanggal, a.status, s.nama AS nama_siswa, k.nama_kelas 
        FROM absensi a
        JOIN siswa s ON a.siswa_id = s.id
        JOIN kelas k ON s.kelas_id = k.id
        ORDER BY a.tanggal DESC, a.id DESC
        LIMIT 10";
$absensi_terbaru = $koneksi->query($sql);

// Ambil data absensi harian dalam minggu ini untuk grafik
$sql = "SELECT tanggal, COUNT(*) AS jumlah FROM absensi 
        WHERE tanggal BETWEEN ? AND ? 
        GROUP BY tanggal ORDER BY tanggal";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param('ss', $start_week, $end_week);
$stmt->execute();
$result = $stmt->get_result();
$absensi_harian = [];
while ($row = $result->fetch_assoc()) {
    $absensi_harian[] = $row;
}
?>
<?php include '../includes/header.php'; ?>
<div class="container mt-4">
    <h2 class="mb-4">Dashboard</h2>

    <!-- Welcome Card -->
    <div class="card mb-4 shadow-sm rounded">
        <div class="card-body bg-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h5 class="card-title mb-1">Selamat datang, <?= htmlspecialchars($_SESSION['user']['nama']) ?>!</h5>
                    <p class="card-text mb-0">
                        Anda login sebagai <span class="badge bg-primary"><?= ucfirst($_SESSION['user']['role']) ?></span>
                    </p>
                </div>
                <div class="text-end">
                    <p class="mb-0"><?= date('l, d F Y') ?></p>
                    <small>Sistem Absensi Siswa</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary shadow-sm h-100 rounded-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Siswa</h6>
                        <h3><?= $statistics['siswa'] ?></h3>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="../siswa/" class="text-white small text-decoration-none">Lihat semua siswa</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow-sm h-100 rounded-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Kelas</h6>
                        <h3><?= $statistics['kelas'] ?></h3>
                    </div>
                    <i class="fas fa-chalkboard fa-2x opacity-75"></i>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="../kelas/" class="text-white small text-decoration-none">Lihat semua kelas</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info shadow-sm h-100 rounded-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Absen Hari Ini</h6>
                        <h3><?= $statistics['absen_hari_ini'] ?></h3>
                    </div>
                    <i class="fas fa-clipboard-check fa-2x opacity-75"></i>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="../absen/" class="text-white small text-decoration-none">Input absen hari ini</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning shadow-sm h-100 rounded-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Absen Minggu Ini</h6>
                        <h3><?= $statistics['absen_minggu_ini'] ?></h3>
                    </div>
                    <i class="fas fa-calendar-week fa-2x opacity-75"></i>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="../rekap/kelas.php" class="text-white small text-decoration-none">Lihat rekap</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Absensi Minggu Ini -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm rounded-3">
                <div class="card-header">
                    <i class="fas fa-chart-line me-2"></i>Statistik Absensi Minggu Ini
                </div>
                <div class="card-body">
                    <canvas id="absensiChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm rounded-3">
                <div class="card-header">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../siswa/tambah.php" class="btn btn-outline-primary text-start">
                            <i class="fas fa-user-plus me-2"></i> Tambah Siswa
                        </a>
                        <a href="../kelas/tambah.php" class="btn btn-outline-success text-start">
                            <i class="fas fa-plus-circle me-2"></i> Tambah Kelas
                        </a>
                        <a href="../absen/" class="btn btn-outline-info text-start">
                            <i class="fas fa-clipboard-list me-2"></i> Input Absen
                        </a>
                        <a href="../siswa/import.php" class="btn btn-outline-warning text-start">
                            <i class="fas fa-file-import me-2"></i> Import Siswa
                        </a>
                        <a href="../rekap/kelas.php" class="btn btn-outline-secondary text-start">
                            <i class="fas fa-eye me-2"></i> Lihat Rekap
                        </a>
                        <a href="../absen/absensi_persiswa.php" class="btn btn-outline-primary text-start">
                            <i class="fas fa-user-check me-2"></i> Absensi Per Siswa
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="card shadow-sm rounded-3">
                <div class="card-header">
                    <i class="fas fa-server me-2"></i>Sistem Status
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Database
                            <span class="badge bg-success">Online</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Versi Aplikasi
                            <span class="badge bg-info">v1.0.0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            IP Address
                            <span><?= $_SERVER['REMOTE_ADDR'] ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Absensi Terbaru -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm rounded-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-clipboard-list me-2"></i>Absensi Terbaru</span>
                    <a href="../absen/" class="btn btn-sm btn-primary">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($absensi_terbaru && $absensi_terbaru->num_rows > 0): ?>
                                    <?php while ($row = $absensi_terbaru->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                            <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                            <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                            <td>
                                                <span class="badge rounded-pill
                                                    <?= $row['status'] === 'Hadir' ? 'bg-success' : '' ?>
                                                    <?= $row['status'] === 'Sakit' ? 'bg-warning text-dark' : '' ?>
                                                    <?= $row['status'] === 'Izin' ? 'bg-info' : '' ?>
                                                    <?= $row['status'] === 'Alfa' ? 'bg-danger' : '' ?>
                                                    <?= $row['status'] === 'Terlambat' ? 'bg-secondary' : '' ?>">
                                                    <?= $row['status'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Tidak ada data absensi</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js "></script>
<script>
    const ctx = document.getElementById('absensiChart').getContext('2d');
    const labels = <?= json_encode(array_column($absensi_harian, 'tanggal')) ?>;
    const data = <?= json_encode(array_column($absensi_harian, 'jumlah')) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Absensi',
                data: data,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>
<?php include '../includes/footer.php'; ?>
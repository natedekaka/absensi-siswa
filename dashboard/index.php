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

// === ANALISIS GENDER (sesuai format: "Laki-laki" / "Perempuan") ===
$sql = "SELECT jenis_kelamin, COUNT(*) AS jumlah FROM siswa GROUP BY jenis_kelamin";
$result = $koneksi->query($sql);
$gender_stats = ['L' => 0, 'P' => 0];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['jenis_kelamin'] === 'Laki-laki') {
            $gender_stats['L'] = $row['jumlah'];
        } elseif ($row['jenis_kelamin'] === 'Perempuan') {
            $gender_stats['P'] = $row['jumlah'];
        }
        // Abaikan nilai lain (jika ada)
    }
}

// === ANALISIS PER KELAS (dengan "Laki-laki" / "Perempuan") ===
$sql = "
    SELECT 
        k.id AS kelas_id,
        k.nama_kelas,
        COUNT(s.id) AS total_siswa,
        SUM(CASE WHEN s.jenis_kelamin = 'Laki-laki' THEN 1 ELSE 0 END) AS laki,
        SUM(CASE WHEN s.jenis_kelamin = 'Perempuan' THEN 1 ELSE 0 END) AS perempuan
    FROM kelas k
    LEFT JOIN siswa s ON k.id = s.kelas_id
    GROUP BY k.id, k.nama_kelas
    ORDER BY k.nama_kelas
";
$kelas_stats = $koneksi->query($sql);
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
<!-- Pastikan header.php sudah memuat Bootstrap 5.3+, Font Awesome, dan jQuery jika diperlukan -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    :root {
        --whatsapp-green: #25D366;
        --whatsapp-dark: #128C7E;
        --whatsapp-light: #ECE5DD;
        --whatsapp-gray: #546E7A;
        --whatsapp-white: #FFFFFF;
    }
    .bg-whatsapp-green { background-color: var(--whatsapp-green) !important; }
    .text-whatsapp-green { color: var(--whatsapp-green) !important; }
    .bg-whatsapp-dark { background-color: var(--whatsapp-dark) !important; }
    .bg-whatsapp-light { background-color: var(--whatsapp-light) !important; }
    .bg-whatsapp-gray { background-color: var(--whatsapp-gray) !important; }
    .btn-outline-whatsapp {
        color: var(--whatsapp-green);
        border-color: var(--whatsapp-green);
    }
    .btn-outline-whatsapp:hover {
        background-color: var(--whatsapp-green);
        color: var(--whatsapp-white);
    }
    .card-footer-whatsapp {
        background-color: rgba(0,0,0,.15);
    }
    .gender-card {
        transition: transform 0.2s;
    }
    .gender-card:hover {
        transform: translateY(-3px);
    }
</style>

<div class="container mt-4">
    <h2 class="mb-4 text-whatsapp-dark">Dashboard</h2>

    <!-- Card Selamat Datang -->
    <div class="card mb-4 shadow-sm rounded-3">
        <div class="card-body bg-whatsapp-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h5 class="card-title mb-1 text-whatsapp-dark">Selamat datang, <?= htmlspecialchars($_SESSION['user']['nama']) ?>! 👋</h5>
                    <p class="card-text mb-0 text-whatsapp-dark">
                        Anda login sebagai <span class="badge bg-whatsapp-dark text-white"><?= ucfirst($_SESSION['user']['role']) ?></span>
                    </p>
                </div>
                <div class="text-end text-whatsapp-dark">
                    <p class="mb-0"><?= date('l, d F Y') ?></p>
                    <small>Sistem Absensi Siswa</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Utama -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-whatsapp-green shadow-sm h-100 rounded-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Siswa</h6>
                        <h3><?= $statistics['siswa'] ?></h3>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
                <div class="card-footer card-footer-whatsapp border-0">
                    <a href="../siswa/" class="text-white small text-decoration-none">Lihat semua siswa</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-whatsapp-dark shadow-sm h-100 rounded-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Kelas</h6>
                        <h3><?= $statistics['kelas'] ?></h3>
                    </div>
                    <i class="fas fa-chalkboard fa-2x opacity-75"></i>
                </div>
                <div class="card-footer card-footer-whatsapp border-0">
                    <a href="../kelas/" class="text-white small text-decoration-none">Lihat semua kelas</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-whatsapp-green shadow-sm h-100 rounded-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Absen Hari Ini</h6>
                        <h3><?= $statistics['absen_hari_ini'] ?></h3>
                    </div>
                    <i class="fas fa-clipboard-check fa-2x opacity-75"></i>
                </div>
                <div class="card-footer card-footer-whatsapp border-0">
                    <a href="../absen/" class="text-white small text-decoration-none">Input absen hari ini</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-whatsapp-dark shadow-sm h-100 rounded-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Absen Minggu Ini</h6>
                        <h3><?= $statistics['absen_minggu_ini'] ?></h3>
                    </div>
                    <i class="fas fa-calendar-week fa-2x opacity-75"></i>
                </div>
                <div class="card-footer card-footer-whatsapp border-0">
                    <a href="../rekap/kelas.php" class="text-white small text-decoration-none">Lihat rekap</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ANALISIS GENDER & PER KELAS -->
    <div class="row g-3 mb-4">
        <!-- Gender -->
        <div class="col-md-6">
            <div class="card gender-card shadow-sm rounded-3 h-100">
                <div class="card-header bg-whatsapp-dark text-white">
                    <i class="fas fa-venus-mars me-2"></i>Analisis Gender Siswa
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-around align-items-center text-center">
                        <div>
                            <div class="display-6 text-primary"><i class="fas fa-male"></i></div>
                            <h5 class="mt-2">Laki-laki</h5>
                            <p class="display-6 mb-0"><?= $gender_stats['L'] ?></p>
                        </div>
                        <div class="vr"></div>
                        <div>
                            <div class="display-6 text-danger"><i class="fas fa-female"></i></div>
                            <h5 class="mt-2">Perempuan</h5>
                            <p class="display-6 mb-0"><?= $gender_stats['P'] ?></p>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <small class="text-muted">Total: <?= $statistics['siswa'] ?> siswa</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Per Kelas -->
        <div class="col-md-6">
            <div class="card shadow-sm rounded-3 h-100">
                <div class="card-header bg-whatsapp-dark text-white d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-school me-2"></i>Analisis Per Kelas</span>
                    <a href="../kelas/" class="btn btn-sm bg-whatsapp-green text-white">Detail</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kelas</th>
                                    <th>Total</th>
                                    <th>L</th>
                                    <th>P</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($kelas_stats && $kelas_stats->num_rows > 0): ?>
                                    <?php while ($row = $kelas_stats->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                            <td><strong><?= $row['total_siswa'] ?></strong></td>
                                            <td><?= $row['laki'] ?></td>
                                            <td><?= $row['perempuan'] ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted">Tidak ada data kelas</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik & Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm rounded-3">
                <div class="card-header bg-whatsapp-dark text-white">
                    <i class="fas fa-chart-line me-2"></i>Statistik Absensi Minggu Ini
                </div>
                <div class="card-body">
                    <canvas id="absensiChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4 shadow-sm rounded-3">
                <div class="card-header bg-whatsapp-dark text-white">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../siswa/tambah.php" class="btn btn-outline-whatsapp text-start">
                            <i class="fas fa-user-plus me-2"></i> Tambah Siswa
                        </a>
                        <a href="../kelas/tambah.php" class="btn btn-outline-whatsapp text-start">
                            <i class="fas fa-plus-circle me-2"></i> Tambah Kelas
                        </a>
                        <a href="../absen/" class="btn btn-outline-whatsapp text-start">
                            <i class="fas fa-clipboard-list me-2"></i> Input Absen
                        </a>
                        <a href="../siswa/import.php" class="btn btn-outline-whatsapp text-start">
                            <i class="fas fa-file-import me-2"></i> Import Siswa
                        </a>
                        <a href="../rekap/kelas.php" class="btn btn-outline-whatsapp text-start">
                            <i class="fas fa-eye me-2"></i> Lihat Rekap
                        </a>
                        <a href="../absen/absensi_persiswa.php" class="btn btn-outline-whatsapp text-start">
                            <i class="fas fa-user-check me-2"></i> Absensi Per Siswa
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm rounded-3">
                <div class="card-header bg-whatsapp-dark text-white">
                    <i class="fas fa-server me-2"></i>Sistem Status
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Database
                            <span class="badge bg-whatsapp-green text-white">Online</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Versi Aplikasi
                            <span class="badge bg-whatsapp-green text-white">v1.0.0</span>
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

    <!-- Absensi Terbaru -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm rounded-3">
                <div class="card-header d-flex justify-content-between align-items-center bg-whatsapp-dark text-white">
                    <span><i class="fas fa-clipboard-list me-2"></i>Absensi Terbaru</span>
                    <a href="../absen/" class="btn btn-sm bg-whatsapp-green text-white">Lihat Semua</a>
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
                                                    <?= $row['status'] === 'Hadir' ? 'bg-success text-white' : '' ?>
                                                    <?= $row['status'] === 'Sakit' ? 'bg-warning text-dark' : '' ?>
                                                    <?= $row['status'] === 'Izin' ? 'bg-info text-white' : '' ?>
                                                    <?= $row['status'] === 'Alfa' ? 'bg-danger text-white' : '' ?>
                                                    <?= $row['status'] === 'Terlambat' ? 'bg-whatsapp-gray text-white' : '' ?>">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('absensiChart').getContext('2d');
    // Format label menjadi hari (Sen, Sel, dst)
    const labels = <?= json_encode(array_map(fn($d) => date('D', strtotime($d)), array_column($absensi_harian, 'tanggal'))) ?>;
    const data = <?= json_encode(array_column($absensi_harian, 'jumlah')) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Absensi',
                data: data,
                borderColor: 'var(--whatsapp-green)',
                backgroundColor: 'rgba(37, 211, 102, 0.2)',
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
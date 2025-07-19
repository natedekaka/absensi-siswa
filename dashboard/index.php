<?php
// Set zona waktu (sesuaikan dengan lokasi Anda)
date_default_timezone_set('Asia/Jakarta');
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/functions.php';
check_login(); // Pastikan fungsi check_login ada di functions.php

include_once '../config.php'; // Koneksi ke database

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
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <h2>Dashboard</h2>

    <!-- Welcome Card -->
    <div class="card mb-4">
        <div class="card-body bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Selamat datang, <?= htmlspecialchars($_SESSION['user']['nama']) ?>!</h5>
                    <p class="card-text">
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
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Siswa</h5>
                            <h2><?= $statistics['siswa'] ?></h2>
                        </div>
                        <i class="fas fa-users display-4"></i>
                    </div>
                    <a href="../siswa/" class="text-white small">Lihat semua siswa</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Kelas</h5>
                            <h2><?= $statistics['kelas'] ?></h2>
                        </div>
                        <i class="fas fa-chalkboard display-4"></i>
                    </div>
                    <a href="../kelas/" class="text-white small">Lihat semua kelas</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Absen Hari Ini</h5>
                            <h2><?= $statistics['absen_hari_ini'] ?></h2>
                        </div>
                        <i class="fas fa-clipboard-check display-4"></i>
                    </div>
                    <a href="../absen/" class="text-white small">Input absen hari ini</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Absen Minggu Ini</h5>
                            <h2><?= $statistics['absen_minggu_ini'] ?></h2>
                        </div>
                        <i class="fas fa-calendar-week display-4"></i>
                    </div>
                    <a href="../rekap/kelas.php" class="text-white small">Lihat rekap</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Absensi Terbaru -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Absensi Terbaru</span>
                    <a href="../absen/" class="btn btn-sm btn-primary">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
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
                                                <span class="badge 
                                                    <?= $row['status'] === 'Hadir' ? 'bg-success' : '' ?>
                                                    <?= $row['status'] === 'Sakit' ? 'bg-warning' : '' ?>
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

        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    Quick Actions
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
                        <!-- Tombol Baru: Absensi Per Siswa -->
                        <a href="../absen/absensi_persiswa.php" class="btn btn-outline-primary text-start">
                            <i class="fas fa-user-check me-2"></i> Absensi Per Siswa
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="card">
                <div class="card-header">
                    Sistem Status
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
</div>

<?php include '../includes/footer.php'; ?>
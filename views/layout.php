<?php
require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/Database.php';

initKonfigurasiSekolah(conn());
$sekolah = getKonfigurasiSekolah(conn());
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Sistem Absensi Siswa' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: <?= $sekolah['warna_primer'] ?? '#4f46e5' ?>;
            --secondary-color: <?= $sekolah['warna_sekunder'] ?? '#64748b' ?>;
        }
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%) !important;
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['user'])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>dashboard/">
                <?php if ($sekolah['logo'] && file_exists(__DIR__ . '/../assets/uploads/' . $sekolah['logo'])): ?>
                    <img src="<?= asset('uploads/' . $sekolah['logo']) ?>" alt="Logo" style="height: 30px; width: auto; margin-right: 8px;">
                <?php else: ?>
                    <i class="fas fa-school me-2"></i>
                <?php endif; ?>
                <span><?= htmlspecialchars($sekolah['nama_sekolah']) ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>dashboard/">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>absensi/">
                            <i class="fas fa-clipboard-check me-2"></i>Absensi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>absensi/barcode.php">
                            <i class="fas fa-qrcode me-2"></i>Absensi Barcode
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>siswa/">
                            <i class="fas fa-users me-2"></i>Siswa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>kelas/">
                            <i class="fas fa-door-open me-2"></i>Kelas
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-2"></i>Pengaturan
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>profil_sekolah.php"><i class="fas fa-building me-2"></i>Profil Sekolah</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>kenaikan/"><i class="fas fa-graduation-cap me-2"></i>Kenaikan Kelas & Kelulusan</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>tahun_ajaran/"><i class="fas fa-calendar me-2"></i>Tahun Ajaran</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>rekap/kelas.php"><i class="fas fa-chart-bar me-2"></i>Rekap Absensi</a></li>
                        </ul>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-white">
                        <i class="fas fa-user-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['user']['nama']) ?>
                        <span class="badge bg-success ms-1"><?= ucfirst($_SESSION['user']['role']) ?></span>
                    </span>
                    <a href="<?= BASE_URL ?>logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i><span class="d-none d-md-inline">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <div class="container-main">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger-custom alert-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success-custom alert-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </div>

    <footer class="footer-custom">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> Sistem Absensi Siswa</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?= $scripts ?? '' ?>
</body>
</html>

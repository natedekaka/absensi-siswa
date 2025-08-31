<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom CSS untuk palet warna ungu */
        :root {
            --purple-main: #6C5B7B;
            --purple-dark: #3F334E;
            --purple-light: #C8A2C8;
            --purple-accent: #A16A9E;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f5f0f7; /* Latar belakang lebih lembut */
        }
        .container {
            flex: 1;
        }
        footer {
            margin-top: auto;
            padding: 20px 0;
            background-color: var(--purple-light);
            color: var(--purple-dark);
        }
        .navbar-toggler {
            border: none;
        }
        .brand-text {
            color: white;
            font-weight: bold;
            margin-right: 15px;
        }
        .user-info {
            color: var(--purple-light);
            margin-right: 15px;
        }
        .chart-container {
            width: 400px;
            height: 400px;
            margin: 20px auto;
        }
        .navbar {
            background-color: var(--purple-dark) !important;
        }
        .nav-link, .navbar-brand {
            color: var(--purple-light) !important;
        }
        .nav-link:hover, .navbar-brand:hover {
            color: #fff !important;
        }
        .dropdown-menu {
            background-color: var(--purple-dark);
            border: 1px solid var(--purple-main);
        }
        .dropdown-item {
            color: var(--purple-light);
        }
        .dropdown-item:hover {
            background-color: var(--purple-main);
            color: #fff;
        }
        .btn-outline-light {
            color: var(--purple-light);
            border-color: var(--purple-light);
        }
        .btn-outline-light:hover {
            background-color: var(--purple-light);
            color: var(--purple-dark) !important;
        }
        .badge.bg-light.text-dark {
            background-color: var(--purple-light) !important;
            color: var(--purple-dark) !important;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="../dashboard/">
            <i class="fas fa-book-reader me-2"></i>Absensi Siswa
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarCollapse">
            <?php if (isset($_SESSION['user'])): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../siswa/">
                            <i class="fas fa-users me-1"></i>Siswa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../kelas/">
                            <i class="fas fa-chalkboard me-1"></i>Kelas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../absen/">
                            <i class="fas fa-clipboard-check me-1"></i>Absensi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../absen/absensi_persiswa.php">
                            <i class="fas fa-user-check me-1"></i>Absensi Per Siswa
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="rekapDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar me-1"></i>Rekap
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="rekapDropdown">
                            <li><a class="dropdown-item" href="../rekap/siswa.php">Per Siswa</a></li>
                            <li><a class="dropdown-item" href="../rekap/kelas.php">Per Kelas</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="importDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-file-import me-1"></i>Import
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="importDropdown">
                            <li><a class="dropdown-item" href="../siswa/import.php">Import Siswa</a></li>
                            <li><a class="dropdown-item" href="../kelas/import.php">Import Kelas</a></li>
                        </ul>
                    </li>
                </ul>
            <?php endif; ?>

            <div class="d-flex align-items-center">
                <div class="brand-text d-none d-lg-block">
                    MGMP Informatika
                </div>

                <?php if (isset($_SESSION['user'])): ?>
                    <div class="user-info">
                        <i class="fas fa-user-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['user']['nama']) ?>
                        <span class="badge bg-light text-dark ms-1"><?= ucfirst($_SESSION['user']['role']) ?></span>
                    </div>
                    <a href="../logout.php" class="btn btn-outline-light ms-2">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                <?php else: ?>
                    <a href="../login.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
    <div class="container mt-4">
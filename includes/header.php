
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            flex: 1;
        }
        footer {
            margin-top: auto;
            padding: 20px 0;
            background-color: #f8f9fa;
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
            color: white;
            margin-right: 15px;
        }
        .chart-container {
            width: 400px;
            height: 400px;
            margin: 20px auto;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
                    <!-- 🔹 Menu Baru: Absensi Per Siswa -->
                    <li class="nav-item">
                        <a class="nav-link" href="../absen/absensi_persiswa.php">
                            <i class="fas fa-user-check me-1"></i>Absensi Per Siswa
                        </a>
                    </li>
                    <!-- Dropdown Rekap -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="rekapDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar me-1"></i>Rekap
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="rekapDropdown">
                            <li><a class="dropdown-item" href="../rekap/siswa.php">Per Siswa</a></li>
                            <li><a class="dropdown-item" href="../rekap/kelas.php">Per Kelas</a></li>
                        </ul>
                    </li>
                    <!-- Dropdown Import -->
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
                <!-- MGMP Informatika -->
                <div class="brand-text d-none d-lg-block">
                    MGMP Informatika
                </div>

                <!-- User Info & Logout -->
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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Siswa</title>
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --whatsapp-green: #25D366;
            --whatsapp-dark: #128C7E;
            --whatsapp-light: #ECE5DD;
            --whatsapp-gray: #546E7A;
            --whatsapp-white: #FFFFFF;
        }
        body {
            background-color: var(--whatsapp-light);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .navbar {
            background-color: var(--whatsapp-dark) !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .navbar-brand,
        .nav-link,
        .user-info,
        .brand-text {
            color: var(--whatsapp-white) !important;
        }
        .nav-link:hover,
        .navbar-brand:hover {
            color: var(--whatsapp-green) !important;
        }
        .dropdown-menu {
            background-color: var(--whatsapp-dark);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .dropdown-item {
            color: var(--whatsapp-white);
            padding: 0.5rem 1rem;
        }
        .dropdown-item:hover {
            background-color: var(--whatsapp-green);
            color: var(--whatsapp-white) !important;
        }
        .btn-outline-light {
            color: var(--whatsapp-white);
            border-color: var(--whatsapp-white);
        }
        .btn-outline-light:hover {
            background-color: var(--whatsapp-green);
            color: var(--whatsapp-white) !important;
            border-color: var(--whatsapp-green);
        }
        .badge-role {
            background-color: var(--whatsapp-green);
            color: var(--whatsapp-white);
            font-size: 0.75rem;
            padding: 0.35em 0.5em;
            border-radius: 0.35rem;
        }
        footer {
            margin-top: auto;
            padding: 1.5rem 0;
            background-color: var(--whatsapp-dark);
            color: var(--whatsapp-white);
            text-align: center;
        }
        .nav-icon {
            width: 1.25rem;
            text-align: center;
            margin-right: 0.5rem;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold" href="../dashboard/">
            <i class="fas fa-book-reader me-2"></i>
            <span>Absensi Siswa</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
            aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarCollapse">
            <?php if (isset($_SESSION['user'])): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/">
                            <i class="fas fa-tachometer-alt nav-icon"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../siswa/">
                            <i class="fas fa-users nav-icon"></i> Siswa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../kelas/">
                            <i class="fas fa-chalkboard nav-icon"></i> Kelas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../absen/">
                            <i class="fas fa-clipboard-check nav-icon"></i> Absensi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../absen/absensi_persiswa.php">
                            <i class="fas fa-user-check nav-icon"></i> Absensi Per Siswa
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="rekapDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-chart-bar nav-icon"></i> Rekap
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../rekap/siswa.php"><i class="fas fa-user me-2"></i> Per Siswa</a></li>
                            <li><a class="dropdown-item" href="../rekap/kelas.php"><i class="fas fa-chalkboard me-2"></i> Per Kelas</a></li>
                            <li><a class="dropdown-item" href="../rekap/rekap_per_tanggal_siswa.php"><i class="fas fa-calendar-day me-2"></i> Per Tanggal</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="importDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-import nav-icon"></i> Import
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../siswa/import.php"><i class="fas fa-users me-2"></i> Import Siswa</a></li>
                            <li><a class="dropdown-item" href="../kelas/import.php"><i class="fas fa-chalkboard me-2"></i> Import Kelas</a></li>
                        </ul>
                    </li>
                </ul>
            <?php endif; ?>

            <div class="d-flex align-items-center gap-2">
                <div class="brand-text d-none d-md-block text-white fw-semibold">
                    MGMP Informatika
                </div>

                <?php if (isset($_SESSION['user'])): ?>
                    <div class="d-flex align-items-center text-white">
                        <i class="fas fa-user-circle me-1"></i>
                        <span><?= htmlspecialchars($_SESSION['user']['nama']) ?></span>
                        <span class="badge-role ms-1"><?= ucfirst($_SESSION['user']['role']) ?></span>
                    </div>
                    <a href="../logout.php" class="btn btn-outline-light btn-sm d-flex align-items-center">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom CSS untuk palet warna WhatsApp */
        :root {
            --whatsapp-green: #25D366; /* Warna hijau utama WhatsApp */
            --whatsapp-dark: #128C7E; /* Hijau gelap untuk navbar */
            --whatsapp-light: #ECE5DD; /* Latar belakang terang ala WhatsApp */
            --whatsapp-gray: #546E7A; /* Abu-abu untuk teks sekunder */
            --whatsapp-white: #FFFFFF; /* Putih untuk aksen */
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--whatsapp-light); /* Latar belakang terang */
        }
        .container {
            flex: 1;
        }
        footer {
            margin-top: auto;
            padding: 20px 0;
            background-color: var(--whatsapp-dark);
            color: var(--whatsapp-white);
        }
        .navbar-toggler {
            border: none;
        }
        .brand-text {
            color: var(--whatsapp-white);
            font-weight: bold;
            margin-right: 15px;
        }
        .user-info {
            color: var(--whatsapp-white);
            margin-right: 15px;
        }
        .chart-container {
            width: 400px;
            height: 400px;
            margin: 20px auto;
        }
        .navbar {
            background-color: var(--whatsapp-dark) !important;
        }
        .nav-link, .navbar-brand {
            color: var(--whatsapp-white) !important;
        }
        .nav-link:hover, .navbar-brand:hover {
            color: var(--whatsapp-green) !important;
        }
        .dropdown-menu {
            background-color: var(--whatsapp-dark);
            border: 1px solid var(--whatsapp-gray);
        }
        .dropdown-item {
            color: var(--whatsapp-white);
        }
        .dropdown-item:hover {
            background-color: var(--whatsapp-green);
            color: var(--whatsapp-white);
        }
        .btn-outline-light {
            color: var(--whatsapp-white);
            border-color: var(--whatsapp-white);
        }
        .btn-outline-light:hover {
            background-color: var(--whatsapp-green);
            color: var(--whatsapp-white) !important;
        }
        .badge.bg-light.text-dark {
            background-color: var(--whatsapp-green) !important;
            color: var(--whatsapp-white) !important;
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
                            <li><a class="dropdown-item" href="../rekap/rekap_per_tanggal_siswa.php">Per Tanggal</a></li>
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
                    
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
    <div class="container mt-4">
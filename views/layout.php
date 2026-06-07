<?php
require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/Database.php';

initKonfigurasiSekolah(conn());
$sekolah = getKonfigurasiSekolah(conn());

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$page_section = $current_dir !== 'absensi-siswa' ? $current_dir : '';

function navActive($section, $file = '') {
    global $current_dir, $current_page;
    if ($file) {
        return ($current_dir === $section && $current_page === $file) ? 'active' : '';
    }
    return $current_dir === $section ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="<?= $sekolah['warna_primer'] ?? '#3B82F6' ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    <title><?= $title ?? 'Sistem Absensi Siswa' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        :root {
            --primary-color: <?= $sekolah['warna_primer'] ?? '#3B82F6' ?>;
            --secondary-color: <?= $sekolah['warna_sekunder'] ?? '#64748B' ?>;
        }
        .sidebar .sidebar-nav a.active {
            background: var(--primary-color) !important;
        }
        .stat-card-modern.primary { background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); }
        .stat-card-modern.success { background: linear-gradient(135deg, #10B981 0%, #059669 100%); }
        .stat-card-modern.warning { background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); }
        .stat-card-modern.danger { background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); }
    </style>
</head>
<body class="bg-[var(--color-bg)]">

<?php if (isset($_SESSION['user'])): ?>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo flex items-center gap-3">
        <?php if ($sekolah['logo'] && file_exists(__DIR__ . '/../assets/uploads/' . $sekolah['logo'])): ?>
            <img src="<?= asset('uploads/' . $sekolah['logo']) ?>" alt="Logo" class="w-9 h-9 rounded-xl object-contain">
        <?php else: ?>
            <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center">
                <i class="fas fa-school text-white text-lg"></i>
            </div>
        <?php endif; ?>
        <span class="text-white font-bold text-sm truncate"><?= htmlspecialchars($sekolah['nama_sekolah']) ?></span>
    </div>

    <nav class="sidebar-nav mt-2">
        <?php if (has_role('admin', 'guru', 'wali_kelas')): ?>
        <div class="sidebar-section-label">Utama</div>
        <a href="<?= BASE_URL ?>dashboard/" class="<?= navActive('dashboard') ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <?php endif; ?>

        <?php if (has_role('admin')): ?>
        <a href="<?= BASE_URL ?>absensi/" class="<?= navActive('absensi') && $current_page === 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-clipboard-check"></i> Absensi
        </a>
        <a href="<?= BASE_URL ?>absensi/barcode.php" class="<?= $current_page === 'barcode.php' && $current_dir === 'absensi' ? 'active' : '' ?>">
            <i class="fas fa-qrcode"></i> Scan Barcode
        </a>
        <?php endif; ?>

        <?php if (has_role('admin', 'guru', 'wali_kelas')): ?>
        <a href="<?= BASE_URL ?>absensi/mapel.php" class="<?= $current_page === 'mapel.php' && $current_dir === 'absensi' ? 'active' : '' ?>">
            <i class="fas fa-book-open"></i> Absen Mapel
        </a>
        <?php endif; ?>

        <?php if (has_role('admin', 'guru', 'wali_kelas')): ?>
        <div class="sidebar-section-label">Data</div>
        <a href="<?= BASE_URL ?>siswa/" class="<?= navActive('siswa') && $current_page === 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Siswa
        </a>
        <?php endif; ?>
        <?php if (has_role('admin')): ?>
        <a href="<?= BASE_URL ?>siswa/barcode.php" class="<?= $current_page === 'barcode.php' && $current_dir === 'siswa' ? 'active' : '' ?>">
            <i class="fas fa-barcode"></i> Kartu Siswa
        </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>siswa/riwayat.php" class="<?= $current_page === 'riwayat.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i> Riwayat Absensi
        </a>

        <?php if (has_role('admin')): ?>
        <a href="<?= BASE_URL ?>kelas/" class="<?= navActive('kelas') ? 'active' : '' ?>">
            <i class="fas fa-door-open"></i> Kelas
        </a>
        <?php endif; ?>

        <div class="sidebar-section-label">Laporan</div>
        <?php if (has_role('admin')): ?>
        <a href="<?= BASE_URL ?>rekap/kelas.php" class="<?= $current_page === 'kelas.php' && $current_dir === 'rekap' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i> Rekap Absensi
        </a>
        <?php endif; ?>
        <?php if (has_role('admin', 'guru', 'wali_kelas')): ?>
        <a href="<?= BASE_URL ?>rekap/mapel.php" class="<?= $current_page === 'mapel.php' && $current_dir === 'rekap' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i> Rekap Mapel
        </a>
        <?php endif; ?>

        <?php if (has_role('admin')): ?>
        <div class="sidebar-section-label">Pengaturan</div>
        <a href="<?= BASE_URL ?>users/" class="<?= navActive('users') ? 'active' : '' ?>">
            <i class="fas fa-users-cog"></i> Pengguna
        </a>
        <a href="<?= BASE_URL ?>profil_sekolah.php" class="<?= $current_page === 'profil_sekolah.php' ? 'active' : '' ?>">
            <i class="fas fa-building"></i> Profil Sekolah
        </a>
        <a href="<?= BASE_URL ?>mapel/" class="<?= navActive('mapel') ? 'active' : '' ?>">
            <i class="fas fa-book"></i> Mata Pelajaran
        </a>
        <a href="<?= BASE_URL ?>kelas/guru.php" class="<?= $current_dir === 'kelas' && $current_page === 'guru.php' ? 'active' : '' ?>">
            <i class="fas fa-chalkboard-teacher"></i> Guru Kelas
        </a>
        <a href="<?= BASE_URL ?>siswa/orang_tua.php" class="<?= $current_dir === 'siswa' && $current_page === 'orang_tua.php' ? 'active' : '' ?>">
            <i class="fas fa-user-friends"></i> Orang Tua Siswa
        </a>
        <a href="<?= BASE_URL ?>kenaikan/" class="<?= navActive('kenaikan') ? 'active' : '' ?>">
            <i class="fas fa-graduation-cap"></i> Kenaikan Kelas
        </a>
        <a href="<?= BASE_URL ?>tahun_ajaran/" class="<?= navActive('tahun_ajaran') ? 'active' : '' ?>">
            <i class="fas fa-calendar"></i> Tahun Ajaran
        </a>
        <?php endif; ?>
    </nav>
</aside>

<!-- Main Content -->
<div class="main-content">
    <!-- Topbar -->
    <header class="topbar flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-gray-700 p-1 -ml-1">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="hidden lg:flex text-xs text-gray-400 gap-1">
                <span class="font-medium text-gray-600"><?= htmlspecialchars($sekolah['nama_sekolah']) ?></span>
                <span>/</span>
                <span><?= $title ?? 'Dashboard' ?></span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="hidden sm:block text-right">
                <div class="text-sm font-semibold text-gray-800" id="topbarClock"></div>
                <div class="text-xs text-gray-400"><?= date('l, d F Y') ?></div>
            </div>
            <div class="flex items-center gap-2 pl-4 border-l border-gray-200">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">
                        <?= strtoupper(substr($_SESSION['user']['nama'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="hidden md:block">
                        <div class="text-sm font-medium text-gray-800 leading-tight"><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'User') ?></div>
                        <div class="text-xs text-gray-400"><?= ucfirst($_SESSION['user']['role'] ?? '') ?></div>
                    </div>
                </div>
                <button id="darkModeToggle" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition" title="Toggle Dark Mode">
                    <i class="fas fa-moon"></i>
                </button>
                <a href="<?= BASE_URL ?>logout.php" class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition" title="Keluar">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Content -->
    <div class="flex-1 p-6 lg:p-8">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-modern alert-danger-modern mb-4">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span><?= $_SESSION['error'] ?></span>
                <button onclick="this.parentElement.remove()" class="ml-auto text-lg leading-none opacity-60 hover:opacity-100">&times;</button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-modern alert-success-modern mb-4">
                <i class="fas fa-check-circle text-lg"></i>
                <span><?= $_SESSION['success'] ?></span>
                <button onclick="this.parentElement.remove()" class="ml-auto text-lg leading-none opacity-60 hover:opacity-100">&times;</button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </div>

    <!-- Footer -->
    <footer class="px-8 py-4 border-t border-gray-200 text-center text-xs text-gray-400">
        &copy; <?= date('Y') ?> Sistem Absensi Siswa
    </footer>
</div>

<script>
// Sidebar toggle
function toggleSidebar() {
    document.getElementById('sidebar')?.classList.toggle('open');
    document.getElementById('sidebarOverlay')?.classList.toggle('open');
}

// Dark mode
const darkModeToggle = document.getElementById('darkModeToggle');
if (darkModeToggle) {
    const savedTheme = localStorage.getItem('theme');
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (savedTheme === 'dark' || (!savedTheme && systemDark)) {
        document.documentElement.classList.add('dark');
        darkModeToggle.querySelector('i').classList.replace('fa-moon', 'fa-sun');
    }
    darkModeToggle.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark');
        const isDark = document.documentElement.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        const icon = darkModeToggle.querySelector('i');
        if (isDark) { icon.classList.replace('fa-moon', 'fa-sun'); }
        else { icon.classList.replace('fa-sun', 'fa-moon'); }
    });
}

// Clock
function updateTopbarClock() {
    const el = document.getElementById('topbarClock');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}
setInterval(updateTopbarClock, 1000);
updateTopbarClock();

// Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= BASE_URL ?>service-worker.js').catch(() => {});
}
</script>

<?php else: ?>
<!-- Not logged in — standalone pages like login use their own layout -->
<?= $content ?? '' ?>
<?php endif; ?>

<?= $scripts ?? '' ?>
</body>
</html>

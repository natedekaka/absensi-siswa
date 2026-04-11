<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Dashboard - Sistem Absensi Siswa';

$scripts = '<script>
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, "0");
    const minutes = String(now.getMinutes()).padStart(2, "0");
    const seconds = String(now.getSeconds()).padStart(2, "0");
    const time = hours + ":" + minutes + ":" + seconds;
    document.getElementById("clock").innerHTML = time;
    if(document.getElementById("clock-mobile")) {
        document.getElementById("clock-mobile").innerHTML = time;
    }
}
setInterval(updateClock, 1000);
updateClock();
</script>';

ob_start();

$today = date('Y-m-d');
$semester_aktif = conn()->query("SELECT * FROM semester WHERE is_active = 1 LIMIT 1")->fetch_assoc();
$semester_id = $_GET['semester_id'] ?? ($semester_aktif['id'] ?? '');

// Stats
$stats['siswa'] = conn()->query("SELECT COUNT(*) as total FROM siswa WHERE status = 'aktif' OR status IS NULL")->fetch_assoc()['total'];
$stats['kelas'] = conn()->query("SELECT COUNT(*) as total FROM kelas")->fetch_assoc()['total'];

$where_semester = $semester_id ? " AND semester_id = " . (int)$semester_id : "";
$stats['absen_hari_ini'] = conn()->query("SELECT COUNT(*) as total FROM absensi WHERE tanggal = '$today' $where_semester")->fetch_assoc()['total'];

// Status hari ini
$status_query = conn()->query("
    SELECT LOWER(status) as status, COUNT(*) as total 
    FROM absensi 
    WHERE tanggal = '$today' $where_semester
    GROUP BY LOWER(status)
");

$today_status = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0, 'Terlambat' => 0];
if ($status_query) {
    while ($row = $status_query->fetch_assoc()) {
        $status = ucfirst(strtolower($row['status']));
        if (isset($today_status[$status])) {
            $today_status[$status] = $row['total'];
        }
    }
}

$kehadiran_persen = $stats['siswa'] > 0 ? round(($today_status['Hadir'] / $stats['siswa']) * 100, 1) : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
    </h2>
    <div class="text-end d-none d-md-block">
        <div id="clock" class="fw-bold text-wa-dark" style="font-size: 1.5rem; font-family: monospace;"></div>
        <small class="text-muted"><?= date('l, d F Y') ?></small>
    </div>
</div>

<!-- Mobile clock -->
<div class="d-md-none mb-3 text-center">
    <div id="clock-mobile" class="fw-bold text-wa-dark" style="font-size: 1.25rem; font-family: monospace;"></div>
    <small class="text-muted"><?= date('l, d F Y') ?></small>
</div>

<form method="GET" class="card-custom p-3 mb-4">
    <div class="row g-3 align-items-center">
        <div class="col-auto">
            <label class="mb-0 fw-semibold"><i class="fas fa-filter me-2"></i>Filter Semester:</label>
        </div>
        <div class="col-auto">
            <select name="semester_id" class="form-select form-select-custom" onchange="this.form.submit()">
                <option value="">Semua Semester</option>
                <?php
                $semester_list = conn()->query("SELECT * FROM semester ORDER BY is_active DESC, tahun_ajaran_id DESC, semester ASC");
                while ($row = $semester_list->fetch_assoc()):
                ?>
                <option value="<?= $row['id'] ?>" <?= ($row['id'] == $semester_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['nama']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-custom p-4 text-white" style="background: linear-gradient(135deg, var(--wa-dark) 0%, #0d6e67 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small>Siswa</small>
                    <h2 class="mb-0"><?= $stats['siswa'] ?></h2>
                </div>
                <i class="fas fa-users fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom p-4 text-white" style="background: linear-gradient(135deg, var(--wa-green) 0%, #1ebe57 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small>Kelas</small>
                    <h2 class="mb-0"><?= $stats['kelas'] ?></h2>
                </div>
                <i class="fas fa-door-open fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom p-4 text-white" style="background: linear-gradient(135deg, var(--wa-dark) 0%, #0d6e67 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small>Kehadiran Hari Ini</small>
                    <h2 class="mb-0"><?= $kehadiran_persen ?>%</h2>
                </div>
                <i class="fas fa-clipboard-check fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom p-4 text-white" style="background: linear-gradient(135deg, var(--wa-green) 0%, #1ebe57 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small>Absen Terinput</small>
                    <h2 class="mb-0"><?= $stats['absen_hari_ini'] ?></h2>
                </div>
                <i class="fas fa-calendar-check fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-chart-pie me-2"></i>Detail Absensi Hari Ini
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col">
                        <div class="p-3 bg-success bg-opacity-10 rounded">
                            <h4 class="text-success mb-1"><?= $today_status['Hadir'] ?></h4>
                            <small class="text-muted">Hadir</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 bg-warning bg-opacity-10 rounded">
                            <h4 class="text-warning mb-1"><?= $today_status['Sakit'] ?></h4>
                            <small class="text-muted">Sakit</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 bg-info bg-opacity-10 rounded">
                            <h4 class="text-info mb-1"><?= $today_status['Izin'] ?></h4>
                            <small class="text-muted">Izin</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 bg-danger bg-opacity-10 rounded">
                            <h4 class="text-danger mb-1"><?= $today_status['Alfa'] ?></h4>
                            <small class="text-muted">Alfa</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 bg-secondary bg-opacity-10 rounded">
                            <h4 class="text-secondary mb-1"><?= $today_status['Terlambat'] ?></h4>
                            <small class="text-muted">Terlambat</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-bolt me-2"></i>Aksi Cepat
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>absensi/" class="btn btn-wa-primary text-start">
                        <i class="fas fa-clipboard-check me-2"></i>Input Absensi
                    </a>
                    <a href="<?= BASE_URL ?>siswa/" class="btn btn-wa-primary text-start">
                        <i class="fas fa-users me-2"></i>Kelola Siswa
                    </a>
                    <a href="<?= BASE_URL ?>kelas/" class="btn btn-wa-primary text-start">
                        <i class="fas fa-door-open me-2"></i>Kelola Kelas
                    </a>
                    <a href="<?= BASE_URL ?>rekap/kelas/" class="btn btn-wa-primary text-start">
                        <i class="fas fa-chart-bar me-2"></i>Lihat Rekap
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-history me-2"></i>Absensi Terbaru
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
$recent = conn()->query("
    SELECT a.tanggal, a.status, s.nama, k.nama_kelas 
    FROM absensi a
    JOIN siswa s ON a.siswa_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE 1=1 $where_semester
    ORDER BY a.id DESC LIMIT 10
");
                            
                            if ($recent && $recent->num_rows > 0):
                                while ($row = $recent->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= date('d/m', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($row['status']) ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center text-muted">Belum ada absensi</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

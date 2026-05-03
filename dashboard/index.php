<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Dashboard - Sistem Absensi Siswa';

$scripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
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

// Period filter
$period = $_GET['period'] ?? 7;
if ($period === 'custom') {
    $tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-d', strtotime('-7 days'));
    $tgl_akhir = $_GET['tgl_akhir'] ?? $today;
} else {
    $days_ago = (int)$period;
    $tgl_awal = date('Y-m-d', strtotime("-$days_ago days"));
    $tgl_akhir = $today;
}

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

// Chart data - 7 days
$days = [];
$hadir_data = [];
$sakit_data = [];
$izin_data = [];
$alfa_data = [];

$num_days = (int)((strtotime($tgl_akhir) - strtotime($tgl_awal)) / (60*60*24)) + 1;
$days = [];
$hadir_data = [];
$sakit_data = [];
$izin_data = [];
$alfa_data = [];

for ($i = $num_days - 1; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("+$i days", strtotime($tgl_awal)));
    $days[] = date('d/M', strtotime($tgl));
    
    $q = conn()->query("
        SELECT LOWER(status) as status, COUNT(*) as total 
        FROM absensi 
        WHERE tanggal = '$tgl' $where_semester
        GROUP BY LOWER(status)
    ");
    
    $data = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alfa' => 0];
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $s = strtolower($r['status']);
            if (isset($data[$s])) $data[$s] = (int)$r['total'];
        }
    }
    $hadir_data[] = $data['hadir'];
    $sakit_data[] = $data['sakit'];
    $izin_data[] = $data['izin'];
    $alfa_data[] = $data['alfa'];
}

// Pie chart per kelas (dalam range tanggal)
$where_tgl = " AND a.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'";
$kelas_pie = conn()->query("
    SELECT k.nama_kelas, 
           COALESCE(SUM(CASE WHEN LOWER(a.status) = 'hadir' THEN 1 ELSE 0 END), 0) as hadir,
           COALESCE(SUM(CASE WHEN LOWER(a.status) IN ('sakit', 'izin', 'alfa', 'terlambat') THEN 1 ELSE 0 END), 0) as tidak_hadir
    FROM kelas k
    LEFT JOIN siswa s ON s.kelas_id = k.id
    LEFT JOIN absensi a ON a.siswa_id = s.id $where_tgl $where_semester
    GROUP BY k.id, k.nama_kelas
    ORDER BY k.nama_kelas
");
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
        <div class="col-md-2">
            <label class="mb-0 fw-semibold"><i class="fas fa-filter me-2"></i>Filter:</label>
        </div>
        <div class="col-md-2">
            <select name="period" class="form-select form-select-custom" onchange="toggleDateRange(this.value)">
                <option value="7" <?= ($period ?? 7) == 7 ? 'selected' : '' ?>>7 Hari</option>
                <option value="30" <?= ($period ?? 7) == 30 ? 'selected' : '' ?>>30 Hari</option>
                <option value="90" <?= ($period ?? 7) == 90 ? 'selected' : '' ?>>90 Hari</option>
                <option value="custom" <?= ($period ?? 7) == 'custom' ? 'selected' : '' ?>>Custom</option>
            </select>
        </div>
        <div class="col-md-3" id="tgl_awal_group" style="display: <?= ($period ?? 7) == 'custom' ? 'block' : 'none' ?>;">
            <input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?? date('Y-m-d', strtotime('-7 days')) ?>">
        </div>
        <div class="col-md-3" id="tgl_akhir_group" style="display: <?= ($period ?? 7) == 'custom' ? 'block' : 'none' ?>;">
            <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?? date('Y-m-d') ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-wa-primary w-100">
                <i class="fas fa-search me-1"></i> Filter
            </button>
        </div>
    </div>
    <div class="row g-3 align-items-center mt-2">
        <div class="col-auto">
            <label class="mb-0 fw-semibold"><i class="fas fa-school me-2"></i>Semester:</label>
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

<script>
function toggleDateRange(value) {
    document.getElementById('tgl_awal_group').style.display = value === 'custom' ? 'block' : 'none';
    document.getElementById('tgl_akhir_group').style.display = value === 'custom' ? 'block' : 'none';
}
</script>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-custom p-4 text-white" style="background: var(--gradient-primary);">
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
        <div class="card-custom p-4 text-white" style="background: var(--gradient-primary);">
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
    <div class="col-md-8">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-chart-line me-2"></i>Tren Kehadiran 7 Hari Terakhir
            </div>
            <div class="card-body">
                <canvas id="chartLine" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-chart-pie me-2"></i>Persentase Hari Ini
            </div>
            <div class="card-body">
                <canvas id="chartPie"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-chart-bar me-2"></i>Kehadiran per Kelas Hari Ini
            </div>
            <div class="card-body">
                <canvas id="chartBarKelas" height="100"></canvas>
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
                    <a href="<?= BASE_URL ?>rekap/kelas.php" class="btn btn-wa-primary text-start">
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

$kelas_data = [];
if ($kelas_pie && $kelas_pie->num_rows > 0) {
    while ($k = $kelas_pie->fetch_assoc()) {
        $kelas_data[] = ['nama' => $k['nama_kelas'], 'hadir' => (int)$k['hadir'], 'tidak_hadir' => (int)$k['tidak_hadir']];
    }
}

$page_scripts = '
<script>
new Chart(document.getElementById("chartLine"), {
    type: "line",
    data: {
        labels: ' . json_encode($days) . ',
        datasets: [
            { label: "Hadir", data: ' . json_encode($hadir_data) . ', borderColor: "#28a745", backgroundColor: "rgba(40, 167, 69, 0.1)", fill: true, tension: 0.3 },
            { label: "Sakit", data: ' . json_encode($sakit_data) . ', borderColor: "#ffc107", backgroundColor: "rgba(255, 193, 7, 0.1)", fill: true, tension: 0.3 },
            { label: "Izin", data: ' . json_encode($izin_data) . ', borderColor: "#17a2b8", backgroundColor: "rgba(23, 162, 184, 0.1)", fill: true, tension: 0.3 },
            { label: "Alfa", data: ' . json_encode($alfa_data) . ', borderColor: "#dc3545", backgroundColor: "rgba(220, 53, 69, 0.1)", fill: true, tension: 0.3 }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: "bottom" } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById("chartPie"), {
    type: "doughnut",
    data: {
        labels: ["Hadir", "Sakit", "Izin", "Alfa", "Terlambat"],
        datasets: [{
            data: [' . $today_status['Hadir'] . ', ' . $today_status['Sakit'] . ', ' . $today_status['Izin'] . ', ' . $today_status['Alfa'] . ', ' . $today_status['Terlambat'] . '],
            backgroundColor: ["#28a745", "#ffc107", "#17a2b8", "#dc3545", "#6c757d"]
        }]
    },
    options: { responsive: true, plugins: { legend: { position: "bottom" } } }
});

new Chart(document.getElementById("chartBarKelas"), {
    type: "bar",
    data: {
        labels: ' . json_encode(array_column($kelas_data, 'nama')) . ',
        datasets: [
            { label: "Hadir", data: ' . json_encode(array_column($kelas_data, 'hadir')) . ', backgroundColor: "#28a745" },
            { label: "Tidak Hadir", data: ' . json_encode(array_column($kelas_data, 'tidak_hadir')) . ', backgroundColor: "#dc3545" }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: "bottom" } }, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
});
</script>';

$scripts .= $page_scripts;

require_once '../views/layout.php';

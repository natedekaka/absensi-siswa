<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin', 'guru', 'wali_kelas');

$title = 'Dashboard';

$scripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function updateClock() {
    const now = new Date();
    const time = now.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
    const el = document.getElementById("dashboardClock");
    if (el) el.textContent = time;
}
setInterval(updateClock, 1000);
updateClock();
</script>';

ob_start();

$today = date('Y-m-d');
$semester_aktif = conn()->query("SELECT * FROM semester WHERE is_active = 1 LIMIT 1")->fetch_assoc();
$semester_id = $_GET['semester_id'] ?? ($semester_aktif['id'] ?? '');

$period = $_GET['period'] ?? 7;
if ($period === 'custom') {
    $tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-d', strtotime('-7 days'));
    $tgl_akhir = $_GET['tgl_akhir'] ?? $today;
} else {
    $days_ago = (int)$period;
    $tgl_awal = date('Y-m-d', strtotime("-$days_ago days"));
    $tgl_akhir = $today;
}

$kelas_ids = get_accessible_kelas_ids();
$kelas_filter = !empty($kelas_ids) ? ' AND s.kelas_id IN (' . implode(',', array_map('intval', $kelas_ids)) . ')' : '';
$kelas_filter_single = !empty($kelas_ids) ? ' AND id IN (' . implode(',', array_map('intval', $kelas_ids)) . ')' : '';
$kelas_filter_k = !empty($kelas_ids) ? ' AND k.id IN (' . implode(',', array_map('intval', $kelas_ids)) . ')' : '';

$stats['siswa'] = conn()->query("SELECT COUNT(*) as total FROM siswa s WHERE (s.status = 'aktif' OR s.status IS NULL) $kelas_filter")->fetch_assoc()['total'];
$stats['kelas'] = conn()->query("SELECT COUNT(*) as total FROM kelas WHERE 1=1 $kelas_filter_single")->fetch_assoc()['total'];

$where_semester = $semester_id ? " AND semester_id = " . (int)$semester_id : "";
$stats['absen_hari_ini'] = conn()->query("SELECT COUNT(*) as total FROM absensi a JOIN siswa s ON a.siswa_id = s.id WHERE a.tanggal = '$today' $where_semester $kelas_filter")->fetch_assoc()['total'];

$status_query = conn()->query("
    SELECT LOWER(a.status) as status, COUNT(*) as total 
    FROM absensi a JOIN siswa s ON a.siswa_id = s.id
    WHERE a.tanggal = '$today' $where_semester $kelas_filter
    GROUP BY LOWER(a.status)
");

$today_status = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0, 'Terlambat' => 0];
if ($status_query) {
    while ($row = $status_query->fetch_assoc()) {
        $status = ucfirst(strtolower($row['status']));
        if (isset($today_status[$status])) $today_status[$status] = $row['total'];
    }
}

$kehadiran_persen = $stats['siswa'] > 0 ? round(($today_status['Hadir'] / $stats['siswa']) * 100, 1) : 0;

$num_days = (int)((strtotime($tgl_akhir) - strtotime($tgl_awal)) / (60*60*24)) + 1;

// Limit daily chart to max 31 titik data, aggregate weekly for longer periods
$use_weekly = $num_days > 31;
$days = []; $hadir_data = []; $sakit_data = []; $izin_data = []; $alfa_data = [];

// Single query — ganti N query per hari jadi 1 query
$chart_raw = [];
if ($use_weekly) {
    // Aggregasi per minggu
    $q = conn()->query("
        SELECT YEARWEEK(a.tanggal, 1) as week_key,
               MIN(a.tanggal) as week_start,
               LOWER(a.status) as status,
               COUNT(*) as total
        FROM absensi a JOIN siswa s ON a.siswa_id = s.id
        WHERE a.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir' $where_semester $kelas_filter
        GROUP BY YEARWEEK(a.tanggal, 1), LOWER(a.status)
        ORDER BY week_key ASC
    ");
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $week = $r['week_key'];
            $st = strtolower($r['status']);
            if (!isset($chart_raw[$week])) {
                $chart_raw[$week] = ['label' => date('d/M', strtotime($r['week_start'])), 'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alfa' => 0];
            }
            if (isset($chart_raw[$week][$st])) $chart_raw[$week][$st] = (int)$r['total'];
        }
    }
    foreach ($chart_raw as $w) {
        $days[] = $w['label'];
        $hadir_data[] = $w['hadir'];
        $sakit_data[] = $w['sakit'];
        $izin_data[] = $w['izin'];
        $alfa_data[] = $w['alfa'];
    }
} else {
    // Harian — 1 query untuk semua tanggal
    $q = conn()->query("
        SELECT a.tanggal, LOWER(a.status) as status, COUNT(*) as total
        FROM absensi a JOIN siswa s ON a.siswa_id = s.id
        WHERE a.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir' $where_semester $kelas_filter
        GROUP BY a.tanggal, LOWER(a.status)
        ORDER BY a.tanggal ASC
    ");
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $tgl = $r['tanggal'];
            $st = strtolower($r['status']);
            if (!isset($chart_raw[$tgl])) {
                $chart_raw[$tgl] = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alfa' => 0];
            }
            if (isset($chart_raw[$tgl][$st])) $chart_raw[$tgl][$st] = (int)$r['total'];
        }
    }
    for ($i = $num_days - 1; $i >= 0; $i--) {
        $tgl = date('Y-m-d', strtotime("+$i days", strtotime($tgl_awal)));
        $days[] = date('d/M', strtotime($tgl));
        $d = $chart_raw[$tgl] ?? ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alfa' => 0];
        $hadir_data[] = $d['hadir'];
        $sakit_data[] = $d['sakit'];
        $izin_data[] = $d['izin'];
        $alfa_data[] = $d['alfa'];
    }
}

$where_tgl = " AND a.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'";
$kelas_pie = conn()->query("
    SELECT k.nama_kelas, 
           COALESCE(SUM(CASE WHEN LOWER(a.status) = 'hadir' THEN 1 ELSE 0 END), 0) as hadir,
           COALESCE(SUM(CASE WHEN LOWER(a.status) IN ('sakit','izin','alfa','terlambat') THEN 1 ELSE 0 END), 0) as tidak_hadir
    FROM kelas k
    LEFT JOIN siswa s ON s.kelas_id = k.id
    LEFT JOIN absensi a ON a.siswa_id = s.id $where_tgl $where_semester
    WHERE 1=1 $kelas_filter_k
    GROUP BY k.id, k.nama_kelas
    ORDER BY k.nama_kelas
");
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-tachometer-alt mr-3 text-primary"></i>Dashboard
    </h2>
    <div class="text-right text-sm text-gray-500" id="dashboardClock">
        <span class="font-mono font-bold text-gray-700 text-base"></span>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-card mb-6">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1 block">
                <i class="fas fa-filter mr-1"></i>Periode
            </label>
            <select name="period" class="form-input-modern text-sm min-w-[130px]" onchange="toggleCustom(this.value)">
                <option value="7" <?= ($period ?? 7) == 7 ? 'selected' : '' ?>>7 Hari</option>
                <option value="30" <?= ($period ?? 7) == 30 ? 'selected' : '' ?>>30 Hari</option>
                <option value="90" <?= ($period ?? 7) == 90 ? 'selected' : '' ?>>90 Hari</option>
                <option value="custom" <?= ($period ?? 7) == 'custom' ? 'selected' : '' ?>>Custom</option>
            </select>
        </div>
        <div id="tglAwalGroup" class="<?= ($period ?? 7) == 'custom' ? '' : 'hidden' ?>">
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1 block">Dari</label>
            <input type="date" name="tgl_awal" class="form-input-modern text-sm" value="<?= $tgl_awal ?>">
        </div>
        <div id="tglAkhirGroup" class="<?= ($period ?? 7) == 'custom' ? '' : 'hidden' ?>">
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1 block">Sampai</label>
            <input type="date" name="tgl_akhir" class="form-input-modern text-sm" value="<?= $tgl_akhir ?>">
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1 block">
                <i class="fas fa-school mr-1"></i>Semester
            </label>
            <select name="semester_id" class="form-input-modern text-sm min-w-[160px]" onchange="this.form.submit()">
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
        <div class="flex-1 min-w-0"></div>
        <button type="submit" class="btn-modern btn-primary-modern">
            <i class="fas fa-search"></i> Tampilkan
        </button>
    </form>
</div>

<script>
function toggleCustom(val) {
    const show = val === 'custom';
    document.getElementById('tglAwalGroup').classList.toggle('hidden', !show);
    document.getElementById('tglAkhirGroup').classList.toggle('hidden', !show);
}
</script>

<!-- Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card-modern primary text-white">
        <div class="relative z-10">
            <div class="text-white/70 text-xs font-semibold uppercase tracking-wide">Siswa</div>
            <div class="text-3xl font-bold mt-1"><?= $stats['siswa'] ?></div>
        </div>
        <i class="stat-icon fas fa-users"></i>
    </div>
    <div class="stat-card-modern success text-white">
        <div class="relative z-10">
            <div class="text-white/70 text-xs font-semibold uppercase tracking-wide">Kelas</div>
            <div class="text-3xl font-bold mt-1"><?= $stats['kelas'] ?></div>
        </div>
        <i class="stat-icon fas fa-door-open"></i>
    </div>
    <div class="stat-card-modern primary text-white">
        <div class="relative z-10">
            <div class="text-white/70 text-xs font-semibold uppercase tracking-wide">Kehadiran Hari Ini</div>
            <div class="text-3xl font-bold mt-1"><?= $kehadiran_persen ?>%</div>
        </div>
        <i class="stat-icon fas fa-clipboard-check"></i>
    </div>
    <div class="stat-card-modern success text-white">
        <div class="relative z-10">
            <div class="text-white/70 text-xs font-semibold uppercase tracking-wide">Absen Terinput</div>
            <div class="text-3xl font-bold mt-1"><?= $stats['absen_hari_ini'] ?></div>
        </div>
        <i class="stat-icon fas fa-calendar-check"></i>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 card-modern">
        <div class="card-modern-header">
            <i class="fas fa-chart-line mr-2 text-primary"></i>Tren Kehadiran
        </div>
        <div class="card-modern-body">
            <canvas id="chartLine" height="120"></canvas>
        </div>
    </div>
    <div class="card-modern">
        <div class="card-modern-header">
            <i class="fas fa-chart-pie mr-2 text-primary"></i>Hari Ini
        </div>
        <div class="card-modern-body">
            <canvas id="chartPie"></canvas>
        </div>
    </div>
</div>

<!-- Per-Kelas Chart -->
<div class="card-modern mb-6">
    <div class="card-modern-header">
        <i class="fas fa-chart-bar mr-2 text-primary"></i>Kehadiran per Kelas
    </div>
    <div class="card-modern-body">
        <canvas id="chartBarKelas" height="100"></canvas>
    </div>
</div>

<!-- Bottom Grid: Quick Actions + Recent -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card-modern">
        <div class="card-modern-header">
            <i class="fas fa-bolt mr-2 text-primary"></i>Aksi Cepat
        </div>
        <div class="card-modern-body flex flex-col gap-2.5">
            <a href="<?= BASE_URL ?>absensi/" class="btn-modern btn-primary-modern justify-start">
                <i class="fas fa-clipboard-check"></i> Input Absensi
            </a>
            <a href="<?= BASE_URL ?>siswa/" class="btn-modern btn-primary-modern justify-start">
                <i class="fas fa-users"></i> Kelola Siswa
            </a>
            <a href="<?= BASE_URL ?>kelas/" class="btn-modern btn-primary-modern justify-start">
                <i class="fas fa-door-open"></i> Kelola Kelas
            </a>
            <a href="<?= BASE_URL ?>rekap/kelas.php" class="btn-modern btn-primary-modern justify-start">
                <i class="fas fa-chart-bar"></i> Lihat Rekap
            </a>
        </div>
    </div>
    <div class="card-modern">
        <div class="card-modern-header">
            <i class="fas fa-history mr-2 text-primary"></i>Absensi Terbaru
        </div>
        <div class="card-modern-body p-0">
            <div class="overflow-x-auto">
                <table class="table-modern">
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
                            WHERE 1=1 $where_semester $kelas_filter
                            ORDER BY a.id DESC LIMIT 10
                        ");
                        if ($recent && $recent->num_rows > 0):
                            while ($row = $recent->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="text-sm"><?= date('d/m', strtotime($row['tanggal'])) ?></td>
                            <td class="font-medium"><?= htmlspecialchars($row['nama']) ?></td>
                            <td class="text-gray-500 text-sm"><?= htmlspecialchars($row['nama_kelas']) ?></td>
                            <td>
                                <span class="badge-modern badge-<?= strtolower($row['status']) ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="4" class="text-center text-gray-400 py-8">Belum ada absensi</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
            { label: "Hadir", data: ' . json_encode($hadir_data) . ', borderColor: "#10B981", backgroundColor: "rgba(16,185,129,0.1)", fill: true, tension: 0.4 },
            { label: "Sakit", data: ' . json_encode($sakit_data) . ', borderColor: "#F59E0B", backgroundColor: "rgba(245,158,11,0.1)", fill: true, tension: 0.4 },
            { label: "Izin", data: ' . json_encode($izin_data) . ', borderColor: "#3B82F6", backgroundColor: "rgba(59,130,246,0.1)", fill: true, tension: 0.4 },
            { label: "Alfa", data: ' . json_encode($alfa_data) . ', borderColor: "#EF4444", backgroundColor: "rgba(239,68,68,0.1)", fill: true, tension: 0.4 }
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
            backgroundColor: ["#10B981", "#F59E0B", "#3B82F6", "#EF4444", "#6B7280"]
        }]
    },
    options: { responsive: true, plugins: { legend: { position: "bottom" } } }
});

new Chart(document.getElementById("chartBarKelas"), {
    type: "bar",
    data: {
        labels: ' . json_encode(array_column($kelas_data, 'nama')) . ',
        datasets: [
            { label: "Hadir", data: ' . json_encode(array_column($kelas_data, 'hadir')) . ', backgroundColor: "#10B981", borderRadius: 4 },
            { label: "Tidak Hadir", data: ' . json_encode(array_column($kelas_data, 'tidak_hadir')) . ', backgroundColor: "#EF4444", borderRadius: 4 }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: "bottom" } }, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
});
</script>';

$scripts .= $page_scripts;

require_once '../views/layout.php';

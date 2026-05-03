<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Riwayat Absensi - Sistem Absensi Siswa';

$scripts = '
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
';

ob_start();

$siswa_id = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : 0;
$semester_selected = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : 0;

// Auto-set date range if semester selected
if ($semester_selected > 0) {
    $semester_data = conn()->query("SELECT tgl_mulai, tgl_selesai FROM semester WHERE id = $semester_selected")->fetch_assoc();
    if ($semester_data) {
        $tgl_awal = $_GET['tgl_awal'] ?? $semester_data['tgl_mulai'];
        $tgl_akhir = $_GET['tgl_akhir'] ?? $semester_data['tgl_selesai'];
    }
} else {
    $tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
    $tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');
}

if ($siswa_id > 0) {
    $siswa = conn()->query("SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.id = $siswa_id")->fetch_assoc();
    
    // Use semester_id from GET if provided, otherwise use active semester
    if ($semester_selected > 0) {
        $semester_id = $semester_selected;
    } else {
        $semester_aktif = conn()->query("SELECT id FROM semester WHERE is_active = 1 LIMIT 1")->fetch_assoc();
        $semester_id = $semester_aktif['id'] ?? null;
    }
    $where_semester = $semester_id ? " AND semester_id = " . (int)$semester_id : "";
    
    $absensi = conn()->query("
        SELECT * FROM absensi 
        WHERE siswa_id = $siswa_id AND tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir' $where_semester
        ORDER BY tanggal DESC
    ");
    
    $stats = conn()->query("
        SELECT 
            SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN status = 'Alfa' THEN 1 ELSE 0 END) as alfa,
            COUNT(*) as total
        FROM absensi 
        WHERE siswa_id = $siswa_id AND tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir' $where_semester
    ")->fetch_assoc();
    
    $chart_data = conn()->query("
        SELECT tanggal, status FROM absensi 
        WHERE siswa_id = $siswa_id AND tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir' $where_semester
        ORDER BY tanggal ASC
    ");
    
    $daily_data = [];
    while ($c = $chart_data->fetch_assoc()) {
        $daily_data[$c['tanggal']] = $c['status'];
    }
    
    $total_days = (int)((strtotime($tgl_akhir) - strtotime($tgl_awal)) / (60*60*24)) + 1;
    $hadir_count = ($stats['hadir'] ?? 0) + ($stats['terlambat'] ?? 0) * 0.5;
    $kehadiran_persen = $total_days > 0 ? round(($hadir_count / $total_days) * 100, 1) : 0;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-history me-2"></i>Riwayat Absensi
    </h2>
    <div class="d-flex gap-2">
        <?php if ($siswa_id > 0): ?>
        <a href="export_riwayat.php?siswa_id=<?= $siswa_id ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&format=excel" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Excel
        </a>
        <a href="export_riwayat.php?siswa_id=<?= $siswa_id ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&format=pdf" class="btn btn-danger" target="_blank">
            <i class="fas fa-file-pdf me-2"></i>PDF
        </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Kembali
        </a>
    </div>
</div>

<form method="GET" class="card-custom p-3 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-semibold text-wa-dark">
                <i class="fas fa-user me-2"></i>Cari Siswa
            </label>
            <select name="siswa_id" id="selectSiswa" class="form-select form-select-custom" required>
                <option value="">-- Ketik nama siswa --</option>
                <?php
                $siswa_list = conn()->query("SELECT s.id, s.nama, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.status = 'aktif' OR s.status IS NULL ORDER BY s.nama");
                while ($row = $siswa_list->fetch_assoc()):
                ?>
                <option value="<?= $row['id'] ?>" <?= ($siswa_id == $row['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['nama'] . ' (' . ($row['nama_kelas'] ?? 'No Kelas') . ')') ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold text-wa-dark">
                <i class="fas fa-calendar me-2"></i>Semester
            </label>
            <select name="semester_id" id="selectSemester" class="form-select form-select-custom" onchange="updateDateRange(this.value)">
                <option value="0">-- Pilih Semester --</option>
                <?php
                $semester_list = conn()->query("SELECT * FROM semester ORDER BY tahun_ajaran_id DESC, semester ASC");
                while ($s = $semester_list->fetch_assoc()):
                ?>
                <option value="<?= $s['id'] ?>" <?= ($semester_selected == $s['id']) ? 'selected' : '' ?>
                        data-mulai="<?= $s['tgl_mulai'] ?>" data-selesai="<?= $s['tgl_selesai'] ?>">
                    <?= htmlspecialchars($s['nama']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold text-wa-dark">
                <i class="fas fa-calendar-alt me-2"></i>Tanggal Awal
            </label>
            <input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold text-wa-dark">
                <i class="fas fa-calendar-alt me-2"></i>Tanggal Akhir
            </label>
            <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-wa-primary w-100">
                <i class="fas fa-search me-1"></i>Filter
            </button>
        </div>
    </div>
</form>

<script>
function updateDateRange(semesterId) {
    if (!semesterId || semesterId == '0') return;
    
    const selectedOption = document.querySelector(`#selectSemester option[value="${semesterId}"]`);
    if (selectedOption) {
        const tglMulai = selectedOption.dataset.mulai;
        const tglSelesai = selectedOption.dataset.selesai;
        
        if (tglMulai) document.querySelector('input[name="tgl_awal"]').value = tglMulai;
        if (tglSelesai) document.querySelector('input[name="tgl_akhir"]').value = tglSelesai;
    }
}

// Heatmap rendering
document.addEventListener('DOMContentLoaded', function() {
    const heatmapGrid = document.getElementById('heatmapGrid');
    if (!heatmapGrid) return;
    
    const dailyData = <?= json_encode($daily_data) ?>;
    const tglAwal = '<?= $tgl_awal ?>';
    const tglAkhir = '<?= $tgl_akhir ?>';
    
    if (!tglAwal || !tglAkhir) return;
    
    const startDate = new Date(tglAwal);
    const endDate = new Date(tglAkhir);
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const dayShort = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    
    // Color mapping
    function getColor(status) {
        if (!status) return '#e5e7eb';
        const s = status.toLowerCase();
        if (s === 'hadir') return '#10b981';
        if (s === 'terlambat') return '#f59e0b';
        if (s === 'sakit' || s === 'izin') return '#3b82f6';
        if (s === 'alfa') return '#ef4444';
        return '#e5e7eb';
    }
    
    function getStatusText(status) {
        if (!status) return 'Tidak ada data';
        return status;
    }
    
    // Create heatmap grid
    // Layout: rows = days of week, columns = weeks
    const startDateAdjusted = new Date(startDate);
    const dayOfWeek = startDateAdjusted.getDay(); // 0=Sunday
    startDateAdjusted.setDate(startDateAdjusted.getDate() - dayOfWeek); // Go to Sunday of that week
    
    const totalDays = Math.ceil((endDate - startDateAdjusted) / (1000*60*60*24)) + 1;
    const weeks = Math.ceil(totalDays / 7);
    
    // Create container
    let html = '<div style="display: flex; gap: 5px;">';
    
    // Day labels on left
    html += '<div style="display: flex; flex-direction: column; gap: 3px; margin-right: 8px;">';
    for (let i = 0; i < 7; i++) {
        html += `<div style="height: 13px; font-size: 0.7rem; color: #6b7280; text-align: right; padding-right: 5px;">${dayShort[i]}</div>`;
    }
    html += '</div>';
    
    // Weeks columns
    for (let week = 0; week < weeks; week++) {
        html += '<div style="display: flex; flex-direction: column; gap: 3px;">';
        
        for (let day = 0; day < 7; day++) {
            const currentDate = new Date(startDateAdjusted);
            currentDate.setDate(startDateAdjusted.getDate() + (week * 7) + day);
            
            const dateStr = currentDate.toISOString().split('T')[0];
            const status = dailyData[dateStr] || null;
            const color = getColor(status);
            
            const isInRange = currentDate >= startDate && currentDate <= endDate;
            const display = isInRange ? 'block' : 'none';
            
            html += `<div title="${days[day]}, ${dateStr}: ${getStatusText(status)}" 
                        style="width: 13px; height: 13px; background: ${color}; border-radius: 2px; cursor: pointer; display: ${display};"
                        onmouseover="this.style.outline='2px solid #000'" 
                        onmouseout="this.style.outline='none'"></div>`;
        }
        
        html += '</div>';
    }
    
    html += '</div>';
    heatmapGrid.innerHTML = html;
});
</script>

<?php if ($siswa_id && $siswa): ?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-custom p-4">
            <div class="d-flex align-items-center">
                <div class="siswa-avatar <?= ($siswa['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'avatar-laki' : 'avatar-perempuan' ?> me-3">
                    <?= strtoupper(substr($siswa['nama'], 0, 1)) ?>
                </div>
                <div>
                    <h5 class="mb-1"><?= htmlspecialchars($siswa['nama']) ?></h5>
                    <small class="text-muted">
                        <i class="fas fa-door-open me-1"></i><?= htmlspecialchars($siswa['nama_kelas'] ?? 'Tidak ada kelas') ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 text-center" style="border-left: 4px solid #28a745;">
                    <h4 class="mb-0 text-success"><?= $stats['hadir'] ?? 0 ?></h4>
                    <small class="text-muted">Hadir</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 text-center" style="border-left: 4px solid #ffc107;">
                    <h4 class="mb-0 text-warning"><?= $stats['terlambat'] ?? 0 ?></h4>
                    <small class="text-muted">Terlambat</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 text-center" style="border-left: 4px solid #17a2b8;">
                    <h4 class="mb-0 text-info"><?= ($stats['sakit'] ?? 0) + ($stats['izin'] ?? 0) ?></h4>
                    <small class="text-muted">Sakit/Izin</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 text-center" style="border-left: 4px solid #dc3545;">
                    <h4 class="mb-0 text-danger"><?= $stats['alfa'] ?? 0 ?></h4>
                    <small class="text-muted">Alfa</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card-custom p-4">
            <h6 class="fw-bold text-wa-dark mb-3">
                <i class="fas fa-chart-pie me-2"></i>Progress Kehadiran
            </h6>
            <div class="progress" style="height: 25px; border-radius: 12px; background: #f1f5f9;">
                <div class="progress-bar <?= $kehadiran_persen >= 80 ? 'bg-success' : ($kehadiran_persen >= 60 ? 'bg-warning' : 'bg-danger') ?>" 
                     style="width: <?= $kehadiran_persen ?>%; border-radius: 12px; transition: width 0.5s ease; font-weight: 600;">
                    <?= $kehadiran_persen ?>%
                </div>
            </div>
            <small class="text-muted mt-2 d-block">
                Berdasarkan <?= $total_days ?> hari periode (Hadir + 50% Terlambat)
            </small>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card-custom p-4">
            <h6 class="fw-bold text-wa-dark mb-3">
                <i class="fas fa-calendar-alt me-2"></i>Kalender Kehadiran
            </h6>
            <div id="heatmapContainer" style="overflow-x: auto;">
                <div id="heatmapLegend" style="display: flex; gap: 15px; margin-bottom: 15px; font-size: 0.85rem;">
                    <span style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 12px; height: 12px; background: #10b981; border-radius: 2px;"></div> Hadir
                    </span>
                    <span style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 12px; height: 12px; background: #f59e0b; border-radius: 2px;"></div> Terlambat
                    </span>
                    <span style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 12px; height: 12px; background: #3b82f6; border-radius: 2px;"></div> Sakit/Izin
                    </span>
                    <span style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 12px; height: 12px; background: #ef4444; border-radius: 2px;"></div> Alfa
                    </span>
                    <span style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 12px; height: 12px; background: #e5e7eb; border-radius: 2px;"></div> Tidak Ada Data
                    </span>
                </div>
                <div id="heatmapGrid" style="display: inline-block;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-chart-line me-2"></i>Tren Kehadiran
            </div>
            <div class="card-body">
                <canvas id="chartTren" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card-custom">
    <div class="card-header-custom">
        <i class="fas fa-list me-2"></i>Detail Absensi
    </div>
    <div class="table-responsive" style="max-height: 400px;">
        <table class="table table-hover mb-0">
            <thead class="sticky-top">
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Hari</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
                if ($absensi && $absensi->num_rows > 0):
                    while ($row = $absensi->fetch_assoc()):
                        $hari = $days[date('l', strtotime($row['tanggal']))];
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td><?= $hari ?></td>
                    <td>
                        <span class="badge badge-<?= strtolower($row['status']) ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data absensi</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const dates = [];
const hadirData = [];
const tidakHadirData = [];
<?php
$num_days = (int)((strtotime($tgl_akhir) - strtotime($tgl_awal)) / (60*60*24)) + 1;
for ($i = 0; $i < $num_days; $i++) {
    $tgl = date('Y-m-d', strtotime("+$i days", strtotime($tgl_awal)));
    $status = $daily_data[$tgl] ?? '';
    echo "dates.push('" . date('d/M', strtotime($tgl)) . "');\n";
    echo "hadirData.push(" . ($status === 'Hadir' ? '1' : ($status === 'Terlambat' ? '0.5' : '0')) . ");\n";
    echo "tidakHadirData.push(" . (in_array($status, ['Sakit','Izin','Alfa']) ? '1' : '0') . ");\n";
}
?>
new Chart(document.getElementById('chartTren'), {
    type: 'line',
    data: {
        labels: dates,
        datasets: [{
            label: 'Kehadiran',
            data: hadirData,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            fill: true,
            tension: 0.3,
            stepped: false
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                max: 1,
                ticks: {
                    callback: function(value) {
                        if (value === 1) return 'Hadir';
                        if (value === 0.5) return 'Terlambat';
                        if (value === 0) return 'Tidak';
                        return '';
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<style>
/* Fix TomSelect dropdown z-index on desktop */
.ts-wrapper {
    z-index: 9999 !important;
}
.ts-wrapper .ts-dropdown {
    z-index: 10000 !important;
    position: absolute;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TomSelect !== 'undefined') {
        new TomSelect('#selectSiswa', {
            create: false,
            sortField: { field: 'text', direction: 'asc' },
            placeholder: 'Ketik nama siswa...',
            maxOptions: 100,
            allowEmptyOption: true,
            dropdownParent: 'body',
            onChange: function(value) {
                if (value) {
                    this.wrapper.closest('form').submit();
                }
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';
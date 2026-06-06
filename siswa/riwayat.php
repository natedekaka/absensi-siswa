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
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.default.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
';

ob_start();

$siswa_id = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : 0;
$semester_selected = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : 0;

$siswa_list = conn()->query("SELECT s.id, s.nama, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.status = 'aktif' OR s.status IS NULL ORDER BY s.nama ASC");
$semester_list = conn()->query("SELECT id, nama, tgl_mulai, tgl_selesai FROM semester ORDER BY tgl_mulai DESC");

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

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <h2 class="text-xl font-bold text-gray-800 dark:text-white">
        <i class="fas fa-history mr-3 text-primary"></i>Riwayat Absensi
    </h2>
    <div class="flex gap-2">
        <?php if ($siswa_id > 0): ?>
        <a href="export_riwayat.php?siswa_id=<?= $siswa_id ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&format=excel" class="btn-modern btn-success-modern text-sm">
            <i class="fas fa-file-excel mr-1"></i>Excel
        </a>
        <a href="export_riwayat.php?siswa_id=<?= $siswa_id ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&format=pdf" class="btn-modern bg-red-500 hover:bg-red-600 text-white rounded-xl px-4 py-2 text-sm font-semibold transition-all" target="_blank">
            <i class="fas fa-file-pdf mr-1"></i>PDF
        </a>
        <?php endif; ?>
        <a href="index.php" class="btn-modern btn-neutral-modern text-sm">
            <i class="fas fa-arrow-left mr-1"></i>Kembali
        </a>
    </div>
</div>

<form method="GET" class="card-modern p-4 mb-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
        <div class="md:col-span-1">
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">
                <i class="fas fa-user mr-2"></i>Cari Siswa
            </label>
            <select name="siswa_id" id="selectSiswa" class="form-input-modern w-full" required>
                <option value="">-- Ketik nama siswa --</option>
                <?php
                $siswa_list->data_seek(0);
                while ($row = $siswa_list->fetch_assoc()):
                ?>
                <option value="<?= $row['id'] ?>" <?= ($siswa_id == $row['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['nama'] . ' (' . ($row['nama_kelas'] ?? 'No Kelas') . ')') ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">
                <i class="fas fa-calendar mr-2"></i>Semester
            </label>
            <select name="semester_id" id="selectSemester" class="form-input-modern w-full" onchange="updateDateRange(this.value)">
                <option value="0">-- Pilih Semester --</option>
                <?php
                $semester_list->data_seek(0);
                while ($s = $semester_list->fetch_assoc()):
                ?>
                <option value="<?= $s['id'] ?>" <?= ($semester_selected == $s['id']) ? 'selected' : '' ?>
                        data-mulai="<?= $s['tgl_mulai'] ?>" data-selesai="<?= $s['tgl_selesai'] ?>">
                    <?= htmlspecialchars($s['nama']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">
                <i class="fas fa-calendar-alt mr-2"></i>Tanggal Awal
            </label>
            <input type="date" name="tgl_awal" class="form-input-modern w-full" value="<?= $tgl_awal ?>">
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">
                <i class="fas fa-calendar-alt mr-2"></i>Tanggal Akhir
            </label>
            <input type="date" name="tgl_akhir" class="form-input-modern w-full" value="<?= $tgl_akhir ?>">
        </div>
        <div class="md:col-span-4">
            <button type="submit" class="btn-modern btn-primary-modern w-full justify-center">
                <i class="fas fa-search mr-1"></i>Filter
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
function toggleCheckAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
    updateBulkDeleteButton();
}

function toggleSelectAll() {
    const checkAll = document.getElementById('checkAll');
    if (checkAll) checkAll.checked = !checkAll.checked;
    toggleCheckAll(checkAll);
}

function updateBulkDeleteButton() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const btn = document.getElementById('btnBulkDelete');
    const countSpan = document.getElementById('selectedCount');
    
    if (btn && countSpan) {
        countSpan.textContent = checked.length;
        btn.style.display = checked.length > 0 ? 'inline-block' : 'none';
    }
    
    // Update selected IDs
    const form = document.getElementById('formBulkDelete');
    const container = document.getElementById('selectedIds');
    if (form && container) {
        container.innerHTML = '';
        checked.forEach(cb => {
            container.innerHTML += `<input type="hidden" name="ids[]" value="${cb.value}">`;
        });
    }
}

function confirmBulkDelete() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    if (checked.length === 0) return;
    
    if (confirm(`Hapus ${checked.length} record absensi yang dipilih?`)) {
        document.getElementById('formBulkDelete').submit();
    }
}
</script>

<?php if ($siswa_id && $siswa): ?>
<div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
    <div class="md:col-span-4">
        <div class="card-modern p-4 flex items-center gap-4">
            <div class="avatar-modern <?= ($siswa['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'avatar-laki' : 'avatar-perempuan' ?> w-12 h-12 text-base shrink-0">
                <?= strtoupper(substr($siswa['nama'], 0, 1)) ?>
            </div>
            <div>
                <h5 class="font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($siswa['nama']) ?></h5>
                <small class="text-gray-400">
                    <i class="fas fa-door-open mr-1"></i><?= htmlspecialchars($siswa['nama_kelas'] ?? 'Tidak ada kelas') ?>
                </small>
            </div>
        </div>
    </div>
    <div class="md:col-span-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="card-modern p-3 text-center" style="border-left: 4px solid #10b981;">
                <h4 class="text-lg font-bold text-emerald-600 dark:text-emerald-400 mb-0"><?= $stats['hadir'] ?? 0 ?></h4>
                <small class="text-gray-400">Hadir</small>
            </div>
            <div class="card-modern p-3 text-center" style="border-left: 4px solid #f59e0b;">
                <h4 class="text-lg font-bold text-amber-500 dark:text-amber-400 mb-0"><?= $stats['terlambat'] ?? 0 ?></h4>
                <small class="text-gray-400">Terlambat</small>
            </div>
            <div class="card-modern p-3 text-center" style="border-left: 4px solid #3b82f6;">
                <h4 class="text-lg font-bold text-blue-500 dark:text-blue-400 mb-0"><?= ($stats['sakit'] ?? 0) + ($stats['izin'] ?? 0) ?></h4>
                <small class="text-gray-400">Sakit/Izin</small>
            </div>
            <div class="card-modern p-3 text-center" style="border-left: 4px solid #ef4444;">
                <h4 class="text-lg font-bold text-red-500 dark:text-red-400 mb-0"><?= $stats['alfa'] ?? 0 ?></h4>
                <small class="text-gray-400">Alfa</small>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <div class="card-modern p-4">
        <h6 class="font-bold text-gray-700 dark:text-white mb-3">
            <i class="fas fa-chart-pie mr-2"></i>Progress Kehadiran
        </h6>
        <div class="progress-modern" style="height: 25px; background: #f1f5f9; border-radius: 12px; overflow: hidden;">
            <div class="flex items-center justify-end pr-2 text-white text-xs font-semibold"
                 style="height: 100%; width: <?= $kehadiran_persen ?>%; border-radius: 12px; <?= $kehadiran_persen >= 80 ? 'background: #10b981;' : ($kehadiran_persen >= 60 ? 'background: #f59e0b;' : 'background: #ef4444;') ?>">
                <?= $kehadiran_persen ?>%
            </div>
        </div>
        <small class="text-gray-400 mt-2 block">
            Berdasarkan <?= $total_days ?> hari periode (Hadir + 50% Terlambat)
        </small>
    </div>
</div>

<div class="mb-4">
    <div class="card-modern p-4">
        <h6 class="font-bold text-gray-700 dark:text-white mb-3">
            <i class="fas fa-calendar-alt mr-2"></i>Kalender Kehadiran
        </h6>
        <div class="overflow-x-auto">
            <div class="flex gap-4 mb-4 text-sm" id="heatmapLegend">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm" style="background:#10b981"></span> Hadir
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm" style="background:#f59e0b"></span> Terlambat
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm" style="background:#3b82f6"></span> Sakit/Izin
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm" style="background:#ef4444"></span> Alfa
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm" style="background:#e5e7eb"></span> Tidak Ada Data
                </span>
            </div>
            <div id="heatmapGrid" class="inline-block"></div>
        </div>
    </div>
</div>

<div class="mb-4">
    <div class="card-modern">
        <div class="card-modern-header">
            <i class="fas fa-chart-line mr-2"></i>Tren Kehadiran
        </div>
        <div class="card-modern-body">
            <canvas id="chartTren" height="80"></canvas>
        </div>
    </div>
</div>

    <div class="card-modern overflow-hidden">
        <div class="card-modern-header flex items-center justify-between">
            <div class="font-semibold">
                <i class="fas fa-list mr-2"></i>Detail Absensi
            </div>
            <div class="flex gap-2">
                <button type="button" class="btn-modern bg-red-50 text-red-600 hover:bg-red-100 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all" id="btnBulkDelete" style="display:none" onclick="confirmBulkDelete()">
                    <i class="fas fa-trash mr-1"></i>Hapus Terpilih (<span id="selectedCount">0</span>)
                </button>
                <button type="button" class="btn-modern bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all" onclick="toggleSelectAll()">
                    <i class="fas fa-check-square mr-1"></i>Pilih Semua
                </button>
            </div>
        </div>
        <form id="formBulkDelete" action="bulk_delete.php" method="POST" style="display: none;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="siswa_id" value="<?= $siswa_id ?>">
            <input type="hidden" name="tgl_awal" value="<?= $tgl_awal ?>">
            <input type="hidden" name="tgl_akhir" value="<?= $tgl_akhir ?>">
            <div id="selectedIds"></div>
        </form>
        <div class="overflow-x-auto" style="max-height: 400px;">
            <table class="table-modern text-sm">
                <thead class="sticky top-0 z-10">
                    <tr>
                        <th class="text-center w-[40px]"><input type="checkbox" id="checkAll" onchange="toggleCheckAll(this)" class="accent-primary"></th>
                        <th class="text-center w-[60px]">No</th>
                        <th>Tanggal</th>
                        <th class="text-center">Hari</th>
                        <th class="text-center">Status</th>
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
                        <td class="text-center"><input type="checkbox" class="row-checkbox" value="<?= $row['id'] ?>" onchange="updateBulkDeleteButton()"></td>
                        <td class="text-center text-gray-400"><?= $no++ ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td class="text-center"><?= $hari ?></td>
                        <td class="text-center">
                            <span class="badge-modern badge-<?= strtolower($row['status']) ?> text-xs px-3 py-1">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center text-gray-400 py-8">Tidak ada data absensi</td></tr>
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
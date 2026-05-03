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
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');

if ($siswa_id > 0) {
    $siswa = conn()->query("SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.id = $siswa_id")->fetch_assoc();
    
    $semester_aktif = conn()->query("SELECT id FROM semester WHERE is_active = 1 LIMIT 1")->fetch_assoc();
    $semester_id = $semester_aktif['id'] ?? null;
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
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-history me-2"></i>Riwayat Absensi
    </h2>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Kembali
    </a>
</div>

<form method="GET" class="card-custom p-3 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TomSelect !== 'undefined') {
        new TomSelect('#selectSiswa', {
            create: false,
            sortField: { field: 'text', direction: 'asc' },
            placeholder: 'Ketik nama siswa...',
            maxOptions: 100,
            allowEmptyOption: true,
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
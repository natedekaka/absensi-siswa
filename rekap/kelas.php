<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Rekap Absensi - Sistem Absensi Siswa';

$semester_id = $_GET['semester_id'] ?? '';
$kelas_id = $_GET['kelas_id'] ?? '';
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');

ob_start();
?>

<style>
.filter-card {
    border: none;
    border-radius: 20px;
    background: white;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.filter-header {
    background: linear-gradient(135deg, var(--wa-dark) 0%, #0d6e67 100%);
    color: white;
    padding: 1.25rem 1.5rem;
    border-radius: 20px 20px 0 0;
}
.stat-card {
    border: none;
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s;
}
.stat-card:hover {
    transform: translateY(-5px);
}
.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.stat-hadir { background: rgba(25, 135, 84, 0.1); color: #198754; }
.stat-terlambat { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
.stat-sakit { background: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
.stat-izin { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
.stat-alfa { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
.table-rekap {
    border: none;
    border-radius: 16px;
    overflow: hidden;
}
.table-rekap thead {
    background: linear-gradient(135deg, var(--wa-dark) 0%, #0d6e67 100%);
    color: white;
}
.table-rekap tbody tr {
    transition: all 0.2s;
}
.table-rekap tbody tr:hover {
    background: var(--wa-light);
}
.rekap-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
}
.badge-hadir { background: rgba(25, 135, 84, 0.1); color: #198754; }
.badge-terlambat { background: rgba(255, 193, 7, 0.15); color: #b38600; }
.badge-sakit { background: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
.badge-izin { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
.badge-alfa { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
.progress-custom {
    height: 8px;
    border-radius: 10px;
    background: #e9ecef;
}
.progress-bar-hadir { background: linear-gradient(90deg, #25D366, #198754); }
</style>

<div class="d-flex align-items-center mb-4">
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-chart-pie me-2"></i>Rekap Absensi
    </h2>
</div>

<!-- Filter Section -->
<form method="GET">
    <div class="filter-card mb-4">
        <div class="filter-header d-flex align-items-center">
            <i class="fas fa-filter me-2"></i>
            <span class="fw-semibold">Filter Data</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted small">SEMESTER</label>
                    <select name="semester_id" class="form-select" required>
                        <option value="">Pilih Semester</option>
                        <?php
                        $semester = conn()->query("SELECT * FROM semester ORDER BY is_active DESC, tahun_ajaran_id DESC, semester ASC");
                        while ($row = $semester->fetch_assoc()):
                        ?>
                        <option value="<?= $row['id'] ?>" <?= ($row['id'] == $semester_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['nama']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted small">KELAS</label>
                    <select name="kelas_id" class="form-select">
                        <option value="">Semua Kelas</option>
                        <?php
                        $kelas = conn()->query("SELECT * FROM kelas ORDER BY nama_kelas");
                        while ($row = $kelas->fetch_assoc()):
                        ?>
                        <option value="<?= $row['id'] ?>" <?= ($row['id'] == $kelas_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['nama_kelas']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-muted small">TANGGAL AWAL</label>
                    <input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-muted small">TANGGAL AKHIR</label>
                    <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-wa-primary w-100">
                        <i class="fas fa-search me-1"></i> Tampilkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
if ($semester_id && $kelas_id):
    $kelas = conn()->query("SELECT nama_kelas,wali_kelas FROM kelas WHERE id = $kelas_id")->fetch_assoc();
    
    // Get statistics
    $stats = conn()->query("
        SELECT 
            SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN status = 'Alfa' THEN 1 ELSE 0 END) as alfa,
            COUNT(*) as total
        FROM absensi 
        WHERE semester_id = $semester_id 
        AND tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ")->fetch_assoc();

    $total_siswa = conn()->query("SELECT COUNT(*) as total FROM siswa WHERE kelas_id = $kelas_id")->fetch_assoc()['total'];
    $total_hari = (strtotime($tgl_akhir) - strtotime($tgl_awal)) / (60*60*24) + 1;
    $total_seharusnya = $total_siswa * $total_hari;
    $kehadiran_persen = $total_seharusnya > 0 ? round(($stats['hadir'] / $total_seharusnya) * 100, 1) : 0;

    // Get siswa data
    $siswa = conn()->query("
        SELECT s.id, s.nama, s.jenis_kelamin,
            SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN a.status = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN a.status = 'Alfa' THEN 1 ELSE 0 END) as alfa,
            COUNT(a.id) as total_absen
        FROM siswa s
        LEFT JOIN absensi a ON s.id = a.siswa_id 
            AND a.semester_id = $semester_id
            AND a.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
        WHERE s.kelas_id = $kelas_id
        GROUP BY s.id, s.nama, s.jenis_kelamin
        ORDER BY (alfa + sakit + izin) DESC, nama ASC
    ");
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="stat-card shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted d-block">Kehadiran</small>
                    <h3 class="mb-0 fw-bold text-success"><?= $kehadiran_persen ?>%</h3>
                </div>
                <div class="stat-icon stat-hadir">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="progress progress-custom mt-3">
                <div class="progress-bar progress-bar-hadir" style="width: <?= $kehadiran_persen ?>%"></div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted d-block">Hadir</small>
                    <h3 class="mb-0 fw-bold text-success"><?= $stats['hadir'] ?></h3>
                </div>
                <div class="stat-icon stat-hadir">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted d-block">Terlambat</small>
                    <h3 class="mb-0 fw-bold" style="color: #b38600"><?= $stats['terlambat'] ?></h3>
                </div>
                <div class="stat-icon stat-terlambat">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted d-block">Sakit</small>
                    <h3 class="mb-0 fw-bold text-info"><?= $stats['sakit'] ?></h3>
                </div>
                <div class="stat-icon stat-sakit">
                    <i class="fas fa-user-injured"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted d-block">Izin</small>
                    <h3 class="mb-0 fw-bold" style="color: #6f42c1"><?= $stats['izin'] ?></h3>
                </div>
                <div class="stat-icon stat-izin">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted d-block">Alfa</small>
                    <h3 class="mb-0 fw-bold text-danger"><?= $stats['alfa'] ?></h3>
                </div>
                <div class="stat-icon stat-alfa">
                    <i class="fas fa-user-times"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Card -->
<div class="card-custom mb-4 border-start border-4 border-success">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">
                <i class="fas fa-school me-2 text-success"></i>
                <?= htmlspecialchars($kelas['nama_kelas']) ?>
                <small class="text-muted">- <?= htmlspecialchars($kelas['wali_kelas'] ?? 'Belum ada wali kelas') ?></small>
            </h5>
            <small class="text-muted">
                <i class="fas fa-calendar me-1"></i>
                <?= date('d M Y', strtotime($tgl_awal)) ?> - <?= date('d M Y', strtotime($tgl_akhir)) ?>
                <span class="mx-2">|</span>
                <i class="fas fa-users me-1"></i><?= $total_siswa ?> Siswa
                <span class="mx-2">|</span>
                <i class="fas fa-calendar-day me-1"></i><?= $total_hari ?> Hari
            </small>
        </div>
    </div>
</div>

<!-- Chart Section -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-custom p-3" style="height: 300px">
            <h6 class="fw-bold mb-3"><i class="fas fa-chart-pie me-2"></i>Statistik</h6>
            <canvas id="pieChart"></canvas>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card-custom p-3" style="height: 300px">
            <h6 class="fw-bold mb-3"><i class="fas fa-chart-bar me-2"></i>Kehadiran per Hari</h6>
            <canvas id="barChart"></canvas>
        </div>
    </div>
</div>

<!-- Table Section -->
<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-rekap mb-0">
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px">No</th>
                    <th>Nama Siswa</th>
                    <th class="text-center"><span class="rekap-badge badge-hadir">Hadir</span></th>
                    <th class="text-center"><span class="rekap-badge badge-terlambat">Terlambat</span></th>
                    <th class="text-center"><span class="rekap-badge badge-sakit">Sakit</span></th>
                    <th class="text-center"><span class="rekap-badge badge-izin">Izin</span></th>
                    <th class="text-center"><span class="rekap-badge badge-alfa">Alfa</span></th>
                    <th class="text-center">% Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = $siswa->fetch_assoc()):
                    $persen = $total_hari > 0 ? round(($row['hadir'] / $total_hari) * 100, 1) : 0;
                    $persen_class = $persen >= 80 ? 'text-success' : ($persen >= 60 ? 'text-warning' : 'text-danger');
                ?>
                <tr>
                    <td class="text-center text-muted"><?= $no++ ?></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($row['nama']) ?></div>
                        <small class="text-muted"><?= $row['jenis_kelamin'] ?></small>
                    </td>
                    <td class="text-center">
                        <span class="rekap-badge badge-hadir"><?= $row['hadir'] ?></span>
                    </td>
                    <td class="text-center">
                        <span class="rekap-badge badge-terlambat"><?= $row['terlambat'] ?></span>
                    </td>
                    <td class="text-center">
                        <span class="rekap-badge badge-sakit"><?= $row['sakit'] ?></span>
                    </td>
                    <td class="text-center">
                        <span class="rekap-badge badge-izin"><?= $row['izin'] ?></span>
                    </td>
                    <td class="text-center">
                        <span class="rekap-badge badge-alfa"><?= $row['alfa'] ?></span>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold <?= $persen_class ?>"><?= $persen ?>%</span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alfa'],
        datasets: [{
            data: [<?= $stats['hadir'] ?>, <?= $stats['terlambat'] ?>, <?= $stats['sakit'] ?>, <?= $stats['izin'] ?>, <?= $stats['alfa'] ?>],
            backgroundColor: ['#198754', '#ffc107', '#0dcaf0', '#6f42c1', '#dc3545'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'right' } }
    }
});

new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            { label: 'Hadir', data: <?= json_encode($data_hadir) ?>, backgroundColor: '#198754', borderRadius: 5 },
            { label: 'Alfa', data: <?= json_encode($data_alfa) ?>, backgroundColor: '#dc3545', borderRadius: 5 }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php endif; ?>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

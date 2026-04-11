<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$tahun_ajaran_aktif = conn()->query("SELECT id FROM tahun_ajaran WHERE is_active = 1")->fetch_assoc();
$ta_id = $tahun_ajaran_aktif['id'] ?? 0;

if ($ta_id > 0) {
    $semester = conn()->query("SELECT * FROM semester WHERE semester IN (1, 2) AND tahun_ajaran_id = $ta_id ORDER BY semester ASC");
} else {
    $semester = conn()->query("SELECT * FROM semester WHERE semester IN (1, 2) ORDER BY tahun_ajaran_id DESC, semester ASC LIMIT 2");
}
$semester_dates = [];
while ($s = $semester->fetch_assoc()) {
    $semester_dates[$s['semester']] = $s;
}

$title = 'Rekap Absensi - Sistem Absensi Siswa';

ob_start();

$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');

$tgl_awal = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_awal) ? $tgl_awal : date('Y-m-01');
$tgl_akhir = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_akhir) ? $tgl_akhir : date('Y-m-t');

function getSemesterDateRange($semester_num, $semester_dates, $tgl_awal, $tgl_akhir) {
        if (!isset($semester_dates[$semester_num])) {
            return null;
        }
        $s = $semester_dates[$semester_num];
        $smt_mulai = $s['tgl_mulai'];
        $smt_selesai = $s['tgl_selesai'];
        
        $range_awal = max($tgl_awal, $smt_mulai);
        $range_akhir = min($tgl_akhir, $smt_selesai);
        
        if ($range_awal > $range_akhir) {
            return null;
        }
        
        return ['awal' => $range_awal, 'akhir' => $range_akhir, 'nama' => $s['nama'], 'id' => $s['id']];
    }
    
    function getStatsByDateRange($kelas_id, $tgl_awal, $tgl_akhir, $semester_id) {
        if (!$tgl_awal || !$tgl_akhir || $kelas_id <= 0) return ['hadir'=>0,'terlambat'=>0,'sakit'=>0,'izin'=>0,'alfa'=>0,'total'=>0];
        
        $stmt = conn()->prepare("
            SELECT 
                SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN a.status = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN a.status = 'Alfa' THEN 1 ELSE 0 END) as alfa,
                COUNT(*) as total
            FROM absensi a
            INNER JOIN siswa s ON a.siswa_id = s.id
            WHERE s.kelas_id = ? AND a.tanggal BETWEEN ? AND ? AND a.semester_id = ?
        ");
        $stmt->bind_param("issi", $kelas_id, $tgl_awal, $tgl_akhir, $semester_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?: ['hadir'=>0,'terlambat'=>0,'sakit'=>0,'izin'=>0,'alfa'=>0,'total'=>0];
    }
    
    function getSiswaStatsByDateRange($kelas_id, $tgl_awal, $tgl_akhir, $semester_id) {
        if (!$tgl_awal || !$tgl_akhir || $kelas_id <= 0) return null;
        
        $stmt = conn()->prepare("
            SELECT s.id, s.nama, s.jenis_kelamin,
                COALESCE(SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END), 0) as hadir,
                COALESCE(SUM(CASE WHEN a.status = 'Terlambat' THEN 1 ELSE 0 END), 0) as terlambat,
                COALESCE(SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END), 0) as sakit,
                COALESCE(SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END), 0) as izin,
                COALESCE(SUM(CASE WHEN a.status = 'Alfa' THEN 1 ELSE 0 END), 0) as alfa,
                COUNT(a.id) as total_absen
            FROM siswa s
            LEFT JOIN absensi a ON s.id = a.siswa_id 
                AND a.tanggal BETWEEN ? AND ? AND a.semester_id = ?
            WHERE s.kelas_id = ? AND (s.status = 'aktif' OR s.status IS NULL)
            GROUP BY s.id, s.nama, s.jenis_kelamin
            ORDER BY (alfa + sakit + izin) ASC, hadir DESC, nama ASC
        ");
        $stmt->bind_param("ssii", $tgl_awal, $tgl_akhir, $semester_id, $kelas_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    if ($kelas_id) {
    $kelas = conn()->query("SELECT * FROM kelas WHERE id = $kelas_id")->fetch_assoc();
    $total_siswa = conn()->query("SELECT COUNT(*) as total FROM siswa WHERE kelas_id = $kelas_id AND (status = 'aktif' OR status IS NULL)")->fetch_assoc()['total'];
    $total_hari = (strtotime($tgl_akhir) - strtotime($tgl_awal)) / (60*60*24) + 1;
    
    $smt1_range = getSemesterDateRange(1, $semester_dates, $tgl_awal, $tgl_akhir);
    $smt2_range = getSemesterDateRange(2, $semester_dates, $tgl_awal, $tgl_akhir);
    
    $stats_smt1 = $smt1_range ? getStatsByDateRange($kelas_id, $smt1_range['awal'], $smt1_range['akhir'], $smt1_range['id']) : ['hadir'=>0,'terlambat'=>0,'sakit'=>0,'izin'=>0,'alfa'=>0,'total'=>0];
    $stats_smt2 = $smt2_range ? getStatsByDateRange($kelas_id, $smt2_range['awal'], $smt2_range['akhir'], $smt2_range['id']) : ['hadir'=>0,'terlambat'=>0,'sakit'=>0,'izin'=>0,'alfa'=>0,'total'=>0];
    
    $hari_smt1 = $smt1_range ? (strtotime($smt1_range['akhir']) - strtotime($smt1_range['awal'])) / (60*60*24) + 1 : 0;
    $hari_smt2 = $smt2_range ? (strtotime($smt2_range['akhir']) - strtotime($smt2_range['awal'])) / (60*60*24) + 1 : 0;
    
    $total_seharusnya_smt1 = $total_siswa * $hari_smt1;
    $total_seharusnya_smt2 = $total_siswa * $hari_smt2;
    
    $kehadiran_smt1 = $total_seharusnya_smt1 > 0 ? round(($stats_smt1['hadir'] / $total_seharusnya_smt1) * 100, 1) : 0;
    $kehadiran_smt2 = $total_seharusnya_smt2 > 0 ? round(($stats_smt2['hadir'] / $total_seharusnya_smt2) * 100, 1) : 0;
    
    $siswa_smt1 = $smt1_range ? getSiswaStatsByDateRange($kelas_id, $smt1_range['awal'], $smt1_range['akhir'], $smt1_range['id']) : null;
    $siswa_smt2 = $smt2_range ? getSiswaStatsByDateRange($kelas_id, $smt2_range['awal'], $smt2_range['akhir'], $smt2_range['id']) : null;
    } else {
    $total_siswa = 0;
    $stats_smt1 = ['hadir'=>0,'terlambat'=>0,'sakit'=>0,'izin'=>0,'alfa'=>0,'total'=>0];
    $stats_smt2 = ['hadir'=>0,'terlambat'=>0,'sakit'=>0,'izin'=>0,'alfa'=>0,'total'=>0];
    $kehadiran_smt1 = 0;
    $kehadiran_smt2 = 0;
    $hari_smt1 = 0;
    $hari_smt2 = 0;
    $smt1_range = null;
    $smt2_range = null;
    $siswa_smt1 = null;
    $siswa_smt2 = null;
    }
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-chart-bar me-2"></i>Rekap Absensi
    </h2>
</div>

<!-- Filter Form -->
<form method="GET" class="card-custom p-4 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold text-wa-dark">
                <i class="fas fa-door-open me-2"></i>Pilih Kelas
            </label>
            <select name="kelas_id" class="form-select form-select-custom" required onchange="this.form.submit()">
                <option value="">-- Pilih Kelas --</option>
                <?php
                $kelas_list = conn()->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
                while ($row = $kelas_list->fetch_assoc()):
                ?>
                <option value="<?= $row['id'] ?>" <?= ($kelas_id == $row['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['nama_kelas']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold text-wa-dark">
                <i class="fas fa-calendar-alt me-2"></i>Tanggal Awal
            </label>
            <input type="date" name="tgl_awal" class="form-control" value="<?= htmlspecialchars($tgl_awal) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold text-wa-dark">
                <i class="fas fa-calendar-alt me-2"></i>Tanggal Akhir
            </label>
            <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir) ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-wa-primary w-100">
                <i class="fas fa-search me-2"></i>Filter
            </button>
        </div>
    </div>
    <?php if ($kelas_id): ?>
    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
                <a href="export.php?kelas_id=<?= $kelas_id ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&type=pdf" class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </a>
                <a href="export.php?kelas_id=<?= $kelas_id ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&type=excel" class="btn btn-success">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</form>

<!-- Stats Cards - Semester 1 vs Semester 2 -->
<?php if ($kelas_id): ?>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="stat-card bg-white shadow-sm" style="border-top: 4px solid #25D366;">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="stat-label text-muted mb-1">
                        <i class="fas fa-1 me-1"></i>Semester 1
                        <span class="badge bg-success ms-2" style="font-size: 0.65rem;"><?= $smt1_range['nama'] ?? '-' ?></span>
                    </div>
                    <div class="stat-value"><?= $kehadiran_smt1 ?>%</div>
                </div>
                <div class="stat-icon stat-hadir">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
            <?php if ($smt1_range): ?>
            <small class="text-muted d-block mb-2">
                <i class="fas fa-calendar me-1"></i>
                <?= date('d M', strtotime($smt1_range['awal'])) ?> - <?= date('d M Y', strtotime($smt1_range['akhir'])) ?>
                (<?= $hari_smt1 ?> hari)
            </small>
            <?php endif; ?>
            <div class="progress-custom">
                <div class="progress-bar-gradient" style="width: <?= $kehadiran_smt1 ?>%"></div>
            </div>
            <div class="row mt-3 text-center">
                <div class="col-3">
                    <div class="text-muted small">Hadir</div>
                    <div class="fw-bold" style="color: #128C7E;"><?= $stats_smt1['hadir'] ?? 0 ?></div>
                </div>
                <div class="col-3">
                    <div class="text-muted small">Telat</div>
                    <div class="fw-bold" style="color: #e6a800;"><?= $stats_smt1['terlambat'] ?? 0 ?></div>
                </div>
                <div class="col-3">
                    <div class="text-muted small">Sakit</div>
                    <div class="fw-bold" style="color: #0ea5e9;"><?= $stats_smt1['sakit'] ?? 0 ?></div>
                </div>
                <div class="col-3">
                    <div class="text-muted small">Alfa</div>
                    <div class="fw-bold" style="color: #e53e3e;"><?= $stats_smt1['alfa'] ?? 0 ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card bg-white shadow-sm" style="border-top: 4px solid #667eea;">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="stat-label text-muted mb-1">
                        <i class="fas fa-2 me-1"></i>Semester 2
                        <span class="badge bg-primary ms-2" style="font-size: 0.65rem;"><?= $smt2_range['nama'] ?? '-' ?></span>
                    </div>
                    <div class="stat-value"><?= $kehadiran_smt2 ?>%</div>
                </div>
                <div class="stat-icon stat-izin">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
            <?php if ($smt2_range): ?>
            <small class="text-muted d-block mb-2">
                <i class="fas fa-calendar me-1"></i>
                <?= date('d M', strtotime($smt2_range['awal'])) ?> - <?= date('d M Y', strtotime($smt2_range['akhir'])) ?>
                (<?= $hari_smt2 ?> hari)
            </small>
            <?php endif; ?>
            <div class="progress-custom">
                <div class="progress-bar" style="width: <?= $kehadiran_smt2 ?>%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); border-radius: 10px;"></div>
            </div>
            <div class="row mt-3 text-center">
                <div class="col-3">
                    <div class="text-muted small">Hadir</div>
                    <div class="fw-bold" style="color: #128C7E;"><?= $stats_smt2['hadir'] ?? 0 ?></div>
                </div>
                <div class="col-3">
                    <div class="text-muted small">Telat</div>
                    <div class="fw-bold" style="color: #e6a800;"><?= $stats_smt2['terlambat'] ?? 0 ?></div>
                </div>
                <div class="col-3">
                    <div class="text-muted small">Sakit</div>
                    <div class="fw-bold" style="color: #0ea5e9;"><?= $stats_smt2['sakit'] ?? 0 ?></div>
                </div>
                <div class="col-3">
                    <div class="text-muted small">Alfa</div>
                    <div class="fw-bold" style="color: #e53e3e;"><?= $stats_smt2['alfa'] ?? 0 ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="kelas-info-card card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="kelas-icon me-3">
                    <i class="fas fa-school"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-1 fw-bold">
                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                        <span class="badge bg-success ms-2" style="font-size: 0.7rem;"><?= $total_siswa ?> Siswa</span>
                    </h5>
                    <p class="mb-0 text-muted small">
                        <i class="fas fa-user-tie me-1"></i>
                        <?= htmlspecialchars($kelas['wali_kelas'] ?? 'Belum ada wali kelas') ?>
                    </p>
                    <p class="mb-0 text-muted small">
                        <i class="fas fa-calendar-range me-1"></i>
                        <?= date('d M Y', strtotime($tgl_awal)) ?> - <?= date('d M Y', strtotime($tgl_akhir)) ?>
                        <span class="mx-2">•</span>
                        <i class="fas fa-calendar-day me-1"></i><?= $total_hari ?> Hari
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Table Section - Semester 1 & 2 Side by Side -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card-custom">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); color: white;">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-list me-2"></i>Semester 1 - Detail Absensi
                </h6>
                <span class="badge bg-light text-dark rounded-pill"><?= $siswa_smt1 ? $siswa_smt1->num_rows : 0 ?> Siswa</span>
            </div>
            <div class="table-responsive" style="max-height: 500px;">
                <table class="table table-rekap mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Nama Siswa</th>
                            <th class="text-center"><span class="rekap-badge badge-hadir">H</span></th>
                            <th class="text-center"><span class="rekap-badge badge-terlambat">T</span></th>
                            <th class="text-center"><span class="rekap-badge badge-sakit">S</span></th>
                            <th class="text-center"><span class="rekap-badge badge-izin">I</span></th>
                            <th class="text-center"><span class="rekap-badge badge-alfa">A</span></th>
                            <th class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($siswa_smt1):
                        while ($row = $siswa_smt1->fetch_assoc()):
                            $persen = $hari_smt1 > 0 ? round(($row['hadir'] / $hari_smt1) * 100, 1) : 0;
                            $persen_class = $persen >= 80 ? 'percentage-excellent' : ($persen >= 60 ? 'percentage-good' : 'percentage-poor');
                            $initial = strtoupper(substr($row['nama'], 0, 1));
                            $avatar_class = ($row['jenis_kelamin'] === 'Laki-laki') ? 'avatar-laki' : 'avatar-perempuan';
                        ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-mini <?= $avatar_class ?> me-2"><?= $initial ?></div>
                                    <div>
                                        <div class="fw-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($row['nama']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center"><span class="rekap-badge badge-hadir"><?= $row['hadir'] ?></span></td>
                            <td class="text-center"><span class="rekap-badge badge-terlambat"><?= $row['terlambat'] ?></span></td>
                            <td class="text-center"><span class="rekap-badge badge-sakit"><?= $row['sakit'] ?></span></td>
                            <td class="text-center"><span class="rekap-badge badge-izin"><?= $row['izin'] ?></span></td>
                            <td class="text-center"><span class="rekap-badge badge-alfa"><?= $row['alfa'] ?></span></td>
                            <td class="text-center"><span class="<?= $persen_class ?>"><?= $persen ?>%</span></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-custom">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-list me-2"></i>Semester 2 - Detail Absensi
                </h6>
                <span class="badge bg-light text-dark rounded-pill"><?= $siswa_smt2 ? $siswa_smt2->num_rows : 0 ?> Siswa</span>
            </div>
            <div class="table-responsive" style="max-height: 500px;">
                <table class="table table-rekap mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Nama Siswa</th>
                            <th class="text-center"><span class="rekap-badge badge-hadir">H</span></th>
                            <th class="text-center"><span class="rekap-badge badge-terlambat">T</span></th>
                            <th class="text-center"><span class="rekap-badge badge-sakit">S</span></th>
                            <th class="text-center"><span class="rekap-badge badge-izin">I</span></th>
                            <th class="text-center"><span class="rekap-badge badge-alfa">A</span></th>
                            <th class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($siswa_smt2):
                        while ($row = $siswa_smt2->fetch_assoc()):
                            $persen = $hari_smt2 > 0 ? round(($row['hadir'] / $hari_smt2) * 100, 1) : 0;
                            $persen_class = $persen >= 80 ? 'percentage-excellent' : ($persen >= 60 ? 'percentage-good' : 'percentage-poor');
                            $initial = strtoupper(substr($row['nama'], 0, 1));
                            $avatar_class = ($row['jenis_kelamin'] === 'Laki-laki') ? 'avatar-laki' : 'avatar-perempuan';
                        ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-mini <?= $avatar_class ?> me-2"><?= $initial ?></div>
                                    <div>
                                        <div class="fw-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($row['nama']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center"><span class="rekap-badge badge-hadir"><?= $row['hadir'] ?></span></td>
                            <td class="text-center"><span class="rekap-badge badge-terlambat"><?= $row['terlambat'] ?></span></td>
                            <td class="text-center"><span class="rekap-badge badge-sakit"><?= $row['sakit'] ?></span></td>
                            <td class="text-center"><span class="rekap-badge badge-izin"><?= $row['izin'] ?></span></td>
                            <td class="text-center"><span class="rekap-badge badge-alfa"><?= $row['alfa'] ?></span></td>
                            <td class="text-center"><span class="<?= $persen_class ?>"><?= $persen ?>%</span></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Charts Comparison -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="card-header d-flex align-items-center">
                <i class="fas fa-chart-pie me-2" style="color: #25D366;"></i>
                Distribusi Kehadiran - Semester 1
            </div>
            <div class="card-body" style="height: 250px;">
                <canvas id="pieChart1"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="card-header d-flex align-items-center">
                <i class="fas fa-chart-pie me-2" style="color: #667eea;"></i>
                Distribusi Kehadiran - Semester 2
            </div>
            <div class="card-body" style="height: 250px;">
                <canvas id="pieChart2"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const pieCtx1 = document.getElementById('pieChart1').getContext('2d');
new Chart(pieCtx1, {
    type: 'doughnut',
    data: {
        labels: ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alfa'],
        datasets: [{
            data: [<?= $stats_smt1['hadir'] ?? 0 ?>, <?= $stats_smt1['terlambat'] ?? 0 ?>, <?= $stats_smt1['sakit'] ?? 0 ?>, <?= $stats_smt1['izin'] ?? 0 ?>, <?= $stats_smt1['alfa'] ?? 0 ?>],
            backgroundColor: ['#25D366', '#ffc107', '#0ea5e9', '#667eea', '#f5576c'],
            borderWidth: 0,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } }
        }
    }
});

const pieCtx2 = document.getElementById('pieChart2').getContext('2d');
new Chart(pieCtx2, {
    type: 'doughnut',
    data: {
        labels: ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alfa'],
        datasets: [{
            data: [<?= $stats_smt2['hadir'] ?? 0 ?>, <?= $stats_smt2['terlambat'] ?? 0 ?>, <?= $stats_smt2['sakit'] ?? 0 ?>, <?= $stats_smt2['izin'] ?? 0 ?>, <?= $stats_smt2['alfa'] ?? 0 ?>],
            backgroundColor: ['#25D366', '#ffc107', '#0ea5e9', '#667eea', '#f5576c'],
            borderWidth: 0,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } }
        }
    }
});
</script>

<?php if (!$kelas_id): ?>
<div class="alert alert-info d-flex align-items-center">
    <i class="fas fa-info-circle me-2" style="font-size: 1.5rem;"></i>
    <div>
        <strong>Silakan pilih kelas</strong> untuk melihat rekap absensi semester 1 dan semester 2.
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

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
    $range_awal = max($tgl_awal, $s['tgl_mulai']);
    $range_akhir = min($tgl_akhir, $s['tgl_selesai']);
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
            COALESCE(SUM(CASE WHEN a.status = 'Alfa' THEN 1 ELSE 0 END), 0) as alfa
        FROM siswa s
        LEFT JOIN absensi a ON s.id = a.siswa_id AND a.tanggal BETWEEN ? AND ? AND a.semester_id = ?
        WHERE s.kelas_id = ? AND (s.status = 'aktif' OR s.status IS NULL)
        GROUP BY s.id, s.nama, s.jenis_kelamin
        ORDER BY s.nama ASC
    ");
    $stmt->bind_param("ssii", $tgl_awal, $tgl_akhir, $semester_id, $kelas_id);
    $stmt->execute();
    return $stmt->get_result();
}

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

if ($kelas_id) {
    $kelas = conn()->query("SELECT * FROM kelas WHERE id = $kelas_id")->fetch_assoc();
    $total_siswa = conn()->query("SELECT COUNT(*) as total FROM siswa WHERE kelas_id = $kelas_id AND (status = 'aktif' OR status IS NULL)")->fetch_assoc()['total'];
    $total_hari = (strtotime($tgl_akhir) - strtotime($tgl_awal)) / (60*60*24) + 1;
    
    $smt1_range = getSemesterDateRange(1, $semester_dates, $tgl_awal, $tgl_akhir);
    $smt2_range = getSemesterDateRange(2, $semester_dates, $tgl_awal, $tgl_akhir);
    
    $stats_smt1 = $smt1_range ? getStatsByDateRange($kelas_id, $smt1_range['awal'], $smt1_range['akhir'], $smt1_range['id']) : $stats_smt1;
    $stats_smt2 = $smt2_range ? getStatsByDateRange($kelas_id, $smt2_range['awal'], $smt2_range['akhir'], $smt2_range['id']) : $stats_smt2;
    
    $hari_smt1 = $smt1_range ? (strtotime($smt1_range['akhir']) - strtotime($smt1_range['awal'])) / (60*60*24) + 1 : 0;
    $hari_smt2 = $smt2_range ? (strtotime($smt2_range['akhir']) - strtotime($smt2_range['awal'])) / (60*60*24) + 1 : 0;
    
    $total_seharusnya_smt1 = $total_siswa * $hari_smt1;
    $total_seharusnya_smt2 = $total_siswa * $hari_smt2;
    
    $kehadiran_smt1 = $total_seharusnya_smt1 > 0 ? round(($stats_smt1['hadir'] / $total_seharusnya_smt1) * 100, 1) : 0;
    $kehadiran_smt2 = $total_seharusnya_smt2 > 0 ? round(($stats_smt2['hadir'] / $total_seharusnya_smt2) * 100, 1) : 0;
    
    $siswa_smt1 = $smt1_range ? getSiswaStatsByDateRange($kelas_id, $smt1_range['awal'], $smt1_range['akhir'], $smt1_range['id']) : null;
    $siswa_smt2 = $smt2_range ? getSiswaStatsByDateRange($kelas_id, $smt2_range['awal'], $smt2_range['akhir'], $smt2_range['id']) : null;
}
?>

<div class="p-4">
    <h2 class="mb-4"><i class="fas fa-chart-bar me-2"></i>Rekap Absensi</h2>
    
    <form method="GET" class="card mb-4 p-3">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Pilih Kelas</label>
                <select name="kelas_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php
                    $kelas_list = conn()->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
                    while ($row = $kelas_list->fetch_assoc()):
                    ?>
                    <option value="<?= $row['id'] ?>" <?= ($kelas_id == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['nama_kelas']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Awal</label>
                <input type="date" name="tgl_awal" class="form-control" value="<?= htmlspecialchars($tgl_awal) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <?php if (!$kelas_id): ?>
    <div class="alert alert-info">Silakan pilih kelas untuk melihat rekap absensi.</div>
    <?php else: ?>
    
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">Semester 1 - <?= $kehadiran_smt1 ?>% Kehadiran</div>
                <div class="card-body">
                    <p class="mb-2"><?= $hari_smt1 ?> hari belajar</p>
                    <div class="row text-center">
                        <div class="col-3"><strong><?= $stats_smt1['hadir'] ?></strong><br><small>Hadir</small></div>
                        <div class="col-3"><strong><?= $stats_smt1['terlambat'] ?></strong><br><small>Telat</small></div>
                        <div class="col-3"><strong><?= $stats_smt1['sakit'] ?></strong><br><small>Sakit</small></div>
                        <div class="col-3"><strong><?= $stats_smt1['alfa'] ?></strong><br><small>Alfa</small></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">Semester 2 - <?= $kehadiran_smt2 ?>% Kehadiran</div>
                <div class="card-body">
                    <p class="mb-2"><?= $hari_smt2 ?> hari belajar</p>
                    <div class="row text-center">
                        <div class="col-3"><strong><?= $stats_smt2['hadir'] ?></strong><br><small>Hadir</small></div>
                        <div class="col-3"><strong><?= $stats_smt2['terlambat'] ?></strong><br><small>Telat</small></div>
                        <div class="col-3"><strong><?= $stats_smt2['sakit'] ?></strong><br><small>Sakit</small></div>
                        <div class="col-3"><strong><?= $stats_smt2['alfa'] ?></strong><br><small>Alfa</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">Semester 1</div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>#</th><th>Siswa</th><th>H</th><th>T</th><th>S</th><th>I</th><th>A</th><th>%</th></tr></thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($siswa_smt1): while ($row = $siswa_smt1->fetch_assoc()):
                                $persen = $hari_smt1 > 0 ? round(($row['hadir'] / $hari_smt1) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= $row['hadir'] ?></td>
                                <td><?= $row['terlambat'] ?></td>
                                <td><?= $row['sakit'] ?></td>
                                <td><?= $row['izin'] ?></td>
                                <td><?= $row['alfa'] ?></td>
                                <td><strong><?= $persen ?>%</strong></td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">Semester 2</div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>#</th><th>Siswa</th><th>H</th><th>T</th><th>S</th><th>I</th><th>A</th><th>%</th></tr></thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($siswa_smt2): while ($row = $siswa_smt2->fetch_assoc()):
                                $persen = $hari_smt2 > 0 ? round(($row['hadir'] / $hari_smt2) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= $row['hadir'] ?></td>
                                <td><?= $row['terlambat'] ?></td>
                                <td><?= $row['sakit'] ?></td>
                                <td><?= $row['izin'] ?></td>
                                <td><?= $row['alfa'] ?></td>
                                <td><strong><?= $persen ?>%</strong></td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';
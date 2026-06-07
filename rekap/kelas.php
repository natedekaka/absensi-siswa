<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin', 'guru', 'wali_kelas');

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

$title = 'Rekap Absensi';

ob_start();

$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');

$tgl_awal = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_awal) ? $tgl_awal : date('Y-m-01');
$tgl_akhir = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_akhir) ? $tgl_akhir : date('Y-m-t');

function getSemesterDateRange($semester_num, $semester_dates, $tgl_awal, $tgl_akhir) {
    if (!isset($semester_dates[$semester_num])) return null;
    $s = $semester_dates[$semester_num];
    $range_awal = max($tgl_awal, $s['tgl_mulai']);
    $range_akhir = min($tgl_akhir, $s['tgl_selesai']);
    if ($range_awal > $range_akhir) return null;
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

<style>
@media print {
    .sidebar, .topbar, footer, .filter-card, .btn-export-group { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .print-header { display: block !important; }
    .card-modern { box-shadow: none !important; border: 1px solid #ddd !important; }
    .table-modern { font-size: 9px; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
.print-header { display: none; text-align: center; margin-bottom: 20px; }
.print-header h1 { font-size: 18px; margin: 0; color: #1e293b; }
.print-header p { font-size: 12px; color: #64748b; margin: 2px 0; }
</style>

<div class="print-header">
    <?php $sekolah = getKonfigurasiSekolah(conn()); ?>
    <h1><?= htmlspecialchars($sekolah['nama_sekolah'] ?? 'LAPORAN REKAP ABSENSI') ?></h1>
    <p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
    <?php if ($kelas_id): ?>
    <p>Kelas: <?= htmlspecialchars($kelas['nama_kelas'] ?? '') ?></p>
    <?php endif; ?>
    <hr style="border:1px solid #000;margin:10px 0;">
</div>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-chart-bar mr-3 text-primary"></i>Rekap Absensi
    </h2>
    <?php if ($kelas_id): ?>
    <div class="btn-export-group flex gap-2">
        <a href="export.php?kelas_id=<?= $kelas_id ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&type=excel"
           class="btn-modern btn-success-modern text-sm" target="_blank">
            <i class="fas fa-file-excel mr-1"></i>Export Excel
        </a>
        <button onclick="window.print()" class="btn-modern btn-primary-modern text-sm">
            <i class="fas fa-print mr-1"></i>Cetak / PDF
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="print-area">

<form method="GET" class="filter-card mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Pilih Kelas</label>
            <select name="kelas_id" class="form-select-modern" onchange="this.form.submit()">
                <option value="">-- Pilih Kelas --</option>
                <?php
                $kelas_list = conn()->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
                while ($row = $kelas_list->fetch_assoc()):
                ?>
                <option value="<?= $row['id'] ?>" <?= ($kelas_id == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['nama_kelas']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Tanggal Awal</label>
            <input type="date" name="tgl_awal" class="form-input-modern" value="<?= htmlspecialchars($tgl_awal) ?>">
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Tanggal Akhir</label>
            <input type="date" name="tgl_akhir" class="form-input-modern" value="<?= htmlspecialchars($tgl_akhir) ?>">
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-modern btn-primary-modern w-full">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
        </div>
    </div>
</form>

<?php if (!$kelas_id): ?>
<div class="alert-modern alert-info-modern">
    <i class="fas fa-info-circle text-lg"></i>
    <span>Silakan pilih kelas untuk melihat rekap absensi.</span>
</div>
<?php else: ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card-modern">
        <div class="card-modern-header text-white font-semibold" style="background:#10B981;">
            Semester 1 — <?= $kehadiran_smt1 ?>% Kehadiran
        </div>
        <div class="card-modern-body">
            <p class="text-sm text-gray-500 mb-3"><?= $hari_smt1 ?> hari belajar</p>
            <div class="grid grid-cols-4 gap-2 text-center text-sm">
                <div><strong class="text-lg text-gray-800"><?= $stats_smt1['hadir'] ?></strong><br><span class="text-gray-400 text-xs">Hadir</span></div>
                <div><strong class="text-lg text-gray-800"><?= $stats_smt1['terlambat'] ?></strong><br><span class="text-gray-400 text-xs">Telat</span></div>
                <div><strong class="text-lg text-gray-800"><?= $stats_smt1['sakit'] ?></strong><br><span class="text-gray-400 text-xs">Sakit</span></div>
                <div><strong class="text-lg text-gray-800"><?= $stats_smt1['alfa'] ?></strong><br><span class="text-gray-400 text-xs">Alfa</span></div>
            </div>
        </div>
    </div>
    <div class="card-modern">
        <div class="card-modern-header text-white font-semibold" style="background:#3B82F6;">
            Semester 2 — <?= $kehadiran_smt2 ?>% Kehadiran
        </div>
        <div class="card-modern-body">
            <p class="text-sm text-gray-500 mb-3"><?= $hari_smt2 ?> hari belajar</p>
            <div class="grid grid-cols-4 gap-2 text-center text-sm">
                <div><strong class="text-lg text-gray-800"><?= $stats_smt2['hadir'] ?></strong><br><span class="text-gray-400 text-xs">Hadir</span></div>
                <div><strong class="text-lg text-gray-800"><?= $stats_smt2['terlambat'] ?></strong><br><span class="text-gray-400 text-xs">Telat</span></div>
                <div><strong class="text-lg text-gray-800"><?= $stats_smt2['sakit'] ?></strong><br><span class="text-gray-400 text-xs">Sakit</span></div>
                <div><strong class="text-lg text-gray-800"><?= $stats_smt2['alfa'] ?></strong><br><span class="text-gray-400 text-xs">Alfa</span></div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card-modern">
        <div class="card-modern-header font-semibold text-white" style="background:#10B981;">
            Semester 1
        </div>
        <div class="overflow-x-auto" style="max-height:400px;">
            <table class="table-modern text-sm">
                <thead class="sticky top-0 z-10">
                    <tr>
                        <th>#</th><th>Siswa</th><th class="text-center">H</th><th class="text-center">T</th>
                        <th class="text-center">S</th><th class="text-center">I</th><th class="text-center">A</th><th class="text-center">%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($siswa_smt1): while ($row = $siswa_smt1->fetch_assoc()):
                        $persen = $hari_smt1 > 0 ? round(($row['hadir'] / $hari_smt1) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td class="text-gray-500"><?= $no++ ?></td>
                        <td class="font-medium"><?= htmlspecialchars($row['nama']) ?></td>
                        <td class="text-center"><?= $row['hadir'] ?></td>
                        <td class="text-center"><?= $row['terlambat'] ?></td>
                        <td class="text-center"><?= $row['sakit'] ?></td>
                        <td class="text-center"><?= $row['izin'] ?></td>
                        <td class="text-center"><?= $row['alfa'] ?></td>
                        <td class="text-center font-semibold"><?= $persen ?>%</td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-modern">
        <div class="card-modern-header font-semibold text-white" style="background:#3B82F6;">
            Semester 2
        </div>
        <div class="overflow-x-auto" style="max-height:400px;">
            <table class="table-modern text-sm">
                <thead class="sticky top-0 z-10">
                    <tr>
                        <th>#</th><th>Siswa</th><th class="text-center">H</th><th class="text-center">T</th>
                        <th class="text-center">S</th><th class="text-center">I</th><th class="text-center">A</th><th class="text-center">%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($siswa_smt2): while ($row = $siswa_smt2->fetch_assoc()):
                        $persen = $hari_smt2 > 0 ? round(($row['hadir'] / $hari_smt2) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td class="text-gray-500"><?= $no++ ?></td>
                        <td class="font-medium"><?= htmlspecialchars($row['nama']) ?></td>
                        <td class="text-center"><?= $row['hadir'] ?></td>
                        <td class="text-center"><?= $row['terlambat'] ?></td>
                        <td class="text-center"><?= $row['sakit'] ?></td>
                        <td class="text-center"><?= $row['izin'] ?></td>
                        <td class="text-center"><?= $row['alfa'] ?></td>
                        <td class="text-center font-semibold"><?= $persen ?>%</td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

</div><!-- .print-area -->

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

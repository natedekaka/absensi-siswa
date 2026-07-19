<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin', 'guru', 'wali_kelas');

$tahun_ajaran_aktif = conn()->query("SELECT id FROM tahun_ajaran WHERE is_active = 1")->fetch_assoc();
$ta_id = $tahun_ajaran_aktif['id'] ?? 0;

$title = 'Rekap Absensi Mapel';

ob_start();

$user_id = (int)($_SESSION['user']['id'] ?? 0);

$guru_id = isset($_GET['guru_id']) ? (int)$_GET['guru_id'] : 0;
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$mapel_id = isset($_GET['mapel_id']) ? (int)$_GET['mapel_id'] : 0;
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');

$tgl_awal = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_awal) ? $tgl_awal : date('Y-m-01');
$tgl_akhir = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_akhir) ? $tgl_akhir : date('Y-m-t');

// Non-admin only sees their own data
if (!has_role('admin')) {
    $guru_id = $user_id;
}

function getRekapMapel($guru_id, $kelas_id, $mapel_id, $tgl_awal, $tgl_akhir) {
    $where = " WHERE a.tanggal BETWEEN ? AND ?";
    $params = [$tgl_awal, $tgl_akhir];
    $types = "ss";

    if ($guru_id > 0) {
        $where .= " AND a.user_id = ?";
        $params[] = $guru_id;
        $types .= "i";
    }
    if ($kelas_id > 0) {
        $where .= " AND a.kelas_id = ?";
        $params[] = $kelas_id;
        $types .= "i";
    }
    if ($mapel_id > 0) {
        $where .= " AND a.mapel_id = ?";
        $params[] = $mapel_id;
        $types .= "i";
    }

    $stmt = conn()->prepare("
        SELECT 
            u.nama AS nama_guru,
            k.nama_kelas,
            mp.nama_mapel,
            COUNT(DISTINCT a.tanggal) AS total_hari
        FROM absensi_mapel a
        JOIN users u ON a.user_id = u.id
        JOIN kelas k ON a.kelas_id = k.id
        JOIN mapel mp ON a.mapel_id = mp.id
        $where
        GROUP BY a.user_id, a.kelas_id, a.mapel_id, u.nama, k.nama_kelas, mp.nama_mapel
        ORDER BY u.nama, k.nama_kelas, mp.nama_mapel
    ");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

function getRekapMapelSiswa($guru_id, $kelas_id, $mapel_id, $tgl_awal, $tgl_akhir) {
    $where = " WHERE a.tanggal BETWEEN ? AND ?";
    $params = [$tgl_awal, $tgl_akhir];
    $types = "ss";

    if ($guru_id > 0) {
        $where .= " AND a.user_id = ?";
        $params[] = $guru_id;
        $types .= "i";
    }
    if ($kelas_id > 0) {
        $where .= " AND a.kelas_id = ?";
        $params[] = $kelas_id;
        $types .= "i";
    }
    if ($mapel_id > 0) {
        $where .= " AND a.mapel_id = ?";
        $params[] = $mapel_id;
        $types .= "i";
    }

    $stmt = conn()->prepare("
        SELECT 
            s.nis,
            s.nama AS nama_siswa,
            COUNT(DISTINCT a.tanggal) AS total_pertemuan,
            COUNT(*) AS total_tercatat,
            SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN a.status = 'Terlambat' THEN 1 ELSE 0 END) AS terlambat,
            SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) AS izin,
            SUM(CASE WHEN a.status = 'Alfa' THEN 1 ELSE 0 END) AS alfa
        FROM absensi_mapel a
        JOIN siswa s ON a.siswa_id = s.id
        $where
        GROUP BY a.siswa_id, s.nis, s.nama
        ORDER BY s.nama
    ");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

// Determine view mode
$show_siswa = ($kelas_id > 0);
$rekap = null;
$rekap_siswa = null;
if ($guru_id > 0 || $kelas_id > 0 || $mapel_id > 0) {
    if ($show_siswa) {
        $rekap_siswa = getRekapMapelSiswa($guru_id, $kelas_id, $mapel_id, $tgl_awal, $tgl_akhir);
    } else {
        $rekap = getRekapMapel($guru_id, $kelas_id, $mapel_id, $tgl_awal, $tgl_akhir);
    }
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
    <h1><?= htmlspecialchars($sekolah['nama_sekolah'] ?? 'LAPORAN REKAP ABSENSI MAPEL') ?></h1>
    <p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
    <hr style="border:1px solid #000;margin:10px 0;">
</div>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-chart-pie mr-3 text-indigo-600"></i>Rekap Absensi Mapel
    </h2>
    <?php if ($guru_id > 0 || $kelas_id > 0 || $mapel_id > 0): ?>
    <div class="btn-export-group flex gap-2">
        <a href="export_mapel.php?guru_id=<?= $guru_id ?>&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&type=excel"
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <?php if (has_role('admin')): ?>
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Guru</label>
            <select name="guru_id" class="form-select-modern">
                <option value="">-- Semua Guru --</option>
                <?php
                $guru_list = conn()->query("SELECT id, nama FROM users WHERE role IN ('guru','wali_kelas') ORDER BY nama");
                while ($g = $guru_list->fetch_assoc()):
                ?>
                <option value="<?= $g['id'] ?>" <?= ($guru_id == $g['id']) ? 'selected' : '' ?>><?= htmlspecialchars($g['nama']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <?php endif; ?>
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Kelas</label>
            <select name="kelas_id" class="form-select-modern">
                <option value="">-- Semua Kelas --</option>
                <?php
                if (has_role('admin')) {
                    $kelas_list = conn()->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
                } else {
                    $kelas_list = conn()->query("
                        SELECT DISTINCT k.id, k.nama_kelas FROM kelas k
                        INNER JOIN guru_kelas gk ON gk.kelas_id = k.id
                        WHERE gk.user_id = $user_id AND gk.mapel_id IS NOT NULL
                        ORDER BY k.nama_kelas
                    ");
                }
                while ($row = $kelas_list->fetch_assoc()):
                ?>
                <option value="<?= $row['id'] ?>" <?= ($kelas_id == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['nama_kelas']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Mapel</label>
            <select name="mapel_id" class="form-select-modern">
                <option value="">-- Semua Mapel --</option>
                <?php
                if (has_role('admin')) {
                    $mapel_list = conn()->query("SELECT id, nama_mapel FROM mapel ORDER BY nama_mapel");
                } else {
                    $mapel_list = conn()->query("
                        SELECT DISTINCT m.id, m.nama_mapel FROM mapel m
                        INNER JOIN guru_kelas gk ON gk.mapel_id = m.id
                        WHERE gk.user_id = $user_id
                        ORDER BY m.nama_mapel
                    ");
                }
                while ($m = $mapel_list->fetch_assoc()):
                ?>
                <option value="<?= $m['id'] ?>" <?= ($mapel_id == $m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_mapel']) ?></option>
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
    </div>
    <div class="mt-4">
        <button type="submit" class="btn-modern btn-primary-modern" style="background:#7C3AED;">
            <i class="fas fa-filter mr-2"></i>Filter
        </button>
    </div>
</form>

<?php if (!$guru_id && !$kelas_id && !$mapel_id && has_role('admin')): ?>
<div class="alert-modern alert-info-modern">
    <i class="fas fa-info-circle text-lg"></i>
    <span>Pilih guru, kelas, atau mapel untuk melihat rekap absensi mapel.</span>
</div>

<?php elseif ($show_siswa && $rekap_siswa && $rekap_siswa->num_rows > 0): ?>
<div class="card-modern">
    <div class="overflow-x-auto">
        <table class="table-modern text-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th class="text-center">Total Pertemuan</th>
                    <th class="text-center">Hadir</th>
                    <th class="text-center">Telat</th>
                    <th class="text-center">Sakit</th>
                    <th class="text-center">Izin</th>
                    <th class="text-center">Alfa</th>
                    <th class="text-center">% Hadir</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = $rekap_siswa->fetch_assoc()):
                    $persen = $row['total_tercatat'] > 0 ? round(($row['hadir'] / $row['total_tercatat']) * 100, 1) : 0;
                ?>
                <tr>
                    <td class="text-gray-500"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nis']) ?></td>
                    <td class="font-medium"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                    <td class="text-center"><?= $row['total_pertemuan'] ?></td>
                    <td class="text-center text-green-600 font-semibold"><?= $row['hadir'] ?></td>
                    <td class="text-center text-yellow-600"><?= $row['terlambat'] ?></td>
                    <td class="text-center text-gray-600"><?= $row['sakit'] ?></td>
                    <td class="text-center text-blue-600"><?= $row['izin'] ?></td>
                    <td class="text-center text-red-600"><?= $row['alfa'] ?></td>
                    <td class="text-center font-semibold"><?= $persen ?>%</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($rekap && $rekap->num_rows > 0): ?>
<div class="card-modern">
    <div class="overflow-x-auto">
        <table class="table-modern text-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <?php if (has_role('admin')): ?>
                    <th>Guru</th>
                    <?php endif; ?>
                    <th>Kelas</th>
                    <th>Mapel</th>
                    <th class="text-center">Total Hari</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = $rekap->fetch_assoc()): ?>
                <tr>
                    <td class="text-gray-500"><?= $no++ ?></td>
                    <?php if (has_role('admin')): ?>
                    <td class="font-medium"><?= htmlspecialchars($row['nama_guru']) ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                    <td><?= htmlspecialchars($row['nama_mapel'] ?? '-') ?></td>
                    <td class="text-center"><?= $row['total_hari'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($guru_id > 0 || $kelas_id > 0 || $mapel_id > 0): ?>
<div class="alert-modern alert-info-modern">
    <i class="fas fa-info-circle text-lg"></i>
    <span>Tidak ada data absensi mapel untuk periode ini.</span>
</div>
<?php endif; ?>

</div><!-- .print-area -->

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

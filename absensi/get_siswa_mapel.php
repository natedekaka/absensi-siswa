<?php
session_start();
require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/Database.php';

if (!has_role('admin', 'guru', 'wali_kelas')) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$user_id = (int)($_SESSION['user']['id'] ?? 0);

$kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$mapel_id = isset($_GET['mapel_id']) ? (int)$_GET['mapel_id'] : 0;
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$semester_id = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : 0;
$search = isset($_GET['search']) ? db()->escape($_GET['search']) : '';

if (!$kelas_id || !$semester_id || !$mapel_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    echo '<div class="alert alert-warning">Pilih kelas, semester, dan mapel terlebih dahulu!</div>';
    exit;
}

// Get tahun_ajaran_id of selected semester — used to filter rekap columns
$ta_id_smt = 0;
$ta_row = conn()->query("SELECT tahun_ajaran_id FROM semester WHERE id = $semester_id");
if ($ta_row && $ta_row->num_rows > 0) {
    $ta_id_smt = (int)$ta_row->fetch_assoc()['tahun_ajaran_id'];
}

// For non-admin, verify this teacher is assigned to this class + mapel via guru_kelas
if (!has_role('admin')) {
    $check = conn()->prepare("SELECT 1 FROM guru_kelas WHERE user_id = ? AND kelas_id = ? AND mapel_id = ?");
    $check->bind_param("iii", $user_id, $kelas_id, $mapel_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        echo '<div class="alert alert-danger">Anda tidak terdaftar sebagai pengajar untuk mapel ini di kelas ini.</div>';
        exit;
    }
}

$query = "
    SELECT 
        s.*,
        k.nama_kelas,
        COALESCE(rekap_smt1.hadir, 0) AS total_hadir_smt1,
        COALESCE(rekap_smt1.terlambat, 0) AS total_terlambat_smt1,
        COALESCE(rekap_smt1.sakit, 0) AS total_sakit_smt1,
        COALESCE(rekap_smt1.izin, 0) AS total_izin_smt1,
        COALESCE(rekap_smt1.alfa, 0) AS total_alfa_smt1,
        COALESCE(rekap_smt2.hadir, 0) AS total_hadir_smt2,
        COALESCE(rekap_smt2.terlambat, 0) AS total_terlambat_smt2,
        COALESCE(rekap_smt2.sakit, 0) AS total_sakit_smt2,
        COALESCE(rekap_smt2.izin, 0) AS total_izin_smt2,
        COALESCE(rekap_smt2.alfa, 0) AS total_alfa_smt2
    FROM siswa s
    JOIN kelas k ON s.kelas_id = k.id
    LEFT JOIN (
        SELECT 
            siswa_id,
            SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN status = 'Terlambat' THEN 1 ELSE 0 END) AS terlambat,
            SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) AS izin,
            SUM(CASE WHEN status = 'Alfa' THEN 1 ELSE 0 END) AS alfa
        FROM absensi_mapel a
        INNER JOIN semester sem ON a.semester_id = sem.id
        WHERE sem.semester = 1 AND sem.tahun_ajaran_id = $ta_id_smt
        GROUP BY siswa_id
    ) rekap_smt1 ON s.id = rekap_smt1.siswa_id
    LEFT JOIN (
        SELECT 
            siswa_id,
            SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN status = 'Terlambat' THEN 1 ELSE 0 END) AS terlambat,
            SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) AS izin,
            SUM(CASE WHEN status = 'Alfa' THEN 1 ELSE 0 END) AS alfa
        FROM absensi_mapel a
        INNER JOIN semester sem ON a.semester_id = sem.id
        WHERE sem.semester = 2 AND sem.tahun_ajaran_id = $ta_id_smt
        GROUP BY siswa_id
    ) rekap_smt2 ON s.id = rekap_smt2.siswa_id
";

if ($kelas_id === 'all') {
    $query .= " WHERE (s.status = 'aktif' OR s.status IS NULL)";
    if (!empty($search)) {
        $query .= " AND s.nama LIKE '%$search%'";
    }
    $query .= " ORDER BY k.nama_kelas, s.nama";
} else {
    $query .= " WHERE s.kelas_id = " . (int)$kelas_id . " AND (s.status = 'aktif' OR s.status IS NULL)";
    if (!empty($search)) {
        $query .= " AND s.nama LIKE '%$search%'";
    }
    $query .= " ORDER BY s.nama";
}

$result = db()->query($query);

if ($result && $result->num_rows > 0):
    // ─── BATCH: Ambil status absensi_mapel semua siswa dalam 1 query (N+1 → 1) ───
    $siswa_rows = [];
    $all_ids = [];
    $nama_kelas = '';
    while ($row = $result->fetch_assoc()) {
        $siswa_rows[] = $row;
        $all_ids[] = (int)$row['id'];
        if (!$nama_kelas) $nama_kelas = $row['nama_kelas'];
    }
    $result->free();

    $existing = [];
    if (!empty($all_ids)) {
        $ids_str = implode(',', $all_ids);
        $kelas_id_int = (int)$kelas_id;
        $q_batch = conn()->query("
            SELECT siswa_id, status FROM absensi_mapel 
            WHERE siswa_id IN ($ids_str) AND user_id = $user_id AND kelas_id = $kelas_id_int AND mapel_id = $mapel_id AND tanggal = '$tanggal' AND semester_id = $semester_id
        ");
        if ($q_batch) {
            while ($a = $q_batch->fetch_assoc()) {
                $existing[(int)$a['siswa_id']] = $a['status'];
            }
        }
    }
?>
<style>
    .table-absensi {
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .table-absensi thead {
        background: #7C3AED;
        color: white;
    }
    .table-absensi th, .table-absensi td {
        vertical-align: middle;
        padding: 0.5rem;
    }
    .table-absensi th {
        text-align: center;
    }
    .table-absensi td:first-child,
    .table-absensi td.col-hadir,
    .table-absensi td.col-status { text-align: center; }
    .table-absensi tbody tr:hover { background: #F1F5F9; }
    .table-absensi th.col-hadir, .table-absensi td.col-hadir { width: 60px; min-width: 60px; }
    .table-absensi th.col-status, .table-absensi td.col-status { width: 50px; min-width: 50px; }
    .table-absensi th.col-rekap, .table-absensi td.col-rekap { min-width: 180px; white-space: nowrap; }
    .rekap-badge {
        font-size: 0.7rem;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 2px 4px;
        font-family: monospace;
        display: inline-flex;
        gap: 2px;
    }
    .rekap-badge span {
        padding: 1px 3px;
        border-radius: 3px;
    }
    .rekap-h { background: #d4edda; color: #155724; font-weight: bold; }
    .rekap-t { background: #fff3cd; color: #856404; font-weight: bold; }
    .rekap-s { background: #e2e3e5; color: #383d41; font-weight: bold; }
    .rekap-i { background: #d1ecf1; color: #0c5460; font-weight: bold; }
    .rekap-a { background: #f8d7da; color: #721c24; font-weight: bold; }
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        color: white;
    }
    .status-hadir { background: #10b981; color: #ffffff; font-weight: 600; }
    .status-terlambat { background: #f59e0b; color: #000000; font-weight: 600; }
    .status-sakit { background: #6b7280; color: #ffffff; font-weight: 600; }
    .status-izin { background: #3b82f6; color: #ffffff; font-weight: 600; }
    .status-alfa { background: #ef4444; color: #ffffff; font-weight: 600; }
    .status-kosong { background: #aaa; }
    .attendance-radio input {
        width: 18px;
        height: 18px;
        accent-color: #10b981;
        cursor: pointer;
    }
    .header-info {
        background: #f5f3ff;
        border: 1px solid #ddd6fe;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #6d28d9;
        font-size: 0.9rem;
    }
</style>

<div class="header-info">
    <i class="fas fa-chalkboard-teacher text-lg"></i>
    <span>Absensi Mata Pelajaran — Kelas <strong><?= htmlspecialchars($nama_kelas) ?></strong></span>
</div>

<table class="table-absensi">
    <thead>
        <tr>
            <?php if ($kelas_id === 'all'): ?>
            <th>Kelas</th>
            <?php endif; ?>
            <th>No</th>
            <th>Nama Siswa</th>
            <th class="col-hadir">
                <input type="checkbox" id="selectAllHadir" onclick="if(window.parent.selectAllHadir)window.parent.selectAllHadir(this.checked);else selectAllHadir(this.checked)" style="accent-color:#fff;width:16px;height:16px;cursor:pointer;" title="Set semua Hadir">
                <br><span style="font-size:9px;opacity:0.8;">Semua</span>
            </th>
            <th class="col-status">T</th>
            <th class="col-status">S</th>
            <th class="col-status">I</th>
            <th class="col-status">A</th>
            <th class="col-rekap">Semester 1</th>
            <th class="col-rekap">Semester 2</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; foreach ($siswa_rows as $row):
            $status_sebelumnya = $existing[(int)$row['id']] ?? '';
            $hadir_checked = ($status_sebelumnya === '') ? 'checked' : '';
            $status_class = strtolower($status_sebelumnya) ?: 'kosong';
        ?>
        <tr>
            <?php if ($kelas_id === 'all'): ?>
            <td class="font-semibold text-gray-500"><?= htmlspecialchars($row['nama_kelas']) ?></td>
            <?php endif; ?>
            <td><?= $no++ ?></td>
            <td class="text-start">
                <strong><?= htmlspecialchars($row['nama']) ?></strong>
                <input type="hidden" name="siswa_id[]" value="<?= $row['id'] ?>">
            </td>
            <td class="col-hadir"><input type="radio" name="status[<?= $row['id'] ?>]" value="Hadir" <?= ($status_sebelumnya == 'Hadir') ? 'checked' : $hadir_checked ?> onchange="triggerAutoSave(this)"></td>
            <td class="col-status"><input type="radio" name="status[<?= $row['id'] ?>]" value="Terlambat" <?= ($status_sebelumnya == 'Terlambat') ? 'checked' : '' ?> onchange="triggerAutoSave(this)"></td>
            <td class="col-status"><input type="radio" name="status[<?= $row['id'] ?>]" value="Sakit" <?= ($status_sebelumnya == 'Sakit') ? 'checked' : '' ?> onchange="triggerAutoSave(this)"></td>
            <td class="col-status"><input type="radio" name="status[<?= $row['id'] ?>]" value="Izin" <?= ($status_sebelumnya == 'Izin') ? 'checked' : '' ?> onchange="triggerAutoSave(this)"></td>
            <td class="col-status"><input type="radio" name="status[<?= $row['id'] ?>]" value="Alfa" <?= ($status_sebelumnya == 'Alfa') ? 'checked' : '' ?> onchange="triggerAutoSave(this)"></td>
            <td class="col-rekap">
                <span class="rekap-badge">
                    <span class="rekap-h">H:<?= (int)$row['total_hadir_smt1'] ?></span>
                    <span class="rekap-t">T:<?= (int)$row['total_terlambat_smt1'] ?></span>
                    <span class="rekap-s">S:<?= (int)$row['total_sakit_smt1'] ?></span>
                    <span class="rekap-i">I:<?= (int)$row['total_izin_smt1'] ?></span>
                    <span class="rekap-a">A:<?= (int)$row['total_alfa_smt1'] ?></span>
                </span>
            </td>
            <td class="col-rekap">
                <span class="rekap-badge">
                    <span class="rekap-h">H:<?= (int)$row['total_hadir_smt2'] ?></span>
                    <span class="rekap-t">T:<?= (int)$row['total_terlambat_smt2'] ?></span>
                    <span class="rekap-s">S:<?= (int)$row['total_sakit_smt2'] ?></span>
                    <span class="rekap-i">I:<?= (int)$row['total_izin_smt2'] ?></span>
                    <span class="rekap-a">A:<?= (int)$row['total_alfa_smt2'] ?></span>
                </span>
            </td>
            <td>
                <?php if ($status_sebelumnya): ?>
                    <span class="status-badge status-<?= $status_class ?>"><?= $status_sebelumnya ?></span>
                <?php else: ?>
                    <span class="status-badge status-kosong">-</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php else: ?>
<div class="alert alert-info text-center py-4">
    <i class="fas fa-user-slash fa-2x d-block mb-2"></i>
    <strong>Tidak ada siswa ditemukan</strong>
</div>
<?php endif; ?>

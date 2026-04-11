<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/Database.php';

$kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$semester_id = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : 0;
$search = isset($_GET['search']) ? db()->escape($_GET['search']) : '';

if (!$kelas_id || !$semester_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    echo '<div class="alert alert-warning">Pilih kelas dan semester terlebih dahulu!</div>';
    exit;
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
        FROM absensi a
        INNER JOIN semester sem ON a.semester_id = sem.id
        WHERE sem.semester = 1
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
        FROM absensi a
        INNER JOIN semester sem ON a.semester_id = sem.id
        WHERE sem.semester = 2
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
?>

<style>
    .table-absensi {
        background: var(--wa-white);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .table-absensi thead {
        background: var(--wa-dark);
        color: white;
    }
    .table-absensi th, .table-absensi td {
        vertical-align: middle;
        text-align: center;
        padding: 0.5rem;
    }
    .table-absensi td:first-child { text-align: center; }
    .table-absensi td:nth-child(3) { text-align: left; }
    .table-absensi tbody tr:hover { background: var(--wa-light); }
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
    .status-hadir { background: var(--wa-green); }
    .status-terlambat { background: #ffb142; }
    .status-sakit { background: #778ca3; }
    .status-izin { background: #2ed573; }
    .status-alfa { background: #ff5252; }
    .status-kosong { background: #aaa; }
    .attendance-radio input {
        width: 18px;
        height: 18px;
        accent-color: var(--wa-green);
        cursor: pointer;
    }
</style>

<table class="table table-absensi table-hover">
    <thead>
        <tr>
            <?php if ($kelas_id === 'all'): ?>
            <th>Kelas</th>
            <?php endif; ?>
            <th>No</th>
            <th>Nama Siswa</th>
            <th class="col-hadir">Hadir</th>
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
        <?php $no = 1; while ($row = $result->fetch_assoc()):
            $check = conn()->prepare("SELECT status FROM absensi WHERE siswa_id = ? AND tanggal = ? AND semester_id = ?");
            $check->bind_param("isi", $row['id'], $tanggal, $semester_id);
            $check->execute();
            $check->store_result();
            
            $status_sebelumnya = '';
            if ($check->num_rows > 0) {
                $check->bind_result($status_sebelumnya);
                $check->fetch();
            }
            $check->close();

            $hadir_checked = ($status_sebelumnya === '') ? 'checked' : '';
            $status_class = strtolower($status_sebelumnya) ?: 'kosong';
        ?>
        <tr>
            <?php if ($kelas_id === 'all'): ?>
            <td class="fw-bold text-secondary"><?= htmlspecialchars($row['nama_kelas']) ?></td>
            <?php endif; ?>
            <td><?= $no++ ?></td>
            <td class="text-start">
                <strong><?= htmlspecialchars($row['nama']) ?></strong>
                <input type="hidden" name="siswa_id[]" value="<?= $row['id'] ?>">
            </td>
            <td class="col-hadir"><input type="radio" name="status[<?= $row['id'] ?>]" value="Hadir" <?= ($status_sebelumnya == 'Hadir') ? 'checked' : $hadir_checked ?>></td>
            <td class="col-status"><input type="radio" name="status[<?= $row['id'] ?>]" value="Terlambat" <?= ($status_sebelumnya == 'Terlambat') ? 'checked' : '' ?>></td>
            <td class="col-status"><input type="radio" name="status[<?= $row['id'] ?>]" value="Sakit" <?= ($status_sebelumnya == 'Sakit') ? 'checked' : '' ?>></td>
            <td class="col-status"><input type="radio" name="status[<?= $row['id'] ?>]" value="Izin" <?= ($status_sebelumnya == 'Izin') ? 'checked' : '' ?>></td>
            <td class="col-status"><input type="radio" name="status[<?= $row['id'] ?>]" value="Alfa" <?= ($status_sebelumnya == 'Alfa') ? 'checked' : '' ?>></td>
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
        <?php endwhile; ?>
    </tbody>
</table>

<?php else: ?>
<div class="alert alert-info text-center py-4">
    <i class="fas fa-user-slash fa-2x d-block mb-2"></i>
    <strong>Tidak ada siswa ditemukan</strong>
</div>
<?php endif; ?>

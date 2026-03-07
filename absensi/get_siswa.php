<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/Database.php';

$kelas_id = $_GET['kelas_id'] ?? '';
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$semester_id = $_GET['semester_id'] ?? null;
$search = isset($_GET['search']) ? db()->escape($_GET['search']) : '';

if (!$kelas_id || !$semester_id) {
    echo '<div class="alert alert-warning">Pilih kelas dan semester terlebih dahulu!</div>';
    exit;
}

$query = "
    SELECT 
        s.*,
        k.nama_kelas,
        COALESCE(rekap.hadir, 0) AS total_hadir,
        COALESCE(rekap.terlambat, 0) AS total_terlambat,
        COALESCE(rekap.sakit, 0) AS total_sakit,
        COALESCE(rekap.izin, 0) AS total_izin,
        COALESCE(rekap.alfa, 0) AS total_alfa
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
        FROM absensi
        WHERE semester_id = " . (int)$semester_id . "
        GROUP BY siswa_id
    ) rekap ON s.id = rekap.siswa_id
";

if ($kelas_id === 'all') {
    $query .= " WHERE s.nama LIKE '%$search%'";
    $query .= " ORDER BY k.nama_kelas, s.nama";
} else {
    $query .= " WHERE s.kelas_id = " . (int)$kelas_id;
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
        padding: 0.75rem;
    }
    .table-absensi td:first-child { text-align: center; }
    .table-absensi td:nth-child(3) { text-align: left; }
    .table-absensi tbody tr:hover { background: var(--wa-light); }
    .rekap-badge {
        font-size: 0.75rem;
        background: #f1f1f1;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 2px 6px;
        font-family: monospace;
    }
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
            <th>Hadir</th>
            <th>Terlambat</th>
            <th>Sakit</th>
            <th>Izin</th>
            <th>Alfa</th>
            <th>Rekap</th>
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
            <td><input type="radio" name="status[<?= $row['id'] ?>]" value="Hadir" <?= ($status_sebelumnya == 'Hadir') ? 'checked' : $hadir_checked ?>></td>
            <td><input type="radio" name="status[<?= $row['id'] ?>]" value="Terlambat" <?= ($status_sebelumnya == 'Terlambat') ? 'checked' : '' ?>></td>
            <td><input type="radio" name="status[<?= $row['id'] ?>]" value="Sakit" <?= ($status_sebelumnya == 'Sakit') ? 'checked' : '' ?>></td>
            <td><input type="radio" name="status[<?= $row['id'] ?>]" value="Izin" <?= ($status_sebelumnya == 'Izin') ? 'checked' : '' ?>></td>
            <td><input type="radio" name="status[<?= $row['id'] ?>]" value="Alfa" <?= ($status_sebelumnya == 'Alfa') ? 'checked' : '' ?>></td>
            <td>
                <span class="rekap-badge">
                    H:<?= (int)$row['total_hadir'] ?> | 
                    T:<?= (int)$row['total_terlambat'] ?> | 
                    S:<?= (int)$row['total_sakit'] ?> | 
                    I:<?= (int)$row['total_izin'] ?> | 
                    A:<?= (int)$row['total_alfa'] ?>
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

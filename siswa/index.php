<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Data Siswa - Sistem Absensi Siswa';

ob_start();

$keyword = $_GET['cari'] ?? '';
$kelas_filter = $_GET['kelas_id'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where = [];
if ($keyword) $where[] = "s.nama LIKE '%" . db()->escape($keyword) . "%'";
if ($kelas_filter) $where[] = "s.kelas_id = '" . db()->escape($kelas_filter) . "'";
$where_sql = $where ? "WHERE " . implode(' AND ', $where) : "";

$total = conn()->query("SELECT COUNT(*) as total FROM siswa s JOIN kelas k ON s.kelas_id = k.id $where_sql")->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

$siswa = conn()->query("
    SELECT s.*, k.nama_kelas 
    FROM siswa s 
    JOIN kelas k ON s.kelas_id = k.id 
    $where_sql 
    ORDER BY s.nama ASC 
    LIMIT $limit OFFSET $offset
");

$kelas_list = conn()->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
?>

<style>
.siswa-card {
    border: none;
    border-radius: 16px;
    transition: all 0.3s ease;
    overflow: hidden;
}
.siswa-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}
.siswa-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.1rem;
}
.avatar-laki {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.avatar-perempuan {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}
.search-card {
    border: none;
    border-radius: 16px;
    background: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}
.search-icon {
    background: var(--wa-bg);
    border: none;
    border-radius: 12px 0 0 12px;
}
.search-input {
    border: none;
    border-radius: 0 12px 12px 0;
    padding-left: 0;
}
.search-input:focus {
    box-shadow: none;
}
.table-header-custom {
    background: linear-gradient(135deg, var(--wa-dark) 0%, #0d6e67 100%);
    color: white;
}
.table-siswa tbody tr {
    transition: all 0.2s;
}
.table-siswa tbody tr:hover {
    background: var(--wa-light);
}
.badge-kelas {
    background: rgba(18, 140, 126, 0.1);
    color: var(--wa-dark);
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}
.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.btn-action:hover {
    transform: scale(1.1);
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-users me-2"></i>Data Siswa
    </h2>
    <div class="d-flex gap-2">
        <a href="tambah.php" class="btn btn-wa-primary">
            <i class="fas fa-user-plus me-2"></i>Tambah
        </a>
        <a href="import.php" class="btn btn-wa-success">
            <i class="fas fa-file-import me-2"></i>Import
        </a>
    </div>
</div>

<div class="search-card p-4 mb-4">
    <form method="get" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label fw-semibold text-muted small">PENCARIAN</label>
            <div class="input-group">
                <span class="input-group-text search-icon">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" name="cari" class="form-control search-input" 
                       placeholder="Cari nama siswa..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold text-muted small">FILTER KELAS</label>
            <select name="kelas_id" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                <?php
                $kelas_list->data_seek(0);
                while ($row = $kelas_list->fetch_assoc()):
                ?>
                <option value="<?= $row['id'] ?>" <?= ($kelas_filter == $row['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['nama_kelas']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold text-muted small">&nbsp;</label>
            <button type="submit" class="btn btn-wa-primary w-100">
                <i class="fas fa-search me-2"></i>Cari
            </button>
        </div>
    </form>
</div>

<?php if ($total > 0): ?>
<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-siswa mb-0">
            <thead class="table-header-custom">
                <tr>
                    <th class="text-center rounded-top-0" style="width: 60px">No</th>
                    <th>Siswa</th>
                    <th class="text-center">NIS</th>
                    <th class="text-center">Kelas</th>
                    <th class="text-center" style="width: 120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = $offset + 1;
                while ($row = $siswa->fetch_assoc()):
                    $initial = strtoupper(substr($row['nama'], 0, 1));
                    $avatar_class = ($row['jenis_kelamin'] === 'Laki-laki') ? 'avatar-laki' : 'avatar-perempuan';
                ?>
                <tr>
                    <td class="text-center text-muted"><?= $no++ ?></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="siswa-avatar <?= $avatar_class ?> me-3">
                                <?= $initial ?>
                            </div>
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($row['nama']) ?></div>
                                <small class="text-muted"><?= $row['jenis_kelamin'] ?></small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="text-muted"><?= htmlspecialchars($row['nis']) ?></span>
                    </td>
                    <td class="text-center">
                        <span class="badge-kelas">
                            <i class="fas fa-door-open me-1"></i><?= htmlspecialchars($row['nama_kelas']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-action btn-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-action btn-danger" title="Hapus"
                           onclick="return confirm('Yakin hapus <?= htmlspecialchars($row['nama']) ?>?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center pagination-custom">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&cari=<?= urlencode($keyword) ?>&kelas_id=<?= $kelas_filter ?>">
                <?= $i ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php else: ?>
<div class="card-custom p-5 text-center">
    <div class="mb-3">
        <i class="fas fa-user-slash fa-4x text-muted"></i>
    </div>
    <h5 class="text-muted">Tidak ada data siswa</h5>
    <p class="text-muted small">Silakan tambah data siswa atau ubah filter pencarian</p>
    <a href="tambah.php" class="btn btn-wa-primary">
        <i class="fas fa-plus me-2"></i>Tambah Siswa
    </a>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

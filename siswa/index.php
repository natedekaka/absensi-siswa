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
$where[] = "(s.status = 'aktif' OR s.status IS NULL)";
if ($keyword) $where[] = "s.nama LIKE '%" . db()->escape($keyword) . "%'";
if ($kelas_filter) $where[] = "s.kelas_id = '" . db()->escape($kelas_filter) . "'";
$where_sql = "WHERE " . implode(' AND ', $where);

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

<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.siswa-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

function updateSelectAll() {
    const checkboxes = document.querySelectorAll('.siswa-checkbox');
    const selectAll = document.getElementById('selectAll');
    const checked = document.querySelectorAll('.siswa-checkbox:checked');
    selectAll.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
    selectAll.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
}

document.querySelectorAll('.siswa-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectAll);
});
</script>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-users me-2"></i>Data Siswa
    </h2>
    <div class="d-flex gap-2 flex-wrap">
        <a href="tambah.php" class="btn btn-wa-primary">
            <i class="fas fa-user-plus me-1 me-md-2"></i><span class="d-none d-md-inline">Tambah</span>
        </a>
        <a href="import.php" class="btn btn-wa-success">
            <i class="fas fa-file-import me-1 me-md-2"></i><span class="d-none d-md-inline">Import</span>
        </a>
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
            <i class="fas fa-trash me-1 me-md-2"></i><span class="d-none d-md-inline">Hapus</span>
        </button>
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
                    <th class="text-center rounded-top-0" style="width: 50px">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                    </th>
                    <th class="text-center" style="width: 60px">No</th>
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
                    <td class="text-center">
                        <input type="checkbox" name="siswa_ids[]" value="<?= $row['id'] ?>" class="siswa-checkbox">
                    </td>
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
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-action btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-action btn-danger" title="Hapus"
                               onclick="return confirm('Yakin hapus <?= htmlspecialchars($row['nama']) ?>?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
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

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border: none; border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-danger bg-opacity-10 border-0 pb-0">
                <div class="text-center w-100 pt-4">
                    <div class="delete-icon-container mb-3">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <h4 class="modal-title fw-bold text-dark">Hapus Siswa</h4>
                    <p class="text-muted mb-0 pb-3">Pilih opsi penghapusan data</p>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <form method="POST" id="deleteForm" action="hapus_batch.php">
                    <div class="delete-option mb-3">
                        <input class="form-check-input" type="radio" name="delete_type" id="deleteSelected" value="selected" checked>
                        <label class="form-check-label w-100" for="deleteSelected">
                            <div class="option-card">
                                <div class="d-flex align-items-center">
                                    <div class="option-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="fas fa-check-square"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">Hapus yang Dipilih</div>
                                        <small class="text-muted" id="selectedCountText">Centang siswa di tabel...</small>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="delete-option mb-3">
                        <input class="form-check-input" type="radio" name="delete_type" id="deleteKelas" value="kelas">
                        <label class="form-check-label w-100" for="deleteKelas">
                            <div class="option-card">
                                <div class="d-flex align-items-center">
                                    <div class="option-icon bg-info bg-opacity-10 text-info">
                                        <i class="fas fa-door-open"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">Hapus per Kelas</div>
                                        <small class="text-muted">Pilih kelas tertentu</small>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="delete-option mb-3">
                        <input class="form-check-input" type="radio" name="delete_type" id="deleteAll" value="all">
                        <label class="form-check-label w-100" for="deleteAll">
                            <div class="option-card">
                                <div class="d-flex align-items-center">
                                    <div class="option-icon bg-danger bg-opacity-10 text-danger">
                                        <i class="fas fa-users-slash"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">Hapus Semua</div>
                                        <small class="text-muted">Hapus seluruh siswa</small>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="mb-3" id="kelasSelect" style="display: none;">
                        <select name="kelas_id" class="form-select" style="border-radius: 12px; border: 2px solid #e0e0e0;">
                            <option value="">Pilih Kelas</option>
                            <?php
                            $kelas_list->data_seek(0);
                            while ($row = $kelas_list->fetch_assoc()):
                            ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kelas']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <input type="hidden" name="siswa_ids" id="siswaIdsInput" value="">
                    
                    <div class="delete-warning">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Data yang dihapus tidak dapat dikembalikan!</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-cancel flex-fill" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-delete flex-fill" onclick="submitDelete()">
                    <i class="fas fa-trash me-2"></i>Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.delete-icon-container {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #DC3545 0%, #c82333 100%);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
}
.delete-icon-container i {
    font-size: 2rem;
    color: white;
}
.delete-option {
    position: relative;
}
.delete-option input {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 10;
}
.option-card {
    padding: 1rem;
    padding-right: 3rem;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}
.option-card::after {
    content: '';
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border: 2px solid #ccc;
    border-radius: 50%;
    transition: all 0.3s;
}
.option-card:hover {
    border-color: #25C185;
    background: rgba(37, 193, 133, 0.05);
    transform: translateX(5px);
}
.delete-option input:checked + .option-card {
    border-color: #25C185;
    background: rgba(37, 193, 133, 0.1);
}
.delete-option input:checked + .option-card::after {
    background: #25C185;
    border-color: #25C185;
    content: '\f00c';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    color: white;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.option-icon {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}
.delete-warning {
    background: #FFF3CD;
    color: #856404;
    padding: 0.875rem 1rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}
.btn-cancel {
    background: #f8f9fa;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: #666;
    font-weight: 600;
    transition: all 0.3s;
}
.btn-cancel:hover {
    background: #e9ecef;
    border-color: #ccc;
}
.btn-delete {
    background: linear-gradient(135deg, #DC3545 0%, #c82333 100%);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    transition: all 0.3s;
}
.btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(220, 53, 69, 0.4);
}
</style>

<script>
document.querySelectorAll('input[name="delete_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const kelasSelect = document.getElementById('kelasSelect');
        const siswaIdsInput = document.getElementById('siswaIdsInput');
        const btn = document.querySelector('.btn-delete');
        
        if (this.value === 'kelas') {
            kelasSelect.style.display = 'block';
            siswaIdsInput.value = '';
            btn.innerHTML = '<i class="fas fa-trash me-2"></i>Ya, Hapus per Kelas';
        } else if (this.value === 'selected') {
            kelasSelect.style.display = 'none';
            const checked = document.querySelectorAll('.siswa-checkbox:checked');
            siswaIdsInput.value = Array.from(checked).map(cb => cb.value).join(',');
            btn.innerHTML = '<i class="fas fa-trash me-2"></i>Ya, Hapus yang Dipilih';
        } else {
            kelasSelect.style.display = 'none';
            siswaIdsInput.value = 'all';
            btn.innerHTML = '<i class="fas fa-trash me-2"></i>Ya, Hapus Semua';
        }
    });
});

function submitDelete() {
    const deleteType = document.querySelector('input[name="delete_type"]:checked').value;
    let message = '';
    
    if (deleteType === 'selected') {
        const checked = document.querySelectorAll('.siswa-checkbox:checked');
        if (checked.length === 0) {
            alert('Pilih siswa yang ingin dihapus!');
            return;
        }
        message = 'Anda akan menghapus ' + checked.length + ' siswa yang dipilih.\n\nApakah Anda yakin?';
    } else if (deleteType === 'kelas') {
        const kelasId = document.querySelector('select[name="kelas_id"]').value;
        const kelasName = document.querySelector('select[name="kelas_id"] option[value="' + kelasId + '"]').text;
        if (!kelasId) {
            document.getElementById('kelasSelect').classList.add('is-invalid');
            return;
        }
        document.getElementById('kelasSelect').classList.remove('is-invalid');
        message = 'Anda akan menghapus semua siswa di kelas ' + kelasName + '.\n\nApakah Anda yakin?';
    } else {
        message = 'Anda akan menghapus SEMUA siswa.\n\nTindakan ini tidak dapat dibatalkan!\n\nApakah Anda yakin?';
    }
    
    if (confirm(message)) {
        const btn = document.querySelector('.btn-delete');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
        btn.disabled = true;
        
        setTimeout(() => {
            document.getElementById('deleteForm').submit();
        }, 500);
    }
}

document.getElementById('deleteModal').addEventListener('show.bs.modal', function() {
    const checked = document.querySelectorAll('.siswa-checkbox:checked');
    const count = checked.length;
    
    const countText = document.getElementById('selectedCountText');
    if (count === 0) {
        countText.textContent = 'Centang siswa di tabel...';
        countText.classList.add('text-danger');
    } else {
        countText.innerHTML = '<span class="text-success fw-bold">' + count + '</span> siswa dipilih';
        countText.classList.remove('text-danger');
    }
    
    document.getElementById('siswaIdsInput').value = Array.from(checked).map(cb => cb.value).join(',');
    
    document.getElementById('deleteSelected').checked = true;
    document.getElementById('kelasSelect').style.display = 'none';
    
    const btn = document.querySelector('.btn-delete');
    btn.innerHTML = '<i class="fas fa-trash me-2"></i>Ya, Hapus yang Dipilih';
    btn.disabled = false;
});
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

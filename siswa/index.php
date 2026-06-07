<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin', 'guru', 'wali_kelas');

$title = 'Data Siswa - Sistem Absensi Siswa';

ob_start();

$keyword = $_GET['cari'] ?? '';
$kelas_filter = $_GET['kelas_id'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$uid = (int)($_SESSION['user']['id'] ?? 0);

// Guru only sees students from classes they teach
$guru_kelas_ids = [];
if (!has_role('admin')) {
    $q = conn()->query("SELECT DISTINCT kelas_id FROM guru_kelas WHERE user_id = $uid AND mapel_id IS NOT NULL");
    while ($r = $q->fetch_assoc()) {
        $guru_kelas_ids[] = (int)$r['kelas_id'];
    }
}

$where = [];
$where[] = "(s.status = 'aktif' OR s.status IS NULL)";
if (!has_role('admin') && !empty($guru_kelas_ids)) {
    $where[] = "s.kelas_id IN (" . implode(',', $guru_kelas_ids) . ")";
}
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

if (!has_role('admin') && !empty($guru_kelas_ids)) {
    $kelas_list = conn()->query("SELECT id, nama_kelas FROM kelas WHERE id IN (" . implode(',', $guru_kelas_ids) . ") ORDER BY nama_kelas");
} else {
    $kelas_list = conn()->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
}
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-users mr-3 text-primary"></i>Data Siswa
    </h2>
    <?php if (has_role('admin')): ?>
    <div class="flex gap-3">
        <a href="tambah.php" class="btn-modern btn-primary-modern">
            <i class="fas fa-user-plus mr-1"></i><span class="hidden md:inline"> Tambah</span>
        </a>
        <a href="import.php" class="btn-modern btn-success-modern">
            <i class="fas fa-file-import mr-1"></i><span class="hidden md:inline"> Import</span>
        </a>
        <button type="button" class="btn-modern btn-danger-modern" onclick="openDeleteModal()">
            <i class="fas fa-trash mr-1"></i><span class="hidden md:inline"> Hapus</span>
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="filter-card mb-6">
    <form method="get" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
        <div class="md:col-span-5">
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Pencarian</label>
            <div class="relative">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="cari" class="form-input-modern form-input-icon" 
                       placeholder="Cari nama siswa..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        <div class="md:col-span-4">
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Filter Kelas</label>
            <select name="kelas_id" class="form-select-modern" onchange="this.form.submit()">
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
        <div class="md:col-span-3">
            <button type="submit" class="btn-modern btn-primary-modern w-full">
                <i class="fas fa-search mr-2"></i>Cari
            </button>
        </div>
    </form>
</div>

<?php if ($total > 0): ?>
<div class="card-modern overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table-modern">
            <thead class="text-white">
                <tr>
                    <?php if (has_role('admin')): ?>
                    <th class="text-center w-[50px]">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll()" class="accent-white">
                    </th>
                    <?php endif; ?>
                    <th class="text-center w-[60px]">No</th>
                    <th>Siswa</th>
                    <th class="text-center">NIS</th>
                    <th class="text-center">Kelas</th>
                    <?php if (has_role('admin')): ?>
                    <th class="text-center w-[120px]">Aksi</th>
                    <?php endif; ?>
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
                    <?php if (has_role('admin')): ?>
                    <td class="text-center">
                        <input type="checkbox" name="siswa_ids[]" value="<?= $row['id'] ?>" class="siswa-checkbox">
                    </td>
                    <?php endif; ?>
                    <td class="text-center"><?= $no++ ?></td>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="avatar-modern <?= $avatar_class ?> w-10 h-10 text-sm">
                                <?= $initial ?>
                            </div>
                            <div>
                                <div class="font-semibold text-sm"><?= htmlspecialchars($row['nama']) ?></div>
                                <span class="text-xs text-gray-500"><?= $row['jenis_kelamin'] ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($row['nis']) ?></td>
                    <td class="text-center">
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium bg-primary-50 text-primary">
                            <i class="fas fa-door-open text-xs"></i><?= htmlspecialchars($row['nama_kelas']) ?>
                        </span>
                    </td>
                    <?php if (has_role('admin')): ?>
                    <td class="text-center">
                        <div class="flex gap-1 justify-center">
                            <a href="edit.php?id=<?= $row['id'] ?>" class="w-9 h-9 rounded-xl inline-flex items-center justify-center text-gray-500 hover:text-yellow-500 hover:bg-yellow-50 transition" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="hapus.php?id=<?= $row['id'] ?>" class="w-9 h-9 rounded-xl inline-flex items-center justify-center text-gray-500 hover:text-red-500 hover:bg-red-50 transition" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pages > 1): ?>
<nav class="mt-6">
    <div class="pagination-modern justify-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>&cari=<?= urlencode($keyword) ?>&kelas_id=<?= $kelas_filter ?>" 
           class="<?= ($i == $page) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
</nav>
<?php endif; ?>

<?php else: ?>
<div class="card-modern p-8 text-center">
    <i class="fas fa-user-slash text-4xl text-gray-300 mb-3"></i>
    <h5 class="text-gray-400 font-semibold">Tidak ada data siswa</h5>
    <p class="text-gray-400 text-sm mt-1">Silakan tambah data siswa atau ubah filter pencarian</p>
    <?php if (has_role('admin')): ?>
    <a href="tambah.php" class="btn-modern btn-primary-modern mt-4 inline-flex">
        <i class="fas fa-plus mr-2"></i>Tambah Siswa
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Delete Modal -->
<div id="deleteModal" class="modal-overlay z-50" style="display: none;">
    <div class="modal-modern max-w-[500px]">
        <div class="modal-modern-header flex-col items-center text-center pt-8">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center mx-auto mb-4 shadow-lg" style="box-shadow:0 10px 30px rgba(220,53,69,0.3);">
                <i class="fas fa-trash-alt text-2xl text-white"></i>
            </div>
            <h4 class="text-lg font-bold text-gray-800">Hapus Siswa</h4>
            <p class="text-sm text-gray-400 mt-1">Pilih opsi penghapusan data</p>
            <button onclick="closeDeleteModal()" class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">&times;</button>
        </div>
        <div class="modal-modern-body">
            <form method="POST" id="deleteForm" action="hapus_batch.php">
                <!-- Option: Selected -->
                <label class="block relative mb-3 cursor-pointer">
                    <input class="absolute inset-0 opacity-0 z-10 cursor-pointer" type="radio" name="delete_type" value="selected" checked onchange="onDeleteTypeChange(this)">
                    <div class="option-card p-4 pr-12 border-2 border-gray-200 rounded-xl transition-all relative has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 hover:border-emerald-500 hover:bg-emerald-50">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 rounded-xl bg-primary-50 text-primary flex items-center justify-center shrink-0">
                                <i class="fas fa-check-square"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-sm">Hapus yang Dipilih</div>
                                <small class="text-gray-400" id="selectedCountText">Centang siswa di tabel...</small>
                            </div>
                        </div>
                    </div>
                </label>
                
                <!-- Option: Per Kelas -->
                <label class="block relative mb-3 cursor-pointer">
                    <input class="absolute inset-0 opacity-0 z-10 cursor-pointer" type="radio" name="delete_type" value="kelas" onchange="onDeleteTypeChange(this)">
                    <div class="option-card p-4 pr-12 border-2 border-gray-200 rounded-xl transition-all relative has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 hover:border-emerald-500 hover:bg-emerald-50">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-sm">Hapus per Kelas</div>
                                <small class="text-gray-400">Pilih kelas tertentu</small>
                            </div>
                        </div>
                    </div>
                </label>
                
                <!-- Option: All -->
                <label class="block relative mb-4 cursor-pointer">
                    <input class="absolute inset-0 opacity-0 z-10 cursor-pointer" type="radio" name="delete_type" value="all" onchange="onDeleteTypeChange(this)">
                    <div class="option-card p-4 pr-12 border-2 border-gray-200 rounded-xl transition-all relative has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 hover:border-emerald-500 hover:bg-emerald-50">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 rounded-xl bg-red-50 text-red-500 flex items-center justify-center shrink-0">
                                <i class="fas fa-users-slash"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-sm">Hapus Semua</div>
                                <small class="text-gray-400">Hapus seluruh siswa</small>
                            </div>
                        </div>
                    </div>
                </label>
                
                <div class="mb-4 hidden" id="kelasSelect">
                    <select name="kelas_id" class="form-select-modern">
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
                
                <div class="flex items-center gap-2 p-3.5 bg-yellow-50 text-yellow-800 rounded-xl text-sm">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Data yang dihapus tidak dapat dikembalikan!</span>
                </div>
            </form>
        </div>
        <div class="modal-modern-footer">
            <button type="button" class="btn-modern btn-ghost flex-1" onclick="closeDeleteModal()">
                <i class="fas fa-times mr-2"></i>Batal
            </button>
            <button type="button" class="btn-modern btn-danger-modern flex-1" onclick="submitDelete()">
                <i class="fas fa-trash mr-2"></i>Ya, Hapus
            </button>
        </div>
    </div>
</div>

<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    document.querySelectorAll('.siswa-checkbox').forEach(cb => cb.checked = selectAll.checked);
}

function openDeleteModal() {
    const checked = document.querySelectorAll('.siswa-checkbox:checked');
    document.getElementById('selectedCountText').innerHTML = checked.length === 0 
        ? '<span class="text-red-500">Centang siswa di tabel...</span>'
        : '<span class="text-emerald-600 font-bold">' + checked.length + '</span> siswa dipilih';
    document.getElementById('siswaIdsInput').value = Array.from(checked).map(cb => cb.value).join(',');
    document.getElementById('deleteModal').style.display = '';
    document.getElementById('deleteSelected').checked = true;
    document.getElementById('kelasSelect').classList.add('hidden');
    document.querySelector('.btn-danger-modern').innerHTML = '<i class="fas fa-trash mr-2"></i>Ya, Hapus yang Dipilih';
    document.querySelector('.btn-danger-modern').disabled = false;
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function onDeleteTypeChange(radio) {
    const kelasSelect = document.getElementById('kelasSelect');
    const btn = document.querySelector('.btn-danger-modern');
    if (radio.value === 'kelas') {
        kelasSelect.classList.remove('hidden');
        document.getElementById('siswaIdsInput').value = '';
        btn.innerHTML = '<i class="fas fa-trash mr-2"></i>Ya, Hapus per Kelas';
    } else if (radio.value === 'selected') {
        kelasSelect.classList.add('hidden');
        const checked = document.querySelectorAll('.siswa-checkbox:checked');
        document.getElementById('siswaIdsInput').value = Array.from(checked).map(cb => cb.value).join(',');
        btn.innerHTML = '<i class="fas fa-trash mr-2"></i>Ya, Hapus yang Dipilih';
    } else {
        kelasSelect.classList.add('hidden');
        document.getElementById('siswaIdsInput').value = 'all';
        btn.innerHTML = '<i class="fas fa-trash mr-2"></i>Ya, Hapus Semua';
    }
}

function submitDelete() {
    const deleteType = document.querySelector('input[name="delete_type"]:checked').value;
    let message = '';    
    if (deleteType === 'selected') {
        const checked = document.querySelectorAll('.siswa-checkbox:checked');
        if (checked.length === 0) { alert('Pilih siswa yang ingin dihapus!'); return; }
        message = 'Anda akan menghapus ' + checked.length + ' siswa yang dipilih.\n\nApakah Anda yakin?';
    } else if (deleteType === 'kelas') {
        const kelasSelect = document.querySelector('select[name="kelas_id"]');
        const kelasName = kelasSelect.options[kelasSelect.selectedIndex]?.text;
        if (!kelasSelect.value) { kelasSelect.focus(); return; }
        message = 'Anda akan menghapus semua siswa di kelas ' + kelasName + '.\n\nApakah Anda yakin?';
    } else {
        message = 'Anda akan menghapus SEMUA siswa.\n\nTindakan ini tidak dapat dibatalkan!\n\nApakah Anda yakin?';
    }
    if (confirm(message)) {
        const btn = document.querySelector('.btn-danger-modern');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menghapus...';
        btn.disabled = true;
        setTimeout(() => document.getElementById('deleteForm').submit(), 500);
    }
}

// Close modal on overlay click
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>


<?php
$content = ob_get_clean();
require_once '../views/layout.php';

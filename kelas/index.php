<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Data Kelas - Sistem Absensi Siswa';

ob_start();

$kelas = conn()->query("SELECT k.*, COUNT(s.id) as total_siswa 
                        FROM kelas k 
                        LEFT JOIN siswa s ON k.id = s.kelas_id 
                        GROUP BY k.id 
                        ORDER BY k.nama_kelas");
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-door-open mr-3 text-primary"></i>Data Kelas
    </h2>
    <div class="flex gap-3">
        <a href="tambah.php" class="btn-modern btn-primary-modern">
            <i class="fas fa-plus mr-1"></i><span class="hidden md:inline"> Tambah</span>
        </a>
        <a href="import.php" class="btn-modern btn-success-modern">
            <i class="fas fa-file-import mr-1"></i><span class="hidden md:inline"> Import</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if ($kelas && $kelas->num_rows > 0): ?>
        <?php while ($row = $kelas->fetch_assoc()): ?>
        <div class="card-modern flex flex-col overflow-hidden group">
            <div class="relative p-5 text-white" style="background:linear-gradient(135deg,var(--primary-color) 0%,var(--secondary-color) 100%)">
                <div class="flex items-start justify-between">
                    <div>
                        <h5 class="font-bold text-white mb-1"><?= htmlspecialchars($row['nama_kelas']) ?></h5>
                        <span class="text-white/70 text-xs">Kelas</span>
                    </div>
                    <div class="relative">
                        <button class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition" onclick="toggleDropdown(this)">
                            <i class="fas fa-ellipsis-v text-sm"></i>
                        </button>
                        <div class="dropdown-menu hidden absolute right-0 top-full mt-1 min-w-[13rem] bg-white rounded-xl shadow-dropdown border border-gray-100 py-1 z-50">
                            <a class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition" href="edit.php?id=<?= $row['id'] ?>">
                                <i class="fas fa-edit text-yellow-500 w-4"></i>Edit
                            </a>
                            <hr class="border-gray-100 my-1">
                            <a class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition" href="hapus.php?id=<?= $row['id'] ?>">
                                <i class="fas fa-trash w-4"></i>Hapus
                            </a>
                        </div>
                    </div>
                </div>
                <i class="fas fa-school absolute -right-2 -bottom-2 text-6xl opacity-15 text-white pointer-events-none"></i>
            </div>
            <div class="p-5 flex flex-col flex-1">
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-primary-50 text-primary">
                        <i class="fas fa-user-tie text-xs"></i>
                        <?= htmlspecialchars($row['wali_kelas'] ?? 'Belum ada') ?>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-green-50 text-green-600">
                        <i class="fas fa-users text-xs"></i>
                        <?= $row['total_siswa'] ?> Siswa
                    </span>
                </div>
                <div class="mt-auto flex gap-2">
                    <a href="../siswa/?kelas_id=<?= $row['id'] ?>" class="btn-modern btn-ghost flex-1 text-sm">
                        <i class="fas fa-users mr-1"></i> Lihat Siswa
                    </a>
                    <a href="../absensi/?kelas_id=<?= $row['id'] ?>" class="btn-modern btn-success-modern flex-1 text-sm">
                        <i class="fas fa-clipboard-check mr-1"></i> Absensi
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-span-full">
            <div class="card-modern p-8 text-center">
                <i class="fas fa-door-open text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-400">Belum ada data kelas</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleDropdown(btn) {
    const menu = btn.nextElementSibling;
    const isOpen = !menu.classList.contains('hidden');
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
    if (!isOpen) menu.classList.remove('hidden');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.relative')) {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
    }
});
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

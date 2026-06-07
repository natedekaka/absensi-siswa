<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin');

$tingkat = (int)($_GET['tingkat'] ?? 10);
if (!in_array($tingkat, [10, 11])) $tingkat = 10;
$tingkat_ke = $tingkat + 1;

$prefix_map = [
    10 => ['X', '10', 'XI', '11'],
    11 => ['XI', '11', 'XII', '12'],
];

$m = $prefix_map[$tingkat];
$prefix_sumber = $m[0];
$prefix_tujuan = $m[2];

$title = "Redistribusi Kelas {$prefix_sumber} → {$prefix_tujuan} - Sistem Absensi Siswa";

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'pindahkan') {
        $siswa_ids = $_POST['siswa_ids'] ?? [];
        $kelas_tujuan = (int)$_POST['kelas_tujuan'];
        
        if (empty($siswa_ids)) {
            $error = "Pilih setidaknya satu siswa";
        } elseif ($kelas_tujuan <= 0) {
            $error = "Pilih kelas tujuan";
        } else {
            $updated = 0;
            foreach ($siswa_ids as $siswa_id) {
                $update = conn()->prepare("UPDATE siswa SET kelas_id = ?, tingkat = ? WHERE id = ?");
                $update->bind_param("iii", $kelas_tujuan, $tingkat_ke, $siswa_id);
                if ($update->execute()) $updated++;
            }
            $success = "Berhasil memindahkan $updated siswa ke kelas baru (tingkat $tingkat_ke)";
        }
    }
}

$kelas_sumber = conn()->query("
    SELECT id, nama_kelas FROM kelas 
    WHERE nama_kelas LIKE '{$m[0]}-%' OR nama_kelas LIKE '{$m[1]}-%' 
    ORDER BY nama_kelas
");
$kelas_tujuan = conn()->query("
    SELECT id, nama_kelas FROM kelas 
    WHERE nama_kelas LIKE '{$m[2]}-%' OR nama_kelas LIKE '{$m[3]}-%' 
    ORDER BY nama_kelas
");

$siswa_list = conn()->query("
    SELECT s.id, s.nis, s.nama, s.jenis_kelamin, k.nama_kelas as kelas_sekarang, k.id as kelas_id
    FROM siswa s 
    JOIN kelas k ON s.kelas_id = k.id 
    WHERE s.status = 'aktif' AND (k.nama_kelas LIKE '{$m[0]}-%' OR k.nama_kelas LIKE '{$m[1]}-%')
    ORDER BY k.nama_kelas, s.nama
");

$siswa_by_kelas = [];
while ($s = $siswa_list->fetch_assoc()) {
    $siswa_by_kelas[$s['kelas_id']][$s['kelas_sekarang']][] = $s;
}

ob_start();
?>

<style>
.siswa-item { display:flex; align-items:center; padding:0.75rem; border-radius:8px; margin-bottom:0.5rem; background:#f8f9fa; transition:all .2s; }
.siswa-item:hover { background:#e9ecef; }
.siswa-item input[type="checkbox"] { width:18px; height:18px; margin-right:1rem; accent-color:#4f46e5; }
.siswa-nama { font-weight:600; color:#333; }
.siswa-nis { font-size:.85rem; color:#666; }
.dark .siswa-item { background:rgba(255,255,255,0.05); }
.dark .siswa-item:hover { background:rgba(255,255,255,0.1); }
.dark .siswa-nama { color:#E2E8F0; }
.dark .siswa-nis { color:#94A3B8; }
</style>

<div class="flex flex-wrap items-center gap-4 mb-6">
    <a href="index.php" class="btn-modern btn-neutral-modern">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h2 class="text-xl font-bold text-gray-800 dark:text-white">
        <i class="fas fa-random mr-3 text-primary"></i>Redistribusi Kelas <?= $prefix_sumber ?> → <?= $prefix_tujuan ?>
    </h2>
    <div class="ml-auto flex gap-2">
        <a href="?tingkat=10" class="btn-modern <?= $tingkat == 10 ? 'btn-primary-modern' : 'btn-neutral-modern' ?>">
            Kelas 10 → 11
        </a>
        <a href="?tingkat=11" class="btn-modern <?= $tingkat == 11 ? 'btn-primary-modern' : 'btn-neutral-modern' ?>">
            Kelas 11 → 12
        </a>
    </div>
</div>

<?php if ($success): ?>
<div class="alert-modern alert-success-modern mb-6 flex items-center gap-3">
    <i class="fas fa-check-circle text-lg"></i><span><?= $success ?></span>
    <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert-modern alert-danger-modern mb-6 flex items-center gap-3">
    <i class="fas fa-exclamation-circle text-lg"></i><span><?= $error ?></span>
    <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <?php if (!empty($siswa_by_kelas)): ?>
            <form method="POST" id="redistribusiForm">
                <input type="hidden" name="action" value="pindahkan">
                <?php foreach ($siswa_by_kelas as $kelas_id => $kelas_data): ?>
                    <?php foreach ($kelas_data as $nama_kelas => $siswa_list): ?>
                    <div class="card-modern mb-4 overflow-hidden">
                        <div class="gradient-header indigo px-5 py-3 flex items-center justify-between">
                            <span class="font-semibold"><i class="fas fa-door-open mr-2"></i><?= htmlspecialchars($nama_kelas) ?></span>
                            <span class="badge-modern badge-primary-modern"><?= count($siswa_list) ?> siswa</span>
                        </div>
                        <div class="p-4">
                            <?php foreach ($siswa_list as $siswa): ?>
                            <div class="siswa-item">
                                <input type="checkbox" name="siswa_ids[]" value="<?= $siswa['id'] ?>" id="s_<?= $siswa['id'] ?>">
                                <label for="s_<?= $siswa['id'] ?>" class="flex items-center flex-1 cursor-pointer">
                                    <div class="flex-1">
                                        <div class="siswa-nama"><?= htmlspecialchars($siswa['nama']) ?></div>
                                        <div class="siswa-nis">NIS: <?= htmlspecialchars($siswa['nis']) ?></div>
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded bg-gray-200 text-gray-600">
                                        <?= $siswa['jenis_kelamin'] == 'Laki-laki' ? 'L' : 'P' ?>
                                    </span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </form>
        <?php else: ?>
            <div class="card-modern p-6 text-center text-gray-400">
                <i class="fas fa-info-circle text-lg mr-2"></i>Tidak ada siswa kelas <?= $prefix_sumber ?> dengan status aktif.
            </div>
        <?php endif; ?>
    </div>
    
    <div class="lg:col-span-1">
        <div class="card-modern sticky" style="top:100px">
            <div class="card-modern-body">
                <h5 class="font-semibold text-gray-800 dark:text-white mb-4">
                    <i class="fas fa-paper-plane mr-2 text-primary"></i>Pindahkan Siswa
                </h5>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Kelas Tujuan (<?= $prefix_tujuan ?>)</label>
                    <select name="kelas_tujuan" class="form-input-modern w-full" form="redistribusiForm" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php 
                        $kelas_tujuan_arr = [];
                        while ($k = $kelas_tujuan->fetch_assoc()): 
                            $kelas_tujuan_arr[$k['id']] = $k['nama_kelas'];
                        ?>
                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="flex gap-2 mb-4">
                    <button type="button" onclick="selectAll()" class="btn-modern btn-primary-modern flex-1 justify-center text-sm">
                        <i class="fas fa-check-square mr-1"></i>Pilih Semua
                    </button>
                    <button type="button" onclick="deselectAll()" class="btn-modern btn-neutral-modern flex-1 justify-center text-sm">
                        <i class="fas fa-square mr-1"></i>Batal
                    </button>
                </div>
                
                <hr class="border-gray-200 dark:border-gray-700 my-4">
                
                <button type="submit" form="redistribusiForm" class="btn-modern btn-primary-modern w-full justify-center mb-2">
                    <i class="fas fa-random mr-2"></i>Pindahkan
                </button>
                <a href="index.php" class="btn-modern btn-neutral-modern w-full justify-center">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>
        </div>
        
        <div class="card-modern mt-4">
            <div class="card-modern-body">
                <h5 class="font-semibold text-gray-800 dark:text-white mb-4">
                    <i class="fas fa-lightbulb mr-2 text-amber-500"></i>Cara Menggunakan
                </h5>
                <ol class="text-sm text-gray-500 dark:text-gray-400 space-y-2" style="padding-left:1.2rem">
                    <li>Ceklis siswa yang ingin dipindahkan</li>
                    <li>Pilih kelas tujuan (<?= $prefix_tujuan ?>-IPA, <?= $prefix_tujuan ?>-IPS, dll)</li>
                    <li>Klik tombol "Pindahkan" (tingkat otomatis naik ke <?= $tingkat_ke ?>)</li>
                    <li>Ulangi untuk kelompok siswa berikutnya</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
function selectAll() { document.querySelectorAll('input[name="siswa_ids[]"]').forEach(cb => cb.checked = true); }
function deselectAll() { document.querySelectorAll('input[name="siswa_ids[]"]').forEach(cb => cb.checked = false); }
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

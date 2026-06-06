<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Kelulusan Siswa - Sistem Absensi Siswa';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tahun_lulus = (int)$_POST['tahun_lulus'];
    
    $siswa_xii = conn()->query("
        SELECT s.id FROM siswa s 
        JOIN kelas k ON s.kelas_id = k.id 
        WHERE s.status = 'aktif' AND (
            k.nama_kelas LIKE 'XII-%' OR 
            k.nama_kelas LIKE '12-%'
        )
    ");

    $count = 0;
    while ($siswa = $siswa_xii->fetch_assoc()) {
        $update = conn()->prepare("UPDATE siswa SET status = 'alumni', tingkat = NULL, tahun_lulus = ? WHERE id = ?");
        $update->bind_param("ii", $tahun_lulus, $siswa['id']);
        $update->execute();
        $count++;
    }

    if ($count > 0) {
        $success = "Berhasil meluluskan $count siswa kelas 12";
    } else {
        $error = "Tidak ada siswa kelas 12 yang diluluskan";
    }
}

$siswa_xii_count = conn()->query("SELECT COUNT(*) as total FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.status = 'aktif' AND (k.nama_kelas LIKE 'XII-%' OR k.nama_kelas LIKE '12-%')")->fetch_assoc()['total'];

$siswa_xii_list = conn()->query("
    SELECT s.id, s.nis, s.nama, k.nama_kelas 
    FROM siswa s 
    JOIN kelas k ON s.kelas_id = k.id 
    WHERE s.status = 'aktif' AND (
        k.nama_kelas LIKE 'XII-%' OR 
        k.nama_kelas LIKE '12-%'
    )
    ORDER BY k.nama_kelas, s.nama
");

ob_start();
?>

<style>
.siswa-item { border:2px solid #e5e7eb; border-radius:14px; padding:.875rem 1rem; margin-bottom:.5rem; transition:all .3s; display:flex; align-items:center; justify-content:space-between; }
.siswa-item:hover { border-color:#f59e0b; background:#fffbeb; }
.kelas-section { margin-bottom:2rem; }
.kelas-section h5 { color:#d97706; font-weight:600; padding:.5rem 1rem; background:#fef3c7; border-radius:10px; display:inline-block; }
</style>

<div class="max-w-4xl mx-auto my-8">
    <div class="card-modern overflow-hidden">
        <div class="gradient-header orange text-center">
            <div class="w-[80px] h-[80px] rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(255,255,255,0.2)">
                <i class="fas fa-user-graduate text-3xl text-white"></i>
            </div>
            <h3 class="text-xl font-semibold text-white">Proses Kelulusan</h3>
            <p class="mt-1 opacity-85 text-sm">Tandai siswa kelas 12 sebagai alumni</p>
        </div>
        <div class="p-6">
            <?php if ($success): ?>
            <div class="alert-modern alert-success-modern mb-4 flex items-center gap-3">
                <i class="fas fa-check-circle text-lg"></i><span><?= $success ?></span>
                <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert-modern alert-danger-modern mb-4 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-lg"></i><span><?= $error ?></span>
                <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 text-center">
                    <h2 class="text-4xl font-bold text-indigo-500"><?= $siswa_xii_count ?></h2>
                    <p class="text-sm text-gray-400">Siswa Kelas 12 Aktif</p>
                </div>
                <div>
                    <form method="POST">
                        <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Tahun Lulus</label>
                        <select name="tahun_lulus" class="form-input-modern w-full mb-3" required>
                            <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                            <option value="<?= date('Y') + 1 ?>"><?= date('Y') + 1 ?></option>
                        </select>
                        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 mb-3 text-sm text-amber-800 dark:text-amber-200">
                            <i class="fas fa-exclamation-triangle mr-2 text-amber-500"></i>
                            <strong>Perhatian!</strong>
                            <ul class="mt-2 mb-0 ps-3">
                                <li>Status jadi <strong>ALUMNI</strong></li>
                                <li>Tidak muncul di absensi</li>
                                <li>Muncul di riwayat alumni</li>
                            </ul>
                        </div>
                        <button type="submit" class="btn-modern btn-warning-modern w-full justify-center">
                            <i class="fas fa-graduation-cap mr-2"></i>Proses Kelulusan
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($siswa_xii_count > 0): ?>
            <hr class="border-gray-200 dark:border-gray-700 my-6">
            <h5 class="font-bold text-gray-800 dark:text-white mb-4"><i class="fas fa-users mr-2 text-primary"></i>Daftar Siswa Kelas 12</h5>
            <div class="space-y-3" style="max-height:350px;overflow-y:auto">
                <?php 
                $current_kelas = '';
                while ($row = $siswa_xii_list->fetch_assoc()): 
                    if ($current_kelas != $row['nama_kelas']):
                        $current_kelas = $row['nama_kelas'];
                ?>
                <div class="kelas-section">
                    <h5><i class="fas fa-door-open mr-2"></i><?= htmlspecialchars($current_kelas) ?></h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <?php endif; ?>
                    <div class="siswa-item">
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($row['nama']) ?></div>
                            <small class="text-gray-400"><?= htmlspecialchars($row['nis']) ?></small>
                        </div>
                        <span class="badge-modern badge-warning-modern">Aktif</span>
                    </div>
                    <?php if ($current_kelas != ($siswa_xii_count > 0 ? '' : '')): ?>
                    </div></div>
                    <?php endif; ?>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-6">
                <i class="fas fa-user-slash text-gray-300 text-3xl mb-2"></i>
                <p class="text-gray-400">Belum ada siswa kelas 12</p>
            </div>
            <?php endif; ?>

            <div class="text-center mt-6">
                <a href="index.php" class="btn-modern btn-neutral-modern justify-center">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

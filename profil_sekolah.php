<?php
session_start();

require_once 'core/init.php';
require_once 'core/Database.php';

if (!is_logged_in()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

initKonfigurasiSekolah(conn());
$sekolah = getKonfigurasiSekolah(conn());
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_profil'])) {
    $nama_sekolah = trim($_POST['nama_sekolah']);
    $warna_primer = trim($_POST['warna_primer']);
    $warna_sekunder = trim($_POST['warna_sekunder']);
    $logo = $sekolah['logo'];
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        
            if (in_array($ext, $allowed) && $_FILES['logo']['size'] <= 2 * 1024 * 1024) {
            $filename = 'logo_' . time() . '.' . $ext;
            $target = __DIR__ . '/assets/uploads/' . $filename;
            
            if (!is_dir(__DIR__ . '/assets/uploads/')) {
                mkdir(__DIR__ . '/assets/uploads/', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
                if ($sekolah['logo'] && file_exists(__DIR__ . '/assets/uploads/' . $sekolah['logo'])) {
                    unlink(__DIR__ . '/assets/uploads/' . $sekolah['logo']);
                }
                $logo = $filename;
            }
        }
    }
    
    if (updateKonfigurasiSekolah(conn(), $nama_sekolah, $logo, $warna_primer, $warna_sekunder)) {
        $message = 'Profil sekolah berhasil diperbarui!';
        $message_type = 'success';
        $sekolah = getKonfigurasiSekolah(conn());
    } else {
        $message = 'Gagal menyimpan perubahan.';
        $message_type = 'danger';
    }
}

$sekolah = getKonfigurasiSekolah(conn());

ob_start();
?>

<div class="page-header-modern flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-building mr-3 text-primary"></i>Profil Sekolah
    </h2>
</div>

<?php if ($message): ?>
    <div class="alert-modern alert-<?= $message_type === 'success' ? 'success' : 'danger' ?>-modern mb-6 flex items-center gap-3">
        <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-lg"></i>
        <span class="flex-1"><?= htmlspecialchars($message) ?></span>
        <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
    </div>
<?php endif; ?>

<div class="card-modern">
    <div class="card-modern-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                <div class="md:col-span-4 text-center">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3 block">Logo Sekolah</label>
                    <div class="w-[120px] h-[120px] rounded-full bg-gray-100 flex items-center justify-center mx-auto overflow-hidden border-3 border-gray-200 mb-3">
                        <?php if ($sekolah['logo'] && file_exists(__DIR__ . '/assets/uploads/' . $sekolah['logo'])): ?>
                            <img src="<?= asset('uploads/' . $sekolah['logo']) ?>" alt="Logo" class="w-full h-full object-contain">
                        <?php else: ?>
                            <i class="fas fa-school text-gray-400 text-4xl"></i>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="logo" class="form-input-modern text-sm" accept="image/*">
                    <p class="text-xs text-gray-400 mt-1">Max 2MB (JPG, PNG, GIF, WEBP)</p>
                </div>
                
                <div class="md:col-span-8">
                    <div class="mb-4">
                        <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Nama Sekolah</label>
                        <input type="text" name="nama_sekolah" class="form-input-modern" 
                               value="<?= htmlspecialchars($sekolah['nama_sekolah']) ?>" required>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Warna Primer</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="warna_primer" class="w-[60px] h-[45px] rounded-lg border-2 border-gray-200 cursor-pointer"
                                       value="<?= $sekolah['warna_primer'] ?>">
                                <input type="text" class="form-input-modern text-sm" value="<?= $sekolah['warna_primer'] ?>" 
                                       id="warnaPrimerValue" readonly>
                            </div>
                        </div>
                        
                        <div>
                            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Warna Sekunder</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="warna_sekunder" class="w-[60px] h-[45px] rounded-lg border-2 border-gray-200 cursor-pointer"
                                       value="<?= $sekolah['warna_sekunder'] ?>">
                                <input type="text" class="form-input-modern text-sm" value="<?= $sekolah['warna_sekunder'] ?>" 
                                       id="warnaSekunderValue" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-5">
                        <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Preview Tampilan</label>
                        <div class="p-4 rounded-xl text-white flex items-center gap-4" id="previewBar" style="background: linear-gradient(135deg, <?= $sekolah['warna_primer'] ?> 0%, <?= $sekolah['warna_sekunder'] ?> 100%);">
                            <div class="w-[50px] h-[50px] rounded-full flex items-center justify-center" style="background:rgba(255,255,255,0.2);">
                                <i class="fas fa-school"></i>
                            </div>
                            <div>
                                <div class="font-bold"><?= htmlspecialchars($sekolah['nama_sekolah']) ?></div>
                                <small class="opacity-80">Sistem Absensi Siswa</small>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="simpan_profil" class="btn-modern btn-primary-modern">
                        <i class="fas fa-check mr-2"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('input[name="warna_primer"]')?.addEventListener('input', function() {
    document.getElementById('warnaPrimerValue').value = this.value;
    updatePreview();
});
document.querySelector('input[name="warna_sekunder"]')?.addEventListener('input', function() {
    document.getElementById('warnaSekunderValue').value = this.value;
    updatePreview();
});
function updatePreview() {
    const p = document.querySelector('input[name="warna_primer"]')?.value;
    const s = document.querySelector('input[name="warna_sekunder"]')?.value;
    const el = document.getElementById('previewBar');
    if (el && p && s) el.style.background = `linear-gradient(135deg, ${p} 0%, ${s} 100%)`;
}
</script>

<?php
$content = ob_get_clean();
$title = 'Profil Sekolah - Sistem Absensi';

require_once 'views/layout.php';

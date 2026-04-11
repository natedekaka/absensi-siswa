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
            
            if (copy($_FILES['logo']['tmp_name'], $target)) {
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

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Profil Sekolah</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <label class="form-label fw-semibold">Logo Sekolah</label>
                                <div class="logo-preview mb-3" style="width: 120px; height: 120px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto; overflow: hidden; border: 3px solid #e2e8f0;">
                                    <?php if ($sekolah['logo'] && file_exists(__DIR__ . '/assets/uploads/' . $sekolah['logo'])): ?>
                                        <img src="<?= asset('uploads/' . $sekolah['logo']) ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                                    <?php else: ?>
                                        <i class="fas fa-school text-secondary" style="font-size: 3rem;"></i>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="logo" class="form-control" accept="image/*">
                                <small class="text-muted">Max 2MB (JPG, PNG, GIF, WEBP)</small>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Sekolah</label>
                                    <input type="text" name="nama_sekolah" class="form-control" 
                                           value="<?= htmlspecialchars($sekolah['nama_sekolah']) ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Warna Primer</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" name="warna_primer" class="form-control form-control-color" 
                                                   value="<?= $sekolah['warna_primer'] ?>" style="width: 60px; height: 45px;">
                                            <input type="text" class="form-control" value="<?= $sekolah['warna_primer'] ?>" 
                                                   id="warnaPrimerValue" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Warna Sekunder</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" name="warna_sekunder" class="form-control form-control-color" 
                                                   value="<?= $sekolah['warna_sekunder'] ?>" style="width: 60px; height: 45px;">
                                            <input type="text" class="form-control" value="<?= $sekolah['warna_sekunder'] ?>" 
                                                   id="warnaSekunderValue" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Preview Tampilan</label>
                                    <div class="p-3 rounded" style="background: linear-gradient(135deg, <?= $sekolah['warna_primer'] ?> 0%, <?= $sekolah['warna_sekunder'] ?> 100%);">
                                        <div class="d-flex align-items-center gap-3 text-white">
                                            <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-school"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($sekolah['nama_sekolah']) ?></div>
                                                <small>Sistem Absensi Siswa</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="simpan_profil" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('input[name="warna_primer"]').addEventListener('input', function() {
    document.getElementById('warnaPrimerValue').value = this.value;
    updatePreview();
});

document.querySelector('input[name="warna_sekunder"]').addEventListener('input', function() {
    document.getElementById('warnaSekunderValue').value = this.value;
    updatePreview();
});

function updatePreview() {
    const primer = document.querySelector('input[name="warna_primer"]').value;
    const sekunder = document.querySelector('input[name="warna_sekunder"]').value;
    document.querySelector('.rounded[style*="background"]').style.background = 
        `linear-gradient(135deg, ${primer} 0%, ${sekunder} 100%)`;
}
</script>

<?php
$content = ob_get_clean();
$title = 'Profil Sekolah - Sistem Absensi';

require_once 'views/layout.php';

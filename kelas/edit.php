<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin');

$title = 'Edit Kelas - Sistem Absensi Siswa';

$error = '';
$kelas = null;

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = conn()->prepare("SELECT * FROM kelas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("Kelas tidak ditemukan");
    }
    
    $kelas = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nama_kelas = $_POST['nama_kelas'];
    $wali_kelas = $_POST['wali_kelas'] ?? '';
    
    $cek = conn()->prepare("SELECT id FROM kelas WHERE nama_kelas = ? AND id != ?");
    $cek->bind_param("si", $nama_kelas, $id);
    $cek->execute();
    $cek->store_result();
    
    if ($cek->num_rows > 0) {
        $error = "Kelas '$nama_kelas' sudah ada!";
    } else {
        $wali_kelas_id = !empty($_POST['wali_kelas_id']) ? (int)$_POST['wali_kelas_id'] : null;
        $wali_kelas_id = $wali_kelas_id ?: null;
        $stmt = conn()->prepare("UPDATE kelas SET nama_kelas=?, wali_kelas=?, wali_kelas_id=? WHERE id=?");
        $stmt->bind_param("ssii", $nama_kelas, $wali_kelas, $wali_kelas_id, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Kelas berhasil diperbarui!";
            header("Location: index.php");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}

ob_start();
?>

<div class="max-w-lg mx-auto my-12">
    <div class="card-modern overflow-hidden text-center">
        <div class="gradient-header orange text-center">
            <div class="w-[70px] h-[70px] rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(255,255,255,0.2)">
                <i class="fas fa-edit text-2xl text-white"></i>
            </div>
            <h3 class="text-xl font-semibold text-white">Edit Kelas</h3>
            <p class="mt-1 opacity-75 text-sm">Perbarui informasi kelas</p>
        </div>
        <div class="p-6">
            <?php if (!empty($error)): ?>
                <div class="alert-modern alert-danger-modern mb-4 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="text-left">
                <input type="hidden" name="id" value="<?= $kelas['id'] ?>">
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Nama Kelas</label>
                    <div class="relative">
                        <i class="fas fa-door-closed absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="nama_kelas" class="form-input-modern w-full form-input-icon" 
                               value="<?= htmlspecialchars($kelas['nama_kelas']) ?>" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Nama Wali Kelas</label>
                    <div class="relative">
                        <i class="fas fa-user-tie absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="wali_kelas" class="form-input-modern w-full form-input-icon" 
                               value="<?= htmlspecialchars($kelas['wali_kelas'] ?? '') ?>"
                               placeholder="Nama wali kelas (opsional)">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Akun Wali Kelas</label>
                    <div class="relative">
                        <i class="fas fa-user-circle absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <select name="wali_kelas_id" class="form-input-modern w-full form-input-icon">
                            <option value="">-- Tidak ada --</option>
                            <?php
                            $wali_users = conn()->query("SELECT id, nama, username FROM users WHERE role = 'wali_kelas' ORDER BY nama ASC");
                            while ($wu = $wali_users->fetch_assoc()):
                            ?>
                            <option value="<?= $wu['id'] ?>" <?= ($kelas['wali_kelas_id'] == $wu['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($wu['nama'] . ' (' . $wu['username'] . ')') ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5">Pilih akun pengguna dengan role <strong>Wali Kelas</strong>.</p>
                </div>
                <div class="flex gap-3 mt-6">
                    <a href="index.php" class="btn-modern btn-neutral-modern flex-1 justify-center">
                        <i class="fas fa-arrow-left mr-2"></i>Batal
                    </a>
                    <button type="submit" class="btn-modern btn-warning-modern flex-1 justify-center">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

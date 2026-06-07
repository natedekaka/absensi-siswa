<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin');

$title = 'Edit Pengguna - Sistem Absensi Siswa';

$id = (int)$_GET['id'] ?? 0;
$user = conn()->query("SELECT * FROM users WHERE id = $id")->fetch_assoc();
if (!$user) {
    $_SESSION['error'] = 'Pengguna tidak ditemukan.';
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $role = $_POST['role'] ?? 'guru';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';

    if (empty($nama)) {
        $error = 'Nama harus diisi!';
    } else {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = conn()->prepare("UPDATE users SET nama=?, role=?, password=?, is_active=? WHERE id=?");
            $stmt->bind_param("sssii", $nama, $role, $hashed, $is_active, $id);
        } else {
            $stmt = conn()->prepare("UPDATE users SET nama=?, role=?, is_active=? WHERE id=?");
            $stmt->bind_param("ssii", $nama, $role, $is_active, $id);
        }

        if ($stmt->execute()) {
            // Sync guru_kelas
            conn()->query("DELETE FROM guru_kelas WHERE user_id = $id");
            if ($role === 'guru') {
                $all_kelas = conn()->query("SELECT id FROM kelas");
                if ($all_kelas) {
                    $insert = conn()->prepare("INSERT IGNORE INTO guru_kelas (user_id, kelas_id) VALUES (?, ?)");
                    while ($k = $all_kelas->fetch_assoc()) {
                        $insert->bind_param("ii", $id, $k['id']);
                        $insert->execute();
                    }
                }
            }

            $_SESSION['success'] = 'Pengguna berhasil diperbarui!';
            header('Location: index.php');
            exit;
        } else {
            $error = 'Gagal: ' . $stmt->error;
        }
    }
}

ob_start();
?>

<div class="max-w-lg mx-auto my-12">
    <div class="card-modern overflow-hidden">
        <div class="gradient-header blue text-center">
            <div class="w-[70px] h-[70px] rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(255,255,255,0.2)">
                <i class="fas fa-user-edit text-2xl text-white"></i>
            </div>
            <h3 class="text-xl font-semibold text-white">Edit Pengguna</h3>
            <p class="mt-1 opacity-75 text-sm"><?= htmlspecialchars($user['username']) ?></p>
        </div>
        <div class="p-6">
            <?php if (!empty($error)): ?>
                <div class="alert-modern alert-danger-modern mb-4 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i><span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="text-left">
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Username</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" class="form-input-modern w-full form-input-icon bg-gray-50" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Nama Lengkap</label>
                    <div class="relative">
                        <i class="fas fa-id-card absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="nama" class="form-input-modern w-full form-input-icon" value="<?= htmlspecialchars($user['nama']) ?>" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">
                        Password <span class="text-gray-300 font-normal">(kosongkan jika tidak diubah)</span>
                    </label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" class="form-input-modern w-full form-input-icon" placeholder="Biarkan kosong">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Role</label>
                    <div class="relative">
                        <i class="fas fa-user-tag absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <select name="role" class="form-input-modern w-full form-input-icon">
                            <?php foreach (['admin', 'guru', 'wali_kelas', 'orang_tua'] as $r): ?>
                            <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>>
                                <?= ucfirst(str_replace('_', ' ', $r)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-6 flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?> class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary">
                    <label for="is_active" class="text-sm font-medium text-gray-700">Akun aktif</label>
                </div>
                <div class="flex gap-3">
                    <a href="index.php" class="btn-modern btn-neutral-modern flex-1 justify-center">
                        <i class="fas fa-arrow-left mr-2"></i>Batal
                    </a>
                    <button type="submit" class="btn-modern btn-primary-modern flex-1 justify-center">
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

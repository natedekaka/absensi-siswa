<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin');

$title = 'Tambah Pengguna - Sistem Absensi Siswa';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama = trim($_POST['nama'] ?? '');
    $role = $_POST['role'] ?? 'guru';

    if (empty($username) || empty($password) || empty($nama)) {
        $error = 'Username, password, dan nama harus diisi!';
    } else {
        $cek = conn()->prepare("SELECT id FROM users WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = "Username '$username' sudah digunakan!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = conn()->prepare("INSERT INTO users (username, password, nama, role, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $username, $hashed, $nama, $role);

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;

                // Jika guru, otomatis link ke semua kelas
                if ($role === 'guru') {
                    $all_kelas = conn()->query("SELECT id FROM kelas");
                    if ($all_kelas) {
                        $insert = conn()->prepare("INSERT IGNORE INTO guru_kelas (user_id, kelas_id) VALUES (?, ?)");
                        while ($k = $all_kelas->fetch_assoc()) {
                            $insert->bind_param("ii", $user_id, $k['id']);
                            $insert->execute();
                        }
                    }
                }

                $_SESSION['success'] = 'Pengguna berhasil ditambahkan!';
                header('Location: index.php');
                exit;
            } else {
                $error = 'Gagal: ' . $stmt->error;
            }
        }
    }
}

ob_start();
?>

<div class="max-w-lg mx-auto my-12">
    <div class="card-modern overflow-hidden">
        <div class="gradient-header purple text-center">
            <div class="w-[70px] h-[70px] rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(255,255,255,0.2)">
                <i class="fas fa-user-plus text-2xl text-white"></i>
            </div>
            <h3 class="text-xl font-semibold text-white">Tambah Pengguna</h3>
            <p class="mt-1 opacity-75 text-sm">Buat akun baru untuk guru, wali kelas, atau orang tua</p>
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
                        <input type="text" name="username" class="form-input-modern w-full form-input-icon" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Nama Lengkap</label>
                    <div class="relative">
                        <i class="fas fa-id-card absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="nama" class="form-input-modern w-full form-input-icon" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" class="form-input-modern w-full form-input-icon" required>
                    </div>
                </div>
                <div class="mb-6">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Role</label>
                    <div class="relative">
                        <i class="fas fa-user-tag absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <select name="role" class="form-input-modern w-full form-input-icon">
                            <option value="guru">Guru</option>
                            <option value="wali_kelas">Wali Kelas</option>
                            <option value="orang_tua">Orang Tua</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5">Guru otomatis mendapatkan akses ke semua kelas.</p>
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

<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin');

$title = 'Manajemen Pengguna - Sistem Absensi Siswa';

// Hapus user
if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    if ($hapus_id === (int)$_SESSION['user']['id']) {
        $_SESSION['error'] = 'Tidak bisa menghapus akun sendiri!';
    } else {
        $stmt = conn()->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $hapus_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Pengguna berhasil dihapus.';
        } else {
            $_SESSION['error'] = 'Gagal menghapus pengguna.';
        }
    }
    header("Location: index.php");
    exit;
}

ob_start();

$users = conn()->query("SELECT id, username, nama, role, is_active, last_login FROM users ORDER BY role ASC, nama ASC");
?>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-users-cog mr-3 text-primary"></i>Manajemen Pengguna
    </h2>
    <a href="tambah.php" class="btn-modern btn-primary-modern">
        <i class="fas fa-plus mr-2"></i>Tambah Pengguna
    </a>
</div>

<div class="card-modern overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table-modern w-full">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Terakhir Login</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users && $users->num_rows > 0): ?>
                    <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td class="font-mono text-sm font-medium"><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['nama']) ?></td>
                        <td>
                            <?php
                            $role_badges = [
                                'admin' => ['bg-purple-100 text-purple-700', 'shield-alt'],
                                'guru' => ['bg-blue-100 text-blue-700', 'chalkboard-teacher'],
                                'wali_kelas' => ['bg-green-100 text-green-700', 'user-tie'],
                                'orang_tua' => ['bg-orange-100 text-orange-700', 'user-friends'],
                            ];
                            $badge = $role_badges[$u['role']] ?? ['bg-gray-100 text-gray-600', 'user'];
                            ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= $badge[0] ?>">
                                <i class="fas fa-<?= $badge[1] ?>"></i>
                                <?= ucfirst(str_replace('_', ' ', $u['role'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="text-green-600 font-semibold text-xs"><i class="fas fa-check-circle mr-1"></i>Aktif</span>
                            <?php else: ?>
                                <span class="text-red-500 font-semibold text-xs"><i class="fas fa-times-circle mr-1"></i>Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-gray-500">
                            <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : '-' ?>
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="edit.php?id=<?= $u['id'] ?>" class="btn-modern btn-neutral-modern !px-3 !py-1.5 text-xs">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ((int)$u['id'] !== (int)$_SESSION['user']['id']): ?>
                                <a href="?hapus=<?= $u['id'] ?>" class="btn-modern !px-3 !py-1.5 text-xs"
                                   style="background:#FEE2E2;color:#DC2626;border:1px solid #FECACA;"
                                   onclick="return confirm('Hapus pengguna <?= htmlspecialchars($u['nama']) ?>?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-gray-400 py-8">Belum ada pengguna</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

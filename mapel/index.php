<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin');

$title = 'Mata Pelajaran - Sistem Absensi Siswa';

$error = '';
$success = '';

// Handle hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    conn()->query("DELETE FROM mapel WHERE id = $id");
    $_SESSION['success'] = 'Mata pelajaran berhasil dihapus!';
    header('Location: index.php');
    exit;
}

// Handle tambah / edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_mapel = trim($_POST['nama_mapel'] ?? '');
    $edit_id = (int)($_POST['id'] ?? 0);

    if (empty($nama_mapel)) {
        $error = 'Nama mata pelajaran harus diisi!';
    } else {
        if ($edit_id > 0) {
            $stmt = conn()->prepare("UPDATE mapel SET nama_mapel = ? WHERE id = ?");
            $stmt->bind_param("si", $nama_mapel, $edit_id);
            $stmt->execute();
            $_SESSION['success'] = 'Mata pelajaran berhasil diperbarui!';
        } else {
            $stmt = conn()->prepare("INSERT INTO mapel (nama_mapel) VALUES (?)");
            $stmt->bind_param("s", $nama_mapel);
            $stmt->execute();
            $_SESSION['success'] = 'Mata pelajaran berhasil ditambahkan!';
        }
        header('Location: index.php');
        exit;
    }
}

$mapel_list = conn()->query("SELECT * FROM mapel ORDER BY nama_mapel");
$edit_mapel = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_mapel = conn()->query("SELECT * FROM mapel WHERE id = $edit_id")->fetch_assoc();
}

ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-book mr-3 text-primary"></i>Mata Pelajaran
    </h2>
</div>

<?php if ($success = $_SESSION['success'] ?? ''): unset($_SESSION['success']); ?>
<div class="alert-modern alert-success-modern mb-4 flex items-center gap-3">
    <i class="fas fa-check-circle"></i><span><?= $success ?></span>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert-modern alert-danger-modern mb-4 flex items-center gap-3">
    <i class="fas fa-exclamation-circle"></i><span><?= $error ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form tambah/edit -->
    <div class="card-modern">
        <div class="card-modern-header">
            <i class="fas fa-<?= $edit_mapel ? 'edit' : 'plus' ?> mr-2 text-primary"></i>
            <?= $edit_mapel ? 'Edit Mapel' : 'Tambah Mapel' ?>
        </div>
        <div class="card-modern-body">
            <form method="POST">
                <?php if ($edit_mapel): ?>
                <input type="hidden" name="id" value="<?= $edit_mapel['id'] ?>">
                <?php endif; ?>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Nama Mapel</label>
                    <input type="text" name="nama_mapel" class="form-input-modern" 
                           value="<?= $edit_mapel ? htmlspecialchars($edit_mapel['nama_mapel']) : '' ?>" 
                           placeholder="Contoh: Informatika" required>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-modern btn-primary-modern flex-1 justify-center">
                        <i class="fas fa-save mr-2"></i><?= $edit_mapel ? 'Update' : 'Simpan' ?>
                    </button>
                    <?php if ($edit_mapel): ?>
                    <a href="index.php" class="btn-modern btn-neutral-modern justify-center">
                        <i class="fas fa-times"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar mapel -->
    <div class="lg:col-span-2">
        <div class="card-modern">
            <div class="card-modern-header">
                <i class="fas fa-list mr-2 text-primary"></i>Daftar Mata Pelajaran
            </div>
            <div class="card-modern-body p-0">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Mapel</th>
                            <th class="text-center">Guru Terdaftar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $mapel_list = conn()->query("
                            SELECT m.*, 
                                   COUNT(gk.id) as total_guru
                            FROM mapel m
                            LEFT JOIN guru_kelas gk ON gk.mapel_id = m.id
                            GROUP BY m.id
                            ORDER BY m.nama_mapel
                        ");
                        while ($m = $mapel_list->fetch_assoc()): 
                        ?>
                        <tr>
                            <td class="text-gray-500"><?= $no++ ?></td>
                            <td class="font-medium"><?= htmlspecialchars($m['nama_mapel']) ?></td>
                            <td class="text-center"><?= $m['total_guru'] ?> guru</td>
                            <td class="text-center">
                                <a href="?edit=<?= $m['id'] ?>" class="text-primary hover:text-primary-dark mr-2" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?hapus=<?= $m['id'] ?>" class="text-red-500 hover:text-red-700" 
                                   onclick="return confirm('Hapus mapel <?= htmlspecialchars($m['nama_mapel']) ?>?')" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

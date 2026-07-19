<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin');

$title = 'Atur Orang Tua Siswa - Sistem Absensi Siswa';

// Hapus link orang_tua
if (isset($_GET['hapus']) && isset($_GET['siswa_id'])) {
    $hapus_id = (int)$_GET['hapus'];
    $siswa_id = (int)$_GET['siswa_id'];
    conn()->query("DELETE FROM siswa_orang_tua WHERE id = $hapus_id AND siswa_id = $siswa_id");
    $_SESSION['success'] = 'Link orang tua berhasil dihapus.';
    header("Location: orang_tua.php?siswa_id=$siswa_id");
    exit;
}

// Tambah link orang_tua
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['siswa_id'], $_POST['orang_tua_id'])) {
    $siswa_id = (int)$_POST['siswa_id'];
    $orang_tua_id = (int)$_POST['orang_tua_id'];
    $hubungan = $_POST['hubungan'] ?? 'wali';

    $cek = conn()->query("SELECT id FROM siswa_orang_tua WHERE siswa_id = $siswa_id AND orang_tua_id = $orang_tua_id");
    if ($cek && $cek->num_rows === 0) {
        $stmt = conn()->prepare("INSERT INTO siswa_orang_tua (siswa_id, orang_tua_id, hubungan) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $siswa_id, $orang_tua_id, $hubungan);
        $stmt->execute();
        $_SESSION['success'] = 'Orang tua berhasil ditautkan!';
    } else {
        $_SESSION['error'] = 'Orang tua sudah ditautkan ke siswa ini.';
    }
    header("Location: orang_tua.php?siswa_id=$siswa_id");
    exit;
}

ob_start();

$siswa_id = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : 0;
$siswa = null;

if ($siswa_id > 0) {
    $siswa = conn()->query("SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.id = $siswa_id")->fetch_assoc();
}
?>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-user-friends mr-3 text-primary"></i>Atur Orang Tua Siswa
    </h2>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: student search -->
    <div class="card-modern">
        <div class="card-modern-header">
            <i class="fas fa-search mr-2 text-primary"></i>Cari Siswa
        </div>
        <div class="card-modern-body">
            <form method="GET" class="space-y-3">
                <div>
                    <select name="siswa_id" class="form-input-modern w-full" onchange="this.form.submit()">
                        <option value="">-- Pilih siswa --</option>
                        <?php
                        $siswa_list = conn()->query("SELECT s.id, s.nama, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.status = 'aktif' OR s.status IS NULL ORDER BY s.nama ASC");
                        while ($s = $siswa_list->fetch_assoc()):
                        ?>
                        <option value="<?= $s['id'] ?>" <?= $siswa_id === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nama'] . ' (' . ($s['nama_kelas'] ?? 'No Kelas') . ')') ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if (!$siswa_id): ?>
                <p class="text-xs text-gray-400 text-center pt-4">Pilih siswa untuk mengelola orang tua.</p>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Right: parent management -->
    <div class="lg:col-span-2">
        <?php if ($siswa): ?>
        <!-- Current parents -->
        <div class="card-modern mb-6">
            <div class="card-modern-header flex items-center justify-between">
                <div>
                    <i class="fas fa-users mr-2 text-primary"></i>Orang Tua <strong><?= htmlspecialchars($siswa['nama']) ?></strong>
                    <span class="text-xs text-gray-400 ml-2">(<?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?>)</span>
                </div>
            </div>
            <div class="card-modern-body p-0">
                <?php
                $ortu = conn()->query("
                    SELECT so.id, so.hubungan, u.nama, u.username
                    FROM siswa_orang_tua so
                    JOIN users u ON so.orang_tua_id = u.id
                    WHERE so.siswa_id = $siswa_id
                    ORDER BY so.hubungan ASC
                ");
                ?>
                <?php if ($ortu && $ortu->num_rows > 0): ?>
                <div class="divide-y divide-gray-100">
                    <?php while ($o = $ortu->fetch_assoc()): ?>
                    <div class="flex items-center justify-between px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-sm">
                                <i class="fas fa-user-friends"></i>
                            </div>
                            <div>
                                <div class="font-medium text-sm"><?= htmlspecialchars($o['nama']) ?></div>
                                <div class="text-xs text-gray-400">
                                    <?= htmlspecialchars($o['username']) ?>
                                    <span class="ml-2 px-2 py-0.5 bg-gray-100 rounded-full text-xs font-medium">
                                        <?= ucfirst($o['hubungan']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <a href="?siswa_id=<?= $siswa_id ?>&hapus=<?= $o['id'] ?>"
                           class="text-red-400 hover:text-red-600 transition p-2"
                           onclick="return confirm('Hapus <?= htmlspecialchars($o['nama']) ?> dari siswa ini?')">
                            <i class="fas fa-unlink"></i>
                        </a>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-user-slash text-3xl mb-3"></i>
                    <p class="text-sm">Belum ada orang tua yang ditautkan.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add parent form -->
        <div class="card-modern">
            <div class="card-modern-header">
                <i class="fas fa-link mr-2 text-primary"></i>Tautkan Orang Tua
            </div>
            <div class="card-modern-body">
                <form method="POST" class="flex items-end gap-3">
                    <input type="hidden" name="siswa_id" value="<?= $siswa_id ?>">
                    <div class="flex-1">
                        <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Akun Orang Tua</label>
                        <select name="orang_tua_id" class="form-input-modern w-full" required>
                            <option value="">-- Pilih --</option>
                            <?php
                            $ortu_list = conn()->query("SELECT id, nama, username FROM users WHERE role = 'orang_tua' ORDER BY nama ASC");
                            while ($o = $ortu_list->fetch_assoc()):
                            ?>
                            <option value="<?= $o['id'] ?>">
                                <?= htmlspecialchars($o['nama'] . ' (' . $o['username'] . ')') ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Hubungan</label>
                        <select name="hubungan" class="form-input-modern w-full">
                            <option value="ayah">Ayah</option>
                            <option value="ibu">Ibu</option>
                            <option value="wali">Wali</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-modern btn-primary-modern">
                        <i class="fas fa-plus mr-2"></i>Tautkan
                    </button>
                </form>
                <?php if (($ortu_list && $ortu_list->num_rows === 0)): ?>
                <p class="text-xs text-gray-400 mt-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Belum ada akun Orang Tua. <a href="../users/tambah.php" class="text-primary font-semibold">Buat akun</a> terlebih dahulu.
                </p>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($siswa_id > 0 && !$siswa): ?>
        <div class="card-modern">
            <div class="card-modern-body text-center py-12 text-gray-400">
                <i class="fas fa-exclamation-circle text-3xl mb-3"></i>
                <p>Siswa tidak ditemukan.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="card-modern">
            <div class="card-modern-body text-center py-12">
                <i class="fas fa-child text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-400">Pilih siswa dari daftar untuk mengelola tautan orang tua.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

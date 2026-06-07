<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin');

$title = 'Atur Guru Kelas - Sistem Absensi Siswa';

// Proses simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $assignments = $_POST['assignments'] ?? [];

    conn()->query("DELETE FROM guru_kelas WHERE user_id = $user_id");

    if (!empty($assignments)) {
        $stmt = conn()->prepare("INSERT INTO guru_kelas (user_id, kelas_id, mapel_id) VALUES (?, ?, ?)");
        foreach ($assignments as $a) {
            $kid = (int)($a['kelas_id'] ?? 0);
            $mid = (int)($a['mapel_id'] ?? 0);
            if ($kid > 0 && $mid > 0) {
                $stmt->bind_param("iii", $user_id, $kid, $mid);
                $stmt->execute();
            }
        }
    }

    $_SESSION['success'] = 'Penugasan guru berhasil diperbarui!';
    header('Location: guru.php?user_id=' . $user_id);
    exit;
}

ob_start();

$guru_list = conn()->query("SELECT id, nama, username FROM users WHERE role = 'guru' ORDER BY nama ASC");
$kelas_list = conn()->query("SELECT k.*, COUNT(s.id) as total_siswa FROM kelas k LEFT JOIN siswa s ON s.kelas_id = k.id GROUP BY k.id ORDER BY k.nama_kelas");
$mapel_list = conn()->query("SELECT * FROM mapel ORDER BY nama_mapel");

$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$assigned = []; // kelas_id => mapel_id

if ($selected_user_id > 0) {
    $q = conn()->query("SELECT kelas_id, mapel_id FROM guru_kelas WHERE user_id = $selected_user_id");
    while ($r = $q->fetch_assoc()) {
        $assigned[$r['kelas_id']] = $r['mapel_id'];
    }
}
?>
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-chalkboard-teacher mr-3 text-primary"></i>Atur Guru Kelas
    </h2>
</div>

<?php if ($success = $_SESSION['success'] ?? ''): unset($_SESSION['success']); ?>
<div class="alert-modern alert-success-modern mb-4 flex items-center gap-3">
    <i class="fas fa-check-circle"></i><span><?= $success ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: guru list -->
    <div class="card-modern">
        <div class="card-modern-header">
            <i class="fas fa-users mr-2 text-primary"></i>Pilih Guru
        </div>
        <div class="card-modern-body p-0">
            <div class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
                <?php while ($g = $guru_list->fetch_assoc()): 
                    $total_kelas = conn()->query("SELECT COUNT(*) as c FROM guru_kelas WHERE user_id = {$g['id']} AND mapel_id IS NOT NULL")->fetch_assoc()['c'];
                ?>
                <a href="?user_id=<?= $g['id'] ?>"
                   class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 transition <?= $selected_user_id == $g['id'] ? 'bg-primary-50 border-l-4 border-primary' : '' ?>">
                    <div>
                        <span class="font-medium text-sm"><?= htmlspecialchars($g['nama']) ?></span>
                        <div class="text-xs text-gray-400">@<?= htmlspecialchars($g['username']) ?></div>
                    </div>
                    <span class="text-xs <?= $total_kelas > 0 ? 'text-primary font-semibold' : 'text-gray-400' ?>"><?= $total_kelas ?> kelas</span>
                </a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Right: class assignment form -->
    <div class="lg:col-span-2">
        <?php if ($selected_user_id > 0):
            $guru = conn()->query("SELECT * FROM users WHERE id = $selected_user_id")->fetch_assoc();
        ?>
        <div class="card-modern">
            <div class="card-modern-header flex items-center justify-between">
                <div>
                    <i class="fas fa-door-open mr-2 text-primary"></i>Atur Kelas untuk <strong><?= htmlspecialchars($guru['nama']) ?></strong>
                </div>
                <span class="text-xs text-gray-400"><?= count($assigned) ?> kelas dipilih</span>
            </div>
            <div class="card-modern-body">
                <form method="POST" id="form-assign">
                    <input type="hidden" name="user_id" value="<?= $selected_user_id ?>">

                    <?php if ($kelas_list && $kelas_list->num_rows > 0): ?>
                    <div class="space-y-3 max-h-[500px] overflow-y-auto">
                        <?php 
                        $no = 1;
                        $kelas_list->data_seek(0);
                        while ($k = $kelas_list->fetch_assoc()): 
                            $is_assigned = isset($assigned[$k['id']]);
                            $selected_mapel = $assigned[$k['id']] ?? 0;
                        ?>
                        <div class="flex items-start gap-3 p-3 rounded-xl border <?= $is_assigned ? 'border-primary-200 bg-primary-50/50' : 'border-gray-200' ?> transition">
                            <input type="checkbox" name="assignments[<?= $no ?>][kelas_id]" value="<?= $k['id'] ?>"
                                   class="assign-check mt-1 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary"
                                   data-row="<?= $no ?>"
                                   <?= $is_assigned ? 'checked' : '' ?>>
                            <div class="flex-1">
                                <div class="font-medium text-sm"><?= htmlspecialchars($k['nama_kelas']) ?></div>
                                <div class="text-xs text-gray-400 mb-2"><?= $k['total_siswa'] ?> siswa</div>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-500">Mapel:</label>
                                    <select name="assignments[<?= $no ?>][mapel_id]" class="assign-mapel form-input-modern text-sm py-1"
                                            data-row="<?= $no ?>" <?= !$is_assigned ? 'disabled' : '' ?>>
                                        <option value="">-- Pilih Mapel --</option>
                                        <?php 
                                        $mapel_list->data_seek(0);
                                        while ($m = $mapel_list->fetch_assoc()): 
                                        ?>
                                        <option value="<?= $m['id'] ?>" <?= $selected_mapel == $m['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['nama_mapel']) ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <a href="#" class="text-xs text-primary hover:underline mt-1 whitespace-nowrap" onclick="return quickAssign(this, <?= $k['id'] ?>, <?= $no ?>)">
                                <i class="fas fa-bolt"></i> Cepat
                            </a>
                        </div>
                        <?php $no++; endwhile; ?>
                    </div>

                    <script>
                    document.querySelectorAll('.assign-check').forEach(function(cb) {
                        cb.addEventListener('change', function() {
                            const row = this.dataset.row;
                            const mapel = document.querySelector('.assign-mapel[data-row="' + row + '"]');
                            mapel.disabled = !this.checked;
                            if (!this.checked) mapel.value = '';
                        });
                    });
                    document.getElementById('form-assign').addEventListener('submit', function(e) {
                        let valid = true;
                        document.querySelectorAll('.assign-check:checked').forEach(function(cb) {
                            const row = cb.dataset.row;
                            const mapel = document.querySelector('.assign-mapel[data-row="' + row + '"]');
                            if (!mapel.value) {
                                valid = false;
                                mapel.classList.add('border-red-500');
                            }
                        });
                        if (!valid) {
                            e.preventDefault();
                            alert('Semua kelas yang dicentang harus dipilih mata pelajarannya!');
                        }
                    });
                    function quickAssign(link, kelasId, row) {
                        const checkbox = document.querySelector('.assign-check[data-row="' + row + '"]');
                        const mapel = document.querySelector('.assign-mapel[data-row="' + row + '"]');
                        checkbox.checked = true;
                        checkbox.dispatchEvent(new Event('change'));
                        mapel.focus();
                        return false;
                    }
                    </script>
                    <?php else: ?>
                    <p class="text-gray-400 text-center py-8">Belum ada kelas. <a href="../kelas/tambah.php" class="text-primary font-semibold">Tambah kelas</a></p>
                    <?php endif; ?>

                    <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="submit" class="btn-modern btn-primary-modern flex-1 justify-center">
                            <i class="fas fa-save mr-2"></i>Simpan
                        </button>
                        <a href="guru.php?user_id=<?= $selected_user_id ?>" class="btn-modern btn-neutral-modern justify-center">
                            <i class="fas fa-sync-alt mr-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="card-modern">
            <div class="card-modern-body text-center py-12">
                <i class="fas fa-hand-point-left text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-400">Pilih guru dari daftar di samping untuk mengatur kelas dan mata pelajaran yang diajar.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

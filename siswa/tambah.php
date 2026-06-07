<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin', 'guru', 'wali_kelas');

$title = 'Tambah Siswa - Sistem Absensi Siswa';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = db()->escape($_POST['nis']);
    $nisn = db()->escape($_POST['nisn']);
    $nama = db()->escape($_POST['nama']);
    $kelas_id = (int)$_POST['kelas_id'];
    $jenis_kelamin = $_POST['jenis_kelamin'];

    if (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'])) {
        $error = "Jenis kelamin harus dipilih.";
    } else {
        $stmt = conn()->prepare("INSERT INTO siswa (nis, nisn, nama, kelas_id, jenis_kelamin) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $nis, $nisn, $nama, $kelas_id, $jenis_kelamin);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Siswa berhasil ditambahkan!";
            header("Location: index.php");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}

$kelas = conn()->query("SELECT * FROM kelas ORDER BY nama_kelas");

ob_start();
?>

<div class="max-w-2xl mx-auto my-12">
    <div class="card-modern overflow-hidden text-center">
        <div class="p-8 text-white" style="background:linear-gradient(135deg,var(--wa-dark,#0d9488) 0%,#0f766e 100%)">
            <div class="w-[70px] h-[70px] rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(255,255,255,0.2)">
                <i class="fas fa-user-plus text-2xl text-white"></i>
            </div>
            <h3 class="text-xl font-semibold text-white">Tambah Siswa Baru</h3>
            <p class="mt-1 opacity-75 text-sm">Isi data siswa dengan lengkap</p>
        </div>
        <div class="p-6">
            <?php if (!empty($error)): ?>
                <div class="alert-modern alert-danger-modern mb-4 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="text-left">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">NIS</label>
                        <div class="relative">
                            <i class="fas fa-id-card absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" name="nis" class="form-input-modern w-full form-input-icon" required>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">NISN</label>
                        <div class="relative">
                            <i class="fas fa-id-card absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" name="nisn" class="form-input-modern w-full form-input-icon" required>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Nama Lengkap</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="nama" class="form-input-modern w-full form-input-icon" placeholder="Nama siswa" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Jenis Kelamin</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="gender-opt border-2 border-gray-200 rounded-xl p-4 text-center cursor-pointer transition-all hover:border-primary" onclick="selectGender(this,'Laki-laki')">
                            <input type="radio" name="jenis_kelamin" value="Laki-laki" class="hidden">
                            <i class="fas fa-male block text-xl mb-2 text-primary"></i>
                            <span class="text-sm">Laki-laki</span>
                        </label>
                        <label class="gender-opt border-2 border-gray-200 rounded-xl p-4 text-center cursor-pointer transition-all hover:border-primary" onclick="selectGender(this,'Perempuan')">
                            <input type="radio" name="jenis_kelamin" value="Perempuan" class="hidden">
                            <i class="fas fa-female block text-xl mb-2 text-pink-500"></i>
                            <span class="text-sm">Perempuan</span>
                        </label>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Kelas</label>
                    <div class="relative">
                        <i class="fas fa-door-open absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <select name="kelas_id" class="form-input-modern w-full form-input-icon" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php while ($row = $kelas->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kelas']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
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

<style>
.gender-opt.selected { border-color: var(--color-primary,#0d9488) !important; background: rgba(13,148,136,0.08); }
</style>
<script>
function selectGender(el, val) {
    document.querySelectorAll('.gender-opt').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input').checked = true;
}
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

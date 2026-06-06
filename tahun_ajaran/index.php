<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Tahun Ajaran & Semester - Sistem Absensi Siswa';

// Proses Tahun Ajaran
if (isset($_POST['tambah_tahun'])) {
    $nama = $_POST['nama'];
    $stmt = conn()->prepare("INSERT INTO tahun_ajaran (nama) VALUES (?)");
    $stmt->bind_param("s", $nama);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Tahun ajaran berhasil ditambahkan!";
    }
    $stmt->close();
}

if (isset($_POST['hapus_tahun'])) {
    $id = $_POST['id'];
    // Cek apakah ada semester
    $cek = conn()->query("SELECT COUNT(*) as total FROM semester WHERE tahun_ajaran_id = $id")->fetch_assoc();
    if ($cek['total'] > 0) {
        $_SESSION['error'] = "Hapus terlebih dahulu semester dalam tahun ajaran ini!";
    } else {
        conn()->query("DELETE FROM tahun_ajaran WHERE id = $id");
        $_SESSION['success'] = "Tahun ajaran berhasil dihapus!";
    }
}

// Proses Semester
if (isset($_POST['tambah_semester'])) {
    $tahun_ajaran_id = $_POST['tahun_ajaran_id'];
    $semester = $_POST['semester'];
    $tgl_mulai = $_POST['tgl_mulai'];
    $tgl_selesai = $_POST['tgl_selesai'];
    
    $nama = "Semester $semester";
    
    $stmt = conn()->prepare("INSERT INTO semester (tahun_ajaran_id, semester, nama, tgl_mulai, tgl_selesai) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $tahun_ajaran_id, $semester, $nama, $tgl_mulai, $tgl_selesai);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Semester berhasil ditambahkan!";
    }
    $stmt->close();
}

if (isset($_POST['aktifkan_semester'])) {
    $id = $_POST['id'];
    conn()->query("UPDATE semester SET is_active = 0");
    conn()->query("UPDATE semester SET is_active = 1 WHERE id = $id");
    
    // Update juga tahun ajaran terkait
    $semester = conn()->query("SELECT tahun_ajaran_id FROM semester WHERE id = $id")->fetch_assoc();
    if ($semester) {
        conn()->query("UPDATE tahun_ajaran SET is_active = 0");
        conn()->query("UPDATE tahun_ajaran SET is_active = 1 WHERE id = " . $semester['tahun_ajaran_id']);
    }
    $_SESSION['success'] = "Semester berhasil diaktifkan!";
}

if (isset($_POST['hapus_semester'])) {
    $id = $_POST['id'];
    conn()->query("DELETE FROM semester WHERE id = $id");
    $_SESSION['success'] = "Semester berhasil dihapus!";
}

ob_start();
?>

<style>
.semester-item { border-left:3px solid #ccc; padding:0.75rem 1rem; margin:0.5rem 0; background:#f8f9fa; border-radius:0 10px 10px 0; transition:all .2s; position:relative; }
.semester-item:hover { background:#f0fdfa; }
.semester-item.active { border-left:4px solid #10b981; background:linear-gradient(90deg,rgba(16,185,129,0.12) 0%,#f8f9fa 100%); }
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; opacity:0; visibility:hidden; transition:all .2s; }
.modal-overlay.open { opacity:1; visibility:visible; }
.modal-card { background:white; border-radius:20px; max-width:500px; width:90%; max-height:90vh; overflow-y:auto; transform:scale(0.95); transition:transform .2s; }
.modal-overlay.open .modal-card { transform:scale(1); }
.modal-header-gradient { background:linear-gradient(135deg,var(--wa-dark,#0d9488) 0%,#0f766e 100%); color:white; border-radius:20px 20px 0 0; }
</style>

<div class="page-header-modern flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800 dark:text-white">
        <i class="fas fa-calendar-alt mr-3 text-primary"></i>Tahun Ajaran & Semester
    </h2>
</div>

<?php
$tahun_ajaran = conn()->query("SELECT * FROM tahun_ajaran ORDER BY nama DESC");
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php while ($ta = $tahun_ajaran->fetch_assoc()):
    $semester = conn()->query("SELECT * FROM semester WHERE tahun_ajaran_id = " . $ta['id'] . " ORDER BY semester ASC");
    $jml_semester = $semester->num_rows;
    ?>
    <div class="card-modern overflow-hidden <?= $ta['is_active'] ? 'ring-2 ring-emerald-500' : '' ?>">
        <div class="px-5 py-4 text-white relative <?= $ta['is_active'] ? 'bg-gradient-to-r from-emerald-500 to-emerald-600' : 'bg-gradient-to-r from-teal-700 to-teal-800' ?>">
            <div class="flex items-start justify-between">
                <div>
                    <h5 class="font-bold flex items-center gap-2">
                        <i class="fas fa-school"></i><?= htmlspecialchars($ta['nama']) ?>
                    </h5>
                    <small class="opacity-75"><?= $jml_semester ?> Semester</small>
                </div>
                <?php if ($ta['is_active']): ?>
                <span class="badge-modern bg-white/90 text-emerald-600 text-xs"><i class="fas fa-check-circle mr-1"></i>Aktif</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="p-4">
            <?php if ($semester && $jml_semester > 0): ?>
                <?php while ($sm = $semester->fetch_assoc()): ?>
                <div class="semester-item flex items-center justify-between <?= $sm['is_active'] ? 'active' : '' ?>">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <?php if ($sm['is_active']): ?>
                                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-500 text-white"><i class="fas fa-star mr-1"></i>AKTIF</span>
                            <?php endif; ?>
                            <span class="font-semibold <?= $sm['is_active'] ? 'text-emerald-600' : 'text-gray-700' ?>">
                                <i class="fas fa-book mr-1 <?= $sm['is_active'] ? 'text-emerald-500' : 'text-gray-400' ?>"></i>
                                <?= htmlspecialchars($sm['nama']) ?>
                            </span>
                        </div>
                        <small class="text-gray-400 block mt-1">
                            <i class="fas fa-calendar mr-1"></i>
                            <?= date('d M Y', strtotime($sm['tgl_mulai'])) ?> - <?= date('d M Y', strtotime($sm['tgl_selesai'])) ?>
                        </small>
                    </div>
                    <div class="flex gap-1 ml-2 shrink-0">
                        <?php if ($sm['is_active']): ?>
                            <span class="text-emerald-500"><i class="fas fa-check-circle text-lg"></i></span>
                        <?php else: ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="id" value="<?= $sm['id'] ?>">
                                <button type="submit" name="aktifkan_semester" class="w-8 h-8 rounded-lg inline-flex items-center justify-center bg-emerald-50 text-emerald-600 hover:bg-emerald-100" title="Aktifkan">
                                    <i class="fas fa-power-off text-sm"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Yakin hapus semester ini?')">
                            <input type="hidden" name="id" value="<?= $sm['id'] ?>">
                            <button type="submit" name="hapus_semester" class="w-8 h-8 rounded-lg inline-flex items-center justify-center bg-red-50 text-red-500 hover:bg-red-100" title="Hapus">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-gray-400 py-4">
                    <i class="fas fa-folder-open text-2xl mb-2"></i>
                    <p class="text-sm">Belum ada semester</p>
                </div>
            <?php endif; ?>
            
            <button onclick="openModal('semesterModal<?= $ta['id'] ?>')" class="btn-modern btn-primary-modern w-full justify-center text-sm mt-3">
                <i class="fas fa-plus mr-1"></i> Tambah Semester
            </button>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <small class="text-gray-400">Dibuat: <?= date('d/m/Y', strtotime($ta['created_at'] ?? date('Y-m-d'))) ?></small>
                <form method="POST" onsubmit="return confirm('Yakin hapus tahun ajaran <?= addslashes($ta['nama']) ?>?')">
                <input type="hidden" name="id" value="<?= $ta['id'] ?>">
                <button type="submit" name="hapus_tahun" class="text-red-500 hover:text-red-600 text-sm" title="Hapus">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Semester -->
    <div class="modal-overlay" id="semesterModal<?= $ta['id'] ?>">
        <div class="modal-card">
            <div class="modal-header-gradient px-6 py-4 flex items-center justify-between rounded-t-2xl">
                <h5 class="font-semibold text-white"><i class="fas fa-plus mr-2"></i>Tambah Semester</h5>
                <button onclick="closeModal('semesterModal<?= $ta['id'] ?>')" class="text-white/80 hover:text-white text-xl leading-none">&times;</button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="tahun_ajaran_id" value="<?= $ta['id'] ?>">
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Semester</label>
                    <select name="semester" class="form-input-modern w-full" required>
                        <option value="">-- Pilih --</option>
                        <option value="1">Semester 1 (Ganjil)</option>
                        <option value="2">Semester 2 (Genap)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Tanggal Mulai</label>
                    <input type="date" name="tgl_mulai" class="form-input-modern w-full" required>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Tanggal Selesai</label>
                    <input type="date" name="tgl_selesai" class="form-input-modern w-full" required>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeModal('semesterModal<?= $ta['id'] ?>')" class="btn-modern btn-neutral-modern">Batal</button>
                    <button type="submit" name="tambah_semester" class="btn-modern btn-primary-modern">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endwhile; ?>

    <!-- Card Tambah Tahun Ajaran -->
    <button onclick="openModal('tahunModal')" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl min-h-[200px] flex items-center justify-center hover:border-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/10 transition-all cursor-pointer">
        <div class="text-center p-4">
            <i class="fas fa-plus-circle text-4xl text-gray-400 mb-3"></i>
            <h6 class="text-gray-400 font-medium">Tambah Tahun Ajaran</h6>
        </div>
    </button>
</div>

<!-- Modal Tambah Tahun Ajaran -->
<div class="modal-overlay" id="tahunModal">
    <div class="modal-card">
        <div class="modal-header-gradient px-6 py-4 flex items-center justify-between rounded-t-2xl">
            <h5 class="font-semibold text-white"><i class="fas fa-plus mr-2"></i>Tambah Tahun Ajaran</h5>
            <button onclick="closeModal('tahunModal')" class="text-white/80 hover:text-white text-xl leading-none">&times;</button>
        </div>
        <form method="POST" class="p-6">
            <div class="mb-4">
                <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Nama Tahun Ajaran</label>
                <div class="relative">
                    <i class="fas fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="nama" class="form-input-modern w-full pl-10" placeholder="2025/2026" required>
                </div>
                <p class="text-xs text-gray-400 mt-1">Contoh: 2025/2026</p>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeModal('tahunModal')" class="btn-modern btn-neutral-modern">Batal</button>
                <button type="submit" name="tambah_tahun" class="btn-modern btn-primary-modern">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
// Close on overlay click
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });
});
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

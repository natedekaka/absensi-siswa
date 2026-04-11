<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Redistribusi Kelas 10 - Sistem Absensi Siswa';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'pindahkan') {
        $siswa_ids = $_POST['siswa_ids'] ?? [];
        $kelas_tujuan = (int)$_POST['kelas_tujuan'];
        
        if (empty($siswa_ids)) {
            $error = "Pilih setidaknya satu siswa";
        } elseif ($kelas_tujuan <= 0) {
            $error = "Pilih kelas tujuan";
        } else {
            $updated = 0;
            foreach ($siswa_ids as $siswa_id) {
                $update = conn()->prepare("UPDATE siswa SET kelas_id = ? WHERE id = ?");
                $update->bind_param("ii", $kelas_tujuan, $siswa_id);
                if ($update->execute()) $updated++;
            }
            $success = "Berhasil memindahkan $updated siswa ke kelas baru";
        }
    }
}

$kelas_x = conn()->query("SELECT id, nama_kelas FROM kelas WHERE nama_kelas LIKE 'X-%' OR nama_kelas LIKE '10-%' ORDER BY nama_kelas");
$kelas_xi = conn()->query("SELECT id, nama_kelas FROM kelas WHERE nama_kelas LIKE 'XI-%' OR nama_kelas LIKE '11-%' ORDER BY nama_kelas");

$siswa_kelas_x = conn()->query("
    SELECT s.id, s.nis, s.nama, s.jenis_kelamin, k.nama_kelas as kelas_sekarang, k.id as kelas_id
    FROM siswa s 
    JOIN kelas k ON s.kelas_id = k.id 
    WHERE s.status = 'aktif' AND (k.nama_kelas LIKE 'X-%' OR k.nama_kelas LIKE '10-%')
    ORDER BY k.nama_kelas, s.nama
");

$siswa_by_kelas = [];
while ($s = $siswa_kelas_x->fetch_assoc()) {
    $siswa_by_kelas[$s['kelas_id']][$s['kelas_sekarang']][] = $s;
}

ob_start();
?>

<style>
.kelas-section {
    background: white;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.kelas-header {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    padding: 1rem 1.5rem;
    font-weight: 600;
}
.siswa-list {
    padding: 1rem;
}
.siswa-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
    transition: all 0.2s;
}
.siswa-item:hover {
    background: #e9ecef;
}
.siswa-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-right: 1rem;
    accent-color: #4f46e5;
}
.siswa-info {
    flex: 1;
}
.siswa-nama {
    font-weight: 600;
    color: #333;
}
.siswa-nis {
    font-size: 0.85rem;
    color: #666;
}
.siswa-jk {
    font-size: 0.8rem;
    padding: 2px 8px;
    border-radius: 4px;
    background: #e9ecef;
}
.select-all-btn {
    background: #4f46e5;
    color: white;
    border: none;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 0.8rem;
    cursor: pointer;
}
.select-all-btn:hover {
    background: #4338ca;
}
</style>

<div class="d-flex align-items-center mb-4">
    <a href="index.php" class="btn btn-outline-secondary me-3">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-random me-2"></i>Redistribusi Kelas 10 → 11
    </h2>
</div>

<?php if ($success): ?>
    <div class="alert alert-success-custom alert-custom d-flex align-items-center mb-4">
        <i class="fas fa-check-circle fs-4 me-3"></i>
        <div><?= $success ?></div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger-custom alert-custom d-flex align-items-center mb-4">
        <i class="fas fa-exclamation-circle fs-4 me-3"></i>
        <div><?= $error ?></div>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <?php if (!empty($siswa_by_kelas)): ?>
            <form method="POST" id="redistribusiForm">
                <input type="hidden" name="action" value="pindahkan">
                
                <?php foreach ($siswa_by_kelas as $kelas_id => $kelas_data): ?>
                    <?php foreach ($kelas_data as $nama_kelas => $siswa_list): ?>
                    <div class="kelas-section">
                        <div class="kelas-header d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-door-open me-2"></i><?= htmlspecialchars($nama_kelas) ?>
                            </span>
                            <span class="badge bg-white text-primary"><?= count($siswa_list) ?> siswa</span>
                        </div>
                        <div class="siswa-list">
                            <?php foreach ($siswa_list as $siswa): ?>
                            <div class="siswa-item">
                                <input type="checkbox" name="siswa_ids[]" value="<?= $siswa['id'] ?>" id="siswa_<?= $siswa['id'] ?>">
                                <label for="siswa_<?= $siswa['id'] ?>" class="siswa-info d-flex align-items-center w-100">
                                    <div class="flex-grow-1">
                                        <div class="siswa-nama"><?= htmlspecialchars($siswa['nama']) ?></div>
                                        <div class="siswa-nis">NIS: <?= htmlspecialchars($siswa['nis']) ?></div>
                                    </div>
                                    <span class="siswa-jk">
                                        <?= $siswa['jenis_kelamin'] == 'Laki-laki' ? 'L' : 'P' ?>
                                    </span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </form>
        <?php else: ?>
            <div class="alert alert-info">Tidak ada siswa kelas 10</div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <div class="card-custom sticky-top" style="top: 100px;">
            <div class="card-header-custom">
                <i class="fas fa-paper-plane me-2"></i>Pindahkan Siswa
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kelas Tujuan (Kelas 11)</label>
                    <select name="kelas_tujuan" class="form-select" form="redistribusiForm" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php 
                        $kelas_xi_arr = [];
                        while ($k = $kelas_xi->fetch_assoc()): 
                            $kelas_xi_arr[$k['id']] = $k['nama_kelas'];
                        ?>
                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih Aksi</label>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAll()">
                            <i class="fas fa-check-square me-1"></i>Pilih Semua
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deselectAll()">
                            <i class="fas fa-square me-1"></i>Batal Pilih
                        </button>
                    </div>
                </div>
                
                <hr>
                
                <button type="submit" form="redistribusiForm" class="btn btn-wa-primary w-100">
                    <i class="fas fa-random me-2"></i>Pindahkan
                </button>
                
                <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
        
        <div class="card-custom mt-3">
            <div class="card-header-custom">
                <i class="fas fa-lightbulb me-2"></i>Cara Menggunakan
            </div>
            <div class="card-body">
                <ol class="mb-0" style="padding-left: 1.2rem; font-size: 0.9rem;">
                    <li class="mb-2">Ceklis siswa yang ingin dipindahkan</li>
                    <li class="mb-2">Pilih kelas tujuan (XI-IPA, XI-IPS, dll)</li>
                    <li class="mb-2">Klik tombol "Pindahkan"</li>
                    <li class="mb-2">Ulangi untuk kelompok siswa berikutnya</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
function selectAll() {
    document.querySelectorAll('input[name="siswa_ids[]"]').forEach(cb => cb.checked = true);
}
function deselectAll() {
    document.querySelectorAll('input[name="siswa_ids[]"]').forEach(cb => cb.checked = false);
}
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

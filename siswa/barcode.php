<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin', 'guru', 'wali_kelas');

$title = 'Generate Kartu Siswa (Barcode/QR) - Sistem Absensi Siswa';

ob_start();

$jenis = $_GET['jenis'] ?? 'barcode'; // barcode atau qrcode
$kelas_id = $_GET['kelas_id'] ?? '';
$keyword = $_GET['cari'] ?? '';

$where = "WHERE (s.status = 'aktif' OR s.status IS NULL)";
if ($kelas_id) $where .= " AND s.kelas_id = " . (int)$kelas_id;
if ($keyword) $where .= " AND s.nama LIKE '%" . db()->escape($keyword) . "%'";

$siswa = conn()->query("
    SELECT s.*, k.nama_kelas 
    FROM siswa s 
    LEFT JOIN kelas k ON s.kelas_id = k.id 
    $where
    ORDER BY k.nama_kelas, s.nama ASC
    LIMIT 50
");

$kelas_list = conn()->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
?>

<style>
.barcode-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 15px;
    text-align: center;
    transition: all 0.3s;
    background: white;
}
.barcode-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.barcode-card h6 {
    font-size: 0.85rem;
    margin-bottom: 5px;
}
.barcode-card small {
    color: #6b7280;
    font-size: 0.75rem;
}
.barcode-card .barcode-img {
    margin: 10px 0;
}
@media print {
    .no-print { display: none !important; }
    .barcode-grid { 
        display: grid; 
        grid-template-columns: repeat(4, 1fr); 
        gap: 15px; 
    }
    .barcode-card { 
        border: 1px solid #ccc; 
        page-break-inside: avoid;
    }
}
</style>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <h2 class="text-xl font-bold text-gray-800 dark:text-white">
        <i class="fas fa-barcode mr-3 text-primary"></i>Generate Kartu Siswa (Barcode/QR)
    </h2>
    <button class="btn-modern btn-primary-modern no-print" onclick="window.print()">
        <i class="fas fa-print mr-2"></i>Print Semua
    </button>
</div>

<form method="GET" class="card-modern p-4 mb-6 no-print">
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 items-end">
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Jenis:</label>
            <select name="jenis" class="form-input-modern w-full" onchange="this.form.submit()">
                <option value="barcode" <?= ($jenis === 'barcode') ? 'selected' : '' ?>>Barcode</option>
                <option value="qrcode" <?= ($jenis === 'qrcode') ? 'selected' : '' ?>>QR Code</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Filter Kelas:</label>
            <select name="kelas_id" class="form-input-modern w-full" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                <?php 
                $kelas_list->data_seek(0);
                while ($k = $kelas_list->fetch_assoc()): ?>
                <option value="<?= $k['id'] ?>" <?= ($kelas_id == $k['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($k['nama_kelas']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Cari Nama:</label>
            <input type="text" name="cari" class="form-input-modern w-full" placeholder="Cari siswa..." value="<?= htmlspecialchars($keyword) ?>">
        </div>
        <div>
            <button type="submit" class="btn-modern btn-primary-modern w-full justify-center">
                <i class="fas fa-search mr-1"></i>Filter
            </button>
        </div>
        <div>
            <a href="barcode.php" class="btn-modern btn-neutral-modern w-full justify-center">Reset</a>
        </div>
    </div>
</form>

<div class="barcode-grid">
    <?php while ($s = $siswa->fetch_assoc()): ?>
    <div class="barcode-card">
        <h6 class="fw-bold"><?= htmlspecialchars($s['nama']) ?></h6>
        <small><?= htmlspecialchars($s['nama_kelas'] ?? 'Tidak ada kelas') ?></small>
        <div class="barcode-img" style="width: 100px; height: 100px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
            <?php if ($jenis === 'qrcode'): ?>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($s['nis'] ?? $s['id']) ?>" 
                 alt="QR Code" style="width: 100px; height: 100px;">
            <?php else: ?>
            <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=<?= urlencode($s['nis'] ?? $s['id']) ?>&height=12&scale=3" 
                 alt="Barcode" style="max-width: 100%;">
            <?php endif; ?>
        </div>
        <small class="d-block">NIS: <?= htmlspecialchars($s['nis'] ?? '-') ?></small>
    </div>
    <?php endwhile; ?>
</div>

<?php if (!$siswa || $siswa->num_rows == 0): ?>
<div class="alert alert-info">Tidak ada data siswa</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';
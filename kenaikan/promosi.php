<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin');

$title = 'Promosi Kelas - Sistem Absensi Siswa';

$success = '';
$error = '';
$step = 1;
$tingkat_from = isset($_GET['tingkat']) ? (int)$_GET['tingkat'] : 10;
if (!in_array($tingkat_from, [10, 11])) $tingkat_from = 10;
$tingkat_to = $tingkat_from + 1;

$prefix_map = [10 => ['X', '10', 'XI', '11'], 11 => ['XI', '11', 'XII', '12']];
$map = $prefix_map[$tingkat_from];
$prefix_asal = $map[0];
$prefix_tujuan = $map[2];

$label_from = $tingkat_from;
$label_to = $tingkat_to;
$label_asal = "Kelas {$prefix_asal}";
$label_tujuan = "Kelas {$prefix_tujuan}";

// ─── Handle Export ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="promosi_'.$label_asal.'_ke_'.$label_tujuan.'_'.date('Ymd').'.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header row
    fputcsv($output, ['NIS', 'NISN', 'Nama', 'Kelas Asal', 'ID Kelas Asal', 'Kelas Tujuan', 'ID Kelas Tujuan'], ';');
    
    // Data siswa
    $siswa = conn()->query("
        SELECT s.id, s.nis, s.nisn, s.nama, k.nama_kelas as kelas_asal, k.id as kelas_id_asal
        FROM siswa s
        JOIN kelas k ON s.kelas_id = k.id
        WHERE s.status = 'aktif' AND (
            k.nama_kelas LIKE '{$map[0]}-%' OR k.nama_kelas LIKE '{$map[1]}-%'
        )
        ORDER BY k.nama_kelas, s.nama
    ");
    
    while ($row = $siswa->fetch_assoc()) {
        fputcsv($output, [
            $row['nis'],
            $row['nisn'],
            $row['nama'],
            $row['kelas_asal'],
            $row['kelas_id_asal'],
            '',  // Kelas Tujuan — BK isi ini
            ''   // ID Kelas Tujuan — BK isi ini
        ], ';');
    }
    
    // Empty row as separator
    fputcsv($output, [], ';');
    fputcsv($output, ['=== REFERENSI KELAS TUJUAN ==='], ';');
    fputcsv($output, ['ID Kelas', 'Nama Kelas'], ';');
    
    // Reference table of destination classes
    $kelas_tujuan = conn()->query("
        SELECT id, nama_kelas FROM kelas 
        WHERE nama_kelas LIKE '{$map[2]}-%' OR nama_kelas LIKE '{$map[3]}-%'
        ORDER BY nama_kelas
    ");
    while ($k = $kelas_tujuan->fetch_assoc()) {
        fputcsv($output, [$k['id'], $k['nama_kelas']], ';');
    }
    
    fclose($output);
    exit;
}

// ─── Handle Import (Promosi + Pindah 1 langkah) ──────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'import') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $rows = [];
        while (($line = fgetcsv($handle, 1000, ';')) !== FALSE) {
            $rows[] = $line;
        }
        fclose($handle);
        
        // Skip header, skip until we hit "REFERENSI" or empty
        $data_rows = [];
        foreach ($rows as $i => $row) {
            if ($i === 0) continue; // skip header
            if (count($row) < 2) continue; // skip empty
            if (strpos($row[0] ?? '', '===') !== false) break; // stop at reference
            if (strpos($row[0] ?? '', 'REFERENSI') !== false) break;
            $nis = trim($row[0] ?? '');
            $id_kelas_tujuan = (int)trim($row[6] ?? 0); // Column 7: ID Kelas Tujuan
            if (empty($nis) || $id_kelas_tujuan <= 0) continue;
            $data_rows[] = ['nis' => $nis, 'kelas_tujuan_id' => $id_kelas_tujuan];
        }
        
        if (empty($data_rows)) {
            $error = "Tidak ada data valid. Pastikan kolom ID Kelas Tujuan sudah diisi.";
        } else {
            $updated = 0;
            $errors = [];
            
            foreach ($data_rows as $data) {
                // Validate kelas tujuan exists + detect tingkat
                $kelas_cek = conn()->query("SELECT id, nama_kelas FROM kelas WHERE id = {$data['kelas_tujuan_id']}");
                if (!$kelas_cek || $kelas_cek->num_rows === 0) {
                    $errors[] = "Kelas ID {$data['kelas_tujuan_id']} tidak ditemukan (NIS: {$data['nis']})";
                    continue;
                }
                $kelas_row = $kelas_cek->fetch_assoc();
                
                // Auto-detect tingkat from destination class name
                $tingkat_baru = detectTingkatByKelasNama($kelas_row['nama_kelas']);
                if ($tingkat_baru === null) {
                    $errors[] = "Tidak bisa deteksi tingkat dari kelas {$kelas_row['nama_kelas']} (NIS: {$data['nis']})";
                    continue;
                }
                
                $stmt = conn()->prepare("UPDATE siswa SET kelas_id = ?, tingkat = ? WHERE nis = ? AND status = 'aktif'");
                $stmt->bind_param("iis", $data['kelas_tujuan_id'], $tingkat_baru, $data['nis']);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    // Generate barcode if not exists
                    $siswa = conn()->query("SELECT id, barcode FROM siswa WHERE nis = '{$data['nis']}'")->fetch_assoc();
                    if ($siswa && empty($siswa['barcode'])) {
                        generateBarcodeSiswa($siswa['id'], $data['nis']);
                    }
                    $updated++;
                } else {
                    $errors[] = "NIS {$data['nis']} tidak ditemukan atau sudah diproses";
                }
            }
            
            if ($updated > 0) {
                $success = "✅ Berhasil! $updated siswa dari $label_asal dipromosikan ke $label_tujuan.<br>";
                $success .= "<small class='text-green-600'>Tingkat & kelas otomatis terupdate. Barcode digenerate untuk yang belum punya.</small>";
            }
            if (!empty($errors)) {
                $error = implode("<br>", array_slice($errors, 0, 10));
                if (count($errors) > 10) $error .= "<br>...dan " . (count($errors) - 10) . " error lainnya";
            }
        }
    } else {
        $error = "Silakan pilih file CSV hasil editan BK.";
    }
}

// ─── Count current students in this grade ────────────────────────
$count_asal = conn()->query("
    SELECT COUNT(*) as total FROM siswa s 
    JOIN kelas k ON s.kelas_id = k.id 
    WHERE s.status = 'aktif' AND (k.nama_kelas LIKE '{$map[0]}-%' OR k.nama_kelas LIKE '{$map[1]}-%')
")->fetch_assoc()['total'];

$count_tujuan = conn()->query("
    SELECT COUNT(*) as total FROM kelas 
    WHERE nama_kelas LIKE '{$map[2]}-%' OR nama_kelas LIKE '{$map[3]}-%'
")->fetch_assoc()['total'];

// ─── Current class list for reference ────────────────────────────
$kelas_tujuan_list = conn()->query("
    SELECT id, nama_kelas FROM kelas 
    WHERE nama_kelas LIKE '{$map[2]}-%' OR nama_kelas LIKE '{$map[3]}-%'
    ORDER BY nama_kelas
");

ob_start();
?>

<style>
.step-card { border:2px solid #e5e7eb; border-radius:16px; padding:1.5rem; margin-bottom:1rem; transition:all .2s; }
.step-card:hover { border-color:#818cf8; }
.step-card.active { border-color:#4f46e5; background:#f5f3ff; }
.step-num { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.9rem; }
.step-num.done { background:#10b981; color:#fff; }
.step-num.pending { background:#e5e7eb; color:#6b7280; }
.step-num.current { background:#4f46e5; color:#fff; }
.tingkat-tab { padding:0.75rem 1.5rem; border-radius:12px; font-weight:600; cursor:pointer; transition:all .2s; border:2px solid transparent; }
.tingkat-tab:hover { border-color:#818cf8; }
.tingkat-tab.active { border-color:#4f46e5; background:#f5f3ff; color:#4f46e5; }
</style>

<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <a href="index.php" class="btn-modern btn-neutral-modern text-sm mb-2 inline-flex">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                <i class="fas fa-arrow-up mr-3 text-primary"></i>Promosi Kelas
            </h2>
            <p class="text-sm text-gray-400">Export data siswa → BK isi kelas tujuan → Import 1 langkah jadi</p>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert-modern alert-success-modern mb-6 flex items-center gap-3">
        <i class="fas fa-check-circle text-lg"></i>
        <span class="flex-1"><?= $success ?></span>
        <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-modern alert-danger-modern mb-6 flex items-center gap-3">
        <i class="fas fa-exclamation-circle text-lg"></i>
        <span class="flex-1"><?= $error ?></span>
        <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
    </div>
    <?php endif; ?>

    <!-- Pilih Tingkat -->
    <div class="card-modern mb-6">
        <div class="card-modern-body">
            <div class="flex items-center gap-3 mb-4">
                <span class="step-num current">1</span>
                <h4 class="font-bold text-gray-800 dark:text-white">Pilih Tingkat</h4>
            </div>
            <div class="flex gap-3">
                <a href="?tingkat=10" class="tingkat-tab flex-1 text-center <?= $tingkat_from == 10 ? 'active' : '' ?>">
                    <div class="text-lg font-bold">X → XI</div>
                    <div class="text-xs text-gray-400 mt-1">Kelas 10 naik ke 11</div>
                </a>
                <a href="?tingkat=11" class="tingkat-tab flex-1 text-center <?= $tingkat_from == 11 ? 'active' : '' ?>">
                    <div class="text-lg font-bold">XI → XII</div>
                    <div class="text-xs text-gray-400 mt-1">Kelas 11 naik ke 12</div>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card-modern">
            <div class="card-modern-body text-center">
                <h3 class="text-3xl font-bold text-indigo-500"><?= $count_asal ?></h3>
                <p class="text-sm text-gray-400">Siswa <?= $label_asal ?> Aktif</p>
            </div>
        </div>
        <div class="card-modern">
            <div class="card-modern-body text-center">
                <h3 class="text-3xl font-bold text-emerald-500"><i class="fas fa-arrow-right text-xl mr-1"></i></h3>
                <p class="text-sm text-gray-400">Akan dipromosikan ke</p>
            </div>
        </div>
        <div class="card-modern">
            <div class="card-modern-body text-center">
                <h3 class="text-3xl font-bold text-amber-500"><?= $count_tujuan ?></h3>
                <p class="text-sm text-gray-400">Kelas <?= $label_tujuan ?> Tersedia</p>
            </div>
        </div>
    </div>

    <!-- Referensi Kelas Tujuan -->
    <div class="card-modern mb-6">
        <div class="px-5 py-3" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:12px 12px 0 0;">
            <h5 class="font-semibold text-white"><i class="fas fa-door-open mr-2"></i>Referensi Kelas <?= $label_tujuan ?> (ID Kelas)</h5>
            <p class="text-xs text-white/70 mt-1">Gunakan ID ini saat mengisi kolom "ID Kelas Tujuan" di Excel</p>
        </div>
        <div class="overflow-x-auto">
            <table class="table-modern w-full">
                <thead><tr><th class="text-center">ID Kelas</th><th>Nama Kelas</th></tr></thead>
                <tbody>
                    <?php if ($kelas_tujuan_list && $kelas_tujuan_list->num_rows > 0): 
                        while ($k = $kelas_tujuan_list->fetch_assoc()): ?>
                    <tr>
                        <td class="text-center"><span class="badge-modern badge-primary-modern font-mono font-bold"><?= $k['id'] ?></span></td>
                        <td><?= htmlspecialchars($k['nama_kelas']) ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="2" class="text-center text-gray-400 py-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Belum ada kelas <?= $label_tujuan ?>. 
                        <a href="../kelas/tambah.php" class="text-primary font-semibold underline">Buat kelas dulu</a>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($count_asal > 0 && $count_tujuan > 0): ?>
    <!-- Step 2: Export CSV -->
    <div class="card-modern mb-6">
        <div class="card-modern-body">
            <div class="flex items-center gap-3 mb-4">
                <span class="step-num current">2</span>
                <h4 class="font-bold text-gray-800 dark:text-white">Export Data Siswa</h4>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                Download file CSV berisi semua siswa <strong><?= $label_asal ?></strong>. 
            </p>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="export">
                <button type="submit" class="btn-modern btn-primary-modern">
                    <i class="fas fa-file-download mr-2"></i>Export CSV <?= $label_asal ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Step 3: BK Edit -->
    <div class="card-modern mb-6">
        <div class="card-modern-body">
            <div class="flex items-center gap-3 mb-4">
                <span class="step-num pending">3</span>
                <h4 class="font-bold text-gray-800 dark:text-white">BK Isi Kelas Tujuan di Excel</h4>
            </div>
            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl mb-4 text-sm">
                <i class="fas fa-lightbulb mr-2 text-amber-500"></i>
                <strong>Instruksi untuk BK:</strong>
                <ol class="mt-2 mb-0 ps-4 space-y-1" style="list-style:decimal;">
                    <li>Buka file CSV yang sudah di-<i>download</i> menggunakan <strong>Excel</strong> atau <strong>LibreOffice Calc</strong></li>
                    <li>Isi kolom <strong>ID Kelas Tujuan</strong> (kolom ke-7) dengan ID kelas dari tabel referensi di atas</li>
                    <li>Kolom <strong>Kelas Tujuan</strong> (kolom ke-6) opsional — bisa diisi nama kelas untuk referensi</li>
                    <li><strong>Simpan</strong> file CSV (format CSV UTF-8)</li>
                </ol>
            </div>
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl text-sm">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                <strong>Contoh isian:</strong><br>
                <code class="text-xs">
                1234;0051234567;Adi Saputra;XI-1;21;XII-1;464<br>
                1235;0051234568;Budi Santoso;XI-1;21;XII-2;465
                </code>
            </div>
        </div>
    </div>

    <!-- Step 4: Import -->
    <div class="card-modern mb-6">
        <div class="card-modern-body">
            <div class="flex items-center gap-3 mb-4">
                <span class="step-num pending">4</span>
                <h4 class="font-bold text-gray-800 dark:text-white">Import Hasil Editan BK</h4>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                Upload file CSV yang sudah diisi kolom <strong>ID Kelas Tujuan</strong> oleh BK.
                Sistem akan otomatis:
            </p>
            <ul class="text-sm text-gray-500 mb-4 space-y-1 ps-4" style="list-style:disc;">
                <li>Memindahkan siswa ke kelas baru</li>
                <li>Mengupdate tingkat (<?= $label_from ?> → <?= $label_to ?>)</li>
                <li>Generate barcode untuk yang belum punya</li>
            </ul>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                <div class="flex flex-wrap items-center gap-3">
                    <input type="file" name="csv_file" accept=".csv" required
                           class="block w-full max-w-xs text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <button type="submit" class="btn-modern btn-success-modern">
                        <i class="fas fa-upload mr-2"></i>Import & Promosikan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php elseif ($count_asal == 0): ?>
    <div class="card-modern p-6 text-center">
        <i class="fas fa-info-circle text-3xl text-gray-300 mb-3"></i>
        <p class="text-gray-400">Tidak ada siswa aktif di <?= $label_asal ?>.</p>
        <p class="text-sm text-gray-400 mt-1">Pastikan sudah ada data siswa dan kelas untuk tingkat ini.</p>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

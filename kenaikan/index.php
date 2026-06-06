<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Manajemen Kenaikan Kelas - Sistem Absensi Siswa';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'naik_tingkat') {
        $tingkat_dari = (int)$_POST['tingkat_dari'];
        $tingkat_ke = (int)$_POST['tingkat_ke'];
        $peta_kelas = $_POST['peta_kelas'] ?? []; // old_class_id => new_class_id
        
        if (empty($peta_kelas)) {
            $error = "Silakan pilih kelas tujuan untuk setiap kelas asal.";
        } else {
            $prefix_map = [
                10 => ['X', '10'],
                11 => ['XI', '11'],
                12 => ['XII', '12']
            ];
            
            $prefix_asal = $prefix_map[$tingkat_dari][0] ?? '';
            $prefix_tujuan = $prefix_map[$tingkat_ke][0] ?? '';
            
            $total = 0;
            $detail = [];
            
            foreach ($peta_kelas as $kelas_lama_id => $kelas_baru_id) {
                $kelas_lama_id = (int)$kelas_lama_id;
                $kelas_baru_id = (int)$kelas_baru_id;
                if ($kelas_baru_id <= 0) continue;
                
                $kelas_lama_nama = conn()->query("SELECT nama_kelas FROM kelas WHERE id = $kelas_lama_id")->fetch_assoc()['nama_kelas'] ?? "ID:$kelas_lama_id";
                $kelas_baru_nama = conn()->query("SELECT nama_kelas FROM kelas WHERE id = $kelas_baru_id")->fetch_assoc()['nama_kelas'] ?? "ID:$kelas_baru_id";
                
                $count = 0;
                $siswa = conn()->query("
                    SELECT s.id FROM siswa s 
                    JOIN kelas k ON s.kelas_id = k.id 
                    WHERE s.kelas_id = $kelas_lama_id AND s.status = 'aktif'
                ");
                
                while ($siswa_row = $siswa->fetch_assoc()) {
                    $update = conn()->prepare("UPDATE siswa SET tingkat = ?, kelas_id = ? WHERE id = ?");
                    $update->bind_param("iii", $tingkat_ke, $kelas_baru_id, $siswa_row['id']);
                    if ($update->execute()) $count++;
                }
                
                if ($count > 0) {
                    $total += $count;
                    $detail[] = "$kelas_lama_nama → $kelas_baru_nama ($count siswa)";
                }
            }
            
            if ($total > 0) {
                $success = "✅ Berhasil menaikkan $total siswa dari tingkat $tingkat_dari ke $tingkat_ke:<br>";
                $success .= "<ul class='mt-2 mb-0 ps-4 text-sm'>";
                foreach ($detail as $d) {
                    $success .= "<li>$d</li>";
                }
                $success .= "</ul>";
            } else {
                $error = "Tidak ada siswa yang dipindahkan. Pastikan kelas tujuan sudah dipilih.";
            }
        }
        
    } elseif ($action == 'naik_tingkat_step1') {
        // Step 1: Show class mapping form
        $tingkat_dari = (int)$_POST['tingkat_dari'];
        $tingkat_ke = $tingkat_dari + 1;
        
        $prefix_map = [
            10 => ['X', '10', 'XI', '11'],
            11 => ['XI', '11', 'XII', '12'],
        ];
        
        $map = $prefix_map[$tingkat_dari] ?? [];
        $prefix_asal = $map[0] ?? '';
        $prefix_tujuan = $map[2] ?? '';
        
        $kelas_asal = conn()->query("
            SELECT k.id, k.nama_kelas, COUNT(s.id) as total_siswa
            FROM kelas k
            LEFT JOIN siswa s ON k.id = s.kelas_id AND s.status = 'aktif'
            WHERE k.nama_kelas LIKE '$prefix_asal-%' OR k.nama_kelas LIKE '{$map[1]}-%'
            GROUP BY k.id
            ORDER BY k.nama_kelas
        ");
        
        $kelas_tujuan = conn()->query("
            SELECT id, nama_kelas FROM kelas 
            WHERE nama_kelas LIKE '$prefix_tujuan-%' OR nama_kelas LIKE '{$map[3]}-%'
            ORDER BY nama_kelas
        ");
        
        $tingkat_dari_label = $tingkat_dari;
        $tingkat_ke_label = $tingkat_ke;
        $show_mapping = true;
        
    } elseif ($action == 'export_siswa') {
        $tingkat = (int)$_POST['tingkat_export'];
        
        $prefix_map = [
            10 => ['X', '10'],
            11 => ['XI', '11'],
            12 => ['XII', '12']
        ];
        
        $prefix = $prefix_map[$tingkat][0] ?? '';
        
        $siswa = conn()->query("
            SELECT s.id, s.nis, s.nisn, s.nama, k.nama_kelas as kelas_lama, k.id as kelas_id_lama
            FROM siswa s 
            JOIN kelas k ON s.kelas_id = k.id 
            WHERE s.status = 'aktif' AND (
                k.nama_kelas LIKE '$prefix-%' OR 
                k.nama_kelas LIKE '{$prefix_map[$tingkat][1]}-%'
            )
            ORDER BY k.nama_kelas, s.nama
        ");
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="siswa_kelas_'.$tingkat.'_'.date('Ymd').'.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['NIS', 'NISN', 'Nama', 'Kelas Lama', 'Nama Kelas Baru', 'ID Kelas Baru'], ';');
        
        while ($row = $siswa->fetch_assoc()) {
            fputcsv($output, [
                $row['nis'],
                $row['nisn'],
                $row['nama'],
                $row['kelas_lama'],
                '',
                ''
            ], ';');
        }
        
        fclose($output);
        exit;
        
    } elseif ($action == 'import_kelas') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['csv_file']['tmp_name'];
            
            $handle = fopen($file, 'r');
            $rows = [];
            while (($line = fgetcsv($handle, 1000, ';')) !== FALSE) {
                $rows[] = $line;
            }
            fclose($handle);
            array_shift($rows);
            
            $updated = 0;
            $errors = [];
            
            foreach ($rows as $row) {
                if (count($row) < 6) continue;
                
                $nis = trim($row[0]);
                $nama_kelas_baru = trim($row[4]);
                $kelas_id_baru = (int)trim($row[5]);
                
                if (empty($nis) || empty($kelas_id_baru)) continue;
                
                $update = conn()->prepare("UPDATE siswa SET kelas_id = ? WHERE nis = ? AND status = 'aktif'");
                $update->bind_param("is", $kelas_id_baru, $nis);
                
                if ($update->execute()) {
                    $updated++;
                } else {
                    $errors[] = "Error updating $nis";
                }
            }
            
            if ($updated > 0) {
                $success = "Berhasil mengupdate $updated siswa ke kelas baru";
            }
            if (!empty($errors)) {
                $error = implode(", ", $errors);
            }
        } else {
            $error = "Silakan pilih file CSV yang valid";
        }
        
    } elseif ($action == 'lulus') {
        $tahun_lulus = (int)$_POST['tahun_lulus'];
        
        $siswa_xii = conn()->query("
            SELECT s.id FROM siswa s 
            JOIN kelas k ON s.kelas_id = k.id 
            WHERE s.status = 'aktif' AND (
                k.nama_kelas LIKE 'XII-%' OR 
                k.nama_kelas LIKE '12-%'
            )
        ");

        $count = 0;
        while ($siswa = $siswa_xii->fetch_assoc()) {
            $update = conn()->prepare("UPDATE siswa SET status = 'alumni', tingkat = NULL, tahun_lulus = ? WHERE id = ?");
            $update->bind_param("ii", $tahun_lulus, $siswa['id']);
            $update->execute();
            $count++;
        }

        if ($count > 0) {
            $success = "Berhasil meluluskan $count siswa kelas 12";
        } else {
            $error = "Tidak ada siswa kelas 12 yang diluluskan";
        }
    }
}

$siswa_x_count = conn()->query("SELECT COUNT(*) as total FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.status = 'aktif' AND (k.nama_kelas LIKE 'X-%' OR k.nama_kelas LIKE '10-%')")->fetch_assoc()['total'];
$siswa_xi_count = conn()->query("SELECT COUNT(*) as total FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.status = 'aktif' AND (k.nama_kelas LIKE 'XI-%' OR k.nama_kelas LIKE '11-%')")->fetch_assoc()['total'];
$siswa_xii_count = conn()->query("SELECT COUNT(*) as total FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.status = 'aktif' AND (k.nama_kelas LIKE 'XII-%' OR k.nama_kelas LIKE '12-%')")->fetch_assoc()['total'];

$kelas_list = conn()->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
$kelas_options = [];
while ($k = $kelas_list->fetch_assoc()) {
    $kelas_options[$k['id']] = $k['nama_kelas'];
}

// --- Alumni table with filter + pagination ---
$alumni_page = isset($_GET['alumni_page']) ? max(1, (int)$_GET['alumni_page']) : 1;
$alumni_limit = 20;
$alumni_offset = ($alumni_page - 1) * $alumni_limit;
$alumni_tahun = $_GET['alumni_tahun'] ?? '';
$alumni_search = $_GET['alumni_search'] ?? '';

$alumni_where = ["s.status = 'alumni'"];
if ($alumni_tahun !== '') {
    $alumni_where[] = "s.tahun_lulus = " . (int)$alumni_tahun;
}
if ($alumni_search !== '') {
    $search = db()->escape($alumni_search);
    $alumni_where[] = "(s.nama LIKE '%$search%' OR s.nis LIKE '%$search%')";
}
$alumni_where_sql = "WHERE " . implode(' AND ', $alumni_where);

$alumni_total = conn()->query("SELECT COUNT(*) as total FROM siswa s $alumni_where_sql")->fetch_assoc()['total'];
$alumni_total_pages = max(1, ceil($alumni_total / $alumni_limit));

$alumni_full = conn()->query("
    SELECT s.nis, s.nama, k.nama_kelas, s.tahun_lulus, s.status 
    FROM siswa s 
    LEFT JOIN kelas k ON s.kelas_id = k.id 
    $alumni_where_sql
    ORDER BY s.tahun_lulus DESC, s.nama ASC 
    LIMIT $alumni_limit OFFSET $alumni_offset
");

// Get unique years for filter
$alumni_tahun_list = conn()->query("SELECT DISTINCT tahun_lulus FROM siswa WHERE status = 'alumni' AND tahun_lulus IS NOT NULL ORDER BY tahun_lulus DESC");

ob_start();
?>

<div class="page-header-modern flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800 dark:text-white">
        <i class="fas fa-graduation-cap mr-3 text-primary"></i>Manajemen Kenaikan Kelas
    </h2>
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

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card-modern">
        <div class="card-modern-body flex items-center gap-4">
            <div class="w-[60px] h-[60px] rounded-xl flex items-center justify-center text-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <h4 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $siswa_x_count ?></h4>
                <p class="text-sm text-gray-400">Siswa Kelas 10</p>
            </div>
        </div>
    </div>
    <div class="card-modern">
        <div class="card-modern-body flex items-center gap-4">
            <div class="w-[60px] h-[60px] rounded-xl flex items-center justify-center text-xl bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <h4 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $siswa_xi_count ?></h4>
                <p class="text-sm text-gray-400">Siswa Kelas 11</p>
            </div>
        </div>
    </div>
    <div class="card-modern">
        <div class="card-modern-body flex items-center gap-4">
            <div class="w-[60px] h-[60px] rounded-xl flex items-center justify-center text-xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <h4 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $siswa_xii_count ?></h4>
                <p class="text-sm text-gray-400">Siswa Kelas 12</p>
            </div>
        </div>
    </div>
</div>

<h4 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
    <i class="fas fa-tasks mr-2 text-primary"></i>Langkah-Langkah
</h4>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <!-- Step 1: Kenaikan + Pindah Kelas -->
    <div class="card-modern">
        <div class="gradient-header indigo relative">
            <div class="flex items-center justify-between">
                <h5 class="font-semibold text-white"><i class="fas fa-arrow-up mr-2"></i>Kenaikan & Pindah Kelas</h5>
                <span class="absolute -top-3 right-4 min-w-[32px] h-8 px-2 bg-white text-gray-800 rounded-full flex items-center justify-center text-sm font-bold shadow-lg">1</span>
            </div>
        </div>
        <div class="p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Naikkan tingkat DAN pindahkan siswa ke kelas baru (jurusan) dalam satu langkah.</p>
            
            <?php if (!empty($show_mapping) && $kelas_asal && $kelas_asal->num_rows > 0): ?>
                <!-- Step 2: Class Mapping -->
                <form method="POST">
                    <input type="hidden" name="action" value="naik_tingkat">
                    <input type="hidden" name="tingkat_dari" value="<?= $tingkat_dari_label ?>">
                    <input type="hidden" name="tingkat_ke" value="<?= $tingkat_ke_label ?>">
                    
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-3 mb-4 text-sm text-blue-700 dark:text-blue-300">
                        <i class="fas fa-info-circle mr-1"></i>
                        Pilih kelas tujuan untuk setiap kelas asal. Siswa akan langsung dipindahkan + dinaikkan tingkatnya.
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <?php 
                        $kelas_tujuan_arr = [];
                        while ($kt = $kelas_tujuan->fetch_assoc()):
                            $kelas_tujuan_arr[] = $kt;
                        endwhile;
                        
                        while ($ka = $kelas_asal->fetch_assoc()): 
                        ?>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex-1">
                                <span class="font-semibold text-sm text-gray-700 dark:text-gray-200"><?= htmlspecialchars($ka['nama_kelas']) ?></span>
                                <span class="text-xs text-gray-400 ml-2"><?= $ka['total_siswa'] ?> siswa</span>
                            </div>
                            <i class="fas fa-arrow-right text-gray-300"></i>
                            <select name="peta_kelas[<?= $ka['id'] ?>]" class="form-input-modern text-sm py-1.5 px-3 min-w-[180px]" required>
                                <option value="">-- Pilih Kelas Tujuan --</option>
                                <?php foreach ($kelas_tujuan_arr as $kt): ?>
                                <option value="<?= $kt['id'] ?>"><?= htmlspecialchars($kt['nama_kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="index.php" class="btn-modern btn-neutral-modern flex-1 justify-center">
                            <i class="fas fa-times mr-1"></i>Batal
                        </a>
                        <button type="submit" class="btn-modern btn-success-modern flex-1 justify-center">
                            <i class="fas fa-check mr-2"></i>Naikkan & Pindahkan
                        </button>
                    </div>
                </form>
            <?php elseif (!empty($show_mapping)): ?>
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-4 text-sm text-amber-700 dark:text-amber-300">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Tidak ditemukan kelas asal untuk tingkat <?= htmlspecialchars($tingkat_dari_label) ?>. 
                    Pastikan sudah ada data kelas dengan prefix yang sesuai.
                </div>
                <a href="index.php" class="btn-modern btn-primary-modern inline-flex">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            <?php else: ?>
                <!-- Step 1: Choose tingkat -->
                <form method="POST">
                    <input type="hidden" name="action" value="naik_tingkat_step1">
                    <select name="tingkat_dari" class="form-input-modern w-full mb-3" required>
                        <option value="">Pilih tingkat...</option>
                        <option value="10">Kelas 10 → 11</option>
                        <option value="11">Kelas 11 → 12</option>
                    </select>
                    <button type="submit" class="btn-modern btn-primary-modern w-full justify-center">
                        <i class="fas fa-arrow-right mr-2"></i>Lanjutkan
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Step 2: Redistribusi -->
    <div class="card-modern">
        <div class="gradient-header green">
            <div class="flex items-center justify-between">
                <h5 class="font-semibold text-white"><i class="fas fa-random mr-2"></i>Redistribusi Kelas</h5>
                <span class="absolute -top-3 right-4 min-w-[32px] h-8 px-2 bg-white text-gray-800 rounded-full flex items-center justify-center text-sm font-bold shadow-lg">2</span>
            </div>
        </div>
        <div class="p-5 text-center">
            <div class="w-[70px] h-[70px] rounded-full flex items-center justify-center text-2xl mx-auto mb-4 bg-emerald-50 text-emerald-500 dark:bg-emerald-900/20 dark:text-emerald-400">
                <i class="fas fa-random"></i>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Pindahkan siswa antar kelas/jurusan (IPA/IPS/Bahasa) untuk semua tingkat.</p>
            <a href="redistribusi.php" class="btn-modern btn-success-modern w-full justify-center">
                <i class="fas fa-random mr-2"></i>Buka Redistribusi
            </a>
        </div>
    </div>

    <!-- Step 3: Kelulusan -->
    <div class="card-modern">
        <div class="gradient-header orange">
            <div class="flex items-center justify-between">
                <h5 class="font-semibold text-white"><i class="fas fa-user-graduate mr-2"></i>Kelulusan</h5>
                <span class="absolute -top-3 right-4 min-w-[32px] h-8 px-2 bg-white text-gray-800 rounded-full flex items-center justify-center text-sm font-bold shadow-lg">3</span>
            </div>
        </div>
        <div class="p-5 text-center">
            <div class="w-[70px] h-[70px] rounded-full flex items-center justify-center text-2xl mx-auto mb-4 bg-amber-50 text-amber-500 dark:bg-amber-900/20 dark:text-amber-400">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Proses kelulusan siswa kelas 12 → alumni</p>
            <a href="kelulusan.php" class="btn-modern btn-warning-modern w-full justify-center">
                <i class="fas fa-user-graduate mr-2"></i>Buka Kelulusan
            </a>
        </div>
    </div>

    <!-- Step 4: Export/Import -->
    <div class="card-modern">
        <div class="gradient-header red">
            <div class="flex items-center justify-between">
                <h5 class="font-semibold text-white"><i class="fas fa-file-export mr-2"></i>Export/Import CSV</h5>
                <span class="absolute -top-3 right-4 min-w-[32px] h-8 px-2 bg-white text-gray-800 rounded-full flex items-center justify-center text-sm font-bold shadow-lg">4</span>
            </div>
        </div>
        <div class="p-5">
            <form method="POST" class="mb-4">
                <input type="hidden" name="action" value="export_siswa">
                <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Export Siswa</label>
                <div class="flex gap-2">
                    <select name="tingkat_export" class="form-input-modern flex-1" required>
                        <option value="10">Kelas 10</option>
                        <option value="11">Kelas 11</option>
                    </select>
                    <button type="submit" class="btn-modern btn-neutral-modern"><i class="fas fa-download"></i></button>
                </div>
            </form>
            <hr class="border-gray-200 dark:border-gray-700 my-4">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_kelas">
                <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Import ke Kelas Baru</label>
                <input type="file" name="csv_file" class="form-input-modern w-full mb-2" accept=".csv" required>
                <p class="text-xs text-gray-400 mb-3">Format: NIS;NISN;Nama;Kelas Lama;;ID_Kelas_Baru</p>
                <button type="submit" class="btn-modern btn-primary-modern w-full justify-center">
                    <i class="fas fa-upload mr-2"></i>Import CSV
                </button>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <!-- Daftar Kelas -->
    <div class="card-modern">
        <div class="gradient-header teal">
            <i class="fas fa-building mr-2"></i>Daftar Kelas (Referensi ID)
        </div>
        <div class="p-0 overflow-x-auto" style="max-height:250px">
            <table class="table-modern w-full">
                <thead><tr><th class="text-center w-[50px]">ID</th><th>Nama Kelas</th></tr></thead>
                <tbody>
                    <?php foreach ($kelas_options as $id => $nama): ?>
                    <tr><td class="text-center"><span class="badge-modern badge-primary-modern"><?= $id ?></span></td><td><?= htmlspecialchars($nama) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daftar Alumni (with filter + pagination) -->
    <div class="card-modern">
        <div class="gradient-header indigo">
            <i class="fas fa-user-graduate mr-2"></i>Daftar Alumni
            <span class="ml-2 text-white/70 text-sm font-normal">(<?= $alumni_total ?> orang)</span>
        </div>
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1 block">Filter Tahun</label>
                    <select name="alumni_tahun" class="form-input-modern text-sm py-1.5 px-3 min-w-[120px]" onchange="this.form.submit()">
                        <option value="">Semua Tahun</option>
                        <?php while ($t = $alumni_tahun_list->fetch_assoc()): ?>
                        <option value="<?= $t['tahun_lulus'] ?>" <?= $alumni_tahun == $t['tahun_lulus'] ? 'selected' : '' ?>>
                            <?= $t['tahun_lulus'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1 block">Cari Alumni</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" name="alumni_search" class="form-input-modern text-sm py-1.5 pl-8 pr-3" 
                               placeholder="Nama atau NIS..." value="<?= htmlspecialchars($alumni_search) ?>">
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-modern btn-primary-modern text-sm py-1.5 px-4">
                        <i class="fas fa-filter mr-1"></i>Filter
                    </button>
                    <?php if ($alumni_tahun !== '' || $alumni_search !== ''): ?>
                    <a href="index.php" class="btn-modern btn-neutral-modern text-sm py-1.5 px-4 ml-1">
                        <i class="fas fa-times mr-1"></i>Reset
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="table-modern w-full">
                <thead>
                    <tr><th>NIS</th><th>Nama</th><th>Kelas Terakhir</th><th class="text-center">Tahun Lulus</th><th class="text-center">Status</th></tr>
                </thead>
                <tbody>
                    <?php if ($alumni_full && $alumni_full->num_rows > 0): 
                        while ($row = $alumni_full->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nis']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                        <td class="text-center"><span class="badge-modern badge-primary-modern"><?= $row['tahun_lulus'] ?? '-' ?></span></td>
                        <td class="text-center"><span class="badge-modern badge-secondary-modern">Alumni</span></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center text-gray-400 py-6">Tidak ada data alumni</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($alumni_total_pages > 1): ?>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <small class="text-gray-400">Menampilkan <?= min($alumni_limit, $alumni_total) ?> dari <?= $alumni_total ?> alumni</small>
            <div class="flex gap-1">
                <?php for ($i = 1; $i <= $alumni_total_pages; $i++): ?>
                <a href="?alumni_page=<?= $i ?>&alumni_tahun=<?= urlencode($alumni_tahun) ?>&alumni_search=<?= urlencode($alumni_search) ?>" 
                   class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-medium 
                   <?= $i == $alumni_page ? 'bg-primary text-white' : 'border border-gray-200 text-gray-500 hover:border-primary hover:text-primary' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

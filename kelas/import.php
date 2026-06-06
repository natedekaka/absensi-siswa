<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Import Kelas - Sistem Absensi Siswa';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        fgetcsv($handle);
        
        $imported = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 2) continue;
            
            $nama_kelas = trim($data[0]);
            $wali_kelas = trim($data[1] ?? '');
            
            if (empty($nama_kelas)) {
                continue;
            }
            
            $cek = conn()->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
            $cek->bind_param("s", $nama_kelas);
            $cek->execute();
            $cek->store_result();
            
            if ($cek->num_rows > 0) {
                $errors[] = "Kelas $nama_kelas sudah ada";
                continue;
            }
            
            $stmt = conn()->prepare("INSERT INTO kelas (nama_kelas,wali_kelas) VALUES (?, ?)");
            $stmt->bind_param("ss", $nama_kelas, $wali_kelas);
            
            if ($stmt->execute()) {
                $imported++;
            } else {
                $errors[] = "Error: " . $stmt->error;
            }
        }
        
        fclose($handle);
        
        if ($imported > 0) {
            $success = "Berhasil mengimport $imported data kelas";
        }
        
        if (!empty($errors)) {
            $error = implode("<br>", $errors);
        }
    } else {
        $error = "Silakan pilih file CSV yang valid";
    }
}

ob_start();
?>

<div class="max-w-lg mx-auto my-12">
    <div class="card-modern overflow-hidden text-center">
        <div class="gradient-header teal text-center">
            <div class="w-[70px] h-[70px] rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(255,255,255,0.2)">
                <i class="fas fa-file-import text-2xl text-white"></i>
            </div>
            <h3 class="text-xl font-semibold text-white">Import Data Kelas</h3>
            <p class="mt-1 opacity-75 text-sm">Upload file CSV untuk import data</p>
        </div>
        <div class="p-6">
            <?php if ($success): ?>
                <div class="alert-modern alert-success-modern mb-4 flex items-center gap-3">
                    <i class="fas fa-check-circle"></i><span><?= $success ?></span>
                    <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-modern alert-danger-modern mb-4 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i><span><?= $error ?></span>
                    <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="text-left">
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Pilih File CSV</label>
                    <div class="relative">
                        <i class="fas fa-file-csv absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="file" name="csv_file" class="form-input-modern w-full pl-10" accept=".csv" required>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Format: nama_kelas,wali_kelas</p>
                </div>
                <div class="flex gap-3 mt-6">
                    <a href="index.php" class="btn-modern btn-neutral-modern flex-1 justify-center">
                        <i class="fas fa-arrow-left mr-2"></i>Batal
                    </a>
                    <button type="submit" class="btn-modern btn-primary-modern flex-1 justify-center">
                        <i class="fas fa-upload mr-2"></i>Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

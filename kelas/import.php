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

<div class="d-flex align-items-center mb-4">
    <a href="index.php" class="btn btn-outline-secondary me-3">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-file-import me-2"></i>Import Kelas
    </h2>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card-custom">
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File CSV</label>
                        <input type="file" name="csv_file" class="form-control form-control-custom" accept=".csv" required>
                        <small class="text-muted">
                            Format: nama_kelas,wali_kelas
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-wa-primary">
                            <i class="fas fa-upload me-2"></i>Import
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

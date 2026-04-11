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

<style>
.form-page {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
}
.form-card {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    max-width: 500px;
    width: 100%;
}
.form-card-header {
    background: linear-gradient(135deg, var(--wa-dark) 0%, #0d6e67 100%);
    padding: 2rem;
    text-align: center;
}
.form-card-header h3 {
    color: white;
    font-weight: 600;
    margin: 0;
}
.form-card-header .icon-circle {
    width: 70px;
    height: 70px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}
.form-card-header .icon-circle i {
    font-size: 1.75rem;
    color: white;
}
.form-card-body {
    padding: 2rem;
}
.form-floating > label {
    color: #666;
}
.form-control, .form-select {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s;
}
.form-control:focus, .form-select:focus {
    border-color: var(--wa-green);
    box-shadow: 0 0 0 4px rgba(37,211,102,0.15);
}
.btn-back {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: #666;
    transition: all 0.3s;
}
.btn-back:hover {
    background: #f8f9fa;
    border-color: #ccc;
}
</style>

<div class="form-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="icon-circle">
                            <i class="fas fa-file-import"></i>
                        </div>
                        <h3>Import Data Kelas</h3>
                        <p class="mb-0 opacity-75">Upload file CSV untuk import data</p>
                    </div>
                    <div class="form-card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success bg-success text-white border-0 rounded-3 mb-4">
                                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger bg-danger text-white border-0 rounded-3 mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-dark">Pilih File CSV</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-file-csv text-muted"></i>
                                    </span>
                                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Format: nama_kelas,wali_kelas
                                </small>
                            </div>
                            <div class="d-flex gap-3">
                                <a href="index.php" class="btn btn-back flex-fill">
                                    <i class="fas fa-arrow-left me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-wa-primary flex-fill">
                                    <i class="fas fa-upload me-2"></i>Import
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

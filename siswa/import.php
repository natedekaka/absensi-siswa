<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Import Siswa - Sistem Absensi Siswa';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        fgetcsv($handle);
        
        $imported = 0;
        $updated = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 5) continue;
            
            $nis = trim($data[0]);
            $nisn = trim($data[1]);
            $nama = trim($data[2]);
            $kelas_id = (int)trim($data[3]);
            $jenis_kelamin = trim($data[4]);

            if (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'])) {
                $errors[] = "Jenis kelamin tidak valid pada NIS $nis";
                continue;
            }

            if (empty($nis) || empty($nisn) || empty($nama) || $kelas_id <= 0) {
                $errors[] = "Data tidak valid: $nis";
                continue;
            }
            
            $cek = conn()->prepare("SELECT id FROM siswa WHERE nis = ?");
            $cek->bind_param("s", $nis);
            $cek->execute();
            $cek->store_result();
            
            if ($cek->num_rows > 0) {
                $stmt = conn()->prepare("UPDATE siswa SET nisn = ?, nama = ?, kelas_id = ?, jenis_kelamin = ? WHERE nis = ?");
                $stmt->bind_param("ssiss", $nisn, $nama, $kelas_id, $jenis_kelamin, $nis);
                
                if ($stmt->execute()) {
                    $updated++;
                } else {
                    $errors[] = "Error update NIS $nis";
                }
            } else {
                $stmt = conn()->prepare("INSERT INTO siswa (nis, nisn, nama, kelas_id, jenis_kelamin) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssis", $nis, $nisn, $nama, $kelas_id, $jenis_kelamin);
                
                if ($stmt->execute()) {
                    $imported++;
                } else {
                    $errors[] = "Error insert NIS $nis";
                }
            }
        }
        
        fclose($handle);
        
        if ($imported > 0) {
            $success = "Berhasil menambahkan $imported data siswa";
        }
        if ($updated > 0) {
            $success .= ($success ? "<br>" : "") . "Berhasil memperbarui $updated data";
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
        <i class="fas fa-file-import me-2"></i>Import Siswa
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
                            Format: nis,nisn,nama,kelas_id,jenis_kelamin
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

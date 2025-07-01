<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        // Lewati header
        fgetcsv($handle);
        
        $imported = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 2) continue;
            
            $nama_kelas = trim($data[0]);
            $wali_kelas = trim($data[1]);
            
            // Validasi data
            if (empty($nama_kelas) || empty($wali_kelas)) {
                $errors[] = "Data tidak valid: $nama_kelas, $wali_kelas";
                continue;
            }
            
            // Cek duplikat kelas
            $cek = $koneksi->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
            $cek->bind_param("s", $nama_kelas);
            $cek->execute();
            $cek->store_result();
            
            if ($cek->num_rows > 0) {
                $errors[] = "Kelas $nama_kelas sudah ada";
                continue;
            }
            
            // Insert data
            $stmt = $koneksi->prepare("INSERT INTO kelas (nama_kelas, wali_kelas) VALUES (?, ?)");
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

require_once '../includes/header.php';
?>

<h2>Import Data Kelas</h2>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
        <label>Pilih File CSV</label>
        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        <small class="text-muted">
            Format: nama_kelas,wali_kelas<br>
            <a href="template_kelas.csv" class="btn btn-sm btn-outline-secondary mt-2">Unduh Template</a>
        </small>
    </div>
    <button type="submit" class="btn btn-primary">Import</button>
    <a href="index.php" class="btn btn-secondary">Kembali</a>
</form>

<?php require_once '../includes/footer.php'; ?>

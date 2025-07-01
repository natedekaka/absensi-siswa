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
        $updated = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 5) continue; // Sekarang butuh 5 kolom
            
            $nis = trim($data[0]);
            $nisn = trim($data[1]);
            $nama = trim($data[2]);
            $kelas_id = (int)trim($data[3]);
            $jenis_kelamin = trim($data[4]);

            // Validasi jenis kelamin
            if (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'])) {
                $errors[] = "Jenis kelamin tidak valid pada NIS $nis";
                continue;
            }

            // Validasi data
            if (empty($nis) || empty($nisn) || empty($nama) || $kelas_id <= 0) {
                $errors[] = "Data tidak valid: $nis, $nisn, $nama, $kelas_id, $jenis_kelamin";
                continue;
            }
            
            // Cek duplikat NIS
            $cek = $koneksi->prepare("SELECT id FROM siswa WHERE nis = ?");
            $cek->bind_param("s", $nis);
            $cek->execute();
            $cek->store_result();
            
            if ($cek->num_rows > 0) {
                // Update data siswa
                $stmt = $koneksi->prepare("UPDATE siswa SET nisn = ?, nama = ?, kelas_id = ?, jenis_kelamin = ? WHERE nis = ?");
                $stmt->bind_param("ssiss", $nisn, $nama, $kelas_id, $jenis_kelamin, $nis);
                
                if ($stmt->execute()) {
                    $updated++;
                } else {
                    $errors[] = "Error saat update NIS $nis: " . $stmt->error;
                }
            } else {
                // Tambahkan data baru
                $stmt = $koneksi->prepare("INSERT INTO siswa (nis, nisn, nama, kelas_id, jenis_kelamin) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssis", $nis, $nisn, $nama, $kelas_id, $jenis_kelamin);
                
                if ($stmt->execute()) {
                    $imported++;
                } else {
                    $errors[] = "Error saat insert NIS $nis: " . $stmt->error;
                }
            }
        }
        
        fclose($handle);
        
        $messages = [];
        if ($imported > 0) {
            $messages[] = "Berhasil menambahkan $imported data siswa baru";
        }
        if ($updated > 0) {
            $messages[] = "Berhasil memperbarui $updated data siswa yang sudah ada";
        }
        if (!empty($messages)) {
            $success = implode("<br>", $messages);
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

<h2>Import Data Siswa</h2>

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
            Format: nis,nisn,nama,kelas_id,jenis_kelamin<br>
            Contoh: 123,N123,Dani,8,Laki-laki<br>
            <a href="template_siswa.csv" class="btn btn-sm btn-outline-secondary mt-2">Unduh Template</a>
        </small>
    </div>
    <button type="submit" class="btn btn-primary">Import</button>
    <a href="index.php" class="btn btn-secondary">Kembali</a>
</form>

<?php require_once '../includes/footer.php'; ?>
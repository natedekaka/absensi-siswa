<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kelas = $_POST['nama_kelas'];
    $wali_kelas = $_POST['wali_kelas'];
    
    // Validasi input
    if (empty($nama_kelas)) {
        $error = 'Nama kelas harus diisi!';
    } else {
        // Cek apakah kelas sudah ada
        $cek = $koneksi->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
        $cek->bind_param("s", $nama_kelas);
        $cek->execute();
        $cek->store_result();
        
        if ($cek->num_rows > 0) {
            $error = "Kelas '$nama_kelas' sudah ada!";
        } else {
            $stmt = $koneksi->prepare("INSERT INTO kelas (nama_kelas, wali_kelas) VALUES (?, ?)");
            $stmt->bind_param("ss", $nama_kelas, $wali_kelas);
            
           if ($stmt->execute()) {
    header("Location: index.php?add_success=1");
    exit;
} else {
    $error = "Error: " . $stmt->error;
}
        }
    }
}

require_once '../includes/header.php';
?>

<h2>Tambah Kelas</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="mb-3">
        <label>Nama Kelas</label>
        <input type="text" name="nama_kelas" class="form-control" required
               placeholder="Contoh: X IPA 1">
    </div>
    <div class="mb-3">
        <label>Wali Kelas</label>
        <input type="text" name="wali_kelas" class="form-control" required
               placeholder="Nama lengkap wali kelas">
    </div>
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="index.php" class="btn btn-secondary">Batal</a>
</form>

<?php require_once '../includes/footer.php'; ?>
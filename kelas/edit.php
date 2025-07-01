<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';

// Ambil data kelas yang akan diedit
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $koneksi->prepare("SELECT * FROM kelas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("Kelas tidak ditemukan");
    }
    
    $kelas = $result->fetch_assoc();
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nama_kelas = $_POST['nama_kelas'];
    $wali_kelas = $_POST['wali_kelas'];
    
    // Validasi nama kelas unik (kecuali untuk kelas ini)
    $cek = $koneksi->prepare("SELECT id FROM kelas WHERE nama_kelas = ? AND id != ?");
    $cek->bind_param("si", $nama_kelas, $id);
    $cek->execute();
    $cek->store_result();
    
    if ($cek->num_rows > 0) {
        $error = "Kelas '$nama_kelas' sudah ada!";
    } else {
        $stmt = $koneksi->prepare("UPDATE kelas SET nama_kelas=?, wali_kelas=? WHERE id=?");
        $stmt->bind_param("ssi", $nama_kelas, $wali_kelas, $id);
        
        if ($stmt->execute()) {
            header("Location: index.php?edit_success=1");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}

require_once '../includes/header.php';
?>

<h2>Edit Data Kelas</h2>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="id" value="<?= $kelas['id'] ?>">
    <div class="mb-3">
        <label>Nama Kelas</label>
        <input type="text" name="nama_kelas" class="form-control" 
               value="<?= $kelas['nama_kelas'] ?>" required
               placeholder="Contoh: X IPA 1">
    </div>
    <div class="mb-3">
        <label>Wali Kelas</label>
        <input type="text" name="wali_kelas" class="form-control" 
               value="<?= $kelas['wali_kelas'] ?>" required
               placeholder="Nama lengkap wali kelas">
    </div>
    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="index.php" class="btn btn-secondary">Batal</a>
</form>

<?php require_once '../includes/footer.php'; ?>

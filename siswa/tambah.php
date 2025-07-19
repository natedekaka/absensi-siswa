<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// Panggil config.php untuk koneksi database
require_once '../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $koneksi->real_escape_string($_POST['nis']);
    $nisn = $koneksi->real_escape_string($_POST['nisn']);
    $nama = $koneksi->real_escape_string($_POST['nama']);
    $kelas_id = $koneksi->real_escape_string($_POST['kelas_id']);
    $jenis_kelamin = $koneksi->real_escape_string($_POST['jenis_kelamin']);

    // Validasi jenis kelamin
    if (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'])) {
        $error = "Jenis kelamin harus dipilih.";
    } else {
        // Gunakan prepared statement untuk mencegah SQL injection
        $stmt = $koneksi->prepare("INSERT INTO siswa (nis, nisn, nama, kelas_id, jenis_kelamin) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $koneksi->error);
        }

        $stmt->bind_param("sssis", $nis, $nisn, $nama, $kelas_id, $jenis_kelamin);

        if ($stmt->execute()) {
            header("Location: index.php?add_success=1");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Baru panggil header untuk tampilan
require_once '../includes/header.php';
?>

<!-- Container Form -->
<div class="form-container">
    <h2 class="mb-4 text-center">➕ Tambah Siswa</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- NIS -->
        <div class="mb-3">
            <label class="form-label">NIS</label>
            <input type="text" name="nis" class="form-control" placeholder="Masukkan NIS" required>
        </div>

        <!-- NISN -->
        <div class="mb-3">
            <label class="form-label">NISN</label>
            <input type="text" name="nisn" class="form-control" placeholder="Masukkan NISN" required>
        </div>

        <!-- Nama Lengkap -->
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso" required>
        </div>

        <!-- Jenis Kelamin -->
        <div class="mb-3">
            <label class="form-label">Jenis Kelamin</label>
            <select name="jenis_kelamin" class="form-select" required>
                <option value="">-- Pilih Jenis Kelamin --</option>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
            </select>
        </div>

        <!-- Kelas -->
        <div class="mb-3">
            <label class="form-label">Kelas</label>
            <select name="kelas_id" class="form-select" required>
                <option value="">-- Pilih Kelas --</option>
                <?php
                $kelas = $koneksi->query("SELECT * FROM kelas");
                while ($row = $kelas->fetch_assoc()):
                ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kelas']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Tombol Submit -->
        <div class="d-grid gap-2 d-md-flex justify-content-md-between">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Simpan
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times me-1"></i> Batal
            </a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
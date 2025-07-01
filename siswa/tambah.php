<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// Panggil config.php pertama kali untuk koneksi database
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $_POST['nis'];
    $nisn = $_POST['nisn'];
    $nama = $_POST['nama'];
    $kelas_id = $_POST['kelas_id'];
    $jenis_kelamin = $_POST['jenis_kelamin']; // Ambil nilai jenis kelamin dari form

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
    }
}

// Baru panggil header untuk tampilan
require_once '../includes/header.php';
?>

<h2>Tambah Siswa</h2>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
    <div class="mb-3">
        <label>NIS</label>
        <input type="text" name="nis" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>NISN</label>
        <input type="text" name="nisn" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Jenis Kelamin</label>
        <select name="jenis_kelamin" class="form-select" required>
            <option value="">-- Pilih Jenis Kelamin --</option>
            <option value="Laki-laki">Laki-laki</option>
            <option value="Perempuan">Perempuan</option>
        </select>
    </div>
    <div class="mb-3">
        <label>Kelas</label>
        <select name="kelas_id" class="form-select" required>
            <?php
            $kelas = $koneksi->query("SELECT * FROM kelas");
            while ($row = $kelas->fetch_assoc()):
            ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kelas']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="index.php" class="btn btn-secondary">Batal</a>
</form>

<?php require_once '../includes/footer.php'; ?>
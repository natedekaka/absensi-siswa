<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';

// Ambil data siswa yang akan diedit
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $koneksi->prepare("SELECT siswa.*, kelas.nama_kelas 
                              FROM siswa 
                              JOIN kelas ON siswa.kelas_id = kelas.id 
                              WHERE siswa.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("Siswa tidak ditemukan");
    }
    
    $siswa = $result->fetch_assoc();
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nis = $_POST['nis'];
    $nisn = $_POST['nisn'];
    $nama = $_POST['nama'];
    $kelas_id = $_POST['kelas_id'];
    $jenis_kelamin = $_POST['jenis_kelamin']; // Tambahkan input jenis_kelamin

    // Validasi jenis kelamin
    if (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'])) {
        $error = "Jenis kelamin tidak valid.";
    } else {
        // Validasi NIS unik (kecuali untuk siswa ini)
        $cek = $koneksi->prepare("SELECT id FROM siswa WHERE nis = ? AND id != ?");
        $cek->bind_param("si", $nis, $id);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = "NIS '$nis' sudah digunakan oleh siswa lain!";
        } else {
            $stmt = $koneksi->prepare("UPDATE siswa SET nis=?, nisn=?, nama=?, kelas_id=?, jenis_kelamin=? WHERE id=?");
            $stmt->bind_param("sssisi", $nis, $nisn, $nama, $kelas_id, $jenis_kelamin, $id);

            if ($stmt->execute()) {
                header("Location: index.php?edit_success=1");
                exit;
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}

require_once '../includes/header.php';
?>

<h2>Edit Data Siswa</h2>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="id" value="<?= $siswa['id'] ?>">
    <div class="mb-3">
        <label>NIS</label>
        <input type="text" name="nis" class="form-control" value="<?= htmlspecialchars($siswa['nis']) ?>" required>
    </div>
    <div class="mb-3">
        <label>NISN</label>
        <input type="text" name="nisn" class="form-control" value="<?= htmlspecialchars($siswa['nisn']) ?>" required>
    </div>
    <div class="mb-3">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($siswa['nama']) ?>" required>
    </div>
    <div class="mb-3">
        <label>Jenis Kelamin</label>
        <select name="jenis_kelamin" class="form-select" required>
            <option value="">-- Pilih Jenis Kelamin --</option>
            <option value="Laki-laki" <?= ($siswa['jenis_kelamin'] === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
            <option value="Perempuan" <?= ($siswa['jenis_kelamin'] === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
        </select>
    </div>
    <div class="mb-3">
        <label>Kelas</label>
        <select name="kelas_id" class="form-select" required>
            <?php
            $kelas_result = $koneksi->query("SELECT * FROM kelas");
            while($kelas = $kelas_result->fetch_assoc()):
            ?>
            <option value="<?= $kelas['id'] ?>" <?= ($kelas['id'] == $siswa['kelas_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($kelas['nama_kelas']) ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="index.php" class="btn btn-secondary">Batal</a>
</form>

<?php require_once '../includes/footer.php'; ?>
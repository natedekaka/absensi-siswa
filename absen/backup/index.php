<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';
require_once '../includes/header.php';
?>
<h2>Input Absensi</h2>
<form method="POST" action="proses.php">
    <div class="row mb-3">
        <div class="col-md-4">
            <label>Tanggal</label>
            <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-4">
            <label>Kelas</label>
            <select id="kelas" class="form-select" required>
                <option value="">Pilih Kelas</option>
                <?php
                $kelas = $koneksi->query("SELECT * FROM kelas");
                while($row = $kelas->fetch_assoc()):
                ?>
                <option value="<?= $row['id'] ?>"><?= $row['nama_kelas'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    
    <div id="siswa-container"></div>
    
    <button type="submit" class="btn btn-primary mt-3">Simpan Absensi</button>
</form>

<script>
document.getElementById('kelas').addEventListener('change', loadSiswa);
document.getElementById('tanggal').addEventListener('change', loadSiswa);

function loadSiswa() {
    const kelasId = document.getElementById('kelas').value;
    const tanggal = document.getElementById('tanggal').value;
    
    if(kelasId) {
        fetch(`get_siswa.php?kelas_id=${kelasId}&tanggal=${tanggal}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('siswa-container').innerHTML = data;
            });
    } else {
        document.getElementById('siswa-container').innerHTML = '';
    }
}
</script>
<?php require_once '../includes/footer.php'; ?>
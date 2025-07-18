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
<form method="POST" action="proses.php" id="form-absensi">
    <input type="hidden" name="kelas_id" id="kelas_id"> <!-- untuk menyimpan kelas_id -->

    <div class="row mb-3">
        <div class="col-md-4">
            <label>Tanggal</label>
            <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-4">
            <label>Kelas</label>
            <select id="kelas" class="form-select" required>
                <option value="">Pilih Kelas</option>
                <option value="all">Semua Kelas</option>
                <?php
                $kelas = $koneksi->query("SELECT * FROM kelas");
                while($row = $kelas->fetch_assoc()):
                ?>
                <option value="<?= $row['id'] ?>"><?= $row['nama_kelas'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <!-- Input Pencarian Nama Siswa -->
    <div class="row mb-3">
        <div class="col-md-6">
            <input type="text" id="search_nama" class="form-control" placeholder="Cari nama siswa..." style="display: none;">
        </div>
    </div>

    <!-- Tombol Simpan Atas -->
    <div id="tombolSimpanAtas" style="display: none;" class="mb-3">
        <button type="submit" class="btn btn-primary">Simpan Absensi</button>
    </div>

    <div id="siswa-container"></div>

    <!-- Tombol Simpan Bawah -->
    <div id="tombolSimpanBawah" style="display: none;" class="mt-3">
        <button type="submit" class="btn btn-primary">Simpan Absensi</button>
    </div>
</form>

<script>
// Fungsi untuk menampilkan/menyembunyikan tombol
function toggleTombolSimpan(display) {
    document.getElementById('tombolSimpanAtas').style.display = display;
    document.getElementById('tombolSimpanBawah').style.display = display;
}

// Fungsi untuk menampilkan input pencarian
function toggleSearchInput(display) {
    document.getElementById('search_nama').style.display = display;
}

document.getElementById('kelas').addEventListener('change', function () {
    const kelasId = this.value;

    // Update nilai input hidden kelas_id
    document.getElementById('kelas_id').value = kelasId;

    if (kelasId) {
        toggleTombolSimpan('block');   // Tampilkan tombol
        toggleSearchInput('block');     // Tampilkan pencarian
        loadSiswa();                    // Muat data siswa
    } else {
        toggleTombolSimpan('none');     // Sembunyikan tombol
        toggleSearchInput('none');      // Sembunyikan pencarian
        document.getElementById('siswa-container').innerHTML = ''; // Kosongkan tabel
    }
});

document.getElementById('tanggal').addEventListener('change', loadSiswa);

document.getElementById('search_nama').addEventListener('input', function () {
    loadSiswa(); // Muat ulang data siswa dengan parameter pencarian
});

function loadSiswa() {
    const kelasId = document.getElementById('kelas').value;
    const tanggal = document.getElementById('tanggal').value;
    const search = document.getElementById('search_nama').value;

    if (kelasId) {
        let url = `get_siswa.php?kelas_id=${kelasId}&tanggal=${tanggal}`;
        if (search) {
            url += `&search=${encodeURIComponent(search)}`;
        }

        fetch(url)
            .then(response => response.text())
            .then(data => {
                document.getElementById('siswa-container').innerHTML = data;
            });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
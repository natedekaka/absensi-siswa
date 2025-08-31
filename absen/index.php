<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';
require_once '../includes/header.php';
?>

<style>
:root {
    --wa-green: #075E54;
    --wa-light: #25D366;
    --wa-chat: #dcf8c6;
    --wa-bg: #ECE5DD;
    --wa-text: #333;
}

body {
    background: var(--wa-bg) !important;
    color: var(--wa-text);
}

.wa-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.wa-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(7,94,84,0.15);
}

.wa-input {
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    padding: 10px 20px;
    transition: all 0.3s ease;
}

.wa-input:focus {
    border-color: var(--wa-light);
    box-shadow: 0 0 0 0.2rem rgba(37,211,102,.25);
}

.btn-wa-primary {
    background: var(--wa-green);
    border: none;
    border-radius: 25px;
    padding: 10px 30px;
    color: white;
    transition: all 0.3s ease;
}

.btn-wa-primary:hover {
    background: #054c43;
    transform: scale(1.05);
    color: white;
}

.btn-wa-secondary {
    background: var(--wa-light);
    border: none;
    border-radius: 25px;
    padding: 10px 30px;
    color: white;
    transition: all 0.3s ease;
}

.btn-wa-secondary:hover {
    background: #1ebe57;
    color: white;
}

.wa-table {
    background: white;
    border-radius: 15px;
    overflow: hidden;
}

.wa-table th {
    background: var(--wa-green);
    color: white;
    border: none;
    font-weight: 500;
}

.wa-table tr:hover {
    background: var(--wa-chat);
}

.absensi-bubble {
    background: var(--wa-chat);
    border-radius: 20px;
    padding: 15px;
    margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.search-container {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

#search_nama {
    padding-left: 40px;
    border-radius: 25px;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4" style="color: var(--wa-green);">
                <i class="fas fa-calendar-check me-2"></i>Input Absensi
            </h2>
        </div>
    </div>

    <form method="POST" action="proses.php" id="form-absensi">
        <input type="hidden" name="kelas_id" id="kelas_id">

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="wa-card p-3">
                    <label class="form-label fw-bold" style="color: var(--wa-green);">
                        <i class="fas fa-calendar-alt me-2"></i>Tanggal
                    </label>
                    <input type="date" name="tanggal" id="tanggal" 
                           class="form-control wa-input" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="wa-card p-3">
                    <label class="form-label fw-bold" style="color: var(--wa-green);">
                        <i class="fas fa-users me-2"></i>Kelas
                    </label>
                    <select id="kelas" class="form-select wa-input" required>
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
        </div>

        <!-- Input Pencarian -->
        <div class="row mb-4" id="searchContainer" style="display: none;">
            <div class="col-md-6">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="search_nama" class="form-control wa-input" 
                           placeholder="Cari nama siswa...">
                </div>
            </div>
        </div>

        <!-- Tombol Simpan Atas -->
        <div id="tombolSimpanAtas" style="display: none;" class="mb-4">
            <button type="submit" class="btn btn-wa-primary">
                <i class="fas fa-save me-2"></i>Simpan Absensi
            </button>
        </div>

        <!-- Container Siswa -->
        <div id="siswa-container" class="wa-card p-0 mb-4"></div>

        <!-- Tombol Simpan Bawah -->
        <div id="tombolSimpanBawah" style="display: none;" class="text-center">
            <button type="submit" class="btn btn-wa-primary btn-lg">
                <i class="fas fa-save me-2"></i>Simpan Semua Absensi
            </button>
        </div>
    </form>
</div>

<script>
// Fungsi untuk toggle elemen
function toggleElements(show) {
    const display = show ? 'block' : 'none';
    document.getElementById('tombolSimpanAtas').style.display = display;
    document.getElementById('tombolSimpanBawah').style.display = display;
    document.getElementById('searchContainer').style.display = display;
}

document.getElementById('kelas').addEventListener('change', function () {
    const kelasId = this.value;
    document.getElementById('kelas_id').value = kelasId;

    if (kelasId) {
        toggleElements(true);
        loadSiswa();
    } else {
        toggleElements(false);
        document.getElementById('siswa-container').innerHTML = '';
    }
});

document.getElementById('tanggal').addEventListener('change', loadSiswa);
document.getElementById('search_nama').addEventListener('input', loadSiswa);

function loadSiswa() {
    const kelasId = document.getElementById('kelas').value;
    const tanggal = document.getElementById('tanggal').value;
    const search = document.getElementById('search_nama').value;

    if (kelasId) {
        let url = `get_siswa.php?kelas_id=${kelasId}&tanggal=${tanggal}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;

        fetch(url)
            .then(response => response.text())
            .then(data => {
                document.getElementById('siswa-container').innerHTML = data;
            });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
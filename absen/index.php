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

/* --- PERUBAHAN CSS DIMULAI DI SINI --- */
/* CSS untuk membuat tampilan seimbang dengan footer di bawah */
body {
    margin: 0; /* Hapus margin default browser */
    display: flex;
    flex-direction: column;
    min-height: 100vh; /* Pastikan body setinggi layar */
    background-color: var(--wa-bg) !important;
    color: var(--wa-text);
}

/* Wrapper ini akan mengisi ruang kosong dan mendorong footer ke bawah */
.content-wrapper {
    flex-grow: 1; /* Ini adalah kuncinya: buat area ini mengembang */
}
/* --- PERUBAHAN CSS SELESAI DI SINI --- */


.absensi-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
}
.absensi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(7,94,84,0.12);
}
.btn-wa-primary {
    background: var(--wa-green);
    border: none;
    border-radius: 50px;
    padding: 0.6rem 1.5rem;
    color: white;
    font-weight: 500;
}
.btn-wa-primary:hover {
    background: #054c43;
    transform: scale(1.03);
    color: white;
}
.btn-wa-secondary {
    background: var(--wa-light);
    border: none;
    border-radius: 50px;
    padding: 0.6rem 1.5rem;
    color: white;
    font-weight: 500;
}
.btn-wa-secondary:hover {
    background: #1ebe57;
    color: white;
}
.search-input {
    border-radius: 50px;
    padding-left: 2.5rem;
    border: 2px solid #e0e0e0;
}
.search-input:focus {
    border-color: var(--wa-light);
    box-shadow: 0 0 0 0.2rem rgba(37,211,102,0.25);
}
.search-icon {
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}
</style>

<!-- PERUBAHAN: Saya menambahkan wrapper .content-wrapper -->
<div class="content-wrapper">
    <div class="container py-4">
        <div class="d-flex align-items-center mb-4">
            <h2 class="fw-bold mb-0" style="color: var(--wa-green);">
                <i class="fas fa-calendar-check me-2"></i>Input Absensi
            </h2>
        </div>

        <form method="POST" action="proses.php" id="form-absensi">
            <input type="hidden" name="kelas_id" id="kelas_id">

            <!-- Filter Tanggal & Kelas -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="absensi-card p-3 h-100">
                        <label class="form-label fw-semibold d-flex align-items-center mb-2" style="color: var(--wa-green);">
                            <i class="fas fa-calendar-alt me-2"></i> Tanggal
                        </label>
                        <input type="date" name="tanggal" id="tanggal" 
                               class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="absensi-card p-3 h-100">
                        <label class="form-label fw-semibold d-flex align-items-center mb-2" style="color: var(--wa-green);">
                            <i class="fas fa-chalkboard me-2"></i> Kelas
                        </label>
                        <select id="kelas" class="form-select" required>
                            <option value="">Pilih Kelas</option>
                            <option value="all">Semua Kelas</option>
                            <?php
                            $kelas = $koneksi->query("SELECT * FROM kelas ORDER BY nama_kelas");
                            while ($row = $kelas->fetch_assoc()):
                            ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kelas']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Pencarian Siswa -->
            <div class="row mb-4" id="searchContainer" style="display: none;">
                <div class="col-md-6">
                    <div class="position-relative">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="search_nama" class="form-control search-input" 
                               placeholder="Cari nama siswa...">
                    </div>
                </div>
            </div>

            <!-- Tombol Simpan Atas -->
            <div id="tombolSimpanAtas" class="mb-4" style="display: none;">
                <button type="submit" class="btn btn-wa-primary">
                    <i class="fas fa-save me-2"></i>Simpan Absensi
                </button>
            </div>

            <!-- Daftar Siswa -->
            <div id="siswa-container" class="mb-4"></div>

            <!-- Tombol Simpan Bawah -->
            <div id="tombolSimpanBawah" class="text-center" style="display: none;">
                <button type="submit" class="btn btn-wa-primary btn-lg px-5">
                    <i class="fas fa-save me-2"></i>Simpan Semua Absensi
                </button>
            </div>
        </form>
    </div>
</div>
<!-- AKHIR PERUBAHAN -->

<script>
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
        let url = `get_siswa.php?kelas_id=${encodeURIComponent(kelasId)}&tanggal=${encodeURIComponent(tanggal)}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(data => {
                document.getElementById('siswa-container').innerHTML = data;
            })
            .catch(error => {
                console.error('Error loading siswa:', error);
                document.getElementById('siswa-container').innerHTML = 
                    '<div class="alert alert-danger rounded-3">Gagal memuat data siswa.</div>';
            });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
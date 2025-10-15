<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';
require_once '../includes/header.php'; // Ini adalah header yang tidak boleh diubah
?>
<style>
    body {
        background-color: #f8f9fa;
    }

    .container-absensi {
        max-width: 700px;
        margin: 60px auto 80px auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    h2.text-center {
        margin-bottom: 2rem;
    }

    .search-result {
        cursor: pointer;
        padding: 12px 16px;
        border-bottom: 1px solid #ddd;
        transition: background-color 0.3s, transform 0.2s;
    }

    .search-result:hover {
        background-color: #e9ecef;
        transform: scale(1.02);
    }

    .fade-slide {
        animation: fadeInUp 0.5s ease forwards;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        animation: fadeInUp 0.5s ease forwards;
    }

    /* Tombol lingkaran */
    .btn-check {
        position: absolute;
        clip: rect(0, 0, 0, 0);
    }

    .btn-round {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        transition: all 0.3s ease;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        background-color: #e9ecef;
        color: #333;
    }

    .btn-round:hover {
        transform: scale(1.1);
    }

    .btn-check:checked + .btn-round {
        transform: scale(1.15);
        box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.6), 0 0 10px rgba(0, 123, 255, 0.4);
        border: 2px solid white;
        z-index: 1;
    }

    .btn-check:checked + .btn-round i,
    .btn-check:checked + .btn-round span {
        color: #fff !important;
    }

    .btn-check:checked + .btn-round.btn-success {
        background-color: #28a745 !important;
    }

    .btn-check:checked + .btn-round.btn-secondary {
        background-color: #6c757d !important;
    }

    .btn-check:checked + .btn-round.btn-warning {
        background-color: #ffc107 !important;
        color: #000 !important;
    }

    .btn-check:checked + .btn-round.btn-info {
        background-color: #17a2b8 !important;
    }

    .btn-check:checked + .btn-round.btn-danger {
        background-color: #dc3545 !important;
    }

    #status-dipilih {
        margin-top: 15px;
        font-weight: bold;
        font-size: 18px;
        color: #333;
        display: none;
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Gaya hasil pencarian */
    .list-group {
        border-radius: 10px;
        overflow: hidden;
    }

    .list-group-item {
        transition: background-color 0.3s, transform 0.2s;
        font-weight: 500;
    }

    .list-group-item:hover {
        background-color: #f1f1f1;
        transform: translateX(5px);
    }
</style>

<div class="container-absensi">
    <h2 class="mb-4 text-center">Absensi Per Siswa</h2>

    <!-- Notifikasi Sukses -->
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success fade-slide text-center" id="notif">
            Absensi berhasil disimpan!
        </div>
    <?php endif; ?>

    <!-- Form Pencarian -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8">
            <input type="text" id="search_nama" class="form-control form-control-lg shadow-sm" placeholder="Cari nama siswa..." autocomplete="off">
        </div>
    </div>

    <!-- Hasil Pencarian -->
    <div id="hasil-pencarian" class="mb-4"></div>

    <!-- Form Absensi -->
    <div id="form-container" style="display: none;" class="fade-slide">
        <form method="POST" action="proses_persiswa.php" id="form-absensi">
            <input type="hidden" name="tanggal" id="tanggal_absen" value="<?= date('Y-m-d') ?>" required>
            <input type="hidden" name="siswa_id" id="siswa_id">

            <div class="card shadow-lg border-0 rounded-4 mb-4">
                <div class="card-header bg-gradient text-white rounded-top" style="background: linear-gradient(90deg, #007bff, #00c6ff);">
                    Form Absensi
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nama Siswa</label>
                        <input type="text" class="form-control" id="nama_siswa" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3 text-center">
                        <label class="form-label d-block fw-bold">Status Absensi</label>
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <!-- Hadir -->
                            <input type="radio" class="btn-check" name="status" id="hadir" value="Hadir" checked autocomplete="off">
                            <label class="btn-round btn-success text-white" for="hadir">
                                <i class="fas fa-check-circle fa-2x mb-1"></i><br>
                                <span>Hadir</span>
                            </label>

                            <!-- Terlambat -->
                            <input type="radio" class="btn-check" name="status" id="terlambat" value="Terlambat" autocomplete="off">
                            <label class="btn-round btn-secondary" for="terlambat">
                                <i class="fas fa-clock fa-2x mb-1"></i><br>
                                <span>Terlambat</span>
                            </label>

                            <!-- Sakit -->
                            <input type="radio" class="btn-check" name="status" id="sakit" value="Sakit" autocomplete="off">
                            <label class="btn-round btn-warning text-dark" for="sakit">
                                <i class="fas fa-heartbeat fa-2x mb-1"></i><br>
                                <span>Sakit</span>
                            </label>

                            <!-- Izin -->
                            <input type="radio" class="btn-check" name="status" id="izin" value="Izin" autocomplete="off">
                            <label class="btn-round btn-info text-white" for="izin">
                                <i class="fas fa-hand-paper fa-2x mb-1"></i><br>
                                <span>Izin</span>
                            </label>

                            <!-- Alfa -->
                            <input type="radio" class="btn-check" name="status" id="alfa" value="Alfa" autocomplete="off">
                            <label class="btn-round btn-danger" for="alfa">
                                <i class="fas fa-times-circle fa-2x mb-1"></i><br>
                                <span>Alfa</span>
                            </label>
                        </div>
                    </div>

                    <!-- Tampilkan status yang dipilih -->
                    <div id="status-dipilih" class="text-center mt-3"></div>

                    <div class="d-flex gap-2 mt-4 justify-content-center">
                        <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm">
                            <i class="fas fa-save me-2"></i>Simpan Absensi
                        </button>
                        <button type="button" class="btn btn-outline-secondary px-4 py-2 shadow-sm" onclick="resetForm()">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Tombol Kembali -->
    <div class="text-center mt-4">
        <a href="../dashboard/" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
        </a>
    </div>
</div>

<script>
    // Hilangkan notifikasi setelah 3 detik
    document.addEventListener("DOMContentLoaded", function () {
        const notif = document.getElementById("notif");
        if (notif) {
            setTimeout(() => {
                notif.style.display = "none";
            }, 3000);
        }
    });

    // Fungsi pencarian siswa
    document.getElementById('search_nama').addEventListener('input', function () {
        const search = this.value;

        if (search.length >= 2) {
            fetch('get_siswa_persiswa.php?search=' + encodeURIComponent(search))
                .then(response => response.text())
                .then(data => {
                    document.getElementById('hasil-pencarian').innerHTML = data;
                });
        } else {
            document.getElementById('hasil-pencarian').innerHTML = '';
        }
    });

    // Fungsi untuk isi form setelah pilih siswa
    window.pilihSiswa = function(id, nama) {
        document.getElementById('siswa_id').value = id;
        document.getElementById('nama_siswa').value = nama;
        document.getElementById('form-container').style.display = 'block';
    };

    // Reset form dan sembunyikan
    function resetForm() {
        document.getElementById('form-absensi').reset();
        document.getElementById('form-container').style.display = 'none';
        document.getElementById('search_nama').value = '';
        document.getElementById('hasil-pencarian').innerHTML = '';
        document.getElementById('status-dipilih').style.display = 'none';
    }

    // Tampilkan status yang dipilih
    document.querySelectorAll('input[name="status"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const label = document.querySelector('label[for="' + this.id + '"] span').textContent;
            const statusDipilih = document.getElementById('status-dipilih');
            statusDipilih.textContent = 'Status yang dipilih: ' + label;
            statusDipilih.style.display = 'block';
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>
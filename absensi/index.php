<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Input Absensi - Sistem Absensi Siswa';
$scripts = '<script>
function toggleElements(show) {
    const display = show ? "block" : "none";
    document.getElementById("tombolSimpanAtas").style.display = display;
    document.getElementById("tombolSimpanBawah").style.display = display;
    document.getElementById("searchContainer").style.display = display;
}

document.getElementById("semester").addEventListener("change", function() {
    document.getElementById("semester_id").value = this.value;
    loadSiswa();
});

document.getElementById("kelas").addEventListener("change", function() {
    const kelasId = this.value;
    document.getElementById("kelas_id").value = kelasId;
    if (kelasId) {
        toggleElements(true);
        loadSiswa();
    } else {
        toggleElements(false);
        document.getElementById("siswa-container").innerHTML = "";
    }
});

document.getElementById("tanggal").addEventListener("change", loadSiswa);
document.getElementById("search_nama").addEventListener("input", loadSiswa);

function loadSiswa() {
    const kelasId = document.getElementById("kelas").value;
    const tanggal = document.getElementById("tanggal").value;
    const semesterId = document.getElementById("semester").value;
    const search = document.getElementById("search_nama").value;

    if (kelasId && semesterId) {
        let url = "get_siswa.php?kelas_id=" + encodeURIComponent(kelasId) + 
                  "&tanggal=" + encodeURIComponent(tanggal) + 
                  "&semester_id=" + encodeURIComponent(semesterId);
        if (search) url += "&search=" + encodeURIComponent(search);

        fetch(url)
            .then(response => response.text())
            .then(data => {
                document.getElementById("siswa-container").innerHTML = data;
            })
            .catch(error => {
                console.error("Error:", error);
                document.getElementById("siswa-container").innerHTML = 
                    "<div class=\"alert alert-danger\">Gagal memuat data siswa.</div>";
            });
    } else if (kelasId) {
        document.getElementById("siswa-container").innerHTML = 
            "<div class=\"alert alert-warning\">Pilih semester terlebih dahulu!</div>";
    }
}
</script>';

ob_start();
?>

<div class="d-flex align-items-center mb-4">
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-clipboard-check me-2"></i>Input Absensi Harian
    </h2>
</div>

<form method="POST" action="proses.php" id="form-absensi">
    <input type="hidden" name="kelas_id" id="kelas_id">
    <input type="hidden" name="semester_id" id="semester_id">

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card-custom p-3 h-100">
                <label class="form-label fw-semibold mb-2 text-wa-dark">
                    <i class="fas fa-calendar-alt me-2"></i>Tanggal
                </label>
                <input type="date" name="tanggal" id="tanggal" class="form-control form-select-custom" 
                       value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom p-3 h-100">
                <label class="form-label fw-semibold mb-2 text-wa-dark">
                    <i class="fas fa-graduation-cap me-2"></i>Semester
                </label>
                <select id="semester" name="semester_id" class="form-select form-select-custom" required>
                    <option value="">Pilih Semester</option>
                    <?php
                    $semester = conn()->query("SELECT * FROM semester ORDER BY is_active DESC, tahun_ajaran_id DESC, semester ASC");
                    while ($row = $semester->fetch_assoc()):
                        $selected = $row['is_active'] ? 'selected' : '';
                    ?>
                    <option value="<?= $row['id'] ?>" <?= $selected ?>><?= htmlspecialchars($row['nama']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom p-3 h-100">
                <label class="form-label fw-semibold mb-2 text-wa-dark">
                    <i class="fas fa-door-open me-2"></i>Kelas
                </label>
                <select id="kelas" class="form-select form-select-custom" required>
                    <option value="">Pilih Kelas</option>
                    <option value="all">Semua Kelas</option>
                    <?php
                    $kelas = conn()->query("SELECT * FROM kelas ORDER BY nama_kelas");
                    while ($row = $kelas->fetch_assoc()):
                    ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kelas']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="row mb-4" id="searchContainer" style="display: none;">
        <div class="col-md-6">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search_nama" class="form-control form-control-custom" 
                       placeholder="Cari nama siswa...">
            </div>
        </div>
    </div>

    <div id="tombolSimpanAtas" class="mb-4" style="display: none;">
        <button type="submit" class="btn btn-wa-primary">
            <i class="fas fa-save me-2"></i>Simpan Absensi
        </button>
    </div>

    <div id="siswa-container" class="mb-4"></div>

    <div id="tombolSimpanBawah" class="text-center" style="display: none;">
        <button type="submit" class="btn btn-wa-primary btn-lg px-5">
            <i class="fas fa-save me-2"></i>Simpan Semua Absensi
        </button>
    </div>
</form>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

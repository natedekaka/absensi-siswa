<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Tambah Siswa - Sistem Absensi Siswa';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = db()->escape($_POST['nis']);
    $nisn = db()->escape($_POST['nisn']);
    $nama = db()->escape($_POST['nama']);
    $kelas_id = (int)$_POST['kelas_id'];
    $jenis_kelamin = $_POST['jenis_kelamin'];

    if (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'])) {
        $error = "Jenis kelamin harus dipilih.";
    } else {
        $stmt = conn()->prepare("INSERT INTO siswa (nis, nisn, nama, kelas_id, jenis_kelamin) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $nis, $nisn, $nama, $kelas_id, $jenis_kelamin);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Siswa berhasil ditambahkan!";
            header("Location: index.php");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}

$kelas = conn()->query("SELECT * FROM kelas ORDER BY nama_kelas");

ob_start();
?>

<style>
.form-page {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
}
.form-card {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    max-width: 550px;
    width: 100%;
}
.form-card-header {
    background: linear-gradient(135deg, var(--wa-dark) 0%, #0d6e67 100%);
    padding: 2rem;
    text-align: center;
}
.form-card-header h3 {
    color: white;
    font-weight: 600;
    margin: 0;
}
.form-card-header .icon-circle {
    width: 70px;
    height: 70px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}
.form-card-header .icon-circle i {
    font-size: 1.75rem;
    color: white;
}
.form-card-body {
    padding: 2rem;
}
.form-control, .form-select {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s;
}
.form-control:focus, .form-select:focus {
    border-color: var(--wa-green);
    box-shadow: 0 0 0 4px rgba(37,211,102,0.15);
}
.btn-back {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: #666;
    transition: all 0.3s;
}
.btn-back:hover {
    background: #f8f9fa;
    border-color: #ccc;
}
.gender-option {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}
.gender-option:hover {
    border-color: var(--wa-green);
}
.gender-option.selected {
    border-color: var(--wa-green);
    background: rgba(37,211,102,0.1);
}
.gender-option i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}
</style>

<div class="form-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="icon-circle">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3>Tambah Siswa Baru</h3>
                        <p class="mb-0 opacity-75">Isi data siswa dengan lengkap</p>
                    </div>
                    <div class="form-card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger bg-danger text-white border-0 rounded-3 mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-dark">NIS</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0">
                                            <i class="fas fa-id-card text-muted"></i>
                                        </span>
                                        <input type="text" name="nis" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-dark">NISN</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0">
                                            <i class="fas fa-id-card text-muted"></i>
                                        </span>
                                        <input type="text" name="nisn" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark">Nama Lengkap</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-user text-muted"></i>
                                    </span>
                                    <input type="text" name="nama" class="form-control" placeholder="Nama siswa" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark">Jenis Kelamin</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="gender-option d-block" id="gender-laki">
                                            <input type="radio" name="jenis_kelamin" value="Laki-laki" class="d-none">
                                            <i class="fas fa-male d-block text-primary"></i>
                                            <span class="small">Laki-laki</span>
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <label class="gender-option d-block" id="gender-perempuan">
                                            <input type="radio" name="jenis_kelamin" value="Perempuan" class="d-none">
                                            <i class="fas fa-female d-block text-danger"></i>
                                            <span class="small">Perempuan</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-dark">Kelas</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-door-open text-muted"></i>
                                    </span>
                                    <select name="kelas_id" class="form-select" required>
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php while ($row = $kelas->fetch_assoc()): ?>
                                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kelas']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <a href="index.php" class="btn btn-back flex-fill">
                                    <i class="fas fa-arrow-left me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-wa-primary flex-fill">
                                    <i class="fas fa-save me-2"></i>Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.gender-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.gender-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;
    });
});
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

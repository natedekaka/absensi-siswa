<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Kelulusan Siswa - Sistem Absensi Siswa';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tahun_lulus = (int)$_POST['tahun_lulus'];
    
    $siswa_xii = conn()->query("
        SELECT s.id FROM siswa s 
        JOIN kelas k ON s.kelas_id = k.id 
        WHERE s.status = 'aktif' AND (
            k.nama_kelas LIKE 'XII-%' OR 
            k.nama_kelas LIKE '12-%'
        )
    ");

    $count = 0;
    while ($siswa = $siswa_xii->fetch_assoc()) {
        $update = conn()->prepare("UPDATE siswa SET status = 'alumni', tingkat = NULL, tahun_lulus = ? WHERE id = ?");
        $update->bind_param("ii", $tahun_lulus, $siswa['id']);
        $update->execute();
        $count++;
    }

    if ($count > 0) {
        $success = "Berhasil meluluskan $count siswa kelas 12";
    } else {
        $error = "Tidak ada siswa kelas 12 yang diluluskan";
    }
}

$siswa_xii_count = conn()->query("SELECT COUNT(*) as total FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.status = 'aktif' AND (k.nama_kelas LIKE 'XII-%' OR k.nama_kelas LIKE '12-%')")->fetch_assoc()['total'];

$siswa_xii_list = conn()->query("
    SELECT s.id, s.nis, s.nama, k.nama_kelas 
    FROM siswa s 
    JOIN kelas k ON s.kelas_id = k.id 
    WHERE s.status = 'aktif' AND (
        k.nama_kelas LIKE 'XII-%' OR 
        k.nama_kelas LIKE '12-%'
    )
    ORDER BY k.nama_kelas, s.nama
");

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
    max-width: 600px;
    width: 100%;
}
.form-card-header {
    background: linear-gradient(135deg, #198754 0%, #1ebe57 100%);
    padding: 2rem;
    text-align: center;
}
.form-card-header h3 {
    color: white;
    font-weight: 600;
    margin: 0;
}
.form-card-header p {
    color: rgba(255,255,255,0.85);
    margin-top: 0.5rem;
}
.form-card-header .icon-circle {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}
.form-card-header .icon-circle i {
    font-size: 2rem;
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
.alert-custom {
    border-radius: 12px;
    padding: 1rem 1.25rem;
}
.btn-process {
    border-radius: 12px;
    padding: 0.875rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s;
}
.btn-process:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(25, 135, 84, 0.4);
}
.siswa-card {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s;
}
.siswa-card:hover {
    border-color: #198754;
    background: rgba(25, 135, 84, 0.05);
}
</style>

<div class="form-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="icon-circle">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3>Proses Kelulusan Siswa</h3>
                        <p class="mb-0">Tandai siswa kelas 12 sebagai alumni</p>
                    </div>
                    <div class="form-card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-custom bg-success text-white border-0 mb-4">
                                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-custom bg-danger text-white border-0 mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                            </div>
                        <?php endif; ?>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="p-4 bg-primary bg-opacity-10 rounded-3 text-center">
                                    <h2 class="mb-0 text-primary"><?= $siswa_xii_count ?></h2>
                                    <small class="text-muted">Siswa Kelas 12 Aktif</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Tahun Lulus</label>
                                        <select name="tahun_lulus" class="form-select" required>
                                            <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                                            <option value="<?= date('Y') + 1 ?>"><?= date('Y') + 1 ?></option>
                                        </select>
                                    </div>

                                    <div class="alert alert-warning bg-warning bg-opacity-10 border-0 rounded-3 mb-3">
                                        <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                        <strong>Perhatian!</strong> Siswa yang diluluskan akan:
                                        <ul class="mb-0 mt-2">
                                            <li>Berubah status menjadi <strong>ALUMNI</li>
                                            <li>Tidak muncul di daftar absensi</li>
                                            <li>Tersimpan di riwayat alumni</li>
                                        </ul>
                                    </div>

                                    <button type="submit" class="btn btn-success btn-process w-100" style="background: linear-gradient(135deg, #198754 0%, #1ebe57 100%); border: none;">
                                        <i class="fas fa-graduation-cap me-2"></i>Proses Kelulusan
                                    </button>
                                </form>
                            </div>
                        </div>

                        <?php if ($siswa_xii_count > 0): ?>
                        <hr>
                        <h5 class="fw-bold mb-3"><i class="fas fa-users me-2"></i>Daftar Siswa Kelas 12</h5>
                        <div class="row" style="max-height: 300px; overflow-y: auto;">
                            <?php 
                            $current_kelas = '';
                            while ($row = $siswa_xii_list->fetch_assoc()): 
                                if ($current_kelas != $row['nama_kelas']):
                                    if ($current_kelas != ''): echo '</div>'; endif;
                                    $current_kelas = $row['nama_kelas'];
                            ?>
                            <div class="col-md-6 mb-3">
                                <div class="fw-bold text-primary mb-2"><?= htmlspecialchars($current_kelas) ?></div>
                                <?php endif; ?>
                                <div class="siswa-card d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-medium"><?= htmlspecialchars($row['nama']) ?></span>
                                        <small class="text-muted d-block"><?= htmlspecialchars($row['nis']) ?></small>
                                    </div>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">Aktif</span>
                                </div>
                            <?php endwhile; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle text-muted fa-2x mb-2"></i>
                            <p class="text-muted mb-0">Belum ada siswa kelas 12</p>
                        </div>
                        <?php endif; ?>

                        <div class="mt-4 text-center">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Kenaikan Kelas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

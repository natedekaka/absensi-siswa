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
.kelulusan-page {
    padding: 2rem 0;
}
.kelulusan-card {
    border: none;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}
.kelu-header {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    padding: 2rem;
    color: white;
    text-align: center;
    position: relative;
}
.kelu-header h3 {
    font-weight: 600;
    margin: 0;
}
.kelu-header p {
    color: rgba(255,255,255,0.85);
    margin-top: 0.5rem;
}
.kelu-icon {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
}
.kelu-body {
    padding: 2rem;
}
.stat-box {
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
}
.stat-box h2 {
    font-size: 2.5rem;
    font-weight: 700;
}
.stat-box h2.text-primary { color: #6366f1; }
.stat-box h2.text-success { color: #10b981; }
.stat-box p {
    margin: 0;
    font-size: 0.9rem;
}
.info-box {
    border-radius: 16px;
    padding: 1.25rem;
    background: #fef3c7;
    border: 1px solid #fcd34d;
}
.info-box i { color: #d97706; }
.btn-kelu {
    border-radius: 14px;
    padding: 0.875rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
}
.btn-kelu:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
}
.siswa-item {
    border: 2px solid #e5e7eb;
    border-radius: 14px;
    padding: 0.875rem 1rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.siswa-item:hover {
    border-color: #f59e0b;
    background: #fffbeb;
}
.kelas-section {
    margin-bottom: 2rem;
}
.kelas-section h5 {
    color: #d97706;
    font-weight: 600;
    padding: 0.5rem 1rem;
    background: #fef3c7;
    border-radius: 10px;
    display: inline-block;
}
</style>

<div class="kelulusan-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="kelulusan-card">
                    <div class="kelu-header">
                        <div class="kelu-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3>Proses Kelulusan</h3>
                        <p class="mb-0">Tandai siswa kelas 12 sebagai alumni</p>
                    </div>
                    <div class="kelu-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success bg-success text-white border-0 mb-4" style="border-radius: 12px;">
                                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger bg-danger text-white border-0 mb-4" style="border-radius: 12px;">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                            </div>
                        <?php endif; ?>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="stat-box bg-light">
                                    <h2 class="text-primary"><?= $siswa_xii_count ?></h2>
                                    <p class="text-muted">Siswa Kelas 12 Aktif</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Tahun Lulus</label>
                                        <select name="tahun_lulus" class="form-control" required>
                                            <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                                            <option value="<?= date('Y') + 1 ?>"><?= date('Y') + 1 ?></option>
                                        </select>
                                    </div>

                                    <div class="info-box mb-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Perhatian!</strong>
                                        <ul class="mb-0 mt-2 ps-3">
                                            <li>Status jadi <strong>ALUMNI</li>
                                            <li>Tidak di absensi</li>
                                            <li>Di riwayat alumni</li>
                                        </ul>
                                    </div>

                                    <button type="submit" class="btn btn-kelu w-100" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; color: white;">
                                        <i class="fas fa-graduation-cap me-2"></i>Proses Kelulusan
                                    </button>
                                </form>
                            </div>
                        </div>

                        <?php if ($siswa_xii_count > 0): ?>
                        <hr>
                        <h5 class="fw-bold mb-3"><i class="fas fa-users me-2"></i>Daftar Siswa Kelas 12</h5>
                        <div class="row g-3" style="max-height: 350px; overflow-y: auto;">
                            <?php 
                            $current_kelas = '';
                            $col_num = 0;
                            while ($row = $siswa_xii_list->fetch_assoc()): 
                                if ($current_kelas != $row['nama_kelas']):
                                    $current_kelas = $row['nama_kelas'];
                                    $col_num = 0;
                            ?>
                            <div class="col-12">
                                <div class="kelas-section">
                                    <h5><i class="fas fa-door-open me-2"></i><?= htmlspecialchars($current_kelas) ?></h5>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <div class="siswa-item">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($row['nama']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['nis']) ?></small>
                                    </div>
                                    <span class="badge bg-warning text-dark">Aktif</span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-slash text-muted fa-2x mb-2"></i>
                            <p class="text-muted mb-0">Belum ada siswa kelas 12</p>
                        </div>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-outline-dark">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
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

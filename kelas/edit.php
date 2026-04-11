<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Edit Kelas - Sistem Absensi Siswa';

$error = '';
$kelas = null;

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = conn()->prepare("SELECT * FROM kelas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("Kelas tidak ditemukan");
    }
    
    $kelas = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nama_kelas = $_POST['nama_kelas'];
    $wali_kelas = $_POST['wali_kelas'] ?? '';
    
    $cek = conn()->prepare("SELECT id FROM kelas WHERE nama_kelas = ? AND id != ?");
    $cek->bind_param("si", $nama_kelas, $id);
    $cek->execute();
    $cek->store_result();
    
    if ($cek->num_rows > 0) {
        $error = "Kelas '$nama_kelas' sudah ada!";
    } else {
        $stmt = conn()->prepare("UPDATE kelas SET nama_kelas=?, wali_kelas=? WHERE id=?");
        $stmt->bind_param("ssi", $nama_kelas, $wali_kelas, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Kelas berhasil diperbarui!";
            header("Location: index.php");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}

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
    max-width: 500px;
    width: 100%;
}
.form-card-header {
    background: linear-gradient(135deg, #ffb142 0%, #ff8c00 100%);
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
</style>

<div class="form-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="icon-circle">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h3>Edit Kelas</h3>
                        <p class="mb-0 opacity-75">Perbarui informasi kelas</p>
                    </div>
                    <div class="form-card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger bg-danger text-white border-0 rounded-3 mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $kelas['id'] ?>">
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-dark">Nama Kelas</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-door-closed text-muted"></i>
                                    </span>
                                    <input type="text" name="nama_kelas" class="form-control" 
                                           value="<?= htmlspecialchars($kelas['nama_kelas']) ?>" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-dark">Wali Kelas</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-user-tie text-muted"></i>
                                    </span>
                                    <input type="text" name="wali_kelas" class="form-control" 
                                           value="<?= htmlspecialchars($kelas['wali_kelas'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <a href="index.php" class="btn btn-back flex-fill">
                                    <i class="fas fa-arrow-left me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-warning text-dark flex-fill">
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

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

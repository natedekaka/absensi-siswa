<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: dashboard/");
    exit;
}

require_once 'core/init.php';

$title = 'Login - Sistem Absensi Siswa';

ob_start();
?>

<style>
body {
    background: linear-gradient(135deg, #e8f5e9 0%, #f0f9f1 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-school fa-3x mb-3"></i>
            <h3 class="mb-1">Sistem Absensi Siswa</h3>
            <p class="mb-0 opacity-75">Masuk untuk melanjutkan</p>
        </div>
        <div class="card-body p-4">
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="proses_login.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" name="username" placeholder="Masukkan username" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="password" placeholder="Masukkan password" required>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-wa-success btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'views/layout.php';

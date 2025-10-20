<?php
session_start();
if(isset($_SESSION['user'])) {
    header('Location: dashboard/');
    exit;
}

include 'includes/header.php';
?>

<!-- CSS Kustom -->
<style>
    /* Ubah body jadi flex column */
    body {
        background: linear-gradient(135deg, #e8f5e9 0%, #f0f9f1 100%);
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh; /* Penting! */
    }

    /* Header tetap di atas */
    header .navbar {
        flex-shrink: 0;
    }

    /* Konten utama mengisi sisa ruang */
    .login-page-wrapper {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 1.5rem 1rem;
    }

    .login-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        width: 100%;
        max-width: 400px;
        background: white;
    }

    .login-card .card-body {
        padding: 2rem 1.75rem;
    }

    @media (min-width: 576px) {
        .login-card .card-body {
            padding: 2.5rem;
        }
    }

    .info-panel {
        background: linear-gradient(135deg, #128C7E 0%, #25D366 100%);
        color: white;
        border-radius: 1rem;
        padding: 2.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 100%;
        box-shadow: 0 10px 30px rgba(37, 211, 102, 0.25);
    }

    .info-panel h2 {
        font-weight: 700;
        margin-bottom: 1.25rem;
        font-size: 1.75rem;
    }

    .info-panel p {
        opacity: 0.95;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    .info-panel ul {
        padding-left: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .info-panel ul li {
        margin-bottom: 0.6rem;
        line-height: 1.5;
    }

    .info-icon {
        font-size: 2.2rem;
        margin-bottom: 1.25rem;
        opacity: 0.9;
    }

    @media (max-width: 767.98px) {
        .info-panel {
            display: none;
        }
    }

    .text-whatsapp {
        color: #25D366 !important;
    }

    .form-control:focus {
        border-color: #25D366;
        box-shadow: 0 0 0 0.2rem rgba(37, 211, 102, 0.25);
    }

    .btn-login {
        background-color: #25D366;
        border-color: #25D366;
        padding: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        transition: all 0.2s ease-in-out;
    }

    .btn-login:hover {
        background-color: #128C7E;
        border-color: #128C7E;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(37, 211, 102, 0.45);
    }

    .form-floating > label {
        padding-left: 1.25rem;
    }

    .form-floating > .form-control {
        padding-left: 1.25rem;
    }

    .container-login {
        max-width: 900px;
    }

    /* Footer tetap di bawah */
    footer {
        margin-top: auto;
        padding: 1.5rem 0;
        background-color: #f8f9fa;
        color: #6c757d;
        text-align: center;
        flex-shrink: 0;
    }
</style>

<!-- Konten Login -->
<div class="login-page-wrapper">
    <div class="container container-login">
        <div class="row justify-content-center align-items-center g-0">
            <div class="col-12 col-md-6 d-flex justify-content-center">
                <div class="card login-card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock-fill text-whatsapp" style="font-size: 2.8rem;"></i>
                            <h3 class="mt-2 fw-bold">Login Sistem Absensi</h3>
                            <p class="text-muted mb-0">Masuk untuk melanjutkan</p>
                        </div>

                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="proses_login.php" method="POST">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                                <label for="username">Username</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <label for="password">Password</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-login text-white">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 d-none d-md-flex justify-content-center">
                <div class="info-panel">
                    <i class="bi bi-check2-circle info-icon"></i>
                    <h2>Sistem Absensi Digital SMAN 6 Cimahi</h2>
                    <p>Aplikasi ini dirancang untuk memudahkan pencatatan kehadiran siswa di Piket Sekolah, dan aman.</p>
                    <ul>
                        <li>✅ Absensi real-time berbasis web</li>
                        <li>🔒 Keamanan data dengan autentikasi pengguna</li>
                        <li>📊 Laporan kehadiran harian & bulanan</li>
                        <li>📱 Responsif di semua perangkat</li>
                        <li>⚡ Integrasi mudah dengan sistem internal</li>
                    </ul>
                    <p class="mb-0">Dikembangkan untuk meningkatkan efisiensi administrasi kehadiran di lingkungan pendidikan.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<?php
session_start();
if(isset($_SESSION['user'])) {
    header('Location: dashboard/');
    exit;
}

include 'includes/header.php';
?>

<!-- CSS Kustom untuk Tema dan Tampilan Modern -->
<style>
    body {
        background-color: #f0f2f5;
    }
    .login-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 0;
    }
    .login-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        overflow: hidden; /* Memastikan rounded corner teraplikasi dengan baik */
    }
    .login-card .card-body {
        padding: 2.5rem;
    }
    /* Wana Tema WhatsApp */
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
        font-weight: 500;
        transition: all 0.2s ease-in-out;
    }
    .btn-login:hover {
        background-color: #128C7E;
        border-color: #128C7E;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
    }
</style>

<div class="login-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card">
                    <div class="card-body">
                        <!-- Header dengan Ikon -->
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock-fill text-whatsapp" style="font-size: 3rem;"></i>
                            <h3 class="mt-2 fw-bold">Login Sistem Absensi</h3>
                            <p class="text-muted">Masuk untuk melanjutkan</p>
                        </div>

                        <?php if(isset($_SESSION['error'])): ?>
                            <!-- Alert dengan tombol tutup -->
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="proses_login.php" method="POST">
                            <!-- Menggunakan Floating Labels -->
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                                <label for="username">Username</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <label for="password">Password</label>
                            </div>
                            
                            <!-- Tombol login dengan ikon dan efek hover -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-login text-white">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

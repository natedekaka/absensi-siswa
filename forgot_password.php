<?php
session_start();

require_once 'core/init.php';
require_once 'core/Database.php';

initKonfigurasiSekolah(conn());
$sekolah = getKonfigurasiSekolah(conn());

$title = 'Lupa Password - Sistem Absensi Siswa';
$primaryColor = $sekolah['warna_primer'] ?? '#4f46e5';
$secondaryColor = $sekolah['warna_sekunder'] ?? '#64748b';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: <?php echo $primaryColor; ?>;
            --secondary: <?php echo $secondaryColor; ?>;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-container i {
            font-size: 48px;
            color: var(--primary);
        }
        .form-control {
            border-radius: 12px;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .btn-reset {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            color: white;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo-container">
            <i class="fas fa-key"></i>
            <h3 class="mt-3">Lupa Password?</h3>
            <p class="text-muted">Masukkan username untuk reset password</p>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form action="proses_forgot_password.php" method="POST">
            <?php echo csrf_field(); ?>
            
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required>
            </div>

            <button type="submit" class="btn-reset">
                <i class="fas fa-paper-plane me-2"></i>Kirim Instruksi Reset
            </button>
        </form>

        <a href="<?php echo BASE_URL; ?>login.php" class="back-link">
            <i class="fas fa-arrow-left me-1"></i>Kembali ke Login
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

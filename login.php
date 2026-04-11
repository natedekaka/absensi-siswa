<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: dashboard/");
    exit;
}

require_once 'core/init.php';
require_once 'core/Database.php';

initKonfigurasiSekolah(conn());
$sekolah = getKonfigurasiSekolah(conn());

$title = 'Login - Sistem Absensi Siswa';

$primaryColor = $sekolah['warna_primer'] ?? '#4f46e5';
$secondaryColor = $sekolah['warna_sekunder'] ?? '#64748b';

ob_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: <?= $primaryColor ?>;
            --secondary: <?= $secondaryColor ?>;
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            overflow: hidden;
            position: relative;
        }

        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.4;
            animation: float 20s infinite ease-in-out;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            bottom: 10%;
            right: 5%;
            animation-delay: -5s;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.12);
            top: 40%;
            right: 15%;
            animation-delay: -10s;
        }

        .shape-4 {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.06);
            bottom: 20%;
            left: 10%;
            animation-delay: -15s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(20px, -30px) rotate(5deg);
            }
            50% {
                transform: translate(-10px, 20px) rotate(-5deg);
            }
            75% {
                transform: translate(30px, 10px) rotate(3deg);
            }
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 0;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            padding: 40px 40px 30px;
            text-align: center;
            background: linear-gradient(180deg, rgba(255,255,255,0.1) 0%, transparent 100%);
        }

        .logo-container {
            width: 90px;
            height: 90px;
            margin: 0 auto 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: pulse 3s infinite ease-in-out;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            }
        }

        .logo-container img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 16px;
        }

        .logo-container i {
            font-size: 32px;
            color: white;
        }

        .login-header h2 {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            font-weight: 400;
        }

        .login-body {
            padding: 20px 40px 40px;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-floating > .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid transparent;
            border-radius: 14px;
            padding: 16px 16px 16px 50px;
            height: auto;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-floating > .form-control:focus {
            background: white;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.15);
            outline: none;
        }

        .form-floating > label {
            padding: 16px 16px 16px 50px;
            color: #6b7280;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.1rem;
            z-index: 10;
        }

        .input-wrapper {
            position: relative;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: white;
            color: var(--primary);
            border: none;
            border-radius: 14px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background: #f8fafc;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            font-size: 1.1rem;
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
        }

        .alert {
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 20px;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.9);
            color: white;
        }

        .alert-danger i {
            font-size: 1.1rem;
        }

        .btn-close {
            filter: brightness(0) invert(1);
        }

        @media (max-width: 480px) {
            .login-card {
                margin: 10px;
            }
            
            .login-header {
                padding: 30px 25px 20px;
            }
            
            .login-body {
                padding: 20px 25px 30px;
            }
            
            .logo-container {
                width: 75px;
                height: 75px;
            }
            
            .login-header h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <?php if ($sekolah['logo'] && file_exists(__DIR__ . '/assets/uploads/' . $sekolah['logo'])): ?>
                        <img src="<?= asset('uploads/' . $sekolah['logo']) ?>" alt="Logo">
                    <?php else: ?>
                        <i class="fas fa-graduation-cap"></i>
                    <?php endif; ?>
                </div>
                <h2><?= htmlspecialchars($sekolah['nama_sekolah']) ?></h2>
                <p>Sistem Absensi Siswa</p>
            </div>
            
            <div class="login-body">
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form action="proses_login.php" method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="input-wrapper mb-4">
                        <i class="fas fa-user input-icon"></i>
                        <div class="form-floating">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                            <label for="username">Username</label>
                        </div>
                    </div>
                    
                    <div class="input-wrapper mb-4">
                        <i class="fas fa-lock input-icon"></i>
                        <div class="form-floating">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password">Password</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Masuk
                    </button>
                </form>
                
                <p class="footer-text">
                    &copy; <?= date('Y') ?> Absensi Siswa
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset('css/app.css'); ?>">
    <style>
        :root { --fp-primary: <?php echo $primaryColor; ?>; --fp-secondary: <?php echo $secondaryColor; ?>; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(135deg, var(--fp-primary) 0%, var(--fp-secondary) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .fp-card { background: rgba(255,255,255,0.95); border-radius: 20px; padding: 40px; max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.6s ease-out; }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>
    <div class="fp-card">
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-[20px] flex items-center justify-center" style="background:linear-gradient(135deg,var(--fp-primary) 0%,var(--fp-secondary) 100%)">
                <i class="fas fa-key text-white text-2xl"></i>
            </div>
            <h3 class="text-gray-800 font-bold text-xl mb-1">Lupa Password?</h3>
            <p class="text-gray-500 text-sm">Masukkan username untuk reset password</p>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert-modern alert-danger-modern mb-4 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span class="flex-1"><?php echo $_SESSION['error']; ?></span>
                <button onclick="this.parentElement.remove()" class="text-red-700/50 hover:text-red-700">&times;</button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert-modern alert-success-modern mb-4 flex items-center gap-3">
                <i class="fas fa-check-circle text-lg"></i>
                <span class="flex-1"><?php echo $_SESSION['success']; ?></span>
                <button onclick="this.parentElement.remove()" class="text-green-700/50 hover:text-green-700">&times;</button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form action="proses_forgot_password.php" method="POST">
            <?php echo csrf_field(); ?>
            
            <div class="mb-5">
                <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Username</label>
                <input type="text" class="form-input-modern" name="username" placeholder="Masukkan username" required>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl font-semibold text-white border-none cursor-pointer transition-all hover:-translate-y-0.5" style="background:linear-gradient(135deg,var(--fp-primary) 0%,var(--fp-secondary) 100%)">
                <i class="fas fa-paper-plane mr-2"></i>Kirim Instruksi Reset
            </button>
        </form>

        <a href="<?php echo BASE_URL; ?>login.php" class="block text-center mt-6 no-underline text-sm font-medium" style="color:var(--fp-primary)">
            <i class="fas fa-arrow-left mr-1"></i>Kembali ke Login
        </a>
    </div>
</body>
</html>

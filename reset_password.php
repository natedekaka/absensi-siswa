<?php
session_start();

require_once 'core/init.php';
require_once 'core/Database.php';

initKonfigurasiSekolah(conn());
$sekolah = getKonfigurasiSekolah(conn());

$title = 'Reset Password - Sistem Absensi Siswa';
$primaryColor = $sekolah['warna_primer'] ?? '#4f46e5';
$secondaryColor = $sekolah['warna_sekunder'] ?? '#64748b';

$error = '';
$valid_token = false;
$user_id = null;

if (isset($_GET['token']) && isset($_GET['id'])) {
    $token = $_GET['token'];
    $user_id = intval($_GET['id']);
    
    $stmt = conn()->prepare("SELECT * FROM users WHERE id = ? AND remember_token IS NOT NULL AND remember_expires > NOW()");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($token, $user['remember_token'])) {
            $valid_token = true;
        }
    }
}

if (!$valid_token && isset($_GET['token'])) {
    $error = "Token tidak valid atau sudah expired!";
}
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
        :root { --rp-primary: <?php echo $primaryColor; ?>; --rp-secondary: <?php echo $secondaryColor; ?>; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(135deg, var(--rp-primary) 0%, var(--rp-secondary) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .rp-card { background: rgba(255,255,255,0.95); border-radius: 20px; padding: 40px; max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.6s ease-out; }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>
    <div class="rp-card">
        <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto mb-4 rounded-[20px] flex items-center justify-center" style="background:linear-gradient(135deg,var(--rp-primary) 0%,var(--rp-secondary) 100%)">
                <i class="fas fa-lock text-white text-2xl"></i>
            </div>
            <h3 class="text-gray-800 font-bold text-xl">Reset Password</h3>
        </div>

        <?php if ($error): ?>
            <div class="alert-modern alert-danger-modern mb-4 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span class="flex-1"><?php echo $error; ?></span>
            </div>
            <a href="<?php echo BASE_URL; ?>forgot_password.php" class="block text-center mt-4 text-sm font-medium no-underline" style="color:var(--rp-primary)">Minta token baru</a>
        <?php elseif ($valid_token): ?>
            <form action="proses_reset_password.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Password Baru</label>
                    <input type="password" class="form-input-modern" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                </div>

                <div class="mb-5">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5 block">Konfirmasi Password</label>
                    <input type="password" class="form-input-modern" name="confirm_password" placeholder="Ulangi password" required>
                </div>

                <button type="submit" class="w-full py-3.5 rounded-xl font-semibold text-white border-none cursor-pointer transition-all hover:-translate-y-0.5" style="background:linear-gradient(135deg,var(--rp-primary) 0%,var(--rp-secondary) 100%)">
                    <i class="fas fa-save mr-2"></i>Simpan Password Baru
                </button>
            </form>
        <?php else: ?>
            <div class="alert-modern alert-warning-modern mb-4 flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-lg"></i>
                <span class="flex-1">Token tidak ditemukan. Silakan request reset password ulang.</span>
            </div>
            <a href="<?php echo BASE_URL; ?>forgot_password.php" class="block w-full py-3.5 rounded-xl font-semibold text-white text-center no-underline border-none" style="background:linear-gradient(135deg,var(--rp-primary) 0%,var(--rp-secondary) 100%)">
                <i class="fas fa-redo mr-2"></i>Request Reset Password
            </a>
        <?php endif; ?>
    </div>
</body>
</html>

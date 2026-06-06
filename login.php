<?php
session_start();

if (isset($_SESSION['user'])) {
    header("Location: dashboard/");
    exit;
}

require_once 'core/init.php';
require_once 'core/Database.php';

$secret = defined('APP_SECRET') ? APP_SECRET : 'default_secret_change_me';

if (isset($_COOKIE['remember_user'])) {
    list($user_id, $token) = explode(':', $_COOKIE['remember_user'], 2);
    
    $stmt = conn()->prepare("SELECT * FROM users WHERE id = ? AND remember_token IS NOT NULL AND remember_expires > NOW()");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($token, $user['remember_token'])) {
            $_SESSION['user'] = $user;
            $_SESSION['login_time'] = time();
            
            $new_token = bin2hex(random_bytes(32));
            $hashed_token = password_hash($new_token, PASSWORD_DEFAULT);
            $expires = date('Y-m-d H:i:s', time() + 60*60*24*30);
            
            $update_stmt = conn()->prepare("UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $hashed_token, $expires, $user['id']);
            $update_stmt->execute();
            
            setcookie('remember_user', $user['id'] . ':' . $new_token, time() + 60*60*24*30, '/', '', false, true);
            
            header('Location: ' . BASE_URL . 'dashboard/');
            exit;
        }
    }
    
    setcookie('remember_user', '', time() - 3600, '/');
}

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        :root { --login-primary: <?= $primaryColor ?>; --login-secondary: <?= $secondaryColor ?>; }
        .login-bg { background: linear-gradient(135deg, var(--login-primary) 0%, var(--login-secondary) 100%); }
        .login-card { background: rgba(255,255,255,0.12); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.18); }
        .login-input { background: rgba(255,255,255,0.9); border: 2px solid transparent; }
        .login-input:focus { background: white; border-color: rgba(255,255,255,0.5); box-shadow: 0 0 0 4px rgba(255,255,255,0.15); }
        .login-btn { background: white; color: var(--login-primary); }
        .login-btn:hover { background: #f8fafc; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        @keyframes float { 0%,100% { transform: translate(0,0) rotate(0deg); } 25% { transform: translate(20px,-30px) rotate(5deg); } 50% { transform: translate(-10px,20px) rotate(-5deg); } 75% { transform: translate(30px,10px) rotate(3deg); } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .shape { position: absolute; border-radius: 50%; opacity: 0.35; animation: float 20s infinite ease-in-out; }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-5 overflow-hidden font-[Plus_Jakarta_Sans]">

    <!-- Background shapes -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="shape" style="width:300px;height:300px;background:rgba(255,255,255,0.1);top:-100px;left:-100px;animation-delay:0s;"></div>
        <div class="shape" style="width:200px;height:200px;background:rgba(255,255,255,0.08);bottom:10%;right:5%;animation-delay:-5s;"></div>
        <div class="shape" style="width:150px;height:150px;background:rgba(255,255,255,0.12);top:40%;right:15%;animation-delay:-10s;"></div>
        <div class="shape" style="width:100px;height:100px;background:rgba(255,255,255,0.06);bottom:20%;left:10%;animation-delay:-15s;"></div>
    </div>

    <!-- Login Card -->
    <div class="login-card rounded-[24px] max-w-[420px] w-full overflow-hidden shadow-[0_25px_50px_-12px_rgba(0,0,0,0.25)] animate-[slideUp_0.8s_ease-out]">
        <div class="px-10 pt-10 pb-8 text-center" style="background:linear-gradient(180deg,rgba(255,255,255,0.1) 0%,transparent 100%)">
            <div class="w-[90px] h-[90px] mx-auto mb-5 rounded-[24px] flex items-center justify-center shadow-[0_8px_32px_rgba(0,0,0,0.1)] animate-[pulse_3s_ease-in-out_infinite]" style="background:rgba(255,255,255,0.2);">
                <?php if ($sekolah['logo'] && file_exists(__DIR__ . '/assets/uploads/' . $sekolah['logo'])): ?>
                    <img src="<?= asset('uploads/' . $sekolah['logo']) ?>" alt="Logo" class="w-[60px] h-[60px] object-contain rounded-[16px]">
                <?php else: ?>
                    <i class="fas fa-graduation-cap text-white text-3xl"></i>
                <?php endif; ?>
            </div>
            <h2 class="text-white font-bold text-xl mb-1" style="text-shadow:0 2px 4px rgba(0,0,0,0.1);"><?= htmlspecialchars($sekolah['nama_sekolah']) ?></h2>
            <p class="text-white/70 text-sm">Sistem Absensi Siswa</p>
        </div>

        <div class="px-10 pb-10">
            <?php if(isset($_SESSION['error'])): ?>
                <div class="flex items-center gap-3 px-4 py-3.5 mb-5 rounded-[14px] text-white" style="background:rgba(239,68,68,0.9);">
                    <i class="fas fa-exclamation-circle"></i>
                    <span class="text-sm flex-1"><?= $_SESSION['error'] ?></span>
                    <button onclick="this.parentElement.remove()" class="text-white/70 hover:text-white">&times;</button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="proses_login.php" method="POST">
                <?= csrf_field() ?>
                
                <div class="relative mb-5">
                    <i class="fas fa-user absolute left-[18px] top-1/2 -translate-y-1/2 z-10" style="color:var(--login-primary);"></i>
                    <input type="text" name="username" id="username" required placeholder="Username"
                        class="login-input w-full pl-[50px] pr-4 py-4 rounded-[14px] text-base outline-none transition-all duration-300 font-[Plus_Jakarta_Sans]">
                </div>
                
                <div class="relative mb-5">
                    <i class="fas fa-lock absolute left-[18px] top-1/2 -translate-y-1/2 z-10" style="color:var(--login-primary);"></i>
                    <input type="password" name="password" id="password" required placeholder="Password"
                        class="login-input w-full pl-[50px] pr-4 py-4 rounded-[14px] text-base outline-none transition-all duration-300 font-[Plus_Jakarta_Sans]">
                </div>
                
                <label class="flex items-center gap-2 mb-6 text-white/80 text-sm cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded accent-[var(--login-primary)]">
                    <i class="fas fa-clock text-xs"></i> Ingat saya (30 hari)
                </label>

                <button type="submit" class="login-btn w-full py-4 rounded-[14px] text-base font-semibold flex items-center justify-center gap-3 transition-all duration-300 shadow-[0_4px_15px_rgba(0,0,0,0.1)] cursor-pointer border-none font-[Plus_Jakarta_Sans]">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
            </form>
            
            <p class="text-center mt-8 text-white/60 text-xs">
                &copy; <?= date('Y') ?> Absensi Siswa
                <br>
                <a href="<?= BASE_URL ?>forgot_password.php" class="text-white/70 hover:text-white underline text-sm inline-block mt-1">
                    <i class="fas fa-question-circle"></i> Lupa Password?
                </a>
            </p>
        </div>
    </div>

</body>
</html>

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
    </style>
</head>
<body>
    <div class="card">
        <div class="text-center mb-4">
            <i class="fas fa-lock text-primary" style="font-size: 48px;"></i>
            <h3 class="mt-3">Reset Password</h3>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
            </div>
            <a href="<?php echo BASE_URL; ?>forgot_password.php" class="btn btn-link">Minta token baru</a>
        <?php elseif ($valid_token): ?>
            <form action="proses_reset_password.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password Baru</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required>
                </div>

                <button type="submit" class="btn-reset">
                    <i class="fas fa-save me-2"></i>Simpan Password Baru
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Token tidak ditemukan. Silakan request reset password ulang.
            </div>
            <a href="<?php echo BASE_URL; ?>forgot_password.php" class="btn btn-primary w-100">Request Reset Password</a>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

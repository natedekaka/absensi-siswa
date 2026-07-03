<?php
session_start();

require_once 'core/init.php';
require_once 'core/Database.php';

if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
    $_SESSION['error'] = "Token keamanan tidak valid!";
    header('Location: forgot_password.php');
    exit;
}

if (isset($_POST['user_id']) && isset($_POST['token']) && isset($_POST['password'])) {
    $user_id = intval($_POST['user_id']);
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Password konfirmasi tidak cocok!";
        header('Location: reset_password.php?token=' . urlencode($token) . '&id=' . $user_id);
        exit;
    }
    
    if (strlen($password) < 6) {
        $_SESSION['error'] = "Password minimal 6 karakter!";
        header('Location: reset_password.php?token=' . urlencode($token) . '&id=' . $user_id);
        exit;
    }
    
    $stmt = conn()->prepare("SELECT * FROM password_resets WHERE user_id = ? AND expires_at > NOW() AND used = 0 ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $reset = $result->fetch_assoc();
        
        if (password_verify($token, $reset['token'])) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $update_stmt = conn()->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                // Mark token as used so it can't be reused
                $used_stmt = conn()->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                $used_stmt->bind_param("i", $reset['id']);
                $used_stmt->execute();
                
                // Clear remember me tokens to force re-login
                $clear_stmt = conn()->prepare("UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = ?");
                $clear_stmt->bind_param("i", $user_id);
                $clear_stmt->execute();
                
                $_SESSION['success'] = "Password berhasil direset! Silakan login dengan password baru.";
                header('Location: login.php');
                exit;
            }
        }
    }
    
    $_SESSION['error'] = "Token tidak valid atau sudah expired!";
    header('Location: forgot_password.php');
    exit;
}

header('Location: forgot_password.php');

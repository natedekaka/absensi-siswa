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
    
    $stmt = conn()->prepare("SELECT * FROM users WHERE id = ? AND remember_token IS NOT NULL AND remember_expires > NOW()");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($token, $user['remember_token'])) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $update_stmt = conn()->prepare("UPDATE users SET password = ?, remember_token = NULL, remember_expires = NULL WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
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

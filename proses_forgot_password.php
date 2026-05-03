<?php
session_start();

require_once 'core/init.php';
require_once 'core/Database.php';

if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
    $_SESSION['error'] = "Token keamanan tidak valid!";
    header('Location: forgot_password.php');
    exit;
}

if (isset($_POST['username'])) {
    $username = db()->escape($_POST['username']);
    
    $stmt = conn()->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        $token = bin2hex(random_bytes(32));
        $hashed_token = password_hash($token, PASSWORD_DEFAULT);
        $expires = date('Y-m-d H:i:s', time() + 3600);
        
        $update_stmt = conn()->prepare("UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $hashed_token, $expires, $user['id']);
        $update_stmt->execute();
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $reset_link = $protocol . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'reset_password.php?token=' . $token . '&id=' . $user['id'];
        
        $_SESSION['success'] = "Link reset password: <br><code>" . $reset_link . "</code><br><small>Token berlaku 1 jam.</small>";
        header('Location: forgot_password.php');
        exit;
    }
    
    $_SESSION['error'] = "Username tidak ditemukan!";
    header('Location: forgot_password.php');
    exit;
}

header('Location: forgot_password.php');

<?php
session_start();
require_once 'core/init.php';
require_once 'core/Database.php';

if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
    $_SESSION['error'] = "Token keamanan tidak valid!";
    header('Location: login.php');
    exit;
}

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = db()->escape($_POST['username']);
    $password = $_POST['password'];

    $stmt = conn()->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            $_SESSION['login_time'] = time();
            
            if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
                $token = bin2hex(random_bytes(32));
                $hashed_token = password_hash($token, PASSWORD_DEFAULT);
                $expires = date('Y-m-d H:i:s', time() + 60*60*24*30);
                
                $update_stmt = conn()->prepare("UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $hashed_token, $expires, $user['id']);
                $update_stmt->execute();
                
                setcookie('remember_user', $user['id'] . ':' . $token, time() + 60*60*24*30, '/', '', false, true);
            }
            
            header('Location: ' . BASE_URL . 'dashboard/');
            exit;
        }
    }
    
    $_SESSION['error'] = "Username atau password salah!";
    header('Location: login.php');
    exit;
}

header('Location: login.php');

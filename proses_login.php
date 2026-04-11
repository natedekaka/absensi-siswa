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
            header('Location: ' . BASE_URL . 'dashboard/');
            exit;
        }
    }
    
    $_SESSION['error'] = "Username atau password salah!";
    header('Location: login.php');
    exit;
}

header('Location: login.php');

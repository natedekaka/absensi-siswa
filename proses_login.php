<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// Debugging: Tampilkan input
// error_log("Login attempt: " . $_POST['username']);

if(isset($_POST['username']) && isset($_POST['password'])) {
    $username = $koneksi->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $koneksi->prepare($sql);
    
    if(!$stmt) {
        error_log("Prepare failed: " . $koneksi->error);
        $_SESSION['error'] = "System error. Please try again.";
        header('Location: login.php');
        exit;
    }
    
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Debugging: Tampilkan hash dari database
        // error_log("Stored hash: " . $user['password']);
        
        if(password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header('Location: dashboard/index.php');
            exit;
        } else {
            error_log("Password verification failed for user: $username");
        }
    } else {
        error_log("User not found: $username");
    }
    
    $_SESSION['error'] = "Username atau password salah!";
    header('Location: login.php');
    exit;
}
header('Location: login.php');
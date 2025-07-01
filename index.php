<?php
session_start();

// Jika user sudah login, arahkan ke dashboard
if (isset($_SESSION['user'])) {
    header("Location: dashboard/");
    exit;
} 
// Jika belum login, arahkan ke halaman login
else {
    header("Location: login.php");
    exit;
}
?>
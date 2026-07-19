<?php
session_start();

require_once 'core/init.php';
require_once 'core/Database.php';

if (isset($_SESSION['user'])) {
    $target = ($_SESSION['user']['role'] ?? '') === 'orang_tua' ? 'siswa/riwayat.php' : 'dashboard/';
    header("Location: " . BASE_URL . $target);
    exit;
} else {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

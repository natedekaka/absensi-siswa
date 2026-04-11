<?php
session_start();

require_once 'core/init.php';
require_once 'core/Database.php';

if (isset($_SESSION['user'])) {
    header("Location: " . BASE_URL . "dashboard/");
    exit;
} else {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

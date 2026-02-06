<?php
// session_start();

$host = 'db'; 
$user = 'user'; 
$pass = 'pass123';
$db   = 'absensi_db3'; // Sesuaikan dengan nama baru yang Anda buat

$koneksi = new mysqli($host, $user, $pass, $db);

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Set timezone Indonesia agar waktu absen tidak ngaco
$koneksi->query("SET time_zone = '+07:00'");

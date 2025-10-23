<?php
//session_start();

$host = 'localhost';
$user = 'root';
$pass = '!@3Chromebook*';
$db   = 'absensi_db3';

$koneksi = new mysqli($host, $user, $pass, $db);

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
// JANGAN ADA SPASI ATAU NEWLINE SETELAH INI

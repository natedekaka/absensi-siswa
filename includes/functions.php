<?php

function getAbsensiBySiswa($siswa_id) {
    global $koneksi;
    $stmt = $koneksi->prepare("SELECT * FROM absensi WHERE siswa_id = ?");
    $stmt->bind_param("i", $siswa_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Fungsi untuk mendapatkan absensi siswa dalam rentang tanggal
function getAbsensiBySiswaRentang($siswa_id, $dari, $sampai) {
    global $koneksi;
    $stmt = $koneksi->prepare("SELECT * FROM absensi 
                              WHERE siswa_id = ? 
                              AND tanggal BETWEEN ? AND ?");
    $stmt->bind_param("iss", $siswa_id, $dari, $sampai);
    $stmt->execute();
    return $stmt->get_result();
}

// ========== FUNGSI SISTEM LOGIN BARU ========== //

/**
 * Memeriksa apakah user sudah login
 * Jika belum, redirect ke halaman login
 */
function check_login() {
    if(!isset($_SESSION['user'])) {
        $_SESSION['error'] = "Anda harus login untuk mengakses halaman ini";
        header('Location: ./login.php');
        exit;
    }
}

/**
 * Memeriksa role user
 * @param array $allowed_roles - Role yang diizinkan mengakses halaman
 */
function check_role($allowed_roles) {
    // Pastikan user sudah login
    if(!isset($_SESSION['user'])) {
        check_login();
    }
    
    // Periksa apakah role user termasuk yang diizinkan
    if(!in_array($_SESSION['user']['role'], $allowed_roles)) {
        $_SESSION['error'] = "Akses ditolak: Anda tidak memiliki izin";
        header('Location: ../dashboard/index.php');
        exit;
    }
}

/**
 * Mengenkripsi password
 * @param string $password - Password plaintext
 * @return string - Password terenkripsi
 */
function encrypt_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Memverifikasi password
 * @param string $password - Password plaintext
 * @param string $hash - Password terenkripsi dari database
 * @return bool - True jika password cocok
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Mendapatkan data user berdasarkan username
 * @param string $username
 * @return array|null - Data user atau null jika tidak ditemukan
 */
function get_user_by_username($username) {
    global $koneksi;
    
    $stmt = $koneksi->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}
<?php
require_once __DIR__ . '/config.php';

// BASE_URL is now defined in config.php

function asset($path) {
    $url = BASE_URL . 'assets/' . ltrim($path, '/');
    $file = __DIR__ . '/../assets/' . ltrim($path, '/');
    if (file_exists($file)) {
        $url .= '?v=' . filemtime($file);
    }
    return $url;
}

function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ─── RBAC Helper Functions ─────────────────────────────────────

function current_user_role() {
    return $_SESSION['user']['role'] ?? '';
}

function has_role(...$roles): bool {
    if (!isset($_SESSION['user'])) return false;
    return in_array($_SESSION['user']['role'], $roles, true);
}

function require_role(...$roles): void {
    if (!isset($_SESSION['user'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        $_SESSION['error'] = 'Akses ditolak: Anda tidak memiliki izin untuk halaman ini.';
        header('Location: ' . BASE_URL . 'dashboard/');
        exit;
    }
}

function get_accessible_kelas_ids(): array {
    $role = current_user_role();
    $user_id = (int)($_SESSION['user']['id'] ?? 0);

    if ($role === 'admin') return []; // empty = all

    if ($role === 'guru') {
        $q = conn()->query("SELECT kelas_id FROM guru_kelas WHERE user_id = $user_id");
        if ($q) return array_column($q->fetch_all(MYSQLI_ASSOC), 'kelas_id');
        return [];
    }

    if ($role === 'wali_kelas') {
        $q = conn()->query("SELECT id FROM kelas WHERE wali_kelas_id = $user_id");
        if ($q) return array_column($q->fetch_all(MYSQLI_ASSOC), 'id');
        return [];
    }

    return []; // orang_tua doesn't get kelas access
}

function get_accessible_siswa_ids(): array {
    $role = current_user_role();
    $user_id = (int)($_SESSION['user']['id'] ?? 0);

    if (in_array($role, ['admin', 'guru', 'wali_kelas'])) return []; // empty = all

    if ($role === 'orang_tua') {
        $q = conn()->query("SELECT siswa_id FROM siswa_orang_tua WHERE orang_tua_id = $user_id");
        if ($q) return array_column($q->fetch_all(MYSQLI_ASSOC), 'siswa_id');
        return [];
    }

    return [];
}

function apply_kelas_filter(string $alias = 's'): string {
    $ids = get_accessible_kelas_ids();
    if (empty($ids)) return '';
    $escaped = array_map('intval', $ids);
    return " AND {$alias}.kelas_id IN (" . implode(',', $escaped) . ")";
}

function apply_siswa_filter(string $alias = 'a'): string {
    $ids = get_accessible_siswa_ids();
    if (empty($ids)) return '';
    $escaped = array_map('intval', $ids);
    return " AND {$alias}.siswa_id IN (" . implode(',', $escaped) . ")";
}

function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

$konfigurasi_cache = null;

function initKonfigurasiSekolah($conn) {
    global $konfigurasi_cache;
    
    $table_check = $conn->query("SHOW TABLES LIKE 'konfigurasi_sekolah'");

    if ($table_check->num_rows === 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS konfigurasi_sekolah (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nama_sekolah VARCHAR(255) NOT NULL DEFAULT 'SMA Negeri',
            logo VARCHAR(255) DEFAULT NULL,
            warna_primer VARCHAR(20) DEFAULT '#4f46e5',
            warna_sekunder VARCHAR(20) DEFAULT '#64748b',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $conn->query("INSERT INTO konfigurasi_sekolah (nama_sekolah) VALUES ('SMA Negeri')");
    }
}

function getKonfigurasiSekolah($conn) {
    global $konfigurasi_cache;
    
    if ($konfigurasi_cache !== null) {
        return $konfigurasi_cache;
    }
    
    $result = $conn->query("SELECT * FROM konfigurasi_sekolah LIMIT 1");
    $konfigurasi_cache = $result->fetch_assoc();
    return $konfigurasi_cache;
}

function updateKonfigurasiSekolah($conn, $nama_sekolah, $logo, $warna_primer, $warna_sekunder) {
    global $konfigurasi_cache;
    
    $stmt = $conn->prepare("UPDATE konfigurasi_sekolah SET nama_sekolah = ?, logo = ?, warna_primer = ?, warna_sekunder = ? WHERE id = 1");
    $stmt->bind_param("ssss", $nama_sekolah, $logo, $warna_primer, $warna_sekunder);
    $result = $stmt->execute();
    $stmt->close();
    
    $konfigurasi_cache = null;
    
    return $result;
}

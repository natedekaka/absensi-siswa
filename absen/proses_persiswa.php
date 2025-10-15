<?php
require_once '../config.php';

// Pastikan request metode adalah POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. Ambil NIS dari input form
    $nis = $_POST['nis'];

    // --- VALIDASI: Cek apakah NIS ada di tabel siswa ---
    // Ini penting untuk mencegah input NIS yang tidak valid
    $stmt_siswa = $koneksi->prepare("SELECT id FROM siswa WHERE nis = ?");
    $stmt_siswa->bind_param("s", $nis);
    $stmt_siswa->execute();
    $result_siswa = $stmt_siswa->get_result();

    if ($result_siswa->num_rows === 0) {
        // Jika NIS tidak ditemukan, kembalikan ke halaman dengan pesan error
        header("Location: absensi_persiswa.php?error=nis_not_found");
        exit;
    }

    // Jika NIS ditemukan, ambil ID siswa
    $siswa = $result_siswa->fetch_assoc();
    $siswa_id = $siswa['id'];
    // --- AKHIR VALIDASI ---

    // 2. Set tanggal dan status secara otomatis
    $tanggal = date('Y-m-d'); // Gunakan tanggal hari ini
    $status = 'Hadir';       // Status otomatis Hadir

    // 3. Cek apakah siswa sudah absen hari ini
    $check = $koneksi->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ?");
    $check->bind_param("is", $siswa_id, $tanggal);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Jika sudah ada, lakukan update (meskipun statusnya sama 'Hadir', ini untuk menjaga konsistensi)
        $stmt = $koneksi->prepare("UPDATE absensi SET status = ? WHERE siswa_id = ? AND tanggal = ?");
        $stmt->bind_param("sis", $status, $siswa_id, $tanggal);
        $pesan = "update"; // Opsional, untuk logging atau notifikasi
    } else {
        // Jika belum ada, lakukan insert baru
        $stmt = $koneksi->prepare("INSERT INTO absensi (siswa_id, tanggal, status) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $siswa_id, $tanggal, $status);
        $pesan = "insert"; // Opsional, untuk logging atau notifikasi
    }

    // 4. Eksekusi query dan kembalikan ke halaman utama
    if ($stmt->execute()) {
        // Jika berhasil, redirect dengan pesan sukses
        header("Location: absensi_persiswa.php?success=1");
    } else {
        // Jika gagal, redirect dengan pesan error
        header("Location: absensi_persiswa.php?error=database");
    }
    
    $stmt->close();
    $check->close();
    $stmt_siswa->close();
    $koneksi->close();
    exit;
}
?>
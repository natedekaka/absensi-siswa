<?php
// Kita perlu koneksi ke database untuk mengambil nama siswa berdasarkan ID
require_once '../config.php';

// Inisialisasi variabel untuk pesan
 $success_message = '';
 $error_message = '';

// --- LOGIKA UNTUK PESAN SUKSES ---
if (isset($_GET['success']) && isset($_GET['siswa_id'])) {
    $siswa_id = $_GET['siswa_id'];

    // Ambil nama siswa berdasarkan ID yang dikirim dari URL
    $stmt_nama = $koneksi->prepare("SELECT nama FROM siswa WHERE id = ?");
    $stmt_nama->bind_param("i", $siswa_id);
    $stmt_nama->execute();
    $result_nama = $stmt_nama->get_result();
    
    if ($result_nama->num_rows > 0) {
        $siswa = $result_nama->fetch_assoc();
        $nama_siswa = htmlspecialchars($siswa['nama']);
        
        // Format tanggal dan waktu (lebih robust dari strftime)
        $array_bulan = array(1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
        $tanggal = date('d') . ' ' . $array_bulan[date('n')] . ' ' . date('Y');
        $waktu = date('H:i:s');

        // Buat pesan sukses yang informatif
        $success_message = "
            <div class='alert alert-success alert-dismissible fade show' role='alert'>
                <h4 class='alert-heading'><i class='bi bi-check-circle-fill'></i> Absensi Berhasil!</h4>
                <p>Siswa <strong>{$nama_siswa}</strong> telah berhasil dicatat hadir pada hari <strong>{$tanggal}</strong> pukul <strong>{$waktu}</strong>.</p>
                <hr>
                <p class='mb-0'>Terima kasih, silakan lanjutkan untuk siswa berikutnya.</p>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        ";
    }
    $stmt_nama->close();
}

// --- LOGIKA UNTUK PESAN ERROR ---
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'nis_not_found' && isset($_GET['nis'])) {
        $nis_gagal = htmlspecialchars($_GET['nis']);
        $error_message = "
            <div class='alert alert-danger alert-dismissible fade show' role='alert'>
                <strong><i class='bi bi-exclamation-triangle-fill'></i> Error!</strong> NIS <strong>{$nis_gagal}</strong> tidak ditemukan dalam database.
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        ";
    } else {
        $error_message = "
            <div class='alert alert-danger alert-dismissible fade show' role='alert'>
                <strong><i class='bi bi-exclamation-triangle-fill'></i> Terjadi Kesalahan!</strong> Tidak dapat memproses absensi. Silakan coba lagi.
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        ";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Siswa Harian</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 500px;
            margin-top: 100px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            border-radius: 15px 15px 0 0 !important;
            font-weight: bold;
            text-align: center;
            padding: 1.5rem;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .btn-absen {
            background-color: #28a745;
            border-color: #28a745;
            font-weight: bold;
            padding: 12px;
        }
        .btn-absen:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="login-container mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-qr-code-scan"></i> Sistem Absensi Siswa
            </div>
            <div class="card-body p-4">
                
                <!-- TAMPILKAN PESAN SUKSES ATAU ERROR DI SINI -->
                <?php echo $success_message; ?>
                <?php echo $error_message; ?>

                <form action="proses_persiswa.php" method="POST">
                    <div class="mb-3">
                        <label for="nis_input" class="form-label">Masukkan NIS Siswa</label>
                        <input type="text" name="nis" id="nis_input" class="form-control form-control-lg text-center" placeholder="Scan atau ketik NIS di sini..." required autofocus>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100 btn-absen">
                        <i class="bi bi-check2-square"></i> Absen Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script untuk fokus otomatis ke input NIS (penting untuk barcode scanner) -->
<script>
    window.onload = function() {
        document.getElementById('nis_input').focus();
    };
</script>

</body>
</html>
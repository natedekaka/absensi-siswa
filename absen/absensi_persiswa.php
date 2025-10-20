<?php
// Kita perlu koneksi ke database untuk mengambil nama siswa berdasarkan ID
require_once '../config.php';

// --- SETEL ZONA WAKTU DEFAULT KE INDONESIA (WIB) ---
// Ini memastikan semua fungsi date() menggunakan waktu yang benar untuk lokasi Anda.
date_default_timezone_set('Asia/Jakarta');

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
        
        // Format tanggal dan waktu (sekarang akan menggunakan zona waktu Asia/Jakarta)
        $array_bulan = array(1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
        $tanggal = date('d') . ' ' . $array_bulan[date('n')] . ' ' . date('Y');
        $waktu = date('H:i:s');

        // Buat pesan sukses yang informatif
        $success_message = "
            <div class='alert alert-success alert-dismissible fade show' role='alert'>
                <h4 class='alert-heading'><i class='bi bi-check-circle-fill'></i> Absensi Berhasil!</h4>
                <p>Siswa <strong>{$nama_siswa}</strong> telah berhasil dicatat hadir pada hari <strong>{$tanggal}</strong> pukul <strong>{$waktu}</strong> WIB.</p>
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
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --light-bg: #f0f2f5;
            --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            max-width: 100%;
            width: 100%;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 0 15px;
        }
        
        .back-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .back-btn i {
            font-size: 1.2rem;
        }
        
        .login-container {
            max-width: 500px;
            margin: 50px auto 0;
            width: 100%;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 1.5rem;
            position: relative;
        }
        
        .card-header i {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px;
            font-size: 1.1rem;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .btn-absen {
            background: linear-gradient(135deg, var(--success-color) 0%, #218838 100%);
            border: none;
            border-radius: 10px;
            font-weight: bold;
            padding: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        
        .btn-absen:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .scan-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: #28a745;
            border-radius: 50%;
            margin-left: 10px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
        
        .input-status {
            margin-top: 5px;
            font-size: 0.85rem;
            height: 20px;
        }
        
        .typing-indicator {
            color: #6c757d;
            font-style: italic;
        }
        
        .ready-indicator {
            color: #28a745;
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .login-container {
                margin-top: 20px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .back-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (min-width: 768px) {
            .main-container {
                padding: 30px;
            }
        }
        
        @media (min-width: 992px) {
            .main-container {
                padding: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <a href="/absensi-siswa/" class="btn back-btn">
                <i class="bi bi-arrow-left-circle"></i>
                Kembali ke Dashboard
            </a>
            <div class="date-time">
                <span id="current-date"></span>
            </div>
        </div>
        
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-qr-code-scan"></i> Sistem Absensi Siswa
                    <span class="scan-indicator"></span>
                </div>
                <div class="card-body">
                    <!-- TAMPILKAN PESAN SUKSES ATAU ERROR DI SINI -->
                    <?php echo $success_message; ?>
                    <?php echo $error_message; ?>

                    <form id="absensiForm" action="proses_persiswa.php" method="POST">
                        <div class="mb-3">
                            <label for="nis_input" class="form-label">Masukkan NIS Siswa</label>
                            <input type="text" name="nis" id="nis_input" class="form-control form-control-lg text-center" placeholder="Scan atau ketik NIS di sini..." required autofocus>
                            <div id="input-status" class="input-status"></div>
                            <small class="text-muted">Tekan Enter atau tunggu setelah selesai mengetik untuk memproses</small>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100 btn-absen" style="display: none;">
                            <i class="bi bi-check2-square"></i> Absen Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> Sistem Absensi Siswa. All rights reserved.</p>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script untuk fokus otomatis ke input NIS (penting untuk barcode scanner) -->
    <script>
        window.onload = function() {
            document.getElementById('nis_input').focus();
            
            // Tampilkan tanggal dan waktu saat ini
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('id-ID', options);
            
            // Event listener untuk input NIS
            const nisInput = document.getElementById('nis_input');
            const form = document.getElementById('absensiForm');
            const inputStatus = document.getElementById('input-status');
            
            // Variabel untuk mendeteksi input dari scanner vs manual
            let typingTimer = null;
            let lastKeyTime = 0;
            let keyPressCount = 0;
            let scanStartTime = 0;
            
            // Fungsi untuk submit form
            function submitForm() {
                if (nisInput.value.trim() !== '') {
                    form.submit();
                }
            }
            
            // Deteksi input dari barcode scanner vs manual typing
            nisInput.addEventListener('keydown', function(e) {
                // Reset timer setiap kali tombol ditekan
                clearTimeout(typingTimer);
                
                // Catat waktu untuk deteksi scanner
                const currentTime = new Date().getTime();
                
                // Jika ini adalah karakter pertama setelah input kosong
                if (nisInput.value.length === 0) {
                    scanStartTime = currentTime;
                    keyPressCount = 1;
                } else {
                    keyPressCount++;
                }
                
                // Jika tombol Enter ditekan, submit form
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitForm();
                    return;
                }
                
                // Tampilkan status sedang mengetik
                inputStatus.innerHTML = '<span class="typing-indicator">Mengetik...</span>';
            });
            
            // Event listener untuk input
            nisInput.addEventListener('input', function() {
                // Reset timer
                clearTimeout(typingTimer);
                
                // Set timer untuk mendeteksi kapan input selesai
                typingTimer = setTimeout(function() {
                    const currentTime = new Date().getTime();
                    const timeDiff = currentTime - scanStartTime;
                    
                    // Deteksi apakah ini input dari scanner atau manual
                    // Scanner biasanya sangat cepat (kurang dari 500ms untuk seluruh input)
                    // Manual typing lebih lambat
                    if (timeDiff < 500 && keyPressCount > 5) {
                        // Ini kemungkinan input dari scanner
                        inputStatus.innerHTML = '<span class="ready-indicator">Scan selesai, memproses...</span>';
                        submitForm();
                    } else {
                        // Ini input manual
                        if (nisInput.value.trim() !== '') {
                            inputStatus.innerHTML = '<span class="ready-indicator">Siap diproses, tekan Enter untuk konfirmasi</span>';
                        } else {
                            inputStatus.innerHTML = '';
                        }
                    }
                }, 1000); // Tunggu 1 detik setelah input berhenti
            });
            
            // Fokus kembali ke input setelah alert ditutup
            document.querySelectorAll('.btn-close').forEach(button => {
                button.addEventListener('click', function() {
                    setTimeout(() => {
                        document.getElementById('nis_input').focus();
                    }, 100);
                });
            });
        };
    </script>
</body>
</html>
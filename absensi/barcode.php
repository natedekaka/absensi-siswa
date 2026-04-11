<?php
session_start();

require_once '../core/init.php';
require_once '../core/Database.php';

initKonfigurasiSekolah(conn());
$sekolah = getKonfigurasiSekolah(conn());

$primaryColor = $sekolah['warna_primer'] ?? '#4f46e5';
$secondaryColor = $sekolah['warna_sekunder'] ?? '#64748b';

$title = 'Absensi Barcode - Sistem Absensi Siswa';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        :root {
            --primary: <?= $primaryColor ?>;
            --secondary: <?= $secondaryColor ?>;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .header-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            margin-bottom: 20px;
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-card .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-card .logo img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 12px;
        }

        .header-card .logo i {
            font-size: 32px;
            color: white;
        }

        .header-card h2 {
            color: #1f2937;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .header-card p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .scanner-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            margin-bottom: 20px;
            animation: slideUp 0.6s ease-out 0.2s both;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .scanner-card h4 {
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .scanner-card h4 i {
            color: var(--primary);
        }

        #reader {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            background: #000;
            min-height: 250px;
        }

        #reader video {
            border-radius: 16px;
        }

        #reader img {
            border-radius: 16px;
        }

        .reader-placeholder {
            min-height: 250px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 16px;
            padding: 40px;
        }

        .reader-placeholder i {
            font-size: 60px;
            color: var(--primary);
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .reader-placeholder p {
            color: #6b7280;
            text-align: center;
        }

        .manual-input {
            margin-top: 20px;
        }

        .manual-input .input-group {
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .manual-input .input-group-text {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 15px;
        }

        .manual-input .form-control {
            border: 2px solid #e5e7eb;
            border-left: none;
            padding: 12px 15px;
            font-size: 1rem;
        }

        .manual-input .form-control:focus {
            border-color: var(--primary);
            box-shadow: none;
        }

        .manual-input .btn {
            border-radius: 14px;
            padding: 12px 25px;
            font-weight: 600;
        }

        .btn-scan {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .btn-stop {
            background: #ef4444;
            color: white;
            border: none;
        }

        .btn-stop:hover {
            background: #dc2626;
            color: white;
        }

        .result-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: popIn 0.4s ease-out;
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .result-card.success {
            border: 3px solid #10b981;
        }

        .result-card.error {
            border: 3px solid #ef4444;
        }

        .result-card .siswa-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .result-card .avatar {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            font-weight: 700;
        }

        .result-card .details h4 {
            color: #1f2937;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .result-card .details p {
            color: #6b7280;
            margin: 0;
            font-size: 0.9rem;
        }

        .result-card .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1rem;
        }

        .result-card .status-badge.hadir {
            background: #d1fae5;
            color: #065f46;
        }

        .result-card .status-badge.sudah {
            background: #fef3c7;
            color: #92400e;
        }

        .result-card .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .result-card .btn-action {
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            font-weight: 600;
            margin-top: 15px;
            border: none;
        }

        .result-card .btn-action.success {
            background: #10b981;
            color: white;
        }

        .result-card .btn-action.sudah {
            background: #f59e0b;
            color: white;
        }

        .result-card .btn-action.error {
            background: #6b7280;
            color: white;
        }

        .status-buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .status-btn {
            padding: 15px 10px;
            border-radius: 14px;
            border: 2px solid #e5e7eb;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .status-btn:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
        }

        .status-btn.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(100, 116, 139, 0.1) 100%);
        }

        .status-btn i {
            font-size: 24px;
        }

        .status-btn.hadir {
            color: #10b981;
        }

        .status-btn.sakit {
            color: #f59e0b;
        }

        .status-btn.izin {
            color: #3b82f6;
        }

        .status-btn.alfa {
            color: #ef4444;
        }

        .status-btn span {
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
        }

        .btn-absen {
            width: 100%;
            padding: 16px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 20px;
            border: none;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-absen:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-absen:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .footer-link {
            text-align: center;
            margin-top: 20px;
        }

        .footer-link a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }

        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 45px;
            height: 45px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 100;
        }

        .back-btn:hover {
            transform: scale(1.1);
            background: white;
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .header-card, .scanner-card, .result-card {
                padding: 20px;
                border-radius: 16px;
            }

            .header-card h2 {
                font-size: 1.25rem;
            }

            .status-buttons {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <a href="<?= BASE_URL ?>dashboard/" class="back-btn">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="container">
        <div class="header-card">
            <div class="logo">
                <?php if ($sekolah['logo'] && file_exists(__DIR__ . '/../assets/uploads/' . $sekolah['logo'])): ?>
                    <img src="<?= asset('uploads/' . $sekolah['logo']) ?>" alt="Logo">
                <?php else: ?>
                    <i class="fas fa-graduation-cap"></i>
                <?php endif; ?>
            </div>
            <h2><?= htmlspecialchars($sekolah['nama_sekolah']) ?></h2>
            <p>Absensi Siswa dengan Barcode</p>
        </div>

        <div class="scanner-card" id="scannerSection">
            <h4><i class="fas fa-qrcode"></i> Scanner Barcode</h4>
            
            <div id="reader"></div>
            <div class="reader-placeholder" id="readerPlaceholder">
                <i class="fas fa-camera"></i>
                <p>Klik "Mulai Scan" untuk memulai<br>scan barcode kartu siswa</p>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-scan flex-grow-1" id="startScan">
                    <i class="fas fa-video me-2"></i>Mulai Scan
                </button>
                <button class="btn btn-stop" id="stopScan" style="display: none;">
                    <i class="fas fa-stop"></i>
                </button>
            </div>

            <div class="manual-input">
                <p class="text-muted mb-2" style="font-size: 0.85rem;">
                    <i class="fas fa-info-circle me-1"></i>Atau input manual:
                </p>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                    <input type="text" class="form-control" id="manualBarcode" placeholder="Masukkan NIS atau Barcode siswa">
                    <button class="btn btn-scan" type="button" id="submitManual">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="result-card" id="resultSection" style="display: none;">
            <div class="siswa-info">
                <div class="avatar" id="siswaAvatar">A</div>
                <div class="details">
                    <h4 id="siswaNama">Nama Siswa</h4>
                    <p id="siswaDetail">Kelas • NIS</p>
                </div>
            </div>

            <div id="statusInfo"></div>

            <div id="statusButtonsSection">
                <p class="text-muted mb-2" style="font-size: 0.85rem;">
                    <i class="fas fa-check-circle me-1"></i>Pilih status kehadiran:
                </p>
                <div class="status-buttons">
                    <button class="status-btn hadir selected" data-status="hadir">
                        <i class="fas fa-check"></i>
                        <span>Hadir</span>
                    </button>
                    <button class="status-btn sakit" data-status="sakit">
                        <i class="fas fa-user-injured"></i>
                        <span>Sakit</span>
                    </button>
                    <button class="status-btn izin" data-status="izin">
                        <i class="fas fa-envelope"></i>
                        <span>Izin</span>
                    </button>
                    <button class="status-btn alfa" data-status="alfa">
                        <i class="fas fa-times"></i>
                        <span>Alfa</span>
                    </button>
                </div>
                <button class="btn-absen" id="btnAbsen">
                    <i class="fas fa-save me-2"></i>Simpan Absensi
                </button>
            </div>

            <div class="mt-3 text-center">
                <button class="btn btn-outline-secondary" id="btnBaru">
                    <i class="fas fa-plus me-2"></i>Absensi Siswa Lain
                </button>
            </div>
        </div>
    </div>

    <div class="footer-link">
        <a href="<?= BASE_URL ?>absensi/">← Kembali ke Absensi Manual</a>
    </div>

    <script>
        let html5QrcodeScanner = null;
        let currentSiswa = null;
        let selectedStatus = 'hadir';

        const primaryColor = '<?= $primaryColor ?>';

        document.getElementById('startScan').addEventListener('click', startScanner);
        document.getElementById('stopScan').addEventListener('click', stopScanner);
        document.getElementById('submitManual').addEventListener('click', submitManualBarcode);
        document.getElementById('manualBarcode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') submitManualBarcode();
        });
        document.getElementById('btnAbsen').addEventListener('click', simpanAbsensi);
        document.getElementById('btnBaru').addEventListener('click', resetForm);

        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.status-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                selectedStatus = this.dataset.status;
            });
        });

        function startScanner() {
            document.getElementById('readerPlaceholder').style.display = 'none';
            document.getElementById('startScan').style.display = 'none';
            document.getElementById('stopScan').style.display = 'block';

            html5QrcodeScanner = new Html5Qrcode("reader");
            
            html5QrcodeScanner.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 150 }
                },
                onScanSuccess,
                onScanFailure
            ).catch(err => {
                console.error("Error starting scanner:", err);
                alert("Gagal mengakses kamera. Pastikan izin kamera diberikan.");
                stopScanner();
            });
        }

        function stopScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    html5QrcodeScanner.clear();
                }).catch(err => {
                    console.error("Error stopping scanner:", err);
                });
            }
            document.getElementById('readerPlaceholder').style.display = 'flex';
            document.getElementById('startScan').style.display = 'block';
            document.getElementById('stopScan').style.display = 'none';
        }

        function onScanSuccess(decodedText) {
            stopScanner();
            cariSiswa(decodedText);
        }

        function onScanFailure(error) {
            // Silent fail - continue scanning
        }

        function submitManualBarcode() {
            const barcode = document.getElementById('manualBarcode').value.trim();
            if (barcode) {
                cariSiswa(barcode);
            }
        }

        function cariSiswa(barcode) {
            const btnAbsen = document.getElementById('btnAbsen');
            btnAbsen.disabled = true;
            btnAbsen.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mencari...';

            fetch('cari_siswa.php?barcode=' + encodeURIComponent(barcode))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentSiswa = data.siswa;
                        currentSiswa.barcode = barcode;
                        showSiswaInfo(data);
                    } else {
                        showError(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Terjadi kesalahan saat mencari siswa.');
                })
                .finally(() => {
                    btnAbsen.disabled = false;
                    btnAbsen.innerHTML = '<i class="fas fa-save me-2"></i>Simpan Absensi';
                });
        }

        function showSiswaInfo(data) {
            const siswa = data.siswa;
            
            document.getElementById('siswaAvatar').textContent = siswa.nama.charAt(0).toUpperCase();
            document.getElementById('siswaNama').textContent = siswa.nama;
            document.getElementById('siswaDetail').textContent = `${siswa.kelas_nama} • NIS: ${siswa.nis}`;

            const statusInfo = document.getElementById('statusInfo');
            const statusButtonsSection = document.getElementById('statusButtonsSection');
            const btnAbsen = document.getElementById('btnAbsen');

            if (data.sudah_absen) {
                statusInfo.innerHTML = `
                    <div class="status-badge sudah">
                        <i class="fas fa-check-circle"></i>
                        Siswa sudah absen hari ini (${data.status_display})
                    </div>
                `;
                statusButtonsSection.style.display = 'none';
            } else {
                statusInfo.innerHTML = '';
                statusButtonsSection.style.display = 'block';
                btnAbsen.innerHTML = '<i class="fas fa-save me-2"></i>Simpan Absensi';
            }

            document.getElementById('scannerSection').style.display = 'none';
            document.getElementById('resultSection').style.display = 'block';
            document.getElementById('resultSection').className = 'result-card';
        }

        function showError(message) {
            document.getElementById('scannerSection').style.display = 'none';
            
            const resultSection = document.getElementById('resultSection');
            resultSection.style.display = 'block';
            resultSection.className = 'result-card error';
            
            resultSection.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-times-circle" style="font-size: 60px; color: #ef4444; margin-bottom: 20px;"></i>
                    <h4 style="color: #1f2937; margin-bottom: 10px;">Siswa Tidak Ditemukan</h4>
                    <p style="color: #6b7280; margin-bottom: 20px;">${message}</p>
                    <button class="btn-absen error" id="btnCobaLagi">
                        <i class="fas fa-redo me-2"></i>Coba Lagi
                    </button>
                </div>
            `;

            document.getElementById('btnCobaLagi').addEventListener('click', resetForm);
        }

        function simpanAbsensi() {
            if (!currentSiswa) return;

            const btnAbsen = document.getElementById('btnAbsen');
            btnAbsen.disabled = true;
            btnAbsen.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';

            const formData = new FormData();
            formData.append('siswa_id', currentSiswa.id);
            formData.append('barcode', currentSiswa.barcode);
            formData.append('status', selectedStatus);

            fetch('proses_barcode.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Absensi berhasil disimpan!');
                    resetForm();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan absensi.');
            })
            .finally(() => {
                btnAbsen.disabled = false;
                btnAbsen.innerHTML = '<i class="fas fa-save me-2"></i>Simpan Absensi';
            });
        }

        function resetForm() {
            currentSiswa = null;
            selectedStatus = 'hadir';
            
            document.querySelectorAll('.status-btn').forEach(b => b.classList.remove('selected'));
            document.querySelector('.status-btn.hadir').classList.add('selected');
            
            document.getElementById('manualBarcode').value = '';
            document.getElementById('resultSection').style.display = 'none';
            document.getElementById('scannerSection').style.display = 'block';
            
            stopScanner();
        }
    </script>
</body>
</html>

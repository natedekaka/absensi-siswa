<?php
session_start();

require_once '../core/init.php';
require_once '../core/Database.php';

initKonfigurasiSekolah(conn());
$sekolah = getKonfigurasiSekolah(conn());

$primaryColor = $sekolah['warna_primer'] ?? '#4f46e5';
$secondaryColor = $sekolah['warna_sekunder'] ?? '#64748b';

$title = 'Absensi Barcode - Sistem Absensi Siswa';

$today = date('Y-m-d');
$semester_aktif = conn()->query("SELECT * FROM semester WHERE is_active = 1 LIMIT 1")->fetch_assoc();
$semester_id = $_GET['semester_id'] ?? ($semester_aktif['id'] ?? '');
$tgl_cek = $_GET['tgl'] ?? $today;

$where_semester = $semester_id ? " AND semester_id = " . (int)$semester_id : "";
$stats_hari_ini = conn()->query("
    SELECT status, COUNT(*) as total FROM absensi 
    WHERE tanggal = '$tgl_cek' $where_semester 
    GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

$stats = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0, 'Terlambat' => 0];
foreach ($stats_hari_ini as $s) {
    if (isset($stats[$s['status']])) $stats[$s['status']] = $s['total'];
}

$total_absen = array_sum($stats);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        :root { --scan-primary: <?= $primaryColor ?>; --scan-secondary: <?= $secondaryColor ?>; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(135deg, var(--scan-primary) 0%, var(--scan-secondary) 100%); min-height: 100vh; padding: 20px; }
        .scan-container { max-width: 600px; margin: 0 auto; }
        .scan-glass { background: rgba(255,255,255,0.95); border-radius: 24px; padding: 30px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .scan-glass-sm { background: rgba(255,255,255,0.95); border-radius: 24px; padding: 25px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        @keyframes slideDown { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        @keyframes popIn { from { opacity:0; transform:scale(0.9); } to { opacity:1; transform:scale(1); } }
        #reader { width:100%; border-radius:16px; overflow:hidden; background:#000; min-height:250px; }
        #reader video { border-radius:16px; }
        #reader img { border-radius:16px; }
        .reader-placeholder { min-height:250px; display:flex; flex-direction:column; align-items:center; justify-content:center; background:linear-gradient(135deg,#f3f4f6 0%,#e5e7eb 100%); border-radius:16px; padding:40px; }
        .reader-placeholder i { font-size:60px; color:var(--scan-primary); margin-bottom:15px; opacity:0.5; }
        .reader-placeholder p { color:#6b7280; text-align:center; }
        .status-btn { padding:15px 10px; border-radius:14px; border:2px solid #e5e7eb; background:white; cursor:pointer; transition:all 0.3s ease; display:flex; flex-direction:column; align-items:center; gap:8px; }
        .status-btn:hover { border-color:var(--scan-primary); transform:translateY(-3px); }
        .status-btn.selected { border-color:var(--scan-primary); background:linear-gradient(135deg,rgba(79,70,229,0.1) 0%,rgba(100,116,139,0.1) 100%); }
        .status-btn i { font-size:24px; }
        .status-btn span { font-size:0.8rem; font-weight:600; color:#374151; }
        .btn-absen { width:100%; padding:16px; border-radius:14px; font-weight:600; font-size:1.1rem; margin-top:20px; border:none; background:linear-gradient(135deg,var(--scan-primary) 0%,var(--scan-secondary) 100%); color:white; cursor:pointer; transition:all 0.3s ease; }
        .btn-absen:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 10px 20px rgba(0,0,0,0.15); }
        .btn-absen:disabled { opacity:0.6; cursor:not-allowed; }
        .result-card.pop { animation:popIn 0.4s ease-out; }
        .result-card.success { border:3px solid #10b981; }
        .result-card.error { border:3px solid #ef4444; }
        @media (max-width:480px) { body { padding:10px; } .scan-glass, .scan-glass-sm, .result-card { padding:20px; border-radius:16px; } .status-buttons { grid-template-columns:repeat(2,1fr); } }
    </style>
</head>
<body class="font-[Plus_Jakarta_Sans]">

    <a href="<?= BASE_URL ?>dashboard/" class="fixed top-5 left-5 w-11 h-11 rounded-xl bg-white/90 backdrop-blur flex items-center justify-center text-[var(--scan-primary)] shadow-[0_4px_15px_rgba(0,0,0,0.1)] hover:scale-110 hover:bg-white transition-all z-[100]">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="scan-container">
        <!-- Header Card -->
        <div class="scan-glass text-center mb-5 animate-[slideDown_0.6s_ease-out]">
            <div class="w-20 h-20 mx-auto mb-4 rounded-[20px] flex items-center justify-center" style="background:linear-gradient(135deg,var(--scan-primary) 0%,var(--scan-secondary) 100%)">
                <?php if ($sekolah['logo'] && file_exists(__DIR__ . '/../assets/uploads/' . $sekolah['logo'])): ?>
                    <img src="<?= asset('uploads/' . $sekolah['logo']) ?>" alt="Logo" class="w-[50px] h-[50px] object-contain rounded-xl">
                <?php else: ?>
                    <i class="fas fa-graduation-cap text-white text-3xl"></i>
                <?php endif; ?>
            </div>
            <h2 class="text-gray-800 font-bold text-xl mb-1"><?= htmlspecialchars($sekolah['nama_sekolah']) ?></h2>
            <p class="text-gray-500 text-sm">Absensi Siswa dengan Barcode</p>
            
            <div class="mt-4 p-3 bg-white/80 rounded-xl">
                <form method="GET" class="flex flex-wrap gap-2 justify-center items-center">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-calendar text-gray-500 text-sm"></i>
                        <input type="date" name="tgl" class="form-input-modern text-sm !py-2 !px-3 w-[140px]" value="<?= $tgl_cek ?>">
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-school text-gray-500 text-sm"></i>
                        <select name="semester_id" class="form-select-modern text-sm !py-2 !px-3 w-[180px]">
                            <option value="">Semua Semester</option>
                            <?php
                            $semester_list = conn()->query("SELECT * FROM semester ORDER BY is_active DESC, tahun_ajaran_id DESC, semester ASC");
                            while ($row = $semester_list->fetch_assoc()):
                            ?>
                            <option value="<?= $row['id'] ?>" <?= ($semester_id == $row['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['nama']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-modern btn-primary-modern btn-sm-modern">
                        <i class="fas fa-filter"></i>
                    </button>
                </form>
            </div>
            
            <div class="mt-4 flex justify-center gap-2 flex-wrap">
                <span class="badge-modern badge-hadir">Hadir: <?= $stats['Hadir'] ?></span>
                <span class="badge-modern badge-sakit">Sakit: <?= $stats['Sakit'] ?></span>
                <span class="badge-modern badge-izin">Izin: <?= $stats['Izin'] ?></span>
                <span class="badge-modern badge-alfa">Alfa: <?= $stats['Alfa'] ?></span>
                <span class="badge-modern" style="background:#E2E8F0;color:#475569;">Terlambat: <?= $stats['Terlambat'] ?></span>
                <span class="badge-modern" style="background:#1E293B;color:white;">Total: <?= $total_absen ?></span>
            </div>
        </div>

        <!-- Scanner Card -->
        <div class="scan-glass-sm mb-5 animate-[slideUp_0.6s_ease-out_0.2s_both]" id="scannerSection">
            <h4 class="text-gray-800 font-semibold mb-5 flex items-center gap-3">
                <i class="fas fa-qrcode" style="color:var(--scan-primary);"></i> Scanner Barcode
            </h4>
            
            <div id="reader"></div>
            <div class="reader-placeholder" id="readerPlaceholder">
                <i class="fas fa-camera"></i>
                <p>Klik "Mulai Scan" untuk memulai<br>scan barcode kartu siswa</p>
            </div>

            <div class="flex gap-3 mt-4">
                <button class="btn-modern flex-1 text-white border-none" id="startScan" style="background:linear-gradient(135deg,var(--scan-primary) 0%,var(--scan-secondary) 100%)">
                    <i class="fas fa-video mr-2"></i>Mulai Scan
                </button>
                <button class="btn-modern bg-red-500 hover:bg-red-600 text-white border-none hidden" id="stopScan">
                    <i class="fas fa-stop"></i>
                </button>
            </div>

            <div class="mt-5">
                <p class="text-gray-400 text-xs mb-2"><i class="fas fa-info-circle mr-1"></i>Atau input manual:</p>
                <div class="flex rounded-[14px] overflow-hidden shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1)]">
                    <span class="flex items-center px-4" style="background:var(--scan-primary);color:white;"><i class="fas fa-barcode"></i></span>
                    <input type="text" class="form-input-modern !border-l-0 !rounded-none flex-1" id="manualBarcode" placeholder="Masukkan NIS atau Barcode siswa">
                    <button class="px-5 font-semibold text-white border-none" type="button" id="submitManual" style="background:linear-gradient(135deg,var(--scan-primary) 0%,var(--scan-secondary) 100%)">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <?php
        $riwayat = conn()->query("
            SELECT a.tanggal, a.status, s.nama, k.nama_kelas 
            FROM absensi a
            JOIN siswa s ON a.siswa_id = s.id
            LEFT JOIN kelas k ON s.kelas_id = k.id
            WHERE a.tanggal = '$tgl_cek' $where_semester
            ORDER BY a.id DESC LIMIT 20
        ");
        ?>
        
        <?php if ($riwayat && $riwayat->num_rows > 0): ?>
        <div class="result-card scan-glass-sm mt-4">
            <h5 class="mb-4 font-semibold text-gray-800"><i class="fas fa-history mr-2" style="color:var(--scan-primary);"></i>Riwayat Scan (<?= $riwayat->num_rows ?>)</h5>
            <div class="overflow-x-auto" style="max-height:200px;">
                <table class="table-modern text-sm w-full">
                    <thead class="sticky top-0 z-10">
                        <tr>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $riwayat->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nama']) ?></td>
                            <td class="text-gray-500"><?= htmlspecialchars($r['nama_kelas'] ?? '-') ?></td>
                            <td><span class="badge-modern badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="result-card scan-glass-sm hidden" id="resultSection">
            <div class="flex items-center gap-5 mb-5">
                <div class="w-[70px] h-[70px] rounded-[20px] flex items-center justify-center text-white text-3xl font-bold" id="siswaAvatar" style="background:linear-gradient(135deg,var(--scan-primary) 0%,var(--scan-secondary) 100%)">A</div>
                <div>
                    <h4 class="text-gray-800 font-bold text-lg mb-1" id="siswaNama">Nama Siswa</h4>
                    <p class="text-gray-500 text-sm" id="siswaDetail">Kelas • NIS</p>
                </div>
            </div>

            <div id="statusInfo"></div>

            <div id="statusButtonsSection">
                <p class="text-gray-400 text-xs mb-3"><i class="fas fa-check-circle mr-1"></i>Pilih status kehadiran:</p>
                <div class="grid grid-cols-4 gap-2.5">
                    <button class="status-btn hadir selected" data-status="hadir" style="background:#10B981;color:white;font-weight:600;">
                        <i class="fas fa-check"></i>
                        <span style="color:white;">Hadir</span>
                    </button>
                    <button class="status-btn sakit" data-status="sakit" style="background:#F59E0B;color:white;font-weight:600;color:#000;">
                        <i class="fas fa-user-injured" style="color:#000;"></i>
                        <span style="color:#000;">Sakit</span>
                    </button>
                    <button class="status-btn izin" data-status="izin" style="background:#3B82F6;color:white;font-weight:600;">
                        <i class="fas fa-envelope"></i>
                        <span style="color:white;">Izin</span>
                    </button>
                    <button class="status-btn alfa" data-status="alfa" style="background:#EF4444;color:white;font-weight:600;">
                        <i class="fas fa-times"></i>
                        <span style="color:white;">Alfa</span>
                    </button>
                </div>
                <button class="btn-absen" id="btnAbsen">
                    <i class="fas fa-save mr-2"></i>Simpan Absensi
                </button>
            </div>

            <div class="mt-4 text-center">
                <button class="btn-modern btn-ghost" id="btnBaru">
                    <i class="fas fa-plus mr-2"></i>Absensi Siswa Lain
                </button>
            </div>
        </div>
    </div>

    <div class="text-center mt-6">
        <a href="<?= BASE_URL ?>absensi/" class="text-white/80 hover:text-white font-medium no-underline text-sm">← Kembali ke Absensi Manual</a>
    </div>

    <script>
        let html5QrcodeScanner = null;
        let currentSiswa = null;
        let selectedStatus = 'hadir';

        const primaryColor = '<?= $primaryColor ?>';

        function playSuccessSound() {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        }

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

            const urlParams = new URLSearchParams(window.location.search);
            const tgl = urlParams.get('tgl') || '<?= $tgl_cek ?>';
            const semester_id = urlParams.get('semester_id') || '';

            fetch('cari_siswa.php?barcode=' + encodeURIComponent(barcode) + '&tgl=' + tgl + '&semester_id=' + semester_id)
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

            const urlParams = new URLSearchParams(window.location.search);
            const tgl = urlParams.get('tgl') || '<?= $tgl_cek ?>';
            const semester_id = urlParams.get('semester_id') || '';

            const formData = new FormData();
            formData.append('siswa_id', currentSiswa.id);
            formData.append('barcode', currentSiswa.barcode);
            formData.append('status', selectedStatus);
            formData.append('tgl', tgl);
            formData.append('semester_id', semester_id);

            fetch('proses_barcode.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    playSuccessSound();
                    alert('Absensi berhasil disimpan!');
                    resetForm();
                    location.reload();
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

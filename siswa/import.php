<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Import Siswa - Sistem Absensi Siswa';

$error = '';
$success = '';

$kelas_result = conn()->query("SELECT id, nama_kelas FROM kelas ORDER BY id ASC");
$kelas_list = [];
while ($row = $kelas_result->fetch_assoc()) {
    $kelas_list[] = $row;
}

if (isset($_GET['download']) && $_GET['download'] == 'template') {
    $file = __DIR__ . '/template_siswa.csv';
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="template_import_siswa.csv"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        fgetcsv($handle);
        
        $imported = 0;
        $updated = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 5) continue;
            
            $nis = trim($data[0]);
            $nisn = trim($data[1]);
            $nama = trim($data[2]);
            $kelas_id = (int)trim($data[3]);
            $jenis_kelamin = trim($data[4]);

            if (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'])) {
                $errors[] = "Jenis kelamin tidak valid pada NIS $nis";
                continue;
            }

            if (empty($nis) || empty($nisn) || empty($nama) || $kelas_id <= 0) {
                $errors[] = "Data tidak valid: $nis";
                continue;
            }
            
            $cek = conn()->prepare("SELECT id FROM siswa WHERE nis = ?");
            $cek->bind_param("s", $nis);
            $cek->execute();
            $cek->store_result();
            
            if ($cek->num_rows > 0) {
                $stmt = conn()->prepare("UPDATE siswa SET nisn = ?, nama = ?, kelas_id = ?, jenis_kelamin = ? WHERE nis = ?");
                $stmt->bind_param("ssiss", $nisn, $nama, $kelas_id, $jenis_kelamin, $nis);
                
                if ($stmt->execute()) {
                    $updated++;
                } else {
                    $errors[] = "Error update NIS $nis";
                }
            } else {
                $stmt = conn()->prepare("INSERT INTO siswa (nis, nisn, nama, kelas_id, jenis_kelamin) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssis", $nis, $nisn, $nama, $kelas_id, $jenis_kelamin);
                
                if ($stmt->execute()) {
                    $imported++;
                } else {
                    $errors[] = "Error insert NIS $nis";
                }
            }
        }
        
        fclose($handle);
        
        if ($imported > 0) {
            $success = "Berhasil menambahkan $imported data siswa";
        }
        if ($updated > 0) {
            $success .= ($success ? "<br>" : "") . "Berhasil memperbarui $updated data";
        }
        if (!empty($errors)) {
            $error = implode("<br>", $errors);
        }
    } else {
        $error = "Silakan pilih file CSV yang valid";
    }
}

ob_start();
?>

<div class="flex items-center gap-4 mb-6">
    <a href="index.php" class="btn-modern btn-neutral-modern">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h2 class="text-xl font-bold text-gray-800 dark:text-white">
        <i class="fas fa-file-import mr-3 text-primary"></i>Import Siswa
    </h2>
</div>

<div class="max-w-4xl mx-auto">
    <div class="card-modern">
        <div class="card-modern-body">
            <?php if ($success): ?>
            <div class="alert-modern alert-success-modern mb-4 flex items-center gap-3">
                <i class="fas fa-check-circle text-lg"></i><span><?= $success ?></span>
                <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert-modern alert-danger-modern mb-4 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-lg"></i><span><?= $error ?></span>
                <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
            </div>
            <?php endif; ?>

            <!-- Upload Zone -->
            <div class="border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center mb-6 cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/50 transition-all" id="dropZone">
                <div class="text-4xl text-teal-700 mb-3"><i class="fas fa-cloud-upload-alt"></i></div>
                <h5 class="font-semibold text-gray-800 dark:text-white">Unggah File CSV</h5>
                <p class="text-sm text-gray-400 mb-4">Seret file ke sini atau klik untuk memilih</p>
                <input type="file" name="csv_file" id="csv_file" class="hidden" accept=".csv" required>
                <button type="button" class="btn-modern btn-primary-modern" onclick="document.getElementById('csv_file').click()">
                    <i class="fas fa-folder-open mr-2"></i>Pilih File
                </button>
                <p class="mt-3 text-xs text-gray-400" id="fileName">
                    <i class="fas fa-info-circle mr-1"></i>Format yang didukung: .CSV
                </p>
            </div>

            <!-- Template Info -->
            <div class="p-4 mb-6 rounded-2xl bg-gradient-to-r from-emerald-50 to-green-50 border-l-4 border-emerald-500">
                <div class="flex items-start gap-4">
                    <div class="w-[50px] h-[50px] rounded-xl flex items-center justify-center text-white shrink-0" style="background:var(--wa-green,#10b981)">
                        <i class="fas fa-file-csv text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h6 class="font-bold text-gray-800 dark:text-white mb-2">
                            <i class="fas fa-download mr-2 text-emerald-600"></i>Unduh Format Template
                        </h6>
                        <p class="text-sm text-gray-500 mb-3">Unduh file template di bawah untuk melihat format yang benar.</p>
                        <a href="?download=template" class="btn-modern btn-success-modern text-sm">
                            <i class="fas fa-file-download mr-2"></i>Unduh Template CSV
                        </a>
                    </div>
                </div>
            </div>

            <!-- Format Info -->
            <div class="bg-gray-50 dark:bg-gray-800/30 rounded-2xl p-5 mb-6">
                <h6 class="font-bold text-gray-800 dark:text-white mb-4">
                    <i class="fas fa-info-circle mr-2 text-primary"></i>Petunjuk Pengisian
                </h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="mb-3">
                            <span class="inline-block bg-teal-700 text-white px-2 py-0.5 rounded text-xs mr-2">Kolom 1</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-200">NIS</span>
                            <p class="text-xs text-gray-400 mt-0.5">Nomor Induk Siswa (wajib)</p>
                        </div>
                        <div class="mb-3">
                            <span class="inline-block bg-teal-700 text-white px-2 py-0.5 rounded text-xs mr-2">Kolom 2</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-200">NISN</span>
                            <p class="text-xs text-gray-400 mt-0.5">Nomor Induk Siswa Nasional (wajib)</p>
                        </div>
                        <div class="mb-3">
                            <span class="inline-block bg-teal-700 text-white px-2 py-0.5 rounded text-xs mr-2">Kolom 3</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-200">Nama</span>
                            <p class="text-xs text-gray-400 mt-0.5">Nama lengkap siswa (wajib)</p>
                        </div>
                    </div>
                    <div>
                        <div class="mb-3">
                            <span class="inline-block bg-teal-700 text-white px-2 py-0.5 rounded text-xs mr-2">Kolom 4</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-200">Kelas ID</span>
                            <p class="text-xs text-gray-400 mt-0.5">ID kelas dari tabel kelas (wajib)</p>
                        </div>
                        <div class="mb-3">
                            <span class="inline-block bg-teal-700 text-white px-2 py-0.5 rounded text-xs mr-2">Kolom 5</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-200">Jenis Kelamin</span>
                            <p class="text-xs text-gray-400 mt-0.5">Laki-laki atau Perempuan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Referensi Kelas -->
            <?php if (!empty($kelas_list)): ?>
            <div class="p-4 mb-6 rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500">
                <h6 class="font-bold text-gray-800 dark:text-white mb-3">
                    <i class="fas fa-door-open mr-2 text-blue-600"></i>Referensi Kelas ID
                </h6>
                <p class="text-xs text-gray-500 mb-3">Gunakan ID kelas berikut untuk mengisi Kolom 4 pada file CSV:</p>
                <table class="table-modern w-full text-sm">
                    <thead><tr><th class="text-center">ID Kelas</th><th>Nama Kelas</th></tr></thead>
                    <tbody>
                        <?php foreach ($kelas_list as $kelas): ?>
                        <tr><td class="text-center"><span class="badge-modern badge-primary-modern"><?= $kelas['id'] ?></span></td><td><?= htmlspecialchars($kelas['nama_kelas']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="importForm">
                <input type="file" name="csv_file" id="csv_file_hidden" class="hidden" accept=".csv" required>
                <div class="flex gap-3 mt-4">
                    <button type="submit" class="btn-modern btn-primary-modern" id="submitBtn" disabled>
                        <i class="fas fa-upload mr-2"></i>Import Data
                    </button>
                    <a href="index.php" class="btn-modern btn-neutral-modern">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('csv_file');
const fileInputHidden = document.getElementById('csv_file_hidden');
const submitBtn = document.getElementById('submitBtn');
const fileName = document.getElementById('fileName');

dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-emerald-500','bg-emerald-50/50'); });
dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('border-emerald-500','bg-emerald-50/50'); });
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-emerald-500','bg-emerald-50/50');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        handleFileSelect(e.dataTransfer.files[0]);
    }
});
fileInput.addEventListener('change', (e) => { if (e.target.files.length) handleFileSelect(e.target.files[0]); });

function handleFileSelect(file) {
    if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
        fileInputHidden.files = fileInput.files;
        fileName.innerHTML = '<i class="fas fa-check-circle text-emerald-500 mr-1"></i>File: <strong>' + file.name + '</strong>';
        submitBtn.disabled = false;
    } else {
        fileName.innerHTML = '<i class="fas fa-exclamation-circle text-red-500 mr-1"></i>Format file tidak valid. Pilih file CSV.';
        submitBtn.disabled = true;
    }
}
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

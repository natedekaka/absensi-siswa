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

<div class="d-flex align-items-center mb-4">
    <a href="index.php" class="btn btn-outline-secondary me-3">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-file-import me-2"></i>Import Siswa
    </h2>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-custom">
            <div class="card-header-custom d-flex align-items-center">
                <i class="fas fa-upload me-2"></i>
                <span>Unggah Data Siswa</span>
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success-custom alert-custom d-flex align-items-center">
                        <i class="fas fa-check-circle fs-4 me-3"></i>
                        <div><?= $success ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger-custom alert-custom d-flex align-items-center">
                        <i class="fas fa-exclamation-circle fs-4 me-3"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>

                <div class="upload-zone p-5 text-center mb-4" id="dropZone">
                    <div class="upload-icon mb-3">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h5 class="fw-semibold text-wa-dark">Unggah File CSV</h5>
                    <p class="text-muted mb-3">Seret file ke sini atau klik untuk memilih</p>
                    <input type="file" name="csv_file" id="csv_file" class="form-control d-none" accept=".csv" required>
                    <button type="button" class="btn btn-wa-primary" onclick="document.getElementById('csv_file').click()">
                        <i class="fas fa-folder-open me-2"></i>Pilih File
                    </button>
                    <p class="mt-3 mb-0 text-muted small" id="fileName">
                        <i class="fas fa-info-circle me-1"></i>
                        Format yang didukung: .CSV
                    </p>
                </div>

                <div class="template-info p-4 mb-4">
                    <div class="d-flex align-items-start">
                        <div class="template-icon me-3">
                            <i class="fas fa-file-csv"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold text-wa-dark mb-2">
                                <i class="fas fa-download me-2"></i>Unduh Format Template
                            </h6>
                            <p class="text-muted small mb-3">
                                Unduh file template di bawah untuk melihat format yang benar dalam menginput data siswa.
                            </p>
                            <a href="?download=template" class="btn btn-wa-success btn-sm">
                                <i class="fas fa-file-download me-2"></i>Unduh Template CSV
                            </a>
                        </div>
                    </div>
                </div>

                <div class="format-info p-4">
                    <h6 class="fw-bold text-wa-dark mb-3">
                        <i class="fas fa-info-circle me-2"></i>Petunjuk Pengisian
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="format-item mb-3">
                                <span class="format-badge">Kolom 1</span>
                                <span class="fw-semibold">NIS</span>
                                <p class="text-muted small mb-0">Nomor Induk Siswa (wajib)</p>
                            </div>
                            <div class="format-item mb-3">
                                <span class="format-badge">Kolom 2</span>
                                <span class="fw-semibold">NISN</span>
                                <p class="text-muted small mb-0">Nomor Induk Siswa Nasional (wajib)</p>
                            </div>
                            <div class="format-item mb-3">
                                <span class="format-badge">Kolom 3</span>
                                <span class="fw-semibold">Nama</span>
                                <p class="text-muted small mb-0">Nama lengkap siswa (wajib)</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="format-item mb-3">
                                <span class="format-badge">Kolom 4</span>
                                <span class="fw-semibold">Kelas ID</span>
                                <p class="text-muted small mb-0">ID kelas dari tabel kelas (wajib)</p>
                            </div>
                            <div class="format-item mb-3">
                                <span class="format-badge">Kolom 5</span>
                                <span class="fw-semibold">Jenis Kelamin</span>
                                <p class="text-muted small mb-0">Laki-laki atau Perempuan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($kelas_list)): ?>
                <div class="kelas-info p-4 mt-4">
                    <h6 class="fw-bold text-wa-dark mb-3">
                        <i class="fas fa-door-open me-2"></i>Referensi Kelas ID
                    </h6>
                    <p class="text-muted small mb-3">Gunakan ID kelas berikut untuk mengisi Kolom 4 pada file CSV:</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm kelas-table">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center">ID Kelas</th>
                                    <th>Nama Kelas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kelas_list as $kelas): ?>
                                <tr>
                                    <td class="text-center"><span class="badge bg-primary"><?= $kelas['id'] ?></span></td>
                                    <td><?= htmlspecialchars($kelas['nama_kelas']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="importForm">
                    <input type="file" name="csv_file" id="csv_file_hidden" class="d-none" accept=".csv" required>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-wa-primary" id="submitBtn" disabled>
                            <i class="fas fa-upload me-2"></i>Import Data
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.upload-zone {
    border: 2px dashed #ccc;
    border-radius: 16px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    transition: all 0.3s ease;
}
.upload-zone:hover {
    border-color: var(--wa-green);
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
}
.upload-zone.dragover {
    border-color: var(--wa-green);
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    transform: scale(1.02);
}
.upload-icon {
    font-size: 3rem;
    color: var(--wa-dark);
}
.template-info {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    border-radius: 12px;
    border-left: 4px solid var(--wa-green);
}
.template-icon {
    width: 50px;
    height: 50px;
    background: var(--wa-green);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}
.format-info {
    background: #f8f9fa;
    border-radius: 12px;
}
.format-badge {
    display: inline-block;
    background: var(--wa-dark);
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    margin-right: 8px;
}
.kelas-info {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 12px;
    border-left: 4px solid #2196F3;
}
.kelas-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
}
.kelas-table thead {
    background: var(--wa-dark);
    color: white;
}
.kelas-table td {
    vertical-align: middle;
}
</style>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('csv_file');
const fileInputHidden = document.getElementById('csv_file_hidden');
const submitBtn = document.getElementById('submitBtn');
const fileName = document.getElementById('fileName');

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        handleFileSelect(e.dataTransfer.files[0]);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length) {
        handleFileSelect(e.target.files[0]);
    }
});

function handleFileSelect(file) {
    if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
        fileInputHidden.files = fileInput.files;
        fileName.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> File dipilih: <strong>' + file.name + '</strong>';
        submitBtn.disabled = false;
    } else {
        fileName.innerHTML = '<i class="fas fa-exclamation-circle text-danger me-1"></i> Format file tidak valid. Silakan pilih file CSV.';
        submitBtn.disabled = true;
    }
}
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

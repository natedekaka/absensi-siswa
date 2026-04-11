<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Manajemen Kenaikan Kelas - Sistem Absensi Siswa';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'naik_tingkat') {
        $tingkat_dari = (int)$_POST['tingkat_dari'];
        $tingkat_ke = (int)$_POST['tingkat_ke'];
        
        $prefix_map = [
            10 => ['X', '10'],
            11 => ['XI', '11'],
            12 => ['XII', '12']
        ];
        
        $prefix_asal = $prefix_map[$tingkat_dari][0] ?? '';
        
        $siswa_naik = conn()->query("
            SELECT s.id FROM siswa s 
            JOIN kelas k ON s.kelas_id = k.id 
            WHERE s.status = 'aktif' AND (
                k.nama_kelas LIKE '$prefix_asal-%' OR 
                k.nama_kelas LIKE '{$prefix_map[$tingkat_dari][1]}-%'
            )
        ");

        $count = 0;
        while ($siswa = $siswa_naik->fetch_assoc()) {
            $update = conn()->prepare("UPDATE siswa SET tingkat = ? WHERE id = ?");
            $update->bind_param("ii", $tingkat_ke, $siswa['id']);
            $update->execute();
            $count++;
        }

        if ($count > 0) {
            $success = "Berhasil menaikan $count siswa dari tingkat $tingkat_dari ke $tingkat_ke";
        } else {
            $error = "Tidak ada siswa yang dinaikkan";
        }
        
    } elseif ($action == 'export_siswa') {
        $tingkat = (int)$_POST['tingkat_export'];
        
        $prefix_map = [
            10 => ['X', '10'],
            11 => ['XI', '11'],
            12 => ['XII', '12']
        ];
        
        $prefix = $prefix_map[$tingkat][0] ?? '';
        
        $siswa = conn()->query("
            SELECT s.id, s.nis, s.nisn, s.nama, k.nama_kelas as kelas_lama, k.id as kelas_id_lama
            FROM siswa s 
            JOIN kelas k ON s.kelas_id = k.id 
            WHERE s.status = 'aktif' AND (
                k.nama_kelas LIKE '$prefix-%' OR 
                k.nama_kelas LIKE '{$prefix_map[$tingkat][1]}-%'
            )
            ORDER BY k.nama_kelas, s.nama
        ");
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="siswa_kelas_'.$tingkat.'_'.date('Ymd').'.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['NIS', 'NISN', 'Nama', 'Kelas Lama', 'Nama Kelas Baru', 'ID Kelas Baru'], ';');
        
        while ($row = $siswa->fetch_assoc()) {
            fputcsv($output, [
                $row['nis'],
                $row['nisn'],
                $row['nama'],
                $row['kelas_lama'],
                '',
                ''
            ], ';');
        }
        
        fclose($output);
        exit;
        
    } elseif ($action == 'import_kelas') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['csv_file']['tmp_name'];
            
            $handle = fopen($file, 'r');
            $rows = [];
            while (($line = fgetcsv($handle, 1000, ';')) !== FALSE) {
                $rows[] = $line;
            }
            fclose($handle);
            array_shift($rows);
            
            $updated = 0;
            $errors = [];
            
            foreach ($rows as $row) {
                if (count($row) < 6) continue;
                
                $nis = trim($row[0]);
                $nama_kelas_baru = trim($row[4]);
                $kelas_id_baru = (int)trim($row[5]);
                
                if (empty($nis) || empty($kelas_id_baru)) continue;
                
                $update = conn()->prepare("UPDATE siswa SET kelas_id = ? WHERE nis = ? AND status = 'aktif'");
                $update->bind_param("is", $kelas_id_baru, $nis);
                
                if ($update->execute()) {
                    $updated++;
                } else {
                    $errors[] = "Error updating $nis";
                }
            }
            
            if ($updated > 0) {
                $success = "Berhasil mengupdate $updated siswa ke kelas baru";
            }
            if (!empty($errors)) {
                $error = implode(", ", $errors);
            }
        } else {
            $error = "Silakan pilih file CSV yang valid";
        }
        
    } elseif ($action == 'lulus') {
        $tahun_lulus = (int)$_POST['tahun_lulus'];
        
        $siswa_xii = conn()->query("
            SELECT s.id FROM siswa s 
            JOIN kelas k ON s.kelas_id = k.id 
            WHERE s.status = 'aktif' AND (
                k.nama_kelas LIKE 'XII-%' OR 
                k.nama_kelas LIKE '12-%'
            )
        ");

        $count = 0;
        while ($siswa = $siswa_xii->fetch_assoc()) {
            $update = conn()->prepare("UPDATE siswa SET status = 'alumni', tingkat = NULL, tahun_lulus = ? WHERE id = ?");
            $update->bind_param("ii", $tahun_lulus, $siswa['id']);
            $update->execute();
            $count++;
        }

        if ($count > 0) {
            $success = "Berhasil meluluskan $count siswa kelas 12";
        } else {
            $error = "Tidak ada siswa kelas 12 yang diluluskan";
        }
    }
}

$siswa_x_count = conn()->query("SELECT COUNT(*) as total FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.status = 'aktif' AND (k.nama_kelas LIKE 'X-%' OR k.nama_kelas LIKE '10-%')")->fetch_assoc()['total'];
$siswa_xi_count = conn()->query("SELECT COUNT(*) as total FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.status = 'aktif' AND (k.nama_kelas LIKE 'XI-%' OR k.nama_kelas LIKE '11-%')")->fetch_assoc()['total'];
$siswa_xii_count = conn()->query("SELECT COUNT(*) as total FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.status = 'aktif' AND (k.nama_kelas LIKE 'XII-%' OR k.nama_kelas LIKE '12-%')")->fetch_assoc()['total'];

$kelas_list = conn()->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
$kelas_options = [];
while ($k = $kelas_list->fetch_assoc()) {
    $kelas_options[$k['id']] = $k['nama_kelas'];
}

ob_start();
?>

<style>
.stat-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    transition: transform 0.3s;
}
.stat-card:hover {
    transform: translateY(-5px);
}
.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.btn-action {
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-graduation-cap me-2"></i>Manajemen Kenaikan Kelas
    </h2>
</div>

<?php if ($success): ?>
    <div class="alert alert-success bg-success text-white border-0 rounded-3 mb-4">
        <i class="fas fa-check-circle me-2"></i><?= $success ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger bg-danger text-white border-0 rounded-3 mb-4">
        <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card-custom stat-card p-4">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0"><?= $siswa_x_count ?></h4>
                    <small class="text-muted">Siswa Kelas 10</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom stat-card p-4">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0"><?= $siswa_xi_count ?></h4>
                    <small class="text-muted">Siswa Kelas 11</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom stat-card p-4">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0"><?= $siswa_xii_count ?></h4>
                    <small class="text-muted">Siswa Kelas 12</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-arrow-up me-2"></i>1. Proses Kenaikan Tingkat
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="naik_tingkat">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Naikkan Tingkat</label>
                        <select name="tingkat_dari" class="form-select" required>
                            <option value="">Pilih tingkat</option>
                            <option value="10">Kelas 10 → 11</option>
                            <option value="11">Kelas 11 → 12</option>
                        </select>
                        <input type="hidden" name="tingkat_ke" value="">
                        <script>
                            document.querySelector('select[name="tingkat_dari"]').addEventListener('change', function() {
                                this.nextElementSibling.value = parseInt(this.value) + 1;
                            });
                        </script>
                    </div>

                    <div class="alert alert-info bg-info bg-opacity-10 border-0 rounded-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Tingkat siswa akan dinaikkan (10→11, 11→12). 
                        Kelas belum diubah - lakukan redistribusi di langkah 2 & 3.
                    </div>

                    <button type="submit" class="btn btn-wa-primary btn-action w-100">
                        <i class="fas fa-arrow-up me-2"></i>Proses Kenaikan Tingkat
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card-custom">
            <div class="card-header-custom bg-success">
                <i class="fas fa-random me-2"></i>2. Redistribusi Kelas 10 → 11
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-random text-success" style="font-size: 3rem;"></i>
                </div>
                <p class="text-muted mb-3">
                    Pindahkan siswa kelas 10 ke kelas 11 (IPA/IPS/Bahasa) dengan mudah.<br>
                    <strong>Cocok untuk pembagian minat &amp; bakat!</strong>
                </p>
                <a href="redistribusi.php" class="btn btn-wa-success btn-action w-100">
                    <i class="fas fa-random me-2"></i>Buka Redistribusi Kelas
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-md-6">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-file-export me-2"></i>2. Export & Import Redistribusi
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label fw-semibold">Export Siswa per Tingkat</label>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="action" value="export_siswa">
                        <select name="tingkat_export" class="form-select" required>
                            <option value="10">Kelas 10</option>
                            <option value="11">Kelas 11</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </form>
                </div>

                <hr>

                <label class="form-label fw-semibold">Import ke Kelas Baru</label>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_kelas">
                    <div class="mb-2">
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <small class="text-muted d-block mb-3">
                        Format: NIS;NISN;Nama;Kelas Lama;Nama Kelas Baru;ID Kelas Baru<br>
                        Isi kolom "ID Kelas Baru" (kolom F) dengan ID kelas tujuan.<br>
                        Lihat tabel daftar kelas di bawah untuk referensi ID.
                    </small>
                    <button type="submit" class="btn btn-wa-primary btn-action w-100">
                        <i class="fas fa-upload me-2"></i>Import Redistribusi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-md-6">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-user-graduate me-2"></i>3. Kelulusan Siswa Kelas 12
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-graduate text-success" style="font-size: 3rem;"></i>
                </div>
                <p class="text-muted mb-3">Proses kelulusan siswa kelas 12 untuk menandai sebagai alumni</p>
                <a href="kelulusan.php" class="btn btn-success btn-action w-100" style="background: linear-gradient(135deg, #198754 0%, #1ebe57 100%); border: none;">
                    <i class="fas fa-graduation-cap me-2"></i>Buka Menu Kelulusan
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-list me-2"></i>Daftar Kelas (untuk Import)
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 250px;">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Kelas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kelas_options as $id => $nama): ?>
                            <tr>
                                <td><?= $id ?></td>
                                <td><?= htmlspecialchars($nama) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-md-12">
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-users me-2"></i>Daftar Alumni
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>Kelas Terakhir</th>
                                <th>Tahun Lulus</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $alumni = conn()->query("
                                SELECT s.nis, s.nama, k.nama_kelas, s.tahun_lulus, s.status 
                                FROM siswa s 
                                LEFT JOIN kelas k ON s.kelas_id = k.id 
                                WHERE s.status = 'alumni' 
                                ORDER BY s.tahun_lulus DESC, s.nama ASC
                            ");
                            
                            if ($alumni && $alumni->num_rows > 0):
                                while ($row = $alumni->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nis']) ?></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                <td><?= $row['tahun_lulus'] ? htmlspecialchars($row['tahun_lulus']) : '-' ?></td>
                                <td><span class="badge bg-secondary">Alumni</span></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada alumni</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

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
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
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
.step-card {
    border: none;
    border-radius: 20px;
    transition: all 0.3s ease;
    height: 100%;
}
.step-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}
.step-header {
    padding: 1.25rem 1.5rem;
    color: white;
    position: relative;
    border-radius: 20px 20px 0 0;
}
.step-header.step-1 { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
.step-header.step-2 { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.step-header.step-3 { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.step-header.step-4 { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
.step-number {
    position: absolute;
    top: -12px;
    right: 15px;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    background: white;
    color: #333;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}
.step-body {
    padding: 1.5rem;
}
.step-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin: 0 auto 1rem;
}
.step-icon.bg-purple { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
.step-icon.bg-green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.step-icon.bg-yellow { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.step-icon.bg-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
.kelas-ref-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
}
.kelas-ref-card .card-header-custom {
    background: linear-gradient(135deg, var(--wa-dark) 0%, #0d6e67 100%);
    color: white;
    padding: 1rem 1.25rem;
    border-radius: 0;
}
.alumni-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
}
.alumni-card .card-header-custom {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
}
.kelas-badge-ref {
    display: inline-block;
    background: #e0e7ff;
    color: #4338ca;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}
.action-btn {
    padding: 0.875rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}
.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
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

<div class="row g-4 mb-4">
    <div class="col-lg-3 col-6">
        <div class="stat-card shadow-sm">
            <div class="card-body text-center">
                <div class="step-icon bg-purple mb-2">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="mb-1"><?= $siswa_x_count ?></h3>
                <p class="text-muted mb-0 small">Kelas 10</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="stat-card shadow-sm">
            <div class="card-body text-center">
                <div class="step-icon bg-green mb-2">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="mb-1"><?= $siswa_xi_count ?></h3>
                <p class="text-muted mb-0 small">Kelas 11</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="stat-card shadow-sm">
            <div class="card-body text-center">
                <div class="step-icon bg-yellow mb-2">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="mb-1"><?= $siswa_xii_count ?></h3>
                <p class="text-muted mb-0 small">Kelas 12</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="stat-card shadow-sm">
            <div class="card-body text-center">
                <div class="step-icon bg-red mb-2">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3 class="mb-1"><?= $alumni->num_rows ?? 0 ?></h3>
                <p class="text-muted mb-0 small">Alumni</p>
            </div>
        </div>
    </div>
</div>

<h4 class="fw-bold mb-3"><i class="fas fa-tasks me-2"></i>Langkah-Langkah</h4>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="step-card shadow-sm">
            <div class="step-header step-1">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-arrow-up me-2"></i>Kenaikan Tingkat</h5>
                    <span class="step-number">1</span>
                </div>
            </div>
            <div class="step-body">
                <p class="text-muted mb-3">Naikkan tingkat siswa (X→XI, XI→XII). Kelas belum berubah.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="naik_tingkat">
                    <div class="mb-3">
                        <select name="tingkat_dari" class="form-select form-select-custom" required>
                            <option value="">Pilih tingkat...</option>
                            <option value="10">Kelas 10 → 11</option>
                            <option value="11">Kelas 11 → 12</option>
</select>
                        <input type="hidden" name="tingkat_ke" value="">
                    </div>
                    <script>
                        document.querySelector('select[name="tingkat_dari"]').addEventListener('change', function() {
                            this.nextElementSibling.value = parseInt(this.value) + 1;
                        });
                    </script>
                    <button type="submit" class="btn btn-wa-primary action-btn w-100">
                        <i class="fas fa-arrow-up me-2"></i>Proses Kenaikan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="step-card shadow-sm">
            <div class="step-header step-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-random me-2"></i>Redistribusi Kelas</h5>
                    <span class="step-number">2</span>
                </div>
            </div>
            <div class="step-body text-center">
                <div class="step-icon bg-green mb-3">
                    <i class="fas fa-random"></i>
                </div>
                <p class="text-muted mb-3">Pindahkan siswa ke kelas/jurusan baru (IPA/IPS/Bahasa)</p>
                <a href="redistribusi.php" class="btn btn-wa-success action-btn w-100">
                    <i class="fas fa-random me-2"></i>Buka Redistribusi
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="step-card shadow-sm">
            <div class="step-header step-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Kelulusan</h5>
                    <span class="step-number">3</span>
                </div>
            </div>
            <div class="step-body text-center">
                <div class="step-icon bg-yellow mb-3">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <p class="text-muted mb-3">Proses kelulusan siswa kelas 12 → alumni</p>
                <a href="kelulusan.php" class="btn btn-warning action-btn w-100" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; color: white;">
                    <i class="fas fa-user-graduate me-2"></i>Buka Kelulusan
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="step-card shadow-sm">
            <div class="step-header step-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-export me-2"></i>Export/Import CSV</h5>
                    <span class="step-number">4</span>
                </div>
            </div>
            <div class="step-body">
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="export_siswa">
                    <label class="form-label fw-semibold small">Export Siswa</label>
                    <div class="input-group">
                        <select name="tingkat_export" class="form-select form-select-custom" required>
                            <option value="10">Kelas 10</option>
                            <option value="11">Kelas 11</option>
                        </select>
                        <button type="submit" class="btn btn-outline-dark">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </form>
                <hr>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_kelas">
                    <label class="form-label fw-semibold small">Import ke Kelas Baru</label>
                    <input type="file" name="csv_file" class="form-control form-control-custom mb-2" accept=".csv" required>
                    <small class="text-muted d-block mb-2">Format: NIS;NISN;Nama;Kelas Lama;;ID_Kelas_Baru</small>
                    <button type="submit" class="btn btn-wa-primary action-btn w-100">
                        <i class="fas fa-upload me-2"></i>Import CSV
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="kelas-ref-card shadow-sm">
            <div class="card-header-custom">
                <i class="fas fa-building me-2"></i>Daftar Kelas (Referensi ID)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 250px;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">ID</th>
                                <th>Nama Kelas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kelas_options as $id => $nama): ?>
                            <tr>
                                <td class="text-center"><span class="kelas-badge-ref"><?= $id ?></span></td>
                                <td><?= htmlspecialchars($nama) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="alumni-card shadow-sm">
            <div class="card-header-custom">
                <i class="fas fa-user-graduate me-2"></i>Daftar Alumni
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 250px;">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th class="text-center">Tahun Lulus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $alumni = conn()->query("SELECT s.nis, s.nama, s.tahun_lulus FROM siswa s WHERE s.status = 'alumni' ORDER BY s.tahun_lulus DESC, s.nama ASC");
                            if ($alumni && $alumni->num_rows > 0):
                                while ($row = $alumni->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nis']) ?></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td class="text-center"><span class="badge bg-primary"><?= $row['tahun_lulus'] ?></span></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada alumni</td></tr>
                            <?php endif; ?>
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

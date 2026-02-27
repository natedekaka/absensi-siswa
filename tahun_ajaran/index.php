<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';
require_once '../includes/header.php';

$message = '';

// Proses tambah tahun ajaran
if (isset($_POST['tambah_tahun'])) {
    $nama = $_POST['nama'];
    $stmt = $koneksi->prepare("INSERT INTO tahun_ajaran (nama) VALUES (?)");
    $stmt->bind_param("s", $nama);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Tahun Ajaran berhasil ditambahkan!</div>';
    }
    $stmt->close();
}

// Proses set active tahun ajaran
if (isset($_POST['set_active'])) {
    $id = $_POST['id'];
    $koneksi->query("UPDATE tahun_ajaran SET is_active = 0");
    $koneksi->query("UPDATE tahun_ajaran SET is_active = 1 WHERE id = $id");
    $message = '<div class="alert alert-success">Tahun Ajaran aktif diubah!</div>';
}

// Proses hapus tahun ajaran
if (isset($_POST['hapus_tahun'])) {
    $id = $_POST['id'];
    $koneksi->query("DELETE FROM semester WHERE tahun_ajaran_id = $id");
    $koneksi->query("DELETE FROM tahun_ajaran WHERE id = $id");
    $message = '<div class="alert alert-success">Tahun Ajaran berhasil dihapus!</div>';
}

// Proses tambah semester
if (isset($_POST['tambah_semester'])) {
    $tahun_ajaran_id = $_POST['tahun_ajaran_id'];
    $semester = $_POST['semester'];
    $tgl_mulai = $_POST['tgl_mulai'];
    $tgl_selesai = $_POST['tgl_selesai'];
    $nama = "Semester $semester";
    
    $stmt = $koneksi->prepare("INSERT INTO semester (tahun_ajaran_id, semester, nama, tgl_mulai, tgl_selesai) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $tahun_ajaran_id, $semester, $nama, $tgl_mulai, $tgl_selesai);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Semester berhasil ditambahkan!</div>';
    }
    $stmt->close();
}

// Proses set active semester
if (isset($_POST['set_active_semester'])) {
    $id = $_POST['id'];
    $koneksi->query("UPDATE semester SET is_active = 0");
    $koneksi->query("UPDATE semester SET is_active = 1 WHERE id = $id");
    $message = '<div class="alert alert-success">Semester aktif diubah!</div>';
}

// Proses hapus semester
if (isset($_POST['hapus_semester'])) {
    $id = $_POST['id'];
    $koneksi->query("DELETE FROM semester WHERE id = $id");
    $message = '<div class="alert alert-success">Semester berhasil dihapus!</div>';
}

// Ambil data tahun ajaran
$tahun_ajaran = $koneksi->query("SELECT * FROM tahun_ajaran ORDER BY nama DESC");

// Ambil data semester dengan nama tahun ajaran
$semester = $koneksi->query("
    SELECT s.*, t.nama as nama_tahun 
    FROM semester s 
    JOIN tahun_ajaran t ON s.tahun_ajaran_id = t.id 
    ORDER BY t.nama DESC, s.semester ASC
");

// Ambil semester aktif untuk dropdown
$semester_aktif = $koneksi->query("SELECT * FROM semester WHERE is_active = 1 LIMIT 1")->fetch_assoc();
?>

<style>
:root {
    --wa-green: #075E54;
    --wa-light: #25D366;
    --wa-bg: #ECE5DD;
}
body {
    background-color: var(--wa-bg) !important;
}
.card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.btn-wa {
    background: var(--wa-green);
    color: white;
    border-radius: 50px;
}
.btn-wa:hover {
    background: #054c43;
    color: white;
}
.badge-active {
    background: var(--wa-light);
}
</style>

<div class="container py-4">
    <h2 class="fw-bold mb-4" style="color: var(--wa-green);">
        <i class="fas fa-calendar-alt me-2"></i>Kelola Tahun Ajaran & Semester
    </h2>
    
    <?= $message ?>
    
    <!-- Info Semester Aktif -->
    <?php if($semester_aktif): ?>
    <div class="alert alert-info mb-4">
        <strong>Semester Aktif:</strong> <?= $semester_aktif['nama'] ?> 
        (<?= $semester_aktif['tgl_mulai'] ?> s/d <?= $semester_aktif['tgl_selesai'] ?>)
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Tahun Ajaran -->
        <div class="col-md-6 mb-4">
            <div class="card p-4">
                <h5 class="fw-bold mb-3" style="color: var(--wa-green);">Tahun Ajaran</h5>
                
                <!-- Form Tambah Tahun Ajaran -->
                <form method="POST" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: 2025/2026" required>
                        <button type="submit" name="tambah_tahun" class="btn btn-wa">Tambah</button>
                    </div>
                </form>
                
                <!-- List Tahun Ajaran -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $tahun_ajaran->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td>
                                    <?php if($row['is_active']): ?>
                                    <span class="badge badge-active">Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <?php if(!$row['is_active']): ?>
                                        <button type="submit" name="set_active" class="btn btn-sm btn-outline-success">Aktifkan</button>
                                        <?php endif; ?>
                                        <button type="submit" name="hapus_tahun" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus tahun ajaran?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Semester -->
        <div class="col-md-6 mb-4">
            <div class="card p-4">
                <h5 class="fw-bold mb-3" style="color: var(--wa-green);">Semester</h5>
                
                <!-- Form Tambah Semester -->
                <form method="POST" class="mb-4">
                    <div class="mb-2">
                        <label class="form-label">Tahun Ajaran</label>
                        <select name="tahun_ajaran_id" class="form-select" required>
                            <option value="">Pilih Tahun Ajaran</option>
                            <?php 
                            $ta = $koneksi->query("SELECT * FROM tahun_ajaran ORDER BY nama DESC");
                            while($row = $ta->fetch_assoc()): 
                            ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select" required>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" name="tambah_semester" class="btn btn-wa w-100">Tambah Semester</button>
                </form>
                
                <!-- List Semester -->
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Semester</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $semester->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= $row['tgl_mulai'] ?> - <?= $row['tgl_selesai'] ?></td>
                                <td>
                                    <?php if($row['is_active']): ?>
                                    <span class="badge badge-active">Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <?php if(!$row['is_active']): ?>
                                        <button type="submit" name="set_active_semester" class="btn btn-sm btn-outline-success">Aktifkan</button>
                                        <?php endif; ?>
                                        <button type="submit" name="hapus_semester" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus semester?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

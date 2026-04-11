<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Tahun Ajaran & Semester - Sistem Absensi Siswa';

// Proses Tahun Ajaran
if (isset($_POST['tambah_tahun'])) {
    $nama = $_POST['nama'];
    $stmt = conn()->prepare("INSERT INTO tahun_ajaran (nama) VALUES (?)");
    $stmt->bind_param("s", $nama);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Tahun ajaran berhasil ditambahkan!";
    }
    $stmt->close();
}

if (isset($_POST['hapus_tahun'])) {
    $id = $_POST['id'];
    // Cek apakah ada semester
    $cek = conn()->query("SELECT COUNT(*) as total FROM semester WHERE tahun_ajaran_id = $id")->fetch_assoc();
    if ($cek['total'] > 0) {
        $_SESSION['error'] = "Hapus terlebih dahulu semester dalam tahun ajaran ini!";
    } else {
        conn()->query("DELETE FROM tahun_ajaran WHERE id = $id");
        $_SESSION['success'] = "Tahun ajaran berhasil dihapus!";
    }
}

// Proses Semester
if (isset($_POST['tambah_semester'])) {
    $tahun_ajaran_id = $_POST['tahun_ajaran_id'];
    $semester = $_POST['semester'];
    $tgl_mulai = $_POST['tgl_mulai'];
    $tgl_selesai = $_POST['tgl_selesai'];
    
    $nama = "Semester $semester";
    
    $stmt = conn()->prepare("INSERT INTO semester (tahun_ajaran_id, semester, nama, tgl_mulai, tgl_selesai) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $tahun_ajaran_id, $semester, $nama, $tgl_mulai, $tgl_selesai);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Semester berhasil ditambahkan!";
    }
    $stmt->close();
}

if (isset($_POST['aktifkan_semester'])) {
    $id = $_POST['id'];
    conn()->query("UPDATE semester SET is_active = 0");
    conn()->query("UPDATE semester SET is_active = 1 WHERE id = $id");
    
    // Update juga tahun ajaran terkait
    $semester = conn()->query("SELECT tahun_ajaran_id FROM semester WHERE id = $id")->fetch_assoc();
    if ($semester) {
        conn()->query("UPDATE tahun_ajaran SET is_active = 0");
        conn()->query("UPDATE tahun_ajaran SET is_active = 1 WHERE id = " . $semester['tahun_ajaran_id']);
    }
    $_SESSION['success'] = "Semester berhasil diaktifkan!";
}

if (isset($_POST['hapus_semester'])) {
    $id = $_POST['id'];
    conn()->query("DELETE FROM semester WHERE id = $id");
    $_SESSION['success'] = "Semester berhasil dihapus!";
}

ob_start();
?>

<style>
.tahun-card {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
}
.tahun-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
}
.tahun-card.active-ta {
    border: 2px solid var(--wa-green);
}
.tahun-header {
    background: linear-gradient(135deg, var(--wa-dark) 0%, #0d6e67 100%);
    padding: 1.5rem;
    color: white;
    position: relative;
}
.tahun-header.active {
    background: linear-gradient(135deg, var(--wa-green) 0%, #1ebe57 100%);
}
.tahun-header .badge-status {
    position: absolute;
    top: 1rem;
    right: 1rem;
}
.semester-item {
    border-left: 3px solid #ccc;
    padding: 0.75rem 1rem;
    margin: 0.5rem 0;
    background: #f8f9fa;
    border-radius: 0 10px 10px 0;
    transition: all 0.2s;
    position: relative;
}
.semester-item:hover {
    background: var(--wa-light);
}
.semester-item.active {
    border-left: 4px solid var(--wa-green);
    background: linear-gradient(90deg, rgba(37,211,102,0.15) 0%, #f8f9fa 100%);
    box-shadow: 0 2px 8px rgba(37,211,102,0.2);
}
.semester-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--wa-green);
    border-radius: 10px 0 0 10px;
}
.semester-badge-active {
    background: linear-gradient(135deg, var(--wa-green) 0%, #1ebe57 100%);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    box-shadow: 0 2px 4px rgba(37,211,102,0.3);
}
.semester-badge-inactive {
    background: #e0e0e0;
    color: #666;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
}
.btn-semester {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.add-card {
    border: 2px dashed #ccc;
    border-radius: 20px;
    background: transparent;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}
.add-card:hover {
    border-color: var(--wa-dark);
    background: rgba(18, 140, 126, 0.05);
}
.modal-header-gradient {
    background: linear-gradient(135deg, var(--wa-dark) 0%, #0d6e67 100%);
    color: white;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-calendar-alt me-2"></i>Tahun Ajaran & Semester
    </h2>
</div>

<?php
$tahun_ajaran = conn()->query("SELECT * FROM tahun_ajaran ORDER BY nama DESC");
?>

<div class="row g-4">
    <?php while ($ta = $tahun_ajaran->fetch_assoc()): ?>
    <?php
    $semester = conn()->query("SELECT * FROM semester WHERE tahun_ajaran_id = " . $ta['id'] . " ORDER BY semester ASC");
    $jml_semester = $semester->num_rows;
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="card tahun-card shadow-sm h-100 <?= $ta['is_active'] ? 'active-ta' : '' ?>">
            <div class="tahun-header <?= $ta['is_active'] ? 'active' : '' ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-school me-2"></i><?= htmlspecialchars($ta['nama']) ?>
                        </h5>
                        <small class="opacity-75"><?= $jml_semester ?> Semester</small>
                    </div>
                    <?php if ($ta['is_active']): ?>
                    <span class="badge bg-light text-success badge-status">
                        <i class="fas fa-check-circle me-1"></i>Aktif
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if ($semester && $jml_semester > 0): ?>
                    <?php while ($sm = $semester->fetch_assoc()): ?>
                    <div class="semester-item d-flex justify-content-between align-items-center <?= $sm['is_active'] ? 'active' : '' ?>">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($sm['is_active']): ?>
                                    <span class="semester-badge-active">
                                        <i class="fas fa-star"></i> AKTIF
                                    </span>
                                <?php endif; ?>
                                <div class="fw-semibold <?= $sm['is_active'] ? 'text-success' : '' ?>">
                                    <i class="fas fa-book me-1 <?= $sm['is_active'] ? 'text-success' : 'text-muted' ?>"></i>
                                    <?= htmlspecialchars($sm['nama']) ?>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-calendar me-1"></i>
                                <?= date('d M Y', strtotime($sm['tgl_mulai'])) ?> - <?= date('d M Y', strtotime($sm['tgl_selesai'])) ?>
                            </small>
                        </div>
                        <div class="d-flex gap-1 ms-2">
                            <?php if ($sm['is_active']): ?>
                                <span class="text-success" title="Semester Aktif">
                                    <i class="fas fa-check-circle fa-lg"></i>
                                </span>
                            <?php else: ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $sm['id'] ?>">
                                    <button type="submit" name="aktifkan_semester" class="btn btn-sm btn-success btn-semester" title="Aktifkan">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus semester ini?')">
                                <input type="hidden" name="id" value="<?= $sm['id'] ?>">
                                <button type="submit" name="hapus_semester" class="btn btn-sm btn-danger btn-semester" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-folder-open fa-2x mb-2"></i>
                        <p class="mb-0 small">Belum ada semester</p>
                    </div>
                <?php endif; ?>
                
                <button class="btn btn-outline-primary btn-sm w-100 mt-3" data-bs-toggle="modal" data-bs-target="#modalSemester<?= $ta['id'] ?>">
                    <i class="fas fa-plus me-1"></i> Tambah Semester
                </button>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-between">
                <small class="text-muted">Dibuat: <?= date('d/m/Y', strtotime($ta['created_at'] ?? date('Y-m-d'))) ?></small>
                <form method="POST" onsubmit="return confirm('Yakin hapus tahun ajaran <?= htmlspecialchars($ta['nama']) ?>?')">
                    <input type="hidden" name="id" value="<?= $ta['id'] ?>">
                    <button type="submit" name="hapus_tahun" class="btn btn-sm btn-link text-danger p-0" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Semester -->
    <div class="modal fade" id="modalSemester<?= $ta['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-gradient">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Tambah Semester
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="tahun_ajaran_id" value="<?= $ta['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="1">Semester 1 (Ganjil)</option>
                                <option value="2">Semester 2 (Genap)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_semester" class="btn btn-wa-primary">
                            <i class="fas fa-save me-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

    <!-- Card Tambah Tahun Ajaran -->
    <div class="col-md-6 col-lg-4">
        <button class="add-card w-100 h-100" data-bs-toggle="modal" data-bs-target="#modalTahunAjaran">
            <div class="text-center p-4">
                <i class="fas fa-plus-circle fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Tambah Tahun Ajaran</h6>
            </div>
        </button>
    </div>
</div>

<!-- Modal Tambah Tahun Ajaran -->
<div class="modal fade" id="modalTahunAjaran" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-gradient">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Tambah Tahun Ajaran
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Tahun Ajaran</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-calendar"></i></span>
                            <input type="text" name="nama" class="form-control" placeholder="2025/2026" required>
                        </div>
                        <small class="text-muted">Contoh: 2025/2026</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_tahun" class="btn btn-wa-primary">
                        <i class="fas fa-save me-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

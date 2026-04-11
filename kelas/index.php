<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Data Kelas - Sistem Absensi Siswa';

ob_start();

$kelas = conn()->query("SELECT k.*, COUNT(s.id) as total_siswa 
                        FROM kelas k 
                        LEFT JOIN siswa s ON k.id = s.kelas_id 
                        GROUP BY k.id 
                        ORDER BY k.nama_kelas");
?>

<style>
.class-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
}
.class-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}
.class-card-header {
    background: linear-gradient(135deg, var(--wa-dark) 0%, #0d6e67 100%);
    padding: 1.25rem;
    position: relative;
}
.class-card-header .kelas-icon {
    position: absolute;
    right: -10px;
    bottom: -10px;
    font-size: 4rem;
    opacity: 0.15;
    color: white;
}
.class-card-body {
    padding: 1.25rem;
}
.kelas-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 500;
}
.badge-wali {
    background: rgba(18, 140, 126, 0.1);
    color: var(--wa-dark);
}
.badge-siswa {
    background: rgba(37, 211, 102, 0.1);
    color: #1ebe57;
}
.class-card .dropdown-toggle::after {
    display: none;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="fw-bold text-wa-dark mb-0">
        <i class="fas fa-door-open me-2"></i>Data Kelas
    </h2>
    <div class="d-flex gap-2 flex-wrap">
        <a href="tambah.php" class="btn btn-wa-primary">
            <i class="fas fa-plus me-1 me-md-2"></i><span class="d-none d-md-inline">Tambah</span>
        </a>
        <a href="import.php" class="btn btn-wa-success">
            <i class="fas fa-file-import me-1 me-md-2"></i><span class="d-none d-md-inline">Import</span>
        </a>
    </div>
</div>

<div class="row g-4">
    <?php if ($kelas && $kelas->num_rows > 0): ?>
        <?php while ($row = $kelas->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card class-card h-100 shadow-sm">
                <div class="class-card-header text-white position-relative">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1 fw-bold"><?= htmlspecialchars($row['nama_kelas']) ?></h5>
                            <small class="opacity-75">Kelas</small>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light text-dark rounded-circle" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item py-2" href="edit.php?id=<?= $row['id'] ?>">
                                    <i class="fas fa-edit me-2 text-warning"></i>Edit
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="hapus.php?id=<?= $row['id'] ?>" 
                                       onclick="return confirm('Yakin hapus kelas <?= htmlspecialchars($row['nama_kelas']) ?>?')">
                                    <i class="fas fa-trash me-2"></i>Hapus
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    <i class="fas fa-school kelas-icon"></i>
                </div>
                <div class="class-card-body d-flex flex-column">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="kelas-badge badge-wali">
                            <i class="fas fa-user-tie"></i>
                            <?= htmlspecialchars($row['wali_kelas'] ?? 'Belum ada') ?>
                        </span>
                        <span class="kelas-badge badge-siswa">
                            <i class="fas fa-users"></i>
                            <?= $row['total_siswa'] ?> Siswa
                        </span>
                    </div>
                    <div class="mt-auto d-flex gap-2">
                        <a href="../siswa/?kelas_id=<?= $row['id'] ?>" class="btn btn-outline-dark flex-fill">
                            <i class="fas fa-users me-1"></i> Lihat Siswa
                        </a>
                        <a href="../absensi/?kelas_id=<?= $row['id'] ?>" class="btn btn-wa-success flex-fill">
                            <i class="fas fa-clipboard-check me-1"></i> Absensi
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card-custom p-5 text-center">
                <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Belum ada data kelas</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

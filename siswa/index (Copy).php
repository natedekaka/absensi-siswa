<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../includes/header.php';
require_once '../config.php';

// Pesan sukses WhatsApp-style
function waAlert($msg){
    echo '<div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i>' . $msg . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}
if (isset($_GET['success']))         waAlert("Data berhasil dihapus!");
if (isset($_GET['edit_success']))    waAlert("Data siswa berhasil diperbarui!");
if (isset($_GET['add_success']))     waAlert("Data siswa berhasil ditambahkan!");

$sql_kelas = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas";
$result_kelas = $koneksi->query($sql_kelas);
$kelas_list = [];
while ($row = $result_kelas->fetch_assoc()) $kelas_list[] = $row;
?>

<style>
:root {
    --wa-dark: #075E54;
    --wa-light: #25D366;
    --wa-chat: #dcf8c6;
    --wa-bg: #ECE5DD;
    --wa-card: #ffffff;
}
body {
    background-color: var(--wa-bg);
}
.alert-success {
    background: var(--wa-light);
    color: white;
    box-shadow: 0 2px 8px rgba(7, 94, 84, 0.25);
}
.table thead th {
    background: var(--wa-dark);
    color: white;
    font-weight: 600;
}
.table-hover tbody tr:hover {
    background-color: var(--wa-chat);
}
.btn-wa-primary {
    background: var(--wa-dark);
    border: none;
    color: white;
}
.btn-wa-primary:hover {
    background: #054c43;
    transform: scale(1.03);
}
.btn-wa-success {
    background: var(--wa-light);
    border: none;
    color: white;
}
.btn-wa-success:hover {
    background: #1ebe57;
}
.pagination .page-link {
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 3px;
    color: var(--wa-dark);
    border: 1px solid var(--wa-dark);
}
.pagination .page-item.active .page-link {
    background: var(--wa-light);
    border-color: var(--wa-light);
    color: white;
}
</style>

<div class="container py-3">
    <!-- Judul & Tombol Aksi -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <h2 class="fw-bold text-dark">
            <i class="fas fa-users me-2 text-success"></i>Manajemen Siswa
        </h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="tambah.php" class="btn btn-wa-primary rounded-pill px-4 shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> Tambah Siswa
            </a>
            <a href="template_siswa.csv" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fas fa-download me-1"></i> Template
            </a>
            <a href="import.php" class="btn btn-wa-success rounded-pill px-4 shadow-sm">
                <i class="fas fa-file-import me-1"></i> Import CSV
            </a>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="card rounded-3 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <!-- Pencarian -->
                <div class="col-md-6">
                    <form method="get" class="d-flex">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="cari" class="form-control border-start-0" 
                                   placeholder="Cari nama siswa..." 
                                   value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>">
                            <button class="btn btn-wa-primary" type="submit">
                                Cari
                            </button>
                        </div>
                        <?php if (isset($_GET['cari']) || isset($_GET['kelas_id'])): ?>
                            <a href="index.php" class="btn btn-outline-secondary ms-2">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Filter Kelas -->
                <div class="col-md-6">
                    <form method="get" class="d-flex flex-wrap align-items-end gap-2">
                        <label for="kelas_filter" class="form-label mb-0 fw-medium text-nowrap">Filter Kelas:</label>
                        <select class="form-select w-auto" name="kelas_id" id="kelas_filter" onchange="this.form.submit()">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($kelas_list as $kelas): ?>
                                <option value="<?= $kelas['id'] ?>" <?= (isset($_GET['kelas_id']) && $_GET['kelas_id'] == $kelas['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($_GET['kelas_id']) && $_GET['kelas_id'] !== ''): ?>
                            <a href="hapus_kelas.php?kelas_id=<?= htmlspecialchars($_GET['kelas_id']) ?>" 
                               class="btn btn-outline-danger"
                               onclick="return confirm('Hapus semua siswa di kelas ini? Tindakan tidak bisa dibatalkan.')">
                                <i class="fas fa-trash-alt me-1"></i> Hapus Kelas
                            </a>
                        <?php endif; ?>
                        <!-- Pertahankan parameter pencarian saat filter kelas -->
                        <?php if (isset($_GET['cari'])): ?>
                            <input type="hidden" name="cari" value="<?= htmlspecialchars($_GET['cari']) ?>">
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Siswa -->
    <div class="card rounded-3 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" class="text-center" style="width: 5%">No</th>
                        <th scope="col">NIS</th>
                        <th scope="col">NISN</th>
                        <th scope="col">Nama</th>
                        <th scope="col" class="text-center">Jenis Kelamin</th>
                        <th scope="col" class="text-center">Kelas</th>
                        <th scope="col" class="text-center" style="width: 15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
<?php
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$keyword = isset($_GET['cari']) ? $koneksi->real_escape_string($_GET['cari']) : '';
$kelas_id_filter = isset($_GET['kelas_id']) ? $koneksi->real_escape_string($_GET['kelas_id']) : '';

$query = "SELECT siswa.*, kelas.nama_kelas FROM siswa JOIN kelas ON siswa.kelas_id = kelas.id";
$where = [];
if ($keyword) $where[] = "siswa.nama LIKE '%$keyword%'";
if ($kelas_id_filter) $where[] = "siswa.kelas_id = '$kelas_id_filter'";
if ($where) $query .= " WHERE " . implode(' AND ', $where);

$total_rows = $koneksi->query($query)->num_rows;
$total_pages = ceil($total_rows / $limit);

$query .= " ORDER BY siswa.nama ASC LIMIT $limit OFFSET $offset";
$result = $koneksi->query($query);

$no = $offset + 1;
if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nis']) ?></td>
                        <td><?= htmlspecialchars($row['nisn']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['nama_kelas']) ?></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="edit.php?id=<?= $row['id'] ?>" 
                                   class="btn btn-sm btn-warning rounded-circle p-2" 
                                   title="Edit" data-bs-toggle="tooltip">
                                    <i class="fas fa-edit fa-sm"></i>
                                </a>
                                <a href="hapus.php?id=<?= $row['id'] ?>" 
                                   class="btn btn-sm btn-danger rounded-circle p-2" 
                                   title="Hapus" data-bs-toggle="tooltip"
                                   onclick="return confirm('Yakin hapus data ini?')">
                                    <i class="fas fa-trash fa-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
<?php endwhile; else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-user-friends fa-2x d-block mb-2" style="color: var(--wa-light);"></i>
                            <p class="mb-0">Tidak ada data siswa yang ditemukan.</p>
                        </td>
                    </tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Pagination" class="mt-4">
        <ul class="pagination justify-content-center flex-wrap">
        <?php
        $params = [];
        if ($keyword) $params['cari'] = urlencode($keyword);
        if ($kelas_id_filter) $params['kelas_id'] = urlencode($kelas_id_filter);
        $base = "index.php?" . http_build_query($params);

        $show = 5;
        $start = max(1, $page - floor($show / 2));
        $end = min($total_pages, $start + $show - 1);

        if ($page > 1) {
            echo '<li class="page-item"><a class="page-link" href="' . $base . '&page=' . ($page - 1) . '"><i class="fas fa-chevron-left"></i></a></li>';
        }

        if ($start > 1) {
            echo '<li class="page-item"><a class="page-link" href="' . $base . '&page=1">1</a></li>';
            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        for ($i = $start; $i <= $end; $i++) {
            $active = $i == $page ? 'active' : '';
            echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base . '&page=' . $i . '">' . $i . '</a></li>';
        }

        if ($end < $total_pages) {
            if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $base . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
        }

        if ($page < $total_pages) {
            echo '<li class="page-item"><a class="page-link" href="' . $base . '&page=' . ($page + 1) . '"><i class="fas fa-chevron-right"></i></a></li>';
        }
        ?>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<script>
// Aktifkan tooltip
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>

<?php require_once '../includes/footer.php'; ?>
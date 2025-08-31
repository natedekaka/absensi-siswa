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
    echo '<div class="alert wa-alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>'.$msg.'
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
:root{
    --wa-dark      : #075E54;
    --wa-light     : #25D366;
    --wa-chat      : #dcf8c6;
    --wa-bg        : #ECE5DD;
    --wa-card      : #ffffff;
}
body{ background: var(--wa-bg); }

/* Navbar & Header override (optional) */
.navbar, .sidebar{ background: var(--wa-dark) !important; }

.wa-alert-success{
    background: var(--wa-light);
    color:#fff;
    border:none;
    border-radius: .75rem;
    box-shadow:0 2px 8px rgba(7,94,84,.25);
}

.card.siswa-card{
    border:none;
    border-radius:1.25rem;
    background: var(--wa-card);
    box-shadow:0 4px 12px rgba(0,0,0,.08);
    transition:.3s;
}
.card.siswa-card:hover{
    transform: translateY(-4px);
    box-shadow:0 8px 24px rgba(7,94,84,.18);
}

.btn-wa-primary{
    background: var(--wa-dark);
    border:none;
    color:#fff;
    transition:.2s;
}
.btn-wa-primary:hover{
    background:#054c43;
    transform:scale(1.05);
}

.btn-wa-success{
    background: var(--wa-light);
    border:none;
    color:#fff;
}
.btn-wa-success:hover{ background:#1ebe57; }

.table thead th{
    background: var(--wa-dark);
    color:#fff;
    font-size:.9rem;
}
.table-hover tbody tr:hover{
    background: var(--wa-chat);
    transition:.2s;
}

.pagination .page-item .page-link{
    border-radius:50%;
    width:40px;height:40px;display:flex;align-items:center;justify-content:center;
    margin:0 4px;
    color:var(--wa-dark);
    border:1px solid var(--wa-dark);
    background:#fff;
}
.pagination .page-item.active .page-link{
    background: var(--wa-light);
    border-color:var(--wa-light);
    color:#fff;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h2 class="fw-bold" style="color:var(--wa-dark)">📚 Manajemen Siswa</h2>
    <div class="d-flex gap-2">
        <a href="tambah.php" class="btn btn-wa-primary btn-lg rounded-pill shadow">
            <i class="fas fa-plus-circle me-1"></i>Tambah Siswa
        </a>
        <a href="template_siswa.csv" class="btn btn-outline-secondary btn-lg rounded-pill shadow">
            <i class="fas fa-download me-1"></i>Template
        </a>
        <a href="import.php" class="btn btn-wa-success btn-lg rounded-pill shadow">
            <i class="fas fa-file-import me-1"></i>Import CSV
        </a>
    </div>
</div>

<!-- Filter & Pencarian -->
<div class="d-flex gap-3 mb-4 flex-wrap align-items-center justify-content-between">
    <form method="get" class="d-flex flex-grow-1">
        <div class="input-group">
            <input type="text" name="cari" class="form-control rounded-start-pill" placeholder="Cari nama siswa..."
                   value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>">
            <button class="btn btn-wa-primary rounded-end-pill" type="submit">
                <i class="fas fa-search"></i>
            </button>
            <?php if (isset($_GET['cari']) || isset($_GET['kelas_id'])): ?>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill ms-2">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <form method="get" class="d-flex align-items-center">
        <label for="kelas_filter" class="me-2 fw-bold text-nowrap" style="color:var(--wa-dark)">Filter Kelas:</label>
        <select class="form-select rounded-pill" name="kelas_id" id="kelas_filter" onchange="this.form.submit()">
            <option value="">Semua Kelas</option>
            <?php foreach ($kelas_list as $kelas): ?>
                <option value="<?= $kelas['id'] ?>" <?= (isset($_GET['kelas_id']) && $_GET['kelas_id']==$kelas['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($kelas['nama_kelas']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($_GET['kelas_id']) && $_GET['kelas_id'] !== ''): ?>
            <a href="hapus_kelas.php?kelas_id=<?= htmlspecialchars($_GET['kelas_id']) ?>" 
               class="btn btn-outline-danger rounded-pill ms-2"
               onclick="return confirm('Hapus semua siswa di kelas ini? Tindakan tidak bisa dibatalkan.')">
                <i class="fas fa-trash-alt me-1"></i>Hapus Kelas
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabel Siswa -->
<div class="card siswa-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th><th>NIS</th><th>NISN</th><th>Nama</th>
                        <th>Jenis Kelamin</th><th>Kelas</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
<?php
/* --- Pagination & Query tetap sama --- */
$limit = 20;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset= ($page-1)*$limit;

$keyword        = isset($_GET['cari']) ? $koneksi->real_escape_string($_GET['cari']) : '';
$kelas_id_filter= isset($_GET['kelas_id']) ? $koneksi->real_escape_string($_GET['kelas_id']) : '';

$query = "SELECT siswa.*, kelas.nama_kelas
          FROM siswa JOIN kelas ON siswa.kelas_id=kelas.id";
$where=[];
if($keyword)      $where[]="siswa.nama LIKE '%$keyword%'";
if($kelas_id_filter)$where[]="siswa.kelas_id='$kelas_id_filter'";
if($where) $query.=" WHERE ".implode(' AND ',$where);

$total_rows=$koneksi->query($query)->num_rows;
$total_pages=ceil($total_rows/$limit);

$query.=" ORDER BY siswa.nama ASC LIMIT $limit OFFSET $offset";
$result=$koneksi->query($query);

$no=$offset+1;
if($result->num_rows>0):
    while($row=$result->fetch_assoc()):
?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nis']) ?></td>
                        <td><?= htmlspecialchars($row['nisn']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning rounded-circle" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger rounded-circle" title="Hapus"
                                   onclick="return confirm('Yakin hapus?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
<?php endwhile; else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-search fa-2x d-block mb-2" style="color:var(--wa-light)"></i>
                            Tidak ada data siswa yang ditemukan.
                        </td>
                    </tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center flex-wrap">
    <?php
    $params=[];
    if($keyword) $params['cari']=urlencode($keyword);
    if($kelas_id_filter) $params['kelas_id']=urlencode($kelas_id_filter);

    $base="index.php?".http_build_query($params);

    $show=5;
    $start=max(1,$page-floor($show/2));
    $end=min($total_pages,$start+$show-1);

    if($page>1){
        $prev=$base."&page=".($page-1);
        echo "<li class='page-item'><a class='page-link' href='$prev'><i class='fas fa-chevron-left'></i></a></li>";
    }
    if($start>1){
        echo "<li class='page-item'><a class='page-link' href='{$base}&page=1'>1</a></li>";
        if($start>2) echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
    }
    for($i=$start;$i<=$end;$i++){
        $active=$i==$page?'active':'';
        echo "<li class='page-item $active'><a class='page-link' href='{$base}&page=$i'>$i</a></li>";
    }
    if($end<$total_pages){
        if($end<$total_pages-1) echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        echo "<li class='page-item'><a class='page-link' href='{$base}&page=$total_pages'>$total_pages</a></li>";
    }
    if($page<$total_pages){
        $next=$base."&page=".($page+1);
        echo "<li class='page-item'><a class='page-link' href='$next'><i class='fas fa-chevron-right'></i></a></li>";
    }
    ?>
    </ul>
</nav>

<?php require_once '../includes/footer.php'; ?>
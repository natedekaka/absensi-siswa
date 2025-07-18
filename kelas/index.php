<?php
session_start();
// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/header.php';
require_once '../config.php'; 

// Tampilkan pesan error jika ada
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

// Tampilkan pesan sukses jika ada
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success">Data berhasil dihapus!</div>';
}

if (isset($_GET['edit_success'])) {
    echo '<div class="alert alert-success">Data kelas berhasil diperbarui!</div>';
}

if (isset($_GET['add_success'])) {
    echo '<div class="alert alert-success">Data kelas berhasil ditambahkan!</div>';
}

// Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'asc';
$order = ($sort === 'desc') ? 'DESC' : 'ASC';

// Pagination
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Ambil total data
$totalResult = $koneksi->query("SELECT COUNT(*) AS total FROM kelas");
$totalRow = $totalResult->fetch_assoc();
$totalData = $totalRow['total'];
$totalPages = ceil($totalData / $limit);

// Ambil data sesuai halaman dan urutan
$result = $koneksi->query("SELECT * FROM kelas ORDER BY nama_kelas $order LIMIT $start, $limit");
?>

<h2>Manajemen Kelas</h2>
<a href="tambah.php" class="btn btn-success mb-3">Tambah Kelas</a>
<a href="template_kelas.csv" class="btn btn-secondary mb-3">Unduh Template</a>
<a href="import.php" class="btn btn-info mb-3">Import CSV</a>

<table class="table table-striped">
    <thead>
        <tr>
            <th>
                <a href="?sort=<?= ($sort === 'asc') ? 'desc' : 'asc' ?>&page=<?= $page ?>" class="text-decoration-none text-dark">
                    Kelas <i class="bi <?= ($sort === 'asc') ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up' ?>"></i>
                </a>
            </th>
            <th>Wali Kelas</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['nama_kelas'] ?></td>
            <td><?= $row['wali_kelas'] ?></td>
            <td>
                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Yakin hapus?')">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Pagination -->
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&sort=<?= $sort ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>

<?php require_once '../includes/footer.php'; ?>
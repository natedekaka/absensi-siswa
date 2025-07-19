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

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap @5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css " rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            margin-top: 50px;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .table thead {
            background-color: #007bff;
            color: white;
        }

        .btn-rounded {
            border-radius: 25px;
        }

        .btn i {
            margin-right: 5px;
        }

        .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }

        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="mb-4 text-center">📚 Manajemen Kelas</h2>

    <!-- Tombol Aksi -->
    <div class="d-flex justify-content-center gap-2 mb-4">
        <a href="tambah.php" class="btn btn-primary btn-rounded">
            <i class="fas fa-plus"></i> Tambah Kelas
        </a>
        <a href="template_kelas.csv" class="btn btn-secondary btn-rounded">
            <i class="fas fa-download"></i> Template
        </a>
        <a href="import.php" class="btn btn-info btn-rounded">
            <i class="fas fa-file-import"></i> Import CSV
        </a>
    </div>

    <!-- Notifikasi -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Data berhasil dihapus!</div>
    <?php elseif (isset($_GET['edit_success'])): ?>
        <div class="alert alert-success">Data kelas berhasil diperbarui!</div>
    <?php elseif (isset($_GET['add_success'])): ?>
        <div class="alert alert-success">Data kelas berhasil ditambahkan!</div>
    <?php endif; ?>

    <!-- Tabel Kelas -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center">
                    <thead>
                        <tr>
                            <th>
                                <a href="?sort=<?= ($sort === 'asc') ? 'desc' : 'asc' ?>&page=<?= $page ?>" class="text-white text-decoration-none">
                                    Kelas <i class="fas <?= ($sort === 'asc') ? 'fa-sort-alpha-down' : 'fa-sort-alpha-up-alt' ?>"></i>
                                </a>
                            </th>
                            <th>Wali Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                            <td><?= htmlspecialchars($row['wali_kelas']) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" title="Hapus"
                                   onclick="return confirm('Yakin ingin menghapus kelas ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

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
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap @5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php require_once '../includes/footer.php'; ?>
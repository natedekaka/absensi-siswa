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
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['error'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['error']);
}

// Tampilkan pesan sukses jika ada
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>Data berhasil dihapus!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}

if (isset($_GET['edit_success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>Data kelas berhasil diperbarui!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}

if (isset($_GET['add_success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>Data kelas berhasil ditambahkan!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Poppins', sans-serif;
        }

        .container {
            margin-top: 50px;
        }

        .card {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: none;
            padding: 1.5rem;
            border-top-left-radius: 1.5rem;
            border-top-right-radius: 1.5rem;
            font-weight: 600;
        }

        .btn-rounded {
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-rounded:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .table thead {
            background-color: #34495e;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .table thead th:first-child {
            border-top-left-radius: 1.5rem;
        }

        .table thead th:last-child {
            border-top-right-radius: 1.5rem;
        }

        .table-hover tbody tr:hover {
            background-color: #e9ecef;
            cursor: pointer;
            transform: scale(1.01);
            transition: transform 0.2s;
        }

        .btn-action {
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            transition: transform 0.2s ease-in-out;
        }
        
        .btn-action:hover {
            transform: scale(1.1);
        }

        .page-item .page-link {
            border-radius: 50px;
            margin: 0 4px;
            min-width: 40px;
            text-align: center;
            transition: all 0.3s;
        }

        .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.25);
        }

        .page-item .page-link:hover {
            background-color: #0056b3;
            color: white;
        }

        .alert {
            border-radius: 1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="mb-4 text-center fw-bold">📚 Manajemen Kelas</h2>

    <div class="d-flex justify-content-center flex-wrap gap-3 mb-5">
        <a href="tambah.php" class="btn btn-primary btn-rounded shadow-sm">
            <i class="fas fa-plus me-1"></i> Tambah Kelas
        </a>
        <a href="template_kelas.csv" class="btn btn-secondary btn-rounded shadow-sm">
            <i class="fas fa-download me-1"></i> Template
        </a>
        <a href="import.php" class="btn btn-info btn-rounded text-white shadow-sm">
            <i class="fas fa-file-import me-1"></i> Import CSV
        </a>
    </div>

    <div class="card p-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead class="bg-dark text-white">
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
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                <td><?= htmlspecialchars($row['wali_kelas']) ?></td>
                                <td>
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-action me-2" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-action" title="Hapus"
                                       onclick="return confirm('Yakin ingin menghapus kelas ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <i class="fas fa-exclamation-circle fa-2x d-block mb-2"></i>
                                    Belum ada data kelas yang ditambahkan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-0 pt-0 pb-3">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&sort=<?= $sort ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php require_once '../includes/footer.php'; ?>
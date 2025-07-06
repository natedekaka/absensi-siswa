<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/header.php';
require_once '../config.php'; 

// Tampilkan pesan sukses jika ada
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success">Data berhasil dihapus!</div>';
}

if (isset($_GET['edit_success'])) {
    echo '<div class="alert alert-success">Data siswa berhasil diperbarui!</div>';
}

if (isset($_GET['add_success'])) {
    echo '<div class="alert alert-success">Data siswa berhasil ditambahkan!</div>';
}
?>

<h2>Manajemen Siswa</h2>
<a href="tambah.php" class="btn btn-success mb-3">Tambah Siswa</a>
<a href="template_siswa.csv" class="btn btn-secondary mb-3">Unduh Template</a>
<a href="import.php" class="btn btn-info mb-3">Import CSV</a>

<!-- Form Pencarian -->
<form method="get" class="mb-3 d-flex gap-2">
    <div class="flex-grow-1" style="max-width: 300px;">
        <input 
            type="text" 
            name="cari" 
            class="form-control" 
            placeholder="Cari nama siswa..." 
            value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>"
        >
    </div>
    <button class="btn btn-primary" type="submit">Cari</button>
    <?php if (isset($_GET['cari'])): ?>
        <a href="index.php" class="btn btn-secondary">Reset</a>
    <?php endif; ?>
</form>

<table class="table table-striped">
    <thead>
        <tr>
            <th>NIS</th>
            <th>NISN</th>
            <th>Nama</th>
            <th>Jenis Kelamin</th>
            <th>Kelas</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Pagination config
        $limit = 20;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Keyword pencarian
        $keyword = isset($_GET['cari']) ? $koneksi->real_escape_string($_GET['cari']) : '';

        // Query dasar
        $query = "
            SELECT siswa.*, kelas.nama_kelas 
            FROM siswa 
            JOIN kelas ON siswa.kelas_id = kelas.id
        ";

        // Jika ada keyword pencarian
        if (!empty($keyword)) {
            $query .= " WHERE siswa.nama LIKE '%" . $keyword . "%'";
        }

        // Hitung total data
        $total_result = $koneksi->query($query);
        $total_rows = $total_result->num_rows;

        // Tambahkan LIMIT dan OFFSET
        $query .= " LIMIT $limit OFFSET $offset";
        $result = $koneksi->query($query);

        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= htmlspecialchars($row['nis']) ?></td>
            <td><?= htmlspecialchars($row['nisn']) ?></td>
            <td><?= htmlspecialchars($row['nama']) ?></td>
            <td><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
            <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
            <td>
                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus?')">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Pagination -->
<nav aria-label="Page navigation example">
    <ul class="pagination">
        <?php
        $total_pages = ceil($total_rows / $limit);
        for ($i = 1; $i <= $total_pages; $i++) {
            // Menambahkan parameter pencarian ke URL pagination
            $url = "?page=$i";
            if (!empty($keyword)) {
                $url .= "&cari=" . urlencode($keyword);
            }
            $active = ($i == $page) ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$i</a></li>";
        }
        ?>
    </ul>
</nav>

<?php require_once '../includes/footer.php'; ?>
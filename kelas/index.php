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
?>


<h2>Manajemen Kelas</h2>
<a href="tambah.php" class="btn btn-success mb-3">Tambah Kelas</a>
<a href="template_kelas.csv" class="btn btn-secondary mb-3">Unduh Template</a>
<a href="import.php" class="btn btn-info mb-3">Import CSV</a>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Kelas</th>
            <th>Wali Kelas</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $result = $koneksi->query("SELECT * FROM kelas");
        while($row = $result->fetch_assoc()):
        ?>
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
<?php require_once '../includes/footer.php'; ?>

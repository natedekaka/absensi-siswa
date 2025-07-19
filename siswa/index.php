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

<!-- Judul dan Tombol Aksi -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>📚 Manajemen Siswa</h2>
    <div class="d-flex gap-2">
        <a href="tambah.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i> Tambah Siswa
        </a>
        <a href="template_siswa.csv" class="btn btn-outline-secondary">
            <i class="fas fa-download me-1"></i> Template
        </a>
        <a href="import.php" class="btn btn-info text-white">
            <i class="fas fa-file-import me-1"></i> Import CSV
        </a>
    </div>
</div>

<!-- Form Pencarian -->
<form method="get" class="mb-4">
    <div class="input-group" style="max-width: 400px;">
        <input 
            type="text" 
            name="cari" 
            class="form-control" 
            placeholder="Cari nama siswa..." 
            value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>"
        >
        <button class="btn btn-primary" type="submit">
            <i class="fas fa-search"></i>
        </button>
        <?php if (isset($_GET['cari'])): ?>
            <a href="index.php" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
    </div>
</form>

<!-- Tabel Siswa -->
<div class="card shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
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

                    // Inisialisasi nomor urut
                    $no = $offset + 1;

                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nis']) ?></td>
                        <td><?= htmlspecialchars($row['nisn']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                        <td>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning me-1" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Yakin hapus?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Tidak ada data ditemukan</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center flex-wrap gap-1">
        <?php
        $total_pages = ceil($total_rows / $limit);
        $show_pages = 5; // Jumlah halaman yang ditampilkan di sekitar halaman aktif
        $start_page = max(1, $page - floor($show_pages / 2));
        $end_page = min($total_pages, $start_page + $show_pages - 1);

        // Tombol Previous
        if ($page > 1) {
            $prev_url = "?page=" . ($page - 1) . (!empty($keyword) ? "&cari=" . urlencode($keyword) : "");
            echo "<li class='page-item'><a class='page-link btn btn-outline-primary' href='$prev_url'><i class='fas fa-chevron-left'></i></a></li>";
        }

        // Tampilkan halaman pertama jika tidak termasuk di dalam range
        if ($start_page > 1) {
            echo "<li class='page-item'><a class='page-link' href='?page=1" . (!empty($keyword) ? "&cari=" . urlencode($keyword) : "") . "'>1</a></li>";
            if ($start_page > 2) echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }

        // Tampilkan halaman yang relevan
        for ($i = $start_page; $i <= $end_page; $i++) {
            $url = "?page=$i" . (!empty($keyword) ? "&cari=" . urlencode($keyword) : "");
            $active = ($i == $page) ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$i</a></li>";
        }

        // Tampilkan halaman terakhir jika tidak termasuk di dalam range
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
            echo "<li class='page-item'><a class='page-link' href='?page=$total_pages" . (!empty($keyword) ? "&cari=" . urlencode($keyword) : "") . "'>$total_pages</a></li>";
        }

        // Tombol Next
        if ($page < $total_pages) {
            $next_url = "?page=" . ($page + 1) . (!empty($keyword) ? "&cari=" . urlencode($keyword) : "");
            echo "<li class='page-item'><a class='page-link btn btn-outline-primary' href='$next_url'><i class='fas fa-chevron-right'></i></a></li>";
        }
        ?>
    </ul>
</nav>

<?php require_once '../includes/footer.php'; ?>
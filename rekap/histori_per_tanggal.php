<?php
session_start();

// Cek login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';
require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histori Kehadiran Per Tanggal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .status-hadir { color: #28a745; font-weight: bold; }
        .status-terlambat { color: #ffc107; font-weight: bold; }
        .status-sakit { color: #17a2b8; font-weight: bold; }
        .status-izin { color: #6c757d; font-weight: bold; }
        .status-alfa { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <h2>Histori Kehadiran Per Tanggal</h2>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-6">
            <label><strong>Pilih Kelas</strong></label>
            <select name="kelas_id" class="form-select" required>
                <option value="">-- Pilih Kelas --</option>
                <?php
                $stmt_kelas = $koneksi->prepare("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
                $stmt_kelas->execute();
                $result_kelas = $stmt_kelas->get_result();
                while ($row = $result_kelas->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>" <?= ($row['id'] == ($_GET['kelas_id'] ?? '')) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['nama_kelas']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label><strong>Pilih Tanggal</strong></label>
            <input type="date" name="tanggal" class="form-control" required
                   value="<?= $_GET['tanggal'] ?? date('Y-m-d') ?>">
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
            <a href="?" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <?php if (isset($_GET['kelas_id']) && isset($_GET['tanggal'])): ?>
        <?php
        $kelas_id = filter_input(INPUT_GET, 'kelas_id', FILTER_SANITIZE_NUMBER_INT);
        $tanggal = filter_input(INPUT_GET, 'tanggal', FILTER_SANITIZE_STRING);

        // Ambil nama kelas
        $stmt_kelas_nama = $koneksi->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
        $stmt_kelas_nama->bind_param("i", $kelas_id);
        $stmt_kelas_nama->execute();
        $stmt_kelas_nama->bind_result($nama_kelas);
        $stmt_kelas_nama->fetch();
        $stmt_kelas_nama->close();

        echo "<h4>Kehadiran Kelas: " . htmlspecialchars($nama_kelas) . " pada Tanggal: " . date('d M Y', strtotime($tanggal)) . "</h4>";

        // Siapkan data rekap
        $rekap = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];

        // Ambil data siswa dan status absensi
        $stmt_data = $koneksi->prepare("
            SELECT s.nama, s.jenis_kelamin, a.status 
            FROM siswa s
            LEFT JOIN absensi a ON s.id = a.siswa_id AND a.tanggal = ?
            WHERE s.kelas_id = ?
            ORDER BY s.nama
        ");
        $stmt_data->bind_param("si", $tanggal, $kelas_id);
        $stmt_data->execute();
        $result_data = $stmt_data->get_result();
        ?>

        <table id="tabel-kehadiran" class="table table-bordered table-striped table-hover mt-3">
            <thead class="table-primary">
                <tr>
                    <th>No</th>
                    <th>Nama Siswa</th>
                    <th>Jenis Kelamin</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = $result_data->fetch_assoc()):
                    $status = $row['status'] ?? 'Alfa'; // Default ke 'Alfa' jika tidak ada data
                    $status_text = htmlspecialchars($status);

                    // Tambahkan ke rekap
                    if (isset($rekap[$status_text])) {
                        $rekap[$status_text]++;
                    } else {
                        // Jika status tidak terdaftar, anggap sebagai Alfa
                        $rekap['Alfa']++;
                        $status_text = 'Alfa';
                    }
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                    <td>
                        <span class="badge 
                            <?php 
                                switch ($status_text) {
                                    case 'Hadir': echo 'bg-success'; break;
                                    case 'Terlambat': echo 'bg-warning text-dark'; break;
                                    case 'Sakit': echo 'bg-info text-dark'; break;
                                    case 'Izin': echo 'bg-secondary'; break;
                                    case 'Alfa': echo 'bg-danger'; break;
                                    default: echo 'bg-danger'; break;
                                }
                            ?>">
                            <?= $status_text ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="table-primary fw-bold">
                    <td colspan="3" class="text-end">Total</td>
                    <td>
                        Hadir: <?= $rekap['Hadir'] ?>,
                        Terlambat: <?= $rekap['Terlambat'] ?>,
                        Sakit: <?= $rekap['Sakit'] ?>,
                        Izin: <?= $rekap['Izin'] ?>,
                        Alfa: <?= $rekap['Alfa'] ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <?php
        $params = http_build_query(['kelas_id' => $kelas_id, 'tanggal' => $tanggal]);
        echo "<a href='cetak_per_tanggal.php?$params' class='btn btn-success mt-3' target='_blank'>
                <i class='bi bi-printer'></i> Cetak Harian
              </a>";
        ?>

    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#tabel-kehadiran').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>
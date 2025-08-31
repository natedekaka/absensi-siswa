<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';
require_once '../includes/header.php';
?>
<h2>Rekap Absensi Per Kelas</h2>

<!-- Form Input dengan Rentang Tanggal -->
<form method="GET" class="row g-3 mb-4">
    <div class="col-md-4">
        <label>Kelas</label>
        <select name="kelas_id" class="form-select" required>
            <?php
            $stmt_kelas = $koneksi->prepare("SELECT id, nama_kelas FROM kelas");
            $stmt_kelas->execute();
            $result_kelas = $stmt_kelas->get_result();

            while($row = $result_kelas->fetch_assoc()):
            ?>
            <option value="<?= $row['id'] ?>" <?= ($row['id'] == ($_GET['kelas_id'] ?? '')) ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['nama_kelas']) ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label>Tanggal Awal</label>
        <input type="date" name="tgl_awal" class="form-control" required
               value="<?= $_GET['tgl_awal'] ?? date('Y-m-01') ?>">
    </div>
    <div class="col-md-3">
        <label>Tanggal Akhir</label>
        <input type="date" name="tgl_akhir" class="form-control" required
               value="<?= $_GET['tgl_akhir'] ?? date('Y-m-t') ?>">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </div>
</form>

<?php
if (isset($_GET['kelas_id'])) {
    // Validasi input
    $kelas_id = filter_input(INPUT_GET, 'kelas_id', FILTER_VALIDATE_INT);
    $tgl_awal = filter_input(INPUT_GET, 'tgl_awal', FILTER_SANITIZE_STRING);
    $tgl_akhir = filter_input(INPUT_GET, 'tgl_akhir', FILTER_SANITIZE_STRING);

    // Validasi tanggal
    if (!$kelas_id || !strtotime($tgl_awal) || !strtotime($tgl_akhir)) {
        echo "<div class='alert alert-danger'>Input tidak valid. Pastikan kelas dan tanggal diisi dengan benar.</div>";
        exit;
    }

    // Pastikan tgl_awal <= tgl_akhir
    if (strtotime($tgl_awal) > strtotime($tgl_akhir)) {
        echo "<div class='alert alert-danger'>Tanggal awal tidak boleh lebih besar dari tanggal akhir.</div>";
        exit;
    }

    // Ambil data kelas
    $stmt_kelas = $koneksi->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt_kelas->bind_param("i", $kelas_id);
    $stmt_kelas->execute();
    $stmt_kelas->bind_result($nama_kelas);
    $stmt_kelas->fetch();
    $stmt_kelas->close();

    echo "<h4>Rekap Kelas: " . htmlspecialchars($nama_kelas) . "</h4>";
    echo "<p><strong>Periode:</strong> " . date('d M Y', strtotime($tgl_awal)) . " s.d. " . date('d M Y', strtotime($tgl_akhir)) . "</p>";

    // Inisialisasi rekap total untuk kelas
    $rekapKelas = [
        'Hadir' => 0,
        'Terlambat' => 0,
        'Sakit' => 0,
        'Izin' => 0,
        'Alfa' => 0
    ];

    echo "<table class='table table-bordered mt-3'>";
    echo "<thead>
            <tr>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>Jenis Kelamin</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alfa</th>
                <th>Total</th>
            </tr>
          </thead>";
    echo "<tbody>";

    // Ambil siswa di kelas tertentu
    $stmt_siswa = $koneksi->prepare("SELECT id, nama, jenis_kelamin FROM siswa WHERE kelas_id = ?");
    $stmt_siswa->bind_param("i", $kelas_id);
    $stmt_siswa->execute();
    $result_siswa = $stmt_siswa->get_result();

    $no = 1;

    while($row = $result_siswa->fetch_assoc()) {
        $siswa_id = $row['id'];

        // Ambil absensi per siswa berdasarkan rentang tanggal
        $stmt_absen = $koneksi->prepare("
            SELECT status FROM absensi 
            WHERE siswa_id = ? 
              AND tanggal BETWEEN ? AND ?
        ");
        $stmt_absen->bind_param("iss", $siswa_id, $tgl_awal, $tgl_akhir);
        $stmt_absen->execute();
        $result_absen = $stmt_absen->get_result();

        $rekap = [
            'Hadir' => 0,
            'Terlambat' => 0,
            'Sakit' => 0,
            'Izin' => 0,
            'Alfa' => 0
        ];

        while($absen = $result_absen->fetch_assoc()) {
            $status = $absen['status'];
            if (isset($rekap[$status])) {
                $rekap[$status]++;
                $rekapKelas[$status]++;
            }
        }

        echo "<tr>";
        echo "<td>{$no}</td>";
        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
        echo "<td>" . htmlspecialchars($row['jenis_kelamin']) . "</td>";
        echo "<td>{$rekap['Hadir']}</td>";
        echo "<td>{$rekap['Terlambat']}</td>";
        echo "<td>{$rekap['Sakit']}</td>";
        echo "<td>{$rekap['Izin']}</td>";
        echo "<td>{$rekap['Alfa']}</td>";
        echo "<td>" . array_sum($rekap) . "</td>";
        echo "</tr>";

        $no++;

        $stmt_absen->close();
    }
    $stmt_siswa->close();

    // Baris total per status untuk seluruh kelas
    echo "<tr class='table-primary fw-bold'>";
    echo "<td></td>";
    echo "<td>Total</td>";
    echo "<td></td>";
    echo "<td>{$rekapKelas['Hadir']}</td>";
    echo "<td>{$rekapKelas['Terlambat']}</td>";
    echo "<td>{$rekapKelas['Sakit']}</td>";
    echo "<td>{$rekapKelas['Izin']}</td>";
    echo "<td>{$rekapKelas['Alfa']}</td>";
    echo "<td>" . array_sum($rekapKelas) . "</td>";
    echo "</tr>";

    echo "</tbody></table>";

    // Grafik Pie
    echo '<div class="chart-container" style="height:400px; margin-top:20px;">';
    echo '<canvas id="chart-kelas"></canvas>';
    echo '</div>';

    // Script grafik
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctx = document.getElementById("chart-kelas").getContext("2d");
        var myChart = new Chart(ctx, {
            type: "pie",
            data: {
                labels: ["Hadir", "Terlambat", "Sakit", "Izin", "Alfa"],
                datasets: [{
                    data: [' . 
                        $rekapKelas['Hadir'] . ',' . 
                        $rekapKelas['Terlambat'] . ',' . 
                        $rekapKelas['Sakit'] . ',' . 
                        $rekapKelas['Izin'] . ',' . 
                        $rekapKelas['Alfa'] . 
                    '],
                    backgroundColor: [
                        "#4CAF50", "#FFC107", "#2196F3", "#9C27B0", "#F44336"
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: "Persentase Kehadiran Kelas (' . htmlspecialchars($nama_kelas) . ')",
                        font: { size: 16 }
                    },
                    legend: { position: "right" },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var total = context.dataset.data.reduce((a,b)=>a+b,0);
                                var value = context.raw;
                                var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return context.label + ": " + value + " (" + percentage + "%)";
                            }
                        }
                    }
                }
            }
        });
    });
    </script>';

    // Tombol Cetak (dengan rentang tanggal)
    echo "<a href='cetak.php?type=kelas&id=$kelas_id&tgl_awal=$tgl_awal&tgl_akhir=$tgl_akhir' class='btn btn-success mt-3' target='_blank'>Cetak Rekap</a>";
}
?>

<?php require_once '../includes/footer.php'; ?>
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

<!-- Form Input -->
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
        <label>Bulan</label>
        <select name="bulan" class="form-select" required>
            <?php for($i=1; $i<=12; $i++): ?>
            <option value="<?= $i ?>" <?= ($i == ($_GET['bulan'] ?? date('n'))) ? 'selected' : '' ?>>
                <?= date('F', mktime(0,0,0,$i,1)) ?>
            </option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label>Tahun</label>
        <input type="number" name="tahun" class="form-control"
               value="<?= $_GET['tahun'] ?? date('Y') ?>" required>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </div>
</form>

<?php
if (isset($_GET['kelas_id'])) {
    // Validasi input
    $kelas_id = filter_input(INPUT_GET, 'kelas_id', FILTER_VALIDATE_INT);
    $bulan = filter_input(INPUT_GET, 'bulan', FILTER_VALIDATE_INT);
    $tahun = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT);

    if (!$kelas_id || !$bulan || !$tahun) {
        echo "<div class='alert alert-danger'>Input tidak valid.</div>";
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
    echo "<p>Periode: " . date('F Y', mktime(0,0,0,$bulan,1,$tahun)) . "</p>";

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

    $no = 1; // Mulai penomoran dari 1

    while($row = $result_siswa->fetch_assoc()) {
        $siswa_id = $row['id'];

        // Ambil absensi per siswa
        $stmt_absen = $koneksi->prepare("SELECT status FROM absensi WHERE siswa_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
        $stmt_absen->bind_param("iii", $siswa_id, $bulan, $tahun);
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
            $rekap[$status]++;
            $rekapKelas[$status]++;
        }

        echo "<tr>";
        echo "<td>{$no}</td>"; // Kolom nomor
        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
        echo "<td>" . htmlspecialchars($row['jenis_kelamin']) . "</td>";
        echo "<td>{$rekap['Hadir']}</td>";
        echo "<td>{$rekap['Terlambat']}</td>";
        echo "<td>{$rekap['Sakit']}</td>";
        echo "<td>{$rekap['Izin']}</td>";
        echo "<td>{$rekap['Alfa']}</td>";
        echo "<td>" . array_sum($rekap) . "</td>";
        echo "</tr>";

        $no++; // Tambah nomor setiap baris

        $stmt_absen->close();
    }
    $stmt_siswa->close();

    // Baris total per status untuk seluruh kelas
    echo "<tr class='table-primary fw-bold'>";
    echo "<td></td>"; // Biarkan kosong
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
    echo '<div class="chart-container">';
    echo '<canvas id="chart-kelas"></canvas>';
    echo '</div>';

    // Script grafik
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
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: "Persentase Kehadiran Kelas" },
                    legend: { position: "right" },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var total = context.dataset.data.reduce((a,b)=>a+b,0);
                                return context.label + ": " + context.raw + " (" + Math.round((context.raw/total)*100) + "%)";
                            }
                        }
                    }
                }
            }
        });
    });
    </script>';

    echo "<a href='cetak.php?type=kelas&id=$kelas_id&bulan=$bulan&tahun=$tahun' class='btn btn-success' target='_blank'>Cetak</a>";
}
?>
<?php require_once '../includes/footer.php'; ?>
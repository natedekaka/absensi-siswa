<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';

// Pastikan parameter type ada
if (!isset($_GET['type'])) {
    die("Parameter type tidak valid");
}

$type = $_GET['type'];

// Untuk rekap siswa, mode mungkin tidak ada (default bulan)
$mode = $_GET['mode'] ?? 'bulan';

// Header untuk cetak - harus di atas semua output
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=rekap_absensi_$type.xls");

// Fungsi validasi tanggal
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Ambil rentang tanggal jika mode rentang
if ($mode == 'rentang') {
    $dari = $_GET['dari'] ?? '';
    $sampai = $_GET['sampai'] ?? '';

    if (empty($dari) || empty($sampai)) {
        die("Tanggal 'dari' dan 'sampai' harus diisi untuk mode rentang.");
    }

    if (!isValidDate($dari) || !isValidDate($sampai)) {
        die("Format tanggal tidak valid. Gunakan format Y-m-d.");
    }

    if (strtotime($dari) > strtotime($sampai)) {
        die("Tanggal 'dari' tidak boleh lebih besar dari 'sampai'.");
    }

    $periode = date('d M Y', strtotime($dari)) . ' - ' . date('d M Y', strtotime($sampai));
}

if ($type == 'siswa') {
    if (!isset($_GET['id'])) {
        die("Parameter id tidak valid untuk rekap siswa");
    }
    
    $id = intval($_GET['id']);
    
    // Query siswa
    $stmt = $koneksi->prepare("SELECT siswa.*, kelas.nama_kelas FROM siswa JOIN kelas ON siswa.kelas_id = kelas.id WHERE siswa.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $siswa = $result->fetch_assoc();
    $stmt->close();

    if (!$siswa) {
        die("Siswa dengan ID $id tidak ditemukan.");
    }

    // Default rekap
    $rekap = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];

    if ($mode == 'bulan') {
        $bulan = intval($_GET['bulan'] ?? date('n'));
        $tahun = intval($_GET['tahun'] ?? date('Y'));
        
        $stmt = $koneksi->prepare("SELECT status FROM absensi WHERE siswa_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
        $stmt->bind_param("iii", $id, $bulan, $tahun);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($absen = $result->fetch_assoc()) {
            if (isset($rekap[$absen['status']])) {
                $rekap[$absen['status']]++;
            }
        }
        $stmt->close();
        
        $periode = date('F Y', mktime(0,0,0,$bulan,1,$tahun));
        
    } elseif ($mode == 'rentang') {
        $stmt = $koneksi->prepare("SELECT status FROM absensi WHERE siswa_id = ? AND tanggal BETWEEN ? AND ?");
        $stmt->bind_param("iss", $id, $dari, $sampai);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($absen = $result->fetch_assoc()) {
            if (isset($rekap[$absen['status']])) {
                $rekap[$absen['status']]++;
            }
        }
        $stmt->close();
        
    } elseif ($mode == 'semester') {
        $semester = $_GET['semester'] ?? '1';
        $tahun_ajaran = $_GET['tahun_ajaran'] ?? date('Y') . '/' . (date('Y')+1);
        list($tahun_awal, $tahun_akhir) = explode('/', $tahun_ajaran);
        
        if ($semester == '1') {
            $dari = $tahun_awal . '-07-01';
            $sampai = $tahun_awal . '-12-31';
        } else {
            $dari = $tahun_akhir . '-01-01';
            $sampai = $tahun_akhir . '-06-30';
        }
        
        $stmt = $koneksi->prepare("SELECT status FROM absensi WHERE siswa_id = ? AND tanggal BETWEEN ? AND ?");
        $stmt->bind_param("iss", $id, $dari, $sampai);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($absen = $result->fetch_assoc()) {
            if (isset($rekap[$absen['status']])) {
                $rekap[$absen['status']]++;
            }
        }
        $stmt->close();
        
        $periode = 'Semester ' . $semester . ' TA ' . $tahun_ajaran;
    }

    // Tampilkan informasi siswa
    $jenis_kelamin = isset($siswa['jenis_kelamin']) ? $siswa['jenis_kelamin'] : '-';
    $jenis_kelamin = in_array($jenis_kelamin, ['Laki-laki', 'Perempuan']) ? $jenis_kelamin : 'Tidak Diketahui';

    echo "<h3>Rekap Absensi: {$siswa['nama']} ({$siswa['nama_kelas']})</h3>";
    echo "<p>Jenis Kelamin: $jenis_kelamin</p>";
    echo "<p>Periode: $periode</p>";
    
    // Tabel jumlah kehadiran
    echo "<table border='1'>";
    echo "<tr><th>Hadir</th><th>Terlambat</th><th>Sakit</th><th>Izin</th><th>Alfa</th><th>Total</th></tr>";
    echo "<tr>";
    echo "<td>{$rekap['Hadir']}</td>";
    echo "<td>{$rekap['Terlambat']}</td>";
    echo "<td>{$rekap['Sakit']}</td>";
    echo "<td>{$rekap['Izin']}</td>";
    echo "<td>{$rekap['Alfa']}</td>";
    echo "<td>" . array_sum($rekap) . "</td>";
    echo "</tr></table>";

    // Tabel persentase kehadiran
    echo "<h4>Persentase Kehadiran</h4>";
    echo "<table border='1'>";
    echo "<tr><th>Status</th><th>Jumlah</th><th>Persentase</th></tr>";

    $total = array_sum($rekap);
    foreach ($rekap as $status => $jumlah) {
        $persentase = ($total > 0) ? round(($jumlah / $total) * 100, 2) : 0;
        echo "<tr>";
        echo "<td>$status</td>";
        echo "<td>$jumlah</td>";
        echo "<td>$persentase%</td>";
        echo "</tr>";
    }
    echo "</table>";

} elseif ($type == 'kelas') {
    if (!isset($_GET['id'])) {
        die("Parameter id tidak valid untuk rekap kelas");
    }
    
    $kelas_id = intval($_GET['id']);

    // Ambil data kelas
    $stmt = $koneksi->prepare("SELECT * FROM kelas WHERE id = ?");
    $stmt->bind_param("i", $kelas_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $kelas = $result->fetch_assoc();
    $stmt->close();

    if (!$kelas) {
        die("Kelas dengan ID $kelas_id tidak ditemukan.");
    }

    // Inisialisasi rekap kelas
    $rekapKelas = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];

    if ($mode == 'bulan') {
        $bulan = intval($_GET['bulan'] ?? date('n'));
        $tahun = intval($_GET['tahun'] ?? date('Y'));
        $periode = date('F Y', mktime(0,0,0,$bulan,1,$tahun));

        $stmt = $koneksi->prepare("SELECT * FROM siswa WHERE kelas_id = ?");
        $stmt->bind_param("i", $kelas_id);
        $stmt->execute();
        $siswaResult = $stmt->get_result();

        echo "<h3>Rekap Kelas: {$kelas['nama_kelas']}</h3>";
        echo "<p>Wali Kelas: " . (!empty($kelas['wali_kelas']) ? $kelas['wali_kelas'] : '-') . "</p>";
        echo "<p>Periode: $periode</p>";
        
        echo "<table border='1'>";
        echo "<thead><tr><th>No</th><th>Nama Siswa</th><th>Jenis Kelamin</th><th>Hadir</th><th>Terlambat</th><th>Sakit</th><th>Izin</th><th>Alfa</th><th>Total</th></tr></thead>";
        echo "<tbody>";

        $no = 1;
        while ($row = $siswaResult->fetch_assoc()) {
            $absensiStmt = $koneksi->prepare("SELECT status FROM absensi WHERE siswa_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
            $absensiStmt->bind_param("iii", $row['id'], $bulan, $tahun);
            $absensiStmt->execute();
            $absensiResult = $absensiStmt->get_result();

            $rekap = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];
            while ($absen = $absensiResult->fetch_assoc()) {
                if (isset($rekap[$absen['status']])) {
                    $rekap[$absen['status']]++;
                    $rekapKelas[$absen['status']]++;
                }
            }
            $absensiStmt->close();

            $jenis_kelamin = in_array($row['jenis_kelamin'], ['Laki-laki', 'Perempuan']) ? $row['jenis_kelamin'] : 'Tidak Diketahui';

            echo "<tr>";
            echo "<td>{$no}</td>";
            echo "<td>{$row['nama']}</td>";
            echo "<td>{$jenis_kelamin}</td>";
            echo "<td>{$rekap['Hadir']}</td>";
            echo "<td>{$rekap['Terlambat']}</td>";
            echo "<td>{$rekap['Sakit']}</td>";
            echo "<td>{$rekap['Izin']}</td>";
            echo "<td>{$rekap['Alfa']}</td>";
            echo "<td>" . array_sum($rekap) . "</td>";
            echo "</tr>";

            $no++;
        }
        $stmt->close();

        // Total kelas
        echo "<tr style='background-color: #e0e0e0; font-weight: bold;'>";
        echo "<td colspan='3' align='center'>Total</td>";
        echo "<td>{$rekapKelas['Hadir']}</td>";
        echo "<td>{$rekapKelas['Terlambat']}</td>";
        echo "<td>{$rekapKelas['Sakit']}</td>";
        echo "<td>{$rekapKelas['Izin']}</td>";
        echo "<td>{$rekapKelas['Alfa']}</td>";
        echo "<td>" . array_sum($rekapKelas) . "</td>";
        echo "</tr>";
        echo "</tbody></table>";

    } elseif ($mode == 'rentang') {
        echo "<h3>Rekap Kelas: {$kelas['nama_kelas']}</h3>";
        echo "<p>Wali Kelas: " . (!empty($kelas['wali_kelas']) ? $kelas['wali_kelas'] : '-') . "</p>";
        echo "<p>Periode: $periode</p>";

        $stmt = $koneksi->prepare("SELECT * FROM siswa WHERE kelas_id = ?");
        $stmt->bind_param("i", $kelas_id);
        $stmt->execute();
        $siswaResult = $stmt->get_result();

        echo "<table border='1'>";
        echo "<thead><tr><th>No</th><th>Nama Siswa</th><th>Jenis Kelamin</th><th>Hadir</th><th>Terlambat</th><th>Sakit</th><th>Izin</th><th>Alfa</th><th>Total</th></tr></thead>";
        echo "<tbody>";

        $no = 1;
        while ($row = $siswaResult->fetch_assoc()) {
            $absensiStmt = $koneksi->prepare("SELECT status FROM absensi WHERE siswa_id = ? AND tanggal BETWEEN ? AND ?");
            $absensiStmt->bind_param("iss", $row['id'], $dari, $sampai);
            $absensiStmt->execute();
            $absensiResult = $absensiStmt->get_result();

            $rekap = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];
            while ($absen = $absensiResult->fetch_assoc()) {
                if (isset($rekap[$absen['status']])) {
                    $rekap[$absen['status']]++;
                    $rekapKelas[$absen['status']]++;
                }
            }
            $absensiStmt->close();

            $jenis_kelamin = in_array($row['jenis_kelamin'], ['Laki-laki', 'Perempuan']) ? $row['jenis_kelamin'] : 'Tidak Diketahui';

            echo "<tr>";
            echo "<td>{$no}</td>";
            echo "<td>{$row['nama']}</td>";
            echo "<td>{$jenis_kelamin}</td>";
            echo "<td>{$rekap['Hadir']}</td>";
            echo "<td>{$rekap['Terlambat']}</td>";
            echo "<td>{$rekap['Sakit']}</td>";
            echo "<td>{$rekap['Izin']}</td>";
            echo "<td>{$rekap['Alfa']}</td>";
            echo "<td>" . array_sum($rekap) . "</td>";
            echo "</tr>";

            $no++;
        }
        $stmt->close();

        // Total kelas
        echo "<tr style='background-color: #e0e0e0; font-weight: bold;'>";
        echo "<td colspan='3' align='center'>Total</td>";
        echo "<td>{$rekapKelas['Hadir']}</td>";
        echo "<td>{$rekapKelas['Terlambat']}</td>";
        echo "<td>{$rekapKelas['Sakit']}</td>";
        echo "<td>{$rekapKelas['Izin']}</td>";
        echo "<td>{$rekapKelas['Alfa']}</td>";
        echo "<td>" . array_sum($rekapKelas) . "</td>";
        echo "</tr>";
        echo "</tbody></table>";
    }

    // Tabel persentase untuk kelas
    echo "<h4>Persentase Kehadiran Kelas</h4>";
    echo "<table border='1'>";
    echo "<tr><th>Status</th><th>Jumlah</th><th>Persentase</th></tr>";

    $totalKelas = array_sum($rekapKelas);
    foreach ($rekapKelas as $status => $jumlah) {
        $persentase = ($totalKelas > 0) ? round(($jumlah / $totalKelas) * 100, 2) : 0;
        echo "<tr>";
        echo "<td>$status</td>";
        echo "<td>$jumlah</td>";
        echo "<td>$persentase%</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
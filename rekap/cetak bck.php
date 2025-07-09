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

if ($type == 'siswa') {
    if (!isset($_GET['id'])) {
        die("Parameter id tidak valid untuk rekap siswa");
    }
    
    $id = $_GET['id'];
    
    // Query siswa - pastikan kolom jenis_kelamin tersedia
    $siswa = $koneksi->query("
        SELECT siswa.*, kelas.nama_kelas 
        FROM siswa 
        JOIN kelas ON siswa.kelas_id = kelas.id 
        WHERE siswa.id = $id
    ")->fetch_assoc();

    // Jika data siswa tidak ditemukan
    if (!$siswa) {
        die("Siswa dengan ID $id tidak ditemukan.");
    }

    // Jika jenis_kelamin tidak ada di tabel
    if (!isset($siswa['jenis_kelamin'])) {
        die("Kolom jenis_kelamin tidak ditemukan dalam tabel siswa.");
    }

    // Proses berdasarkan mode
    if ($mode == 'bulan') {
        $bulan = $_GET['bulan'] ?? date('n');
        $tahun = $_GET['tahun'] ?? date('Y');
        
        $absensi = $koneksi->query("SELECT * FROM absensi 
                                   WHERE siswa_id = $id 
                                   AND MONTH(tanggal) = $bulan 
                                   AND YEAR(tanggal) = $tahun");
        
        $rekap = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];
        
        while($absen = $absensi->fetch_assoc()) {
            $rekap[$absen['status']]++;
        }
        
        $periode = date('F Y', mktime(0,0,0,$bulan,1,$tahun));
        
    } elseif ($mode == 'rentang') {
        $dari = $_GET['dari'] ?? date('Y-m-01');
        $sampai = $_GET['sampai'] ?? date('Y-m-t');
        
        $absensi = $koneksi->query("SELECT status FROM absensi 
                                   WHERE siswa_id = $id 
                                   AND tanggal BETWEEN '$dari' AND '$sampai'");
        
        $rekap = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];
        
        while($absen = $absensi->fetch_assoc()) {
            $rekap[$absen['status']]++;
        }
        
        $periode = date('d M Y', strtotime($dari)) . ' - ' . date('d M Y', strtotime($sampai));
        
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
        
        $absensi = $koneksi->query("SELECT status FROM absensi 
                                   WHERE siswa_id = $id 
                                   AND tanggal BETWEEN '$dari' AND '$sampai'");
        
        $rekap = ['Hadir' => 0, 'Terlambat' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];
        
        while($absen = $absensi->fetch_assoc()) {
            $rekap[$absen['status']]++;
        }
        
        $periode = 'Semester ' . $semester . ' TA ' . $tahun_ajaran;
    }

    // Tampilkan informasi siswa
    $jenis_kelamin = isset($siswa['jenis_kelamin']) ? $siswa['jenis_kelamin'] : '-';
    $jenis_kelamin = ($jenis_kelamin == 'Laki-laki') ? 'Laki-laki' : (($jenis_kelamin == 'Perempuan') ? 'Perempuan' : 'Perempuan');

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
    
    $kelas_id = $_GET['id'];
    $bulan = $_GET['bulan'] ?? date('n');
    $tahun = $_GET['tahun'] ?? date('Y');
    
    $kelas = $koneksi->query("SELECT * FROM kelas WHERE id = $kelas_id")->fetch_assoc();
    echo "<h3>Rekap Kelas: {$kelas['nama_kelas']}</h3>";
    echo "<p>Periode: " . date('F Y', mktime(0,0,0,$bulan,1,$tahun)) . "</p>";
    
    $siswa = $koneksi->query("SELECT * FROM siswa WHERE kelas_id = $kelas_id");
    
    // Inisialisasi rekap total untuk kelas
    $rekapKelas = [
        'Hadir' => 0,
        'Terlambat' => 0,
        'Sakit' => 0,
        'Izin' => 0,
        'Alfa' => 0
    ];
    
    echo "<table border='1'>";
    echo "<thead><tr><th>Nama Siswa</th><th>Jenis Kelamin</th><th>Hadir</th><th>Terlambat</th><th>Sakit</th><th>Izin</th><th>Alfa</th><th>Total</th></tr></thead>";
    echo "<tbody>";
    
    while($row = $siswa->fetch_assoc()) {
        $absensi = $koneksi->query("SELECT status FROM absensi 
                                  WHERE siswa_id = {$row['id']} 
                                  AND MONTH(tanggal) = $bulan 
                                  AND YEAR(tanggal) = $tahun");
        
        $rekap = [
            'Hadir' => 0,
            'Terlambat' => 0,
            'Sakit' => 0,
            'Izin' => 0,
            'Alfa' => 0
        ];
        
        while($absen = $absensi->fetch_assoc()) {
            $status = $absen['status'];
            $rekap[$status]++;
            $rekapKelas[$status]++; // Tambahkan ke total kelas
        }

        // Tampilkan jenis kelamin dengan aman
        $jenis_kelamin = isset($row['jenis_kelamin']) ? $row['jenis_kelamin'] : '-';
        $jenis_kelamin = ($jenis_kelamin == 'Laki-laki') ? 'Laki-laki' : (($jenis_kelamin == 'Perempuan') ? 'Perempuan' : 'Perempuan');

        echo "<tr>";
        echo "<td>{$row['nama']}</td>";
        echo "<td>$jenis_kelamin</td>";
        echo "<td>{$rekap['Hadir']}</td>";
        echo "<td>{$rekap['Terlambat']}</td>";
        echo "<td>{$rekap['Sakit']}</td>";
        echo "<td>{$rekap['Izin']}</td>";
        echo "<td>{$rekap['Alfa']}</td>";
        echo "<td>" . array_sum($rekap) . "</td>";
        echo "</tr>";
    }
    
    // Baris total per status untuk seluruh kelas
    echo "<tr style='background-color: #e0e0e0; font-weight: bold;'>";
    echo "<td colspan='2'>Total</td>";
    echo "<td>{$rekapKelas['Hadir']}</td>";
    echo "<td>{$rekapKelas['Terlambat']}</td>";
    echo "<td>{$rekapKelas['Sakit']}</td>";
    echo "<td>{$rekapKelas['Izin']}</td>";
    echo "<td>{$rekapKelas['Alfa']}</td>";
    echo "<td>" . array_sum($rekapKelas) . "</td>";
    echo "</tr>";
    
    echo "</tbody></table>";
    
    // Tambahkan tabel persentase untuk kelas
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
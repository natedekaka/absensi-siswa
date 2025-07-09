<?php
require_once '../config.php';

$kelas_id = $_GET['kelas_id'];
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Modifikasi query untuk menangani semua kelas
if ($kelas_id === 'all') {
    $query = "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id = k.id ORDER BY k.nama_kelas, s.nama";
    $result = $koneksi->query($query);
} else {
    $query = "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.kelas_id = $kelas_id ORDER BY s.nama";
    $result = $koneksi->query($query);
}

if ($result->num_rows > 0) {
    echo '<table class="table table-bordered">';
    
    // Header tabel - bedakan antara semua kelas dan per kelas
    if ($kelas_id === 'all') {
        echo '<thead><tr><th>No.</th><th>Kelas</th><th>Nama Siswa</th><th>Hadir</th><th>Terlambat</th><th>Sakit</th><th>Izin</th><th>Alfa</th><th>Status Sebelumnya</th></tr></thead>';
    } else {
        echo '<thead><tr><th>No.</th><th>Nama Siswa</th><th>Hadir</th><th>Terlambat</th><th>Sakit</th><th>Izin</th><th>Alfa</th><th>Status Sebelumnya</th></tr></thead>';
    }
    
    echo '<tbody>';

    $no = 1;
    
    while($row = $result->fetch_assoc()) {
        $status_sebelumnya = '';
        
        // Cek status sebelumnya jika ada
        $check = $koneksi->prepare("SELECT status FROM absensi WHERE siswa_id = ? AND tanggal = ?");
        $check->bind_param("is", $row['id'], $tanggal);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $check->bind_result($status_sebelumnya);
            $check->fetch();
        }
        
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        
        // Tampilkan kolom kelas hanya untuk semua kelas
        if ($kelas_id === 'all') {
            echo '<td>' . $row['nama_kelas'] . '</td>';
        }
        
        echo '<td>' . $row['nama'] . '<input type="hidden" name="siswa_id[]" value="' . $row['id'] . '"></td>';
        
        $hadir_checked = ($status_sebelumnya === '') ? 'checked' : '';
        
        echo '<td><input type="radio" name="status[' . $row['id'] . ']" value="Hadir" ' . 
             (($status_sebelumnya == 'Hadir') ? 'checked' : $hadir_checked) . ' required></td>';
             
        echo '<td><input type="radio" name="status[' . $row['id'] . ']" value="Terlambat" ' . 
             ($status_sebelumnya == 'Terlambat' ? 'checked' : '') . '></td>';
             
        echo '<td><input type="radio" name="status[' . $row['id'] . ']" value="Sakit" ' . 
             ($status_sebelumnya == 'Sakit' ? 'checked' : '') . '></td>';
             
        echo '<td><input type="radio" name="status[' . $row['id'] . ']" value="Izin" ' . 
             ($status_sebelumnya == 'Izin' ? 'checked' : '') . '></td>';
             
        echo '<td><input type="radio" name="status[' . $row['id'] . ']" value="Alfa" ' . 
             ($status_sebelumnya == 'Alfa' ? 'checked' : '') . '></td>';
             
        echo '<td>' . ($status_sebelumnya ? $status_sebelumnya : '-') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-warning">Tidak ada siswa ditemukan</div>';
}
?>
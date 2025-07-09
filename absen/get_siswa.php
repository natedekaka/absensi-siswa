<?php
require_once '../config.php';

$kelas_id = $_GET['kelas_id'];
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Modifikasi query untuk menangani semua kelas
if ($kelas_id === 'all') {
    $query = "SELECT * FROM siswa ORDER BY kelas_id, nama";
    $result = $koneksi->query($query);
} else {
    $query = "SELECT * FROM siswa WHERE kelas_id = $kelas_id ORDER BY nama";
    $result = $koneksi->query($query);
}

if ($result->num_rows > 0) {
    echo '<table class="table table-bordered">';
    echo '<thead><tr><th>No.</th><th>Kelas</th><th>Nama Siswa</th><th>Hadir</th><th>Terlambat</th><th>Sakit</th><th>Izin</th><th>Alfa</th><th>Status Sebelumnya</th></tr></thead>';
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
        
        // Ambil nama kelas jika menampilkan semua kelas
        $nama_kelas = '';
        if ($kelas_id === 'all') {
            $kelas_query = $koneksi->query("SELECT nama_kelas FROM kelas WHERE id = ".$row['kelas_id']);
            $kelas_data = $kelas_query->fetch_assoc();
            $nama_kelas = $kelas_data['nama_kelas'];
        }
        
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        
        // Tampilkan kolom kelas jika menampilkan semua kelas
        if ($kelas_id === 'all') {
            echo '<td>' . $nama_kelas . '</td>';
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
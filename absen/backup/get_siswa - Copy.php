<?php
require_once '../config.php';

$kelas_id = $_GET['kelas_id'];
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$result = $koneksi->query("SELECT * FROM siswa WHERE kelas_id = $kelas_id");

if ($result->num_rows > 0) {
    echo '<table class="table table-bordered">';
    echo '<thead><tr><th>Nama Siswa</th><th>Hadir</th><th>Terlambat</th><th>Sakit</th><th>Izin</th><th>Alfa</th><th>Status Sebelumnya</th></tr></thead>';
    echo '<tbody>';
    
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
        echo '<td>' . $row['nama'] . '<input type="hidden" name="siswa_id[]" value="' . $row['id'] . '"></td>';
        
        // Status Hadir di-checked secara default jika tidak ada status sebelumnya
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
    echo '<div class="alert alert-warning">Tidak ada siswa di kelas ini</div>';
}
?>
<?php
require_once '../config.php';

$search = isset($_GET['search']) ? $koneksi->real_escape_string($_GET['search']) : '';

if (!empty($search)) {
    $query = "SELECT s.id, s.nama, k.nama_kelas 
              FROM siswa s 
              JOIN kelas k ON s.kelas_id = k.id 
              WHERE s.nama LIKE '%$search%' 
              ORDER BY s.nama LIMIT 10";

    $result = $koneksi->query($query);

    if ($result->num_rows > 0) {
        echo '<ul class="list-group mb-3">';
        while ($row = $result->fetch_assoc()) {
            echo '<li class="list-group-item" style="cursor:pointer;" onclick="pilihSiswa(' . $row['id'] . ', \'' . addslashes($row['nama']) . '\')">';
            echo $row['nama'] . ' (' . $row['nama_kelas'] . ')';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<div class="alert alert-warning">Tidak ada siswa ditemukan</div>';
    }
} else {
    echo '';
}
?>
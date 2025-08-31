<?php
require_once '../config.php';

$kelas_id = $_GET['kelas_id'];
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$search = isset($_GET['search']) ? $koneksi->real_escape_string($_GET['search']) : '';

// Query: Gabungkan siswa + kelas + rekap historis
$query = "
    SELECT 
        s.*,
        k.nama_kelas,
        COALESCE(rekap.hadir, 0) AS total_hadir,
        COALESCE(rekap.terlambat, 0) AS total_terlambat,
        COALESCE(rekap.sakit, 0) AS total_sakit,
        COALESCE(rekap.izin, 0) AS total_izin,
        COALESCE(rekap.alfa, 0) AS total_alfa
    FROM siswa s
    JOIN kelas k ON s.kelas_id = k.id
    LEFT JOIN (
        SELECT 
            siswa_id,
            SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN status = 'Terlambat' THEN 1 ELSE 0 END) AS terlambat,
            SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) AS izin,
            SUM(CASE WHEN status = 'Alfa' THEN 1 ELSE 0 END) AS alfa
        FROM absensi
        GROUP BY siswa_id
    ) rekap ON s.id = rekap.siswa_id
";

// Filter kelas
if ($kelas_id === 'all') {
    if (!empty($search)) {
        $query .= " WHERE s.nama LIKE '%$search%'";
    }
    $query .= " ORDER BY k.nama_kelas, s.nama";
} else {
    $query .= " WHERE s.kelas_id = " . (int)$kelas_id;
    if (!empty($search)) {
        $query .= " AND s.nama LIKE '%$search%'";
    }
    $query .= " ORDER BY s.nama";
}

$result = $koneksi->query($query);

if ($result->num_rows > 0) {
    echo '<table class="table table-bordered table-sm">';
    
    // Header tabel
    if ($kelas_id === 'all') {
        echo '<thead>
                <tr>
                    <th>No.</th>
                    <th>Kelas</th>
                    <th>Nama Siswa</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Sakit</th>
                    <th>Izin</th>
                    <th>Alfa</th>
                    <th>Rekap Historis</th>
                    <th>Status Sebelumnya</th>
                </tr>
              </thead>';
    } else {
        echo '<thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Siswa</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Sakit</th>
                    <th>Izin</th>
                    <th>Alfa</th>
                    <th>Rekap Historis</th>
                    <th>Status Sebelumnya</th>
                </tr>
              </thead>';
    }
    
    echo '<tbody>';
    $no = 1;
    
    while ($row = $result->fetch_assoc()) {
        $status_sebelumnya = '';
        
        // Cek status hari ini
        $check = $koneksi->prepare("SELECT status FROM absensi WHERE siswa_id = ? AND tanggal = ?");
        $check->bind_param("is", $row['id'], $tanggal);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $check->bind_result($status_sebelumnya);
            $check->fetch();
        }
        $check->close();

        $hadir_checked = ($status_sebelumnya === '') ? 'checked' : '';
        
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        
        if ($kelas_id === 'all') {
            echo '<td>' . htmlspecialchars($row['nama_kelas']) . '</td>';
        }
        
        echo '<td>' . htmlspecialchars($row['nama']) . '
              <input type="hidden" name="siswa_id[]" value="' . $row['id'] . '"></td>';
        
        // Radio Hadir
        echo '<td><input type="radio" name="status[' . $row['id'] . ']" value="Hadir" ' . 
             (($status_sebelumnya == 'Hadir') ? 'checked' : $hadir_checked) . ' required></td>';
        
        // Radio Terlambat
        echo '<td><input type="radio" name="status[' . $row['id'] . ']" value="Terlambat" ' . 
             ($status_sebelumnya == 'Terlambat' ? 'checked' : '') . '></td>';
        
        // Radio Sakit
        echo '<td><input type="radio" name="status[' . $row['id'] . ']" value="Sakit" ' . 
             ($status_sebelumnya == 'Sakit' ? 'checked' : '') . '></td>';
        
        // Radio Izin
        echo '<td><input type="radio" name="status[' . $row['id'] . ']" value="Izin" ' . 
             ($status_sebelumnya == 'Izin' ? 'checked' : '') . '></td>';
        
        // Radio Alfa
        echo '<td><input type="radio" name="status[' . $row['id'] . ']" value="Alfa" ' . 
             ($status_sebelumnya == 'Alfa' ? 'checked' : '') . '></td>';

        // 🔹 Kolom Rekap Historis (Hanya Tampilan)
        echo '<td class="text-center" style="font-size: 0.9em; color: #666;">
                <small>
                    H:' . $row['total_hadir'] . ' |
                    T:' . $row['total_terlambat'] . ' |
                    S:' . $row['total_sakit'] . ' |
                    I:' . $row['total_izin'] . ' |
                    A:' . $row['total_alfa'] . '
                </small>
              </td>';

        // Status Sebelumnya
        echo '<td>' . ($status_sebelumnya ? $status_sebelumnya : '-') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-warning">Tidak ada siswa ditemukan</div>';
}
?>
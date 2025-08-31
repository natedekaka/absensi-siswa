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
    // Tambahkan CSS Inline agar tampilan lebih menarik tanpa file eksternal
    echo '
   <style>
        /* ===== WhatsApp Color Palette ===== */
        :root{
            --wa-dark   : #075E54;
            --wa-light  : #25D366;
            --wa-chat   : #dcf8c6;
            --wa-bg     : #ECE5DD;
            --wa-white  : #ffffff;
            --wa-shadow : 0 2px 8px rgba(7,94,84,.15);
        }

        /* Tampilkan tabel seperti chat bubble */
        .table.table-bordered{
            border:none;
            border-radius:12px;
            overflow:hidden;
            box-shadow:var(--wa-shadow);
            background:var(--wa-white);
        }

        /* Header tabel */
        .table-primary th{
            background:var(--wa-dark) !important;
            color:#fff !important;
            border:none;
            font-size:.9rem;
            vertical-align:middle !important;
        }

        /* Hover baris */
        .table-hover tbody tr:hover{
            background:var(--wa-chat) !important;
            transition:.2s;
        }

        /* Sel & kolom */
        .table th, .table td{
            vertical-align:middle !important;
            text-align:center;
        }
        .table th:first-child, .table td:first-child{
            text-align:center;
        }
        .table th:nth-child(3), .table td:nth-child(3){
            text-align:left !important;
            padding-left:15px;
        }

        /* Badge rekap (bubble) */
        .rekap-historis{
            font-size:.75rem;
            font-family:monospace;
            font-weight:600;
            color:#333;
            background:#f1f1f1;
            border:1px solid #ddd;
            border-radius:10px;
            padding:3px 6px;
            display:inline-block;
        }

        /* Label status */
        .status-label{
            font-size:.75rem;
            color:#fff;
            padding:4px 10px;
            border-radius:12px;
            min-width:60px;
            display:inline-block;
        }
        .status-hadir    {background:var(--wa-light);}
        .status-terlambat{background:#ffb142;}
        .status-sakit    {background:#778ca3;}
        .status-izin     {background:#2ed573;}
        .status-alfa     {background:#ff5252;}
        .status-kosong   {background:#aaa;}

        /* Radio button WhatsApp style */
        .form-check-input{
            width:18px;
            height:18px;
            accent-color:var(--wa-light);
            cursor:pointer;
        }
        .form-check-input:checked{
            background:var(--wa-light);
            border-color:var(--wa-light);
        }
    </style>';

    echo '<table class="table table-bordered table-hover table-sm shadow-sm">';
    
    // Header tabel
    if ($kelas_id === 'all') {
        echo '<thead class="table-primary">
                <tr>
                    <th>No.</th>
                    <th>Kelas</th>
                    <th>Nama Siswa</th>
                    <th><span class="status-label status-hadir">Hadir</span></th>
                    <th><span class="status-label status-terlambat">Terlambat</span></th>
                    <th><span class="status-label status-sakit">Sakit</span></th>
                    <th><span class="status-label status-izin">Izin</span></th>
                    <th><span class="status-label status-alfa">Alfa</span></th>
                    <th>📊 Rekap Historis</th>
                    <th>Status Sebelumnya</th>
                </tr>
              </thead>';
    } else {
        echo '<thead class="table-primary">
                <tr>
                    <th>No.</th>
                    <th>Nama Siswa</th>
                    <th><span class="status-label status-hadir">Hadir</span></th>
                    <th><span class="status-label status-terlambat">Terlambat</span></th>
                    <th><span class="status-label status-sakit">Sakit</span></th>
                    <th><span class="status-label status-izin">Izin</span></th>
                    <th><span class="status-label status-alfa">Alfa</span></th>
                    <th>📊 Rekap Historis</th>
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
        $status_class = strtolower($status_sebelumnya) ?: 'kosong';
        ?>
        <tr>
            <td><?= $no++ ?></td>
            
            <?php if ($kelas_id === 'all'): ?>
                <td class="text-secondary fw-bold"><?= htmlspecialchars($row['nama_kelas']) ?></td>
            <?php endif; ?>
            
            <td class="text-start">
                <strong><?= htmlspecialchars($row['nama']) ?></strong>
                <input type="hidden" name="siswa_id[]" value="<?= $row['id'] ?>">
            </td>
            
            <!-- Hadir -->
            <td>
                <input type="radio" name="status[<?= $row['id'] ?>]" value="Hadir" 
                       <?= ($status_sebelumnya == 'Hadir') ? 'checked' : $hadir_checked ?> required>
            </td>
            
            <!-- Terlambat -->
            <td>
                <input type="radio" name="status[<?= $row['id'] ?>]" value="Terlambat" 
                       <?= ($status_sebelumnya == 'Terlambat') ? 'checked' : '' ?>>
            </td>
            
            <!-- Sakit -->
            <td>
                <input type="radio" name="status[<?= $row['id'] ?>]" value="Sakit" 
                       <?= ($status_sebelumnya == 'Sakit') ? 'checked' : '' ?>>
            </td>
            
            <!-- Izin -->
            <td>
                <input type="radio" name="status[<?= $row['id'] ?>]" value="Izin" 
                       <?= ($status_sebelumnya == 'Izin') ? 'checked' : '' ?>>
            </td>
            
            <!-- Alfa -->
            <td>
                <input type="radio" name="status[<?= $row['id'] ?>]" value="Alfa" 
                       <?= ($status_sebelumnya == 'Alfa') ? 'checked' : '' ?>>
            </td>

            <!-- 🔹 Rekap Historis - Cantik dan Rata Tengah -->
            <td class="align-middle">
                <div class="rekap-historis">
                    H:<?= (int)$row['total_hadir'] ?> | 
                    T:<?= (int)$row['total_terlambat'] ?> | 
                    S:<?= (int)$row['total_sakit'] ?> | 
                    I:<?= (int)$row['total_izin'] ?> | 
                    A:<?= (int)$row['total_alfa'] ?>
                </div>
            </td>

            <!-- Status Sebelumnya dengan Label Warna -->
            <td>
                <?php if ($status_sebelumnya): ?>
                    <span class="status-label status-<?= $status_class ?>">
                        <?= $status_sebelumnya ?>
                    </span>
                <?php else: ?>
                    <span class="status-label status-kosong">-</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
    
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-info text-center py-4">
            <i class="fas fa-user-slash" style="font-size: 2em;"></i><br>
            <strong>Tidak ada siswa ditemukan</strong><br>
            Coba pilih kelas lain atau periksa pencarian.
          </div>';
}
?>
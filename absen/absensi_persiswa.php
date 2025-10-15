<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Siswa</title>
    <!-- Gaya sederhana, bisa Anda kembangkan -->
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f4; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        h1 { color: #333; }
        input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
        input[type="submit"] { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
        input[type="submit"]:hover { background-color: #218838; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; color: white; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<div class="container">
    <h1>Absensi Harian</h1>
    <p>Masukkan NIS Siswa</p>

    <?php
    // Tampilkan pesan sukses atau error
    if (isset($_GET['success'])) {
        echo '<div class="alert alert-success">Absensi berhasil dicatat!</div>';
    }
    if (isset($_GET['error'])) {
        if ($_GET['error'] == 'nis_not_found') {
            echo '<div class="alert alert-error">Error: NIS tidak ditemukan. Silakan coba lagi.</div>';
        } else {
            echo '<div class="alert alert-error">Terjadi kesalahan. Silakan coba lagi.</div>';
        }
    }
    ?>

    <form action="proses_persiswa.php" method="POST">
        <input type="text" name="nis" id="nis_input" placeholder="Scan atau ketik NIS di sini..." required autofocus>
        <input type="submit" value="Absen Sekarang">
    </form>
</div>

<!-- Script untuk fokus otomatis ke input NIS -->
<script>
    // Ketika halaman dimuat, fokuskan kursor ke input NIS
    window.onload = function() {
        document.getElementById('nis_input').focus();
    };
</script>

</body>
</html>
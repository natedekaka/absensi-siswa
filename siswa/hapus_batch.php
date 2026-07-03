<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';

require_role('admin', 'guru', 'wali_kelas');

if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
    $_SESSION['error'] = "Token keamanan tidak valid!";
    header('Location: index.php');
    exit;
}

if (!isset($_POST['selected']) || !is_array($_POST['selected']) || count($_POST['selected']) == 0) {
    $_SESSION['error'] = "Tidak ada siswa yang dipilih!";
    header('Location: index.php');
    exit;
}

$selected = $_POST['selected'];
$count = count($selected);

if (isset($_POST['confirm_delete'])) {
    $success = 0;
    $failed = 0;
    
    foreach ($selected as $siswa_id) {
        $id = intval($siswa_id);
        conn()->query("DELETE FROM absensi WHERE siswa_id = $id");
        conn()->query("DELETE FROM absensi_mapel WHERE siswa_id = $id");
        
        $stmt = conn()->prepare("DELETE FROM siswa WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success++;
        } else {
            $failed++;
        }
    }
    
    if ($failed > 0) {
        $_SESSION['error'] = "$success siswa berhasil dihapus, $failed gagal.";
    } else {
        $_SESSION['success'] = "$success siswa berhasil dihapus!";
    }
    
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Hapus Massal - Sistem Absensi Siswa</title>
    <link href="../assets/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; }
    </style>
</head>
<body>
    <div class="card-modern max-w-lg mx-auto p-8 text-center">
        <i class="fas fa-exclamation-triangle text-6xl text-amber-500 mb-4"></i>
        <h3 class="text-xl font-bold text-gray-800">Konfirmasi Hapus</h3>
        <p class="text-gray-500 mt-2">Anda yakin ingin menghapus <strong><?php echo $count; ?></strong> siswa terpilih?</p>
        <p class="text-red-500 text-sm"><small>Tindakan ini tidak dapat dibatalkan!</small></p>
        
        <form method="POST" action="" class="mt-6">
            <?php echo csrf_field(); ?>
            <?php foreach ($selected as $id): ?>
                <input type="hidden" name="selected[]" value="<?php echo intval($id); ?>">
            <?php endforeach; ?>
            
            <div class="flex gap-3 justify-center">
                <a href="index.php" class="btn-modern btn-neutral-modern px-6">Batal</a>
                <button type="submit" name="confirm_delete" value="1" class="btn-modern bg-red-500 hover:bg-red-600 text-white px-6 rounded-xl font-semibold transition-all">
                    <i class="fas fa-trash mr-2"></i>Ya, Hapus Semua
                </button>
            </div>
        </form>
    </div>
</body>
</html>

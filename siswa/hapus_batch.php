<?php
session_start();

require_once '../core/init.php';
require_once '../core/Database.php';

auth();

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .confirm-card { max-width: 500px; margin: 100px auto; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirm-card card">
            <div class="card-body text-center p-5">
                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 64px;"></i>
                <h3 class="mt-3">Konfirmasi Hapus</h3>
                <p class="text-muted">Anda yakin ingin menghapus <strong><?php echo $count; ?></strong> siswa terpilih?</p>
                <p class="text-danger"><small>Tindakan ini tidak dapat dibatalkan!</small></p>
                
                <form method="POST" action="">
                    <?php echo csrf_field(); ?>
                    <?php foreach ($selected as $id): ?>
                        <input type="hidden" name="selected[]" value="<?php echo intval($id); ?>">
                    <?php endforeach; ?>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                        <a href="index.php" class="btn btn-secondary px-4">Batal</a>
                        <button type="submit" name="confirm_delete" value="1" class="btn btn-danger px-4">
                            <i class="fas fa-trash me-2"></i>Ya, Hapus Semua
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

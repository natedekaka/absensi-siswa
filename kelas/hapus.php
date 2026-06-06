<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$has_siswa = $row['total'] > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    if ($has_siswa) {
        $_SESSION['error'] = "Kelas tidak bisa dihapus karena masih memiliki siswa.";
        header("Location: index.php");
        exit;
    }
    
    $stmt = conn()->prepare("DELETE FROM kelas WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Kelas berhasil dihapus!";
        header("Location: index.php");
        exit;
    } else {
        $error = "Error: " . $stmt->error;
    }
}

$kelas = conn()->query("SELECT k.*, COUNT(s.id) as total_siswa FROM kelas k LEFT JOIN siswa s ON k.id = s.kelas_id WHERE k.id = $id GROUP BY k.id")->fetch_assoc();

if (!$kelas) {
    header("Location: index.php");
    exit;
}

$title = 'Konfirmasi Hapus Kelas';
ob_start();
?>

<div class="max-w-lg mx-auto my-16">
    <div class="card-modern overflow-hidden text-center">
        <div class="pt-10 pb-6 px-8">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center mx-auto mb-5 shadow-lg">
                <i class="fas fa-exclamation-triangle text-3xl text-white"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Hapus Kelas</h3>
            <p class="text-gray-400 mt-1">Apakah Anda yakin ingin menghapus kelas ini?</p>
        </div>
        
        <div class="px-8 pb-2">
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl mb-4 text-left">
                <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg shrink-0">
                    <i class="fas fa-door-open"></i>
                </div>
                <div>
                    <div class="font-semibold text-gray-800"><?= htmlspecialchars($kelas['nama_kelas']) ?></div>
                    <div class="text-sm text-gray-400">
                        Wali: <?= htmlspecialchars($kelas['wali_kelas'] ?? '-') ?> &middot; <?= $kelas['total_siswa'] ?> siswa
                    </div>
                </div>
            </div>
            
            <?php if ($has_siswa): ?>
                <div class="flex items-center gap-2 p-3.5 bg-red-50 text-red-700 rounded-xl text-sm mb-4">
                    <i class="fas fa-times-circle shrink-0"></i>
                    <span>Tidak bisa dihapus! Masih ada <strong><?= $kelas['total_siswa'] ?></strong> siswa dalam kelas ini.</span>
                </div>
            <?php else: ?>
                <div class="flex items-center gap-2 p-3.5 bg-yellow-50 text-yellow-800 rounded-xl text-sm mb-6">
                    <i class="fas fa-exclamation-circle shrink-0"></i>
                    <span>Data yang dihapus tidak dapat dikembalikan!</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert-modern alert-danger-modern mb-4 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="flex gap-3">
                <a href="index.php" class="btn-modern btn-neutral-modern flex-1 justify-center">
                    <i class="fas fa-times mr-2"></i>Kembali
                </a>
                <?php if (!$has_siswa): ?>
                <button type="submit" name="confirm" value="1" class="btn-modern btn-danger-modern flex-1 justify-center">
                    <i class="fas fa-trash mr-2"></i>Ya, Hapus
                </button>
                <?php endif; ?>
            </form>
        </div>
        <div class="h-4"></div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

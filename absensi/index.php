<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../core/init.php';
require_once '../core/Database.php';

$title = 'Input Absensi - Sistem Absensi Siswa';

ob_start();
?>

<div class="flex items-center mb-6">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-clipboard-check mr-3 text-primary"></i>Input Absensi Harian
    </h2>
</div>

<form id="form-absensi">
    <?= csrf_field() ?>
    <input type="hidden" name="kelas_id" id="kelas_id">
    <input type="hidden" name="semester_id" id="semester_id">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card-modern p-4">
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2 block">
                <i class="fas fa-calendar-alt mr-2"></i>Tanggal
            </label>
            <input type="date" name="tanggal" id="tanggal" class="form-input-modern" 
                   value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="card-modern p-4">
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2 block">
                <i class="fas fa-graduation-cap mr-2"></i>Semester
            </label>
            <select id="semester" name="semester_id" class="form-select-modern" required>
                <option value="">Pilih Semester</option>
                <?php
                $semester = conn()->query("SELECT * FROM semester ORDER BY is_active DESC, tahun_ajaran_id DESC, semester ASC");
                while ($row = $semester->fetch_assoc()):
                    $selected = $row['is_active'] ? 'selected' : '';
                ?>
                <option value="<?= $row['id'] ?>" <?= $selected ?>><?= htmlspecialchars($row['nama']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="card-modern p-4">
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2 block">
                <i class="fas fa-door-open mr-2"></i>Kelas
            </label>
            <select id="kelas" class="form-select-modern" required>
                <option value="">Pilih Kelas</option>
                <option value="all">Semua Kelas</option>
                <?php
                $kelas = conn()->query("SELECT * FROM kelas ORDER BY nama_kelas");
                while ($row = $kelas->fetch_assoc()):
                ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kelas']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <div class="mb-6 hidden" id="searchContainer" style="display: none;">
        <div class="relative max-w-md">
            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" id="search_nama" class="form-input-modern pl-10" 
                   placeholder="Cari nama siswa...">
        </div>
    </div>

    <div id="tombolSimpanAtas" class="mb-6 hidden" style="display: none;">
        <button type="submit" class="btn-modern btn-primary-modern">
            <i class="fas fa-save mr-2"></i>Simpan Absensi
        </button>
    </div>

    <div id="siswa-container" class="mb-6"></div>

    <div id="tombolSimpanBawah" class="text-center hidden" style="display: none;">
        <button type="submit" class="btn-modern btn-primary-modern btn-lg-modern px-8">
            <i class="fas fa-save mr-2"></i>Simpan Semua Absensi
        </button>
    </div>
</form>

<?php
$content = ob_get_clean();

$scripts = "<script>
window.onload = function() {
    document.getElementById('form-absensi').addEventListener('submit', function(e) {
        e.preventDefault();
        simpanAbsensi(e);
        return false;
    });
};

function toggleElements(show) {
    const display = show ? 'block' : 'none';
    document.getElementById('tombolSimpanAtas').style.display = display;
    document.getElementById('tombolSimpanBawah').style.display = display;
    document.getElementById('searchContainer').style.display = display;
}

document.getElementById('semester').addEventListener('change', function() {
    document.getElementById('semester_id').value = this.value;
    loadSiswa();
});

document.getElementById('kelas').addEventListener('change', function() {
    const kelasId = this.value;
    document.getElementById('kelas_id').value = kelasId;
    if (kelasId) {
        toggleElements(true);
        loadSiswa();
    } else {
        toggleElements(false);
        document.getElementById('siswa-container').innerHTML = '';
    }
});

document.getElementById('tanggal').addEventListener('change', loadSiswa);
document.getElementById('search_nama').addEventListener('input', loadSiswa);

function loadSiswa() {
    const kelasId = document.getElementById('kelas').value;
    const tanggal = document.getElementById('tanggal').value;
    const semesterId = document.getElementById('semester').value;
    const search = document.getElementById('search_nama').value;

    if (kelasId && semesterId) {
        let url = 'get_siswa.php?kelas_id=' + encodeURIComponent(kelasId) + 
                  '&tanggal=' + encodeURIComponent(tanggal) + 
                  '&semester_id=' + encodeURIComponent(semesterId);
        if (search) url += '&search=' + encodeURIComponent(search);

        fetch(url)
            .then(response => response.text())
            .then(data => {
                document.getElementById('siswa-container').innerHTML = data;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('siswa-container').innerHTML = '<div class=\"alert alert-danger\">Gagal memuat data siswa.</div>';
            });
    } else if (kelasId) {
        document.getElementById('siswa-container').innerHTML = '<div class=\"alert alert-warning\">Pilih semester terlebih dahulu!</div>';
    }
}

function simpanAbsensi(e) {
    e.preventDefault();
    
    const form = document.getElementById('form-absensi');
    const submitBtn = document.querySelector('#tombolSimpanBawah button');
    const originalText = submitBtn.innerHTML;
    
    const formData = new FormData(form);
    const csrfToken = formData.get('csrf_token');
    const tanggal = document.getElementById('tanggal').value;
    const semesterId = document.getElementById('semester').value;
    
    const statuses = {};
    const radioButtons = document.querySelectorAll('input[type=\"radio\"]:checked');
    for (let i = 0; i < radioButtons.length; i++) {
        const radio = radioButtons[i];
        if (radio.name.indexOf('status[') === 0) {
            const match = radio.name.match(/status\[(\d+)\]/);
            if (match) {
                statuses[match[1]] = radio.value;
            }
        }
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin me-2\"></i>Menyimpan...';
    
    fetch('proses.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            csrf_token: csrfToken,
            tanggal: tanggal,
            semester_id: semesterId,
            status: statuses
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert(data.message);
                loadSiswa();
            } else {
                alert(data.message);
            }
        } catch(e) {
            console.error('JSON parse error:', e);
            alert('Respons server tidak valid: ' + text.substring(0, 200));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyimpan! Error: ' + error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}
</script>";

require_once '../views/layout.php';

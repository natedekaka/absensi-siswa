<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin', 'guru', 'wali_kelas');

$title = 'Absensi Mapel - Sistem Absensi Siswa';

$uid = (int)($_SESSION['user']['id'] ?? 0);

// For non-admin, get their mapel assignments per kelas: kelas_id => [mapel_id, ...]
$guru_mapel_assignments = [];
if (!has_role('admin')) {
    $q = conn()->query("SELECT kelas_id, mapel_id FROM guru_kelas WHERE user_id = $uid AND mapel_id IS NOT NULL");
    while ($r = $q->fetch_assoc()) {
        $guru_mapel_assignments[$r['kelas_id']][] = (int)$r['mapel_id'];
    }
}

ob_start();
?>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-book-open mr-3 text-indigo-600"></i>Absensi Mata Pelajaran
    </h2>
    <div class="flex items-center gap-2">
        <span id="autoSaveStatus" class="text-xs text-gray-400 hidden">
            <i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...
        </span>
    </div>
</div>

<form id="form-absensi-mapel">
    <?= csrf_field() ?>
    <input type="hidden" name="kelas_id" id="kelas_id">
    <input type="hidden" name="semester_id" id="semester_id">
    <input type="hidden" name="mapel_id" id="mapel_id">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
            <select id="semester" class="form-select-modern" required>
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
                <?php
                if (has_role('admin')) {
                    $kelas = conn()->query("SELECT * FROM kelas ORDER BY nama_kelas");
                } else {
                    $kelas = conn()->query("
                        SELECT DISTINCT k.* FROM kelas k
                        INNER JOIN guru_kelas gk ON gk.kelas_id = k.id
                        WHERE gk.user_id = $uid AND gk.mapel_id IS NOT NULL
                        ORDER BY k.nama_kelas
                    ");
                }
                while ($row = $kelas->fetch_assoc()):
                ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kelas']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="card-modern p-4">
            <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2 block">
                <i class="fas fa-book mr-2"></i>Mata Pelajaran
            </label>
            <select id="mapel" class="form-select-modern" required>
                <option value="">Pilih Mapel</option>
            </select>
        </div>
    </div>

    <div class="mb-6 hidden" id="searchContainer" style="display: none;">
        <div class="relative max-w-md">
            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" id="search_nama" class="form-input-modern form-input-icon"
                   placeholder="Cari nama siswa...">
        </div>
    </div>

    <div id="tombolSimpanAtas" class="mb-6 hidden" style="display: none;">
        <button type="submit" class="btn-modern btn-primary-modern" style="background:#7C3AED;">
            <i class="fas fa-save mr-2"></i>Simpan Absensi Mapel
        </button>
    </div>

    <div id="siswa-container" class="mb-6"></div>

    <div id="tombolSimpanBawah" class="text-center hidden" style="display: none;">
        <button type="submit" class="btn-modern btn-primary-modern btn-lg-modern px-8" style="background:#7C3AED;">
            <i class="fas fa-save mr-2"></i>Simpan Semua Absensi
        </button>
    </div>
</form>

<?php
$all_mapel = [];
$q_mapel = conn()->query("SELECT id, nama_mapel FROM mapel ORDER BY nama_mapel");
while ($m = $q_mapel->fetch_assoc()) {
    $all_mapel[] = $m;
}
$mapel_json = json_encode($all_mapel);
$assign_json = json_encode($guru_mapel_assignments);
?>

<script>
const allMapel = <?= $mapel_json ?>;
const guruAssignments = <?= $assign_json ?>;

function populateMapel(kelasId) {
    const mapelSelect = document.getElementById('mapel');
    mapelSelect.innerHTML = '<option value="">Pilih Mapel</option>';

    let allowedIds = null;
    // Non-admin: filter by their assignments
    if (Object.keys(guruAssignments).length > 0) {
        allowedIds = guruAssignments[kelasId] || [];
        if (allowedIds.length === 0) {
            mapelSelect.innerHTML = '<option value="">-- Tidak ada mapel --</option>';
            return;
        }
    }

    allMapel.forEach(function(m) {
        if (allowedIds === null || allowedIds.includes(parseInt(m.id))) {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.nama_mapel;
            mapelSelect.appendChild(opt);
        }
    });
}

// ─── Inisialisasi ─────────────────────────────────────────────
window.onload = function() {
    document.getElementById('form-absensi-mapel').addEventListener('submit', function(e) {
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
    if (document.getElementById('kelas').value && document.getElementById('mapel').value) {
        loadSiswa();
    }
});

document.getElementById('kelas').addEventListener('change', function() {
    const kelasId = this.value;
    document.getElementById('kelas_id').value = kelasId;
    if (kelasId) {
        populateMapel(kelasId);
    } else {
        document.getElementById('mapel').innerHTML = '<option value="">Pilih Mapel</option>';
        toggleElements(false);
        document.getElementById('siswa-container').innerHTML = '';
    }
});

document.getElementById('mapel').addEventListener('change', function() {
    document.getElementById('mapel_id').value = this.value;
    const kelasId = document.getElementById('kelas').value;
    if (this.value && kelasId) {
        toggleElements(true);
        loadSiswa();
    } else {
        toggleElements(false);
        document.getElementById('siswa-container').innerHTML = '';
    }
});

document.getElementById('tanggal').addEventListener('change', function() {
    if (document.getElementById('kelas').value && document.getElementById('mapel').value) {
        loadSiswa();
    }
});

document.getElementById('search_nama').addEventListener('input', loadSiswa);

// ─── Load Siswa (AJAX) ──────────────────────────────────────
function loadSiswa() {
    const kelasId = document.getElementById('kelas').value;
    const mapelId = document.getElementById('mapel').value;
    const tanggal = document.getElementById('tanggal').value;
    const semesterId = document.getElementById('semester').value;
    const search = document.getElementById('search_nama').value;

    if (kelasId && semesterId && mapelId) {
        let url = 'get_siswa_mapel.php?kelas_id=' + encodeURIComponent(kelasId) +
                  '&mapel_id=' + encodeURIComponent(mapelId) +
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
                document.getElementById('siswa-container').innerHTML = '<div class="alert alert-danger">Gagal memuat data siswa.</div>';
            });
    } else if (kelasId && !semesterId) {
        document.getElementById('siswa-container').innerHTML = '<div class="alert alert-warning">Pilih semester terlebih dahulu!</div>';
    }
}

// ─── FITUR 1: Set Semua Hadir ────────────────────────────────
function selectAllHadir(checked) {
    const radios = document.querySelectorAll('#siswa-container input[type="radio"][value="Hadir"]');
    radios.forEach(function(radio) {
        radio.checked = checked;
    });
    if (checked && radios.length > 0) {
        triggerAutoSave(radios[0]);
    }
}

// ─── FITUR 2: Auto-Save on Radio Click ──────────────────────
var autoSaveTimer = null;
function triggerAutoSave(el) {
    if (autoSaveTimer) clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(function() {
        doAutoSave();
    }, 400);
}

function doAutoSave() {
    const statusEl = document.getElementById('autoSaveStatus');
    statusEl.classList.remove('hidden');
    
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    const tanggal = document.getElementById('tanggal').value;
    const semesterId = document.getElementById('semester').value;
    const kelasId = document.getElementById('kelas').value;
    const mapelId = document.getElementById('mapel').value;
    
    const statuses = {};
    const radioButtons = document.querySelectorAll('input[type="radio"]:checked');
    for (let i = 0; i < radioButtons.length; i++) {
        const radio = radioButtons[i];
        if (radio.name.indexOf('status[') === 0) {
            const match = radio.name.match(/status\[(\d+)\]/);
            if (match) {
                statuses[match[1]] = radio.value;
            }
        }
    }
    
    if (Object.keys(statuses).length === 0) {
        statusEl.classList.add('hidden');
        return;
    }
    
    fetch('proses_mapel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: csrfToken,
            tanggal: tanggal,
            semester_id: semesterId,
            kelas_id: kelasId,
            mapel_id: mapelId,
            status: statuses
        })
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showToast(data.message, 'success');
            }
        } catch(e) {}
    })
    .catch(error => {})
    .finally(() => {
        statusEl.classList.add('hidden');
    });
}

// ─── Toast Notification ──────────────────────────────────────
function showToast(msg, type) {
    let toast = document.getElementById('toastNotif');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toastNotif';
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:500;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:all 0.3s;transform:translateY(100px);opacity:0;';
        document.body.appendChild(toast);
    }
    toast.style.background = type === 'success' ? '#10B981' : '#EF4444';
    toast.style.color = '#fff';
    toast.innerHTML = (type === 'success' ? '<i class="fas fa-check-circle mr-2"></i>' : '<i class="fas fa-exclamation-circle mr-2"></i>') + msg;
    toast.style.transform = 'translateY(0)';
    toast.style.opacity = '1';
    clearTimeout(toast._hide);
    toast._hide = setTimeout(() => {
        toast.style.transform = 'translateY(100px)';
        toast.style.opacity = '0';
    }, 2000);
}

// ─── Simpan Manual (tombol) ─────────────────────────────────
function simpanAbsensi(e) {
    e.preventDefault();
    
    const form = document.getElementById('form-absensi-mapel');
    const submitBtn = document.querySelector('#tombolSimpanBawah button');
    const originalText = submitBtn.innerHTML;
    
    const formData = new FormData(form);
    const csrfToken = formData.get('csrf_token');
    const tanggal = document.getElementById('tanggal').value;
    const semesterId = document.getElementById('semester').value;
    const kelasId = document.getElementById('kelas').value;
    const mapelId = document.getElementById('mapel').value;
    
    const statuses = {};
    const radioButtons = document.querySelectorAll('input[type="radio"]:checked');
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
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
    
    fetch('proses_mapel.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            csrf_token: csrfToken,
            tanggal: tanggal,
            semester_id: semesterId,
            kelas_id: kelasId,
            mapel_id: mapelId,
            status: statuses
        })
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showToast(data.message, 'success');
                loadSiswa();
            } else {
                showToast(data.message, 'error');
            }
        } catch(e) {
            showToast('Respons server tidak valid', 'error');
        }
    })
    .catch(error => {
        showToast('Terjadi kesalahan saat menyimpan!', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

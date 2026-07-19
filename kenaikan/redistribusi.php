<?php
session_start();
require_once '../core/init.php';
require_once '../core/Database.php';
require_role('admin');

$tingkat = (int)($_GET['tingkat'] ?? 10);
if (!in_array($tingkat, [10, 11])) $tingkat = 10;
$tingkat_ke = $tingkat + 1;

$prefix_map = [
    10 => ['X', '10', 'XI', '11'],
    11 => ['XI', '11', 'XII', '12'],
];

$m = $prefix_map[$tingkat];
$prefix_sumber = $m[0];
$prefix_tujuan = $m[2];
$label_sumber = $tingkat == 10 ? 'X' : 'XI';
$label_tujuan = $tingkat == 10 ? 'XI' : 'XII';

$title = "Redistribusi {$label_sumber} → {$label_tujuan}";

// ─── AJAX: pindahkan siswa (single) ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'ajax_pindah') {
    header('Content-Type: application/json');
    $siswa_id = (int)($_POST['siswa_id'] ?? 0);
    $kelas_tujuan = (int)($_POST['kelas_tujuan'] ?? 0);
    $tingkat_ke_post = (int)($_POST['tingkat_ke'] ?? 0);

    if ($siswa_id <= 0 || $kelas_tujuan <= 0) {
        echo json_encode(['success' => false, 'error' => 'Data tidak valid']);
        exit;
    }
    
    if ($tingkat_ke_post > 0) {
        $stmt = conn()->prepare("UPDATE siswa SET kelas_id = ?, tingkat = ? WHERE id = ? AND status = 'aktif'");
        $stmt->bind_param("iii", $kelas_tujuan, $tingkat_ke_post, $siswa_id);
    } else {
        $stmt = conn()->prepare("UPDATE siswa SET kelas_id = ? WHERE id = ? AND status = 'aktif'");
        $stmt->bind_param("ii", $kelas_tujuan, $siswa_id);
    }
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'siswa_id' => $siswa_id,
            'kelas_tujuan' => $kelas_tujuan,
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Siswa tidak ditemukan atau sudah dipindah']);
    }
    exit;
}

// ─── AJAX: pindahkan banyak siswa sekaligus ─────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'ajax_pindah_batch') {
    header('Content-Type: application/json');
    $siswa_ids = isset($_POST['siswa_ids']) ? explode(',', $_POST['siswa_ids']) : [];
    $kelas_tujuan = (int)($_POST['kelas_tujuan'] ?? 0);
    $tingkat_ke_post = (int)($_POST['tingkat_ke'] ?? 0);

    if (empty($siswa_ids) || $kelas_tujuan <= 0) {
        echo json_encode(['success' => false, 'error' => 'Data tidak valid']);
        exit;
    }

    $moved = 0;
    $error_ids = [];
    $stmt = conn()->prepare("UPDATE siswa SET kelas_id = ?, tingkat = ? WHERE id = ? AND status = 'aktif'");
    
    foreach ($siswa_ids as $sid) {
        $id = (int)trim($sid);
        if ($id <= 0) continue;
        $stmt->bind_param("iii", $kelas_tujuan, $tingkat_ke_post, $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $moved++;
        } else {
            $error_ids[] = $id;
        }
    }
    $stmt->close();
    
    echo json_encode([
        'success' => $moved > 0,
        'moved' => $moved,
        'errors' => $error_ids,
        'kelas_tujuan' => $kelas_tujuan,
    ]);
    exit;
}

// ─── Get data ──────────────────────────────────────────────
$kelas_sumber = conn()->query("
    SELECT k.id, k.nama_kelas, COUNT(s.id) as jml
    FROM kelas k
    LEFT JOIN siswa s ON s.kelas_id = k.id AND s.status = 'aktif'
    WHERE k.nama_kelas LIKE '{$m[0]}-%' OR k.nama_kelas LIKE '{$m[1]}-%'
    GROUP BY k.id ORDER BY k.nama_kelas
");

$kelas_tujuan = conn()->query("
    SELECT k.id, k.nama_kelas
    FROM kelas k
    WHERE k.nama_kelas LIKE '{$m[2]}-%' OR k.nama_kelas LIKE '{$m[3]}-%'
    ORDER BY k.nama_kelas
");
$kelas_tujuan_arr = [];
while ($k = $kelas_tujuan->fetch_assoc()) {
    $k['jml'] = 0;
    $k['siswa'] = [];
    $kelas_tujuan_arr[$k['id']] = $k;
}

// ─── Students already in destination classes ──────────────
$tujuan_ids = array_keys($kelas_tujuan_arr);
if (!empty($tujuan_ids)) {
    $siswa_di_tujuan = conn()->query("
        SELECT id, nis, nama, jenis_kelamin, kelas_id FROM siswa
        WHERE status = 'aktif' AND kelas_id IN (" . implode(',', $tujuan_ids) . ")
        ORDER BY nama
    ");
    while ($s = $siswa_di_tujuan->fetch_assoc()) {
        $kid = $s['kelas_id'];
        $kelas_tujuan_arr[$kid]['jml']++;
        $kelas_tujuan_arr[$kid]['siswa'][] = $s;
    }
}

ob_start();
?>

<style>
/* ── Layout ───────────────────────────────────── */
.dnd-panel { min-height:400px; }
.source-class { border:2px solid #e5e7eb; border-radius:14px; margin-bottom:10px; overflow:hidden; transition:all .2s; }
.source-class:hover { border-color:#818cf8; }
.source-class-header { padding:10px 14px; cursor:pointer; display:flex; align-items:center; gap:10px; user-select:none; }
.source-class-header:hover { background:#f5f3ff; }
.source-students { display:none; padding:6px 12px 12px; }
.source-students.open { display:block; }

.student-card {
    display:inline-flex; align-items:center; gap:8px;
    padding:6px 12px 6px 10px; margin:3px 4px;
    border-radius:10px; font-size:0.85rem; cursor:grab;
    border:1.5px solid #e5e7eb; background:#fafafa;
    transition:all .15s; user-select:none;
    width:calc(50% - 8px); box-sizing:border-box;
}
.student-card:hover { border-color:#6366f1; background:#eef2ff; transform:translateY(-1px); box-shadow:0 2px 8px rgba(99,102,241,0.15); }
.student-card:active { cursor:grabbing; }
.student-card.dragging { opacity:0.5; border-style:dashed; }
.student-card .s-name { font-weight:600; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.student-card .s-nis { color:#9ca3af; font-size:0.75rem; }
.student-card.selected { border-color:#6366f1; background:#eef2ff; box-shadow:0 0 0 2px rgba(99,102,241,0.3); }

.dest-class {
    border:2.5px dashed #d1d5db; border-radius:14px; padding:12px; min-height:90px;
    transition:all .2s; margin-bottom:10px;
}
.dest-class.dragover { border-color:#10b981; background:#f0fdf4; border-style:solid; transform:scale(1.02); }
.dest-class-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.dest-class-name { font-weight:700; color:#374151; font-size:0.9rem; }
.dest-class-count { font-size:0.8rem; padding:2px 10px; border-radius:20px; font-weight:600; }
.dest-class-count.empty { background:#f3f4f6; color:#9ca3af; }
.dest-class-count.filled { background:#d1fae5; color:#059669; }
.dest-students { display:flex; flex-wrap:wrap; gap:4px; }
.dest-student {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px 3px 8px; border-radius:8px; font-size:0.8rem;
    background:#f0fdf4; border:1px solid #bbf7d0; color:#166534;
    cursor:grab; transition:all .15s;
}
.dest-student:hover { border-color:#6366f1; background:#ecfdf5; }
.dest-student.dragging { opacity:0.4; border-style:dashed; }
.dest-student.dest-selected { border-color:#6366f1; background:#eef2ff; color:#4338ca; box-shadow:0 0 0 2px rgba(99,102,241,0.3); }
.dest-student .remove-student {
    cursor:pointer; opacity:0.5; font-size:0.75rem; margin-left:2px;
}
.dest-student .remove-student:hover { opacity:1; color:#ef4444; }

.source-students.dragover { background:#f0fdf4; border-radius:10px; min-height:40px; }
.source-class.droptarget { border-color:#10b981; background:#f0fdf4; }

.empty-zone-text { color:#d1d5db; font-size:0.8rem; text-align:center; padding:16px 0; }

/* ── Stats bar ─────────────────────────────────── */
.stats-bar { display:flex; gap:12px; flex-wrap:wrap; }
.stat-item { padding:8px 16px; border-radius:10px; font-size:0.85rem; background:#f9fafb; border:1px solid #e5e7eb; }
.stat-item strong { font-size:1.2rem; }

/* ── Responsive ────────────────────────────────── */
@media (max-width:900px) {
    .student-card { width:100%; }
    .dnd-panels { grid-template-columns:1fr !important; }
}
</style>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
            <a href="index.php" class="btn-modern btn-neutral-modern text-sm mb-2 inline-flex">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                <i class="fas fa-random mr-3 text-primary"></i>Redistribusi Kelas
            </h2>
            <p class="text-sm text-gray-400">Drag siswa dari kelas asal <strong>(<?= $label_sumber ?>)</strong> ke kelas tujuan <strong>(<?= $label_tujuan ?>)</strong></p>
        </div>
        <div class="flex gap-2">
            <a href="?tingkat=10" class="tingkat-tab px-4 py-2 rounded-xl font-semibold text-sm transition-all <?= $tingkat == 10 ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">
                X → XI
            </a>
            <a href="?tingkat=11" class="tingkat-tab px-4 py-2 rounded-xl font-semibold text-sm transition-all <?= $tingkat == 11 ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">
                XI → XII
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-bar mb-5" id="statsBar">
        <div class="stat-item">
            Total sumber: <strong id="totalSumber">0</strong> siswa
        </div>
        <div class="stat-item">
            Sudan dipindah: <strong id="totalDipindah">0</strong> siswa
        </div>
        <div class="stat-item">
            Sisa di sumber: <strong id="totalSisa">0</strong> siswa
        </div>
    </div>

    <!-- Success/Error messages -->
    <div id="msgContainer"></div>

    <!-- 2 Panel -->
    <div class="dnd-panels" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <!-- ─── LEFT: Source Panel ──────────────── -->
        <div class="card-modern dnd-panel">
            <div class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                <i class="fas fa-door-open text-indigo-500"></i>
                Kelas Asal (<?= $label_sumber ?>)
                <span class="ml-auto text-xs font-normal text-gray-400">Klik kelas untuk lihat siswa</span>
            </div>
            <div class="p-4" id="sourcePanel">
                <div class="text-xs text-gray-400 mb-3 flex items-center gap-2">
                    <span><i class="fas fa-mouse-pointer text-indigo-400 mr-1"></i>Klik siswa untuk pilih</span>
                    <span class="text-gray-300">|</span>
                    <span><i class="fas fa-arrow-right text-emerald-400 mr-1"></i>Dropdown atas untuk pindah</span>
                    <span class="text-gray-300">|</span>
                    <span><i class="fas fa-arrows-alt text-amber-400 mr-1"></i>Drag ke panel tujuan</span>
                </div>
                <?php 
                $total_sumber = 0;
                while ($k = $kelas_sumber->fetch_assoc()): 
                    // Get students in this source class
                    $siswa_sumber = conn()->query("
                        SELECT s.id, s.nis, s.nama, s.jenis_kelamin 
                        FROM siswa s WHERE s.kelas_id = {$k['id']} AND s.status = 'aktif'
                        ORDER BY s.nama
                    ");
                    $siswa_list = [];
                    while ($s = $siswa_sumber->fetch_assoc()) {
                        $siswa_list[] = $s;
                    }
                    $total_sumber += count($siswa_list);
                    
                    // Skip classes with no students
                    if (empty($siswa_list) && $k['jml'] == 0) continue;
                ?>
                <div class="source-class" data-class-id="<?= $k['id'] ?>">
                    <div class="source-class-header" onclick="toggleSourceClass(this)">
                        <i class="fas fa-chevron-right text-xs text-gray-400 transition-transform"></i>
                        <span class="font-semibold text-sm flex-1"><?= htmlspecialchars($k['nama_kelas']) ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600 font-medium count-badge"><?= count($siswa_list) ?></span>
                    </div>
                    <div class="source-students" data-class-id="<?= $k['id'] ?>"
                         ondragover="sourceDragOver(event)"
                         ondragleave="sourceDragLeave(event)"
                         ondrop="sourceDrop(event)">
                        <label class="flex items-center gap-2 px-1 py-1.5 mb-2 text-xs text-indigo-600 cursor-pointer select-none hover:bg-indigo-50 rounded-lg"
                               onclick="event.stopPropagation()">
                            <input type="checkbox" class="select-all-cb" style="accent-color:#6366f1;width:16px;height:16px;"
                                   onchange="toggleSelectAll(this, '<?= $k['id'] ?>')">
                            <span><i class="fas fa-check-double mr-1"></i>Pilih Semua</span>
                            <span class="text-gray-400 ml-1">(<?= count($siswa_list) ?> siswa)</span>
                        </label>
                        <?php foreach ($siswa_list as $s): ?>
<div class="student-card" draggable="true"
     data-siswa-id="<?= $s['id'] ?>"
     data-nama="<?= htmlspecialchars($s['nama'], ENT_QUOTES) ?>"
     data-nis="<?= htmlspecialchars($s['nis']) ?>"
     data-kelas-asal="<?= $k['id'] ?>"
     onclick="toggleSelect(event, this)"
     ondragstart="dragStart(event)">
                            <div class="flex-1 min-w-0">
                                <div class="s-name"><?= htmlspecialchars($s['nama']) ?></div>
                                <div class="s-nis"><?= htmlspecialchars($s['nis']) ?></div>
                            </div>
                            <span class="text-xs px-1.5 py-0.5 rounded bg-gray-200 text-gray-500 shrink-0">
                                <?= $s['jenis_kelamin'] == 'Laki-laki' ? 'L' : 'P' ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- ─── RIGHT: Destination Panel ────────── -->
        <div class="card-modern dnd-panel">
            <div class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                <i class="fas fa-door-closed text-emerald-500"></i>
                Kelas Tujuan (<?= $label_tujuan ?>)
                <span class="ml-auto text-xs font-normal text-gray-400">Drop siswa di sini</span>
            </div>
            <div class="p-4" id="destPanel">
                <?php 
                $total_dipindah = 0;
                foreach ($kelas_tujuan_arr as $kt_id => $kt): 
                    $count = $kt['jml'];
                    $total_dipindah += $count;
                ?>
                <div class="dest-class" data-class-id="<?= $kt_id ?>"
                     ondragover="dragOver(event)"
                     ondragleave="dragLeave(event)"
                     ondrop="dropStudent(event)"
                     onclick="handleDestClick(event)">
                    <div class="dest-class-header">
                        <span class="dest-class-name"><?= htmlspecialchars($kt['nama_kelas']) ?></span>
                        <span class="dest-class-count <?= $count > 0 ? 'filled' : 'empty' ?>">
                            <span class="count-num"><?= $count ?></span> siswa
                        </span>
                    </div>
                    <div class="dest-students" data-kelas-id="<?= $kt_id ?>">
                        <?php if ($kt['jml'] > 1): ?>
                        <label class="flex items-center gap-2 mb-2 text-xs text-emerald-600 cursor-pointer select-none hover:bg-emerald-50 rounded-lg px-1 py-0.5"
                               onclick="event.stopPropagation()">
                            <input type="checkbox" class="select-all-dest-cb" style="accent-color:#10b981;width:14px;height:14px;"
                                   onchange="toggleSelectAllDest(this, '<?= $kt_id ?>')">
                            <span><i class="fas fa-check-double mr-1"></i>Pilih Semua</span>
                            <span class="text-gray-400">(<?= $kt['jml'] ?> siswa)</span>
                        </label>
                        <?php endif; ?>
                        <?php foreach ($kt['siswa'] as $s): ?>
                        <span class="dest-student" draggable="true"
                              data-siswa-id="<?= $s['id'] ?>"
                              data-kelas-id="<?= $kt_id ?>"
                              data-kelas-asal="<?= $s['kelas_id'] ?>"
                              data-nama="<?= htmlspecialchars($s['nama'], ENT_QUOTES) ?>"
                              ondragstart="destDragStart(event)"
                              onclick="toggleSelectDest(event, this)">
                            <i class="fas fa-user text-[10px]"></i>
                            <?= htmlspecialchars($s['nama']) ?>
                            <span class="remove-student" onclick="event.stopPropagation();undoMoveStudent(this)" title="Kembalikan ke asal">&times;</span>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($count == 0): ?>
                    <div class="empty-zone-text"><i class="fas fa-arrow-down mr-1"></i>Drop siswa di sini</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Selected count + actions (sticky bar) -->
    <div class="flex items-center gap-3 my-4 flex-wrap p-3 rounded-xl bg-white/90 dark:bg-gray-800/90 backdrop-blur border border-indigo-200 dark:border-indigo-800 shadow-lg" 
         id="selectionActions" style="display:none !important;position:sticky;top:80px;z-index:50;">
        <div class="stat-item" style="background:#eef2ff;border-color:#6366f1;">
            <i class="fas fa-check-circle text-indigo-500 mr-1"></i>
            <span id="selectedCount">0</span> siswa dipilih
        </div>
        <select id="destDropdown" class="form-input-modern text-sm py-1.5 px-3 min-w-[180px]">
            <option value="">-- Pilih Kelas Tujuan --</option>
            <?php foreach ($kelas_tujuan_arr as $kt): ?>
            <option value="<?= $kt['id'] ?>"><?= htmlspecialchars($kt['nama_kelas']) ?> (<?= $kt['jml'] ?> siswa)</option>
            <?php endforeach; ?>
        </select>
        <button onclick="moveSelectedToDropdown()" class="btn-modern btn-success-modern text-sm py-1.5 px-4">
            <i class="fas fa-arrow-right mr-1"></i>Pindahkan
        </button>
        <button onclick="clearSelection()" class="btn-modern btn-neutral-modern text-sm py-1.5 px-4">
            <i class="fas fa-times mr-1"></i>Batal
        </button>
    </div>

    <input type="hidden" id="tingkatKe" value="<?= $tingkat_ke ?>">
</div>

<script>
// ─── State ──────────────────────────────────────
let isMoving = false;

// ─── Multi-select: toggle ─────────────────────────
function toggleSelect(event, el) {
    // Ignore if clicking inside a child that has its own handler
    if (event.target.closest('.remove-student')) return;
    
    el.classList.toggle('selected');
    updateSelectedUI();
}

function updateSelectedUI() {
    updateSelectionActions();
}

function clearSelection() {
    document.querySelectorAll('.student-card.selected').forEach(c => c.classList.remove('selected'));
    document.querySelectorAll('.dest-student.dest-selected').forEach(c => c.classList.remove('dest-selected'));
    updateSelectedUI();
    updateDestSelectedUI();
}

function toggleSelectAll(cb, classId) {
    const container = cb.closest('.source-students');
    const cards = container.querySelectorAll('.student-card');
    cards.forEach(c => {
        if (cb.checked) c.classList.add('selected');
        else c.classList.remove('selected');
    });
    updateSelectedUI();
}

function toggleSelectAllDest(cb, kelasId) {
    const container = cb.closest('.dest-students');
    const items = container.querySelectorAll('.dest-student');
    items.forEach(el => {
        if (cb.checked) el.classList.add('dest-selected');
        else el.classList.remove('dest-selected');
    });
    updateDestSelectedUI();
}

function toggleSelectDest(event, el) {
    if (event.target.closest('.remove-student')) return;
    el.classList.toggle('dest-selected');
    updateDestSelectedUI();
}

function updateDestSelectedUI() {
    // Sync parent checkbox state
    document.querySelectorAll('.dest-students').forEach(container => {
        const cb = container.querySelector('.select-all-dest-cb');
        if (!cb) return;
        const items = container.querySelectorAll('.dest-student');
        const selected = container.querySelectorAll('.dest-student.dest-selected');
        if (items.length === 0) return;
        cb.checked = selected.length === items.length;
        cb.indeterminate = selected.length > 0 && selected.length < items.length;
    });
    updateSelectionActions();
}

function getSelectedStudents() {
    return Array.from(document.querySelectorAll('.student-card.selected')).map(c => ({
        id: c.dataset.siswaId,
        nama: c.dataset.nama,
        kelasAsal: c.dataset.kelasAsal
    }));
}

function getSelectedDestStudents() {
    return Array.from(document.querySelectorAll('.dest-student.dest-selected')).map(el => ({
        id: el.dataset.siswaId,
        nama: el.dataset.nama,
        kelasAsal: el.dataset.kelasAsal,
        kelasSekarang: el.dataset.kelasId
    }));
}

function updateSelectionActions() {
    const srcCount = getSelectedStudents().length;
    const destCount = getSelectedDestStudents().length;
    const total = srcCount + destCount;
    
    const actions = document.getElementById('selectionActions');
    const countEl = document.getElementById('selectedCount');
    const dropdown = document.getElementById('destDropdown');
    const btn = document.querySelector('#selectionActions .btn-success-modern');
    
    if (total > 0) {
        actions.style.display = 'flex';
        countEl.textContent = total;
        dropdown.placeholder = destCount > 0 ? '-- Pindah ke kelas --' : '-- Pilih Kelas Tujuan --';
        btn.innerHTML = destCount > 0 ? '<i class="fas fa-arrow-right mr-1"></i>Pindahkan' : '<i class="fas fa-arrow-right mr-1"></i>Pindahkan';
    } else {
        actions.style.display = 'none';
    }
}

// ─── Accordion: toggle source class ──────────────
function toggleSourceClass(header) {
    const students = header.parentElement.querySelector('.source-students');
    const icon = header.querySelector('.fa-chevron-right');
    const isOpen = students.classList.contains('open');
    
    document.querySelectorAll('.source-students.open').forEach(el => {
        if (el !== students) {
            el.classList.remove('open');
            el.closest('.source-class').querySelector('.fa-chevron-right').style.transform = '';
        }
    });
    
    if (isOpen) {
        students.classList.remove('open');
        icon.style.transform = '';
    } else {
        students.classList.add('open');
        icon.style.transform = 'rotate(90deg)';
    }
}

// ─── Drag FROM destination (single only) ──────────
function destDragStart(e) {
    const span = e.target.closest('.dest-student');
    if (!span) return;
    e.dataTransfer.setData('text/plain', JSON.stringify([{
        id: span.dataset.siswaId,
        nama: span.dataset.nama,
        kelas_asal: span.dataset.kelasAsal,
        kelas_sekarang: span.dataset.kelasId,
        fromDest: true
    }]));
    e.dataTransfer.effectAllowed = 'move';
    span.classList.add('dragging');
}

// ─── Source panel accepts drops (for undo) ─────────
function sourceDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const students = e.target.closest('.source-students');
    if (students) students.classList.add('dragover');
}

function sourceDragLeave(e) {
    const students = e.target.closest('.source-students');
    if (students) students.classList.remove('dragover');
}

function sourceDrop(e) {
    e.preventDefault();
    const srcStudents = e.target.closest('.source-students');
    if (!srcStudents) return;
    srcStudents.classList.remove('dragover');
    
    const raw = e.dataTransfer.getData('text/plain');
    if (!raw) return;
    const dataArr = JSON.parse(raw);
    if (!Array.isArray(dataArr)) return;
    
    for (const data of dataArr) {
        if (data.fromDest) {
            undoMoveToSource(data.id, data.nama, data.kelas_sekarang, srcStudents.dataset.classId);
        }
    }
}

function undoMoveToSource(siswaId, nama, kelasSekarang, kelasTujuan) {
    const tingkatKe = document.getElementById('tingkatKe').value;
    const tingkatAsal = parseInt(tingkatKe) - 1;
    
    const formData = new FormData();
    formData.append('action', 'ajax_pindah');
    formData.append('siswa_id', siswaId);
    formData.append('kelas_tujuan', kelasTujuan);
    formData.append('tingkat_ke', tingkatAsal);
    
    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const oldDest = document.querySelector(`.dest-student[data-siswa-id="${siswaId}"]`);
            if (oldDest) {
                const destClass = oldDest.closest('.dest-class');
                oldDest.remove();
                if (destClass) {
                    updateDestCount(destClass);
                    const emptyText = destClass.querySelector('.empty-zone-text');
                    if (emptyText && destClass.querySelectorAll('.dest-student').length === 0) {
                        emptyText.style.display = '';
                    }
                }
            }
            showMsg('success', `${nama} dikembalikan`);
            setTimeout(() => location.reload(), 600);
        } else {
            showMsg('error', `Gagal mengembalikan ${nama}`);
        }
    })
    .catch(() => location.reload());
}

// ─── Drag Start (source) ──────────────────────────
function dragStart(e) {
    const card = e.target.closest('.student-card');
    if (!card) return;
    
    // If dragging a selected card, drag ALL selected
    const selectedCards = document.querySelectorAll('.student-card.selected');
    if (selectedCards.length > 0 && card.classList.contains('selected')) {
        const allData = Array.from(selectedCards).map(c => ({
            id: c.dataset.siswaId,
            nama: c.dataset.nama,
            kelasAsal: c.dataset.kelasAsal
        }));
        e.dataTransfer.setData('text/plain', JSON.stringify(allData));
    } else {
        // Single drag
        e.dataTransfer.setData('text/plain', JSON.stringify([{
            id: card.dataset.siswaId,
            nama: card.dataset.nama,
            kelasAsal: card.dataset.kelasAsal
        }]));
    }
    
    e.dataTransfer.effectAllowed = 'move';
    card.classList.add('dragging');
}

function dragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const dest = e.target.closest('.dest-class');
    if (dest) dest.classList.add('dragover');
}

function dragLeave(e) {
    const dest = e.target.closest('.dest-class');
    if (dest) dest.classList.remove('dragover');
}

function dropStudent(e) {
    e.preventDefault();
    const dest = e.target.closest('.dest-class');
    if (!dest) return;
    dest.classList.remove('dragover');
    
    const raw = e.dataTransfer.getData('text/plain');
    if (!raw) return;
    const dataArr = JSON.parse(raw);
    if (!Array.isArray(dataArr) || dataArr.length === 0) return;
    
    const kelasTujuan = dest.dataset.classId;
    
    document.querySelectorAll('.dragging').forEach(c => c.classList.remove('dragging'));
    clearSelection();
    
    // Check if it's dest→dest (first item has fromDest)
    if (dataArr[0].fromDest) {
        for (const data of dataArr) {
            const span = document.querySelector(`.dest-student[data-siswa-id="${data.id}"]`);
            if (span) {
                moveToAnotherDest(data.id, data.nama, data.kelas_sekarang, kelasTujuan, span);
            }
        }
    } else {
        // Source → Dest: batch move
        moveMultipleStudents(dataArr, kelasTujuan);
    }
}

// ─── Batch move: source → dest ────────────────────
function moveMultipleStudents(students, kelasTujuan) {
    if (isMoving) return;
    if (students.length === 0) return;
    
    isMoving = true;
    const tingkatKe = document.getElementById('tingkatKe').value;
    const ids = students.map(s => s.id).join(',');
    const names = students.map(s => s.nama);
    
    const formData = new FormData();
    formData.append('action', 'ajax_pindah_batch');
    formData.append('siswa_ids', ids);
    formData.append('kelas_tujuan', kelasTujuan);
    formData.append('tingkat_ke', tingkatKe);
    
    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.moved > 0) {
            // Remove all from source
            for (const s of students) {
                const card = document.querySelector(`.student-card[data-siswa-id="${s.id}"]`);
                if (card) {
                    const srcClass = card.closest('.source-students');
                    card.remove();
                    updateSourceCount(srcClass);
                    
                    const srcContainer = srcClass.closest('.source-class');
                    if (srcContainer && srcClass.querySelectorAll('.student-card').length === 0) {
                        srcContainer.style.display = 'none';
                    }
                }
                
                // Add to destination
                const destStudents = document.querySelector(`.dest-students[data-kelas-id="${kelasTujuan}"]`);
                if (destStudents) {
                    const span = document.createElement('span');
                    span.className = 'dest-student';
                    span.draggable = true;
                    span.dataset.siswaId = s.id;
                    span.dataset.kelasId = kelasTujuan;
                    span.dataset.kelasAsal = s.kelasAsal || '';
                    span.dataset.nama = s.nama;
                    span.ondragstart = function(ev) { destDragStart(ev); };
                    span.onclick = function(ev) { toggleSelectDest(ev, this); };
                    span.innerHTML = `<i class="fas fa-user text-[10px]"></i> ${s.nama} <span class="remove-student" onclick="event.stopPropagation();undoMoveStudent(this)" title="Kembalikan">&times;</span>`;
                    destStudents.appendChild(span);
                }
            }
            
            // Update dest count
            const destClass = document.querySelector(`.dest-class[data-class-id="${kelasTujuan}"]`);
            if (destClass) {
                updateDestCount(destClass);
                const emptyText = destClass.querySelector('.empty-zone-text');
                if (emptyText) emptyText.style.display = 'none';
            }
            
            updateStats();
            showMsg('success', `${res.moved} siswa berhasil dipindahkan`);
        } else {
            showMsg('error', 'Gagal memindahkan siswa');
        }
    })
    .catch(() => showMsg('error', 'Koneksi error'))
    .finally(() => { isMoving = false; });
}

function moveToAnotherDest(siswaId, nama, kelasLama, kelasBaru, spanEl) {
    const tingkatKe = document.getElementById('tingkatKe').value;
    
    const formData = new FormData();
    formData.append('action', 'ajax_pindah');
    formData.append('siswa_id', siswaId);
    formData.append('kelas_tujuan', kelasBaru);
    formData.append('tingkat_ke', tingkatKe);
    
    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const oldDest = spanEl.closest('.dest-class');
            spanEl.remove();
            if (oldDest) {
                updateDestCount(oldDest);
                const emptyText = oldDest.querySelector('.empty-zone-text');
                if (emptyText && oldDest.querySelectorAll('.dest-student').length === 0) {
                    emptyText.style.display = '';
                }
            }
            
            const newDestStudents = document.querySelector(`.dest-students[data-kelas-id="${kelasBaru}"]`);
            if (newDestStudents) {
                const newSpan = document.createElement('span');
                newSpan.className = 'dest-student';
                newSpan.draggable = true;
                newSpan.dataset.siswaId = siswaId;
                newSpan.dataset.kelasId = kelasBaru;
                newSpan.dataset.kelasAsal = spanEl.dataset.kelasAsal || '';
                newSpan.dataset.nama = nama;
                newSpan.ondragstart = function(ev) { destDragStart(ev); };
                newSpan.onclick = function(ev) { toggleSelectDest(ev, this); };
                newSpan.innerHTML = `<i class="fas fa-user text-[10px]"></i> ${nama} <span class="remove-student" onclick="event.stopPropagation();undoMoveStudent(this)" title="Kembalikan">&times;</span>`;
                newDestStudents.appendChild(newSpan);
                
                const newDest = newDestStudents.closest('.dest-class');
                updateDestCount(newDest);
                const emptyText = newDest.querySelector('.empty-zone-text');
                if (emptyText) emptyText.style.display = 'none';
            }
            showMsg('success', `${nama} dipindahkan`);
        } else {
            showMsg('error', 'Gagal memindahkan');
        }
    })
    .catch(() => showMsg('error', 'Koneksi error'));
}

// ─── Dropdown: pindah ke kelas tujuan ────────────────
function moveSelectedToDropdown() {
    const srcSelected = getSelectedStudents();
    const destSelected = getSelectedDestStudents();
    
    if (srcSelected.length === 0 && destSelected.length === 0) {
        showMsg('error', 'Pilih siswa terlebih dahulu');
        return;
    }
    
    const destId = document.getElementById('destDropdown').value;
    if (!destId) {
        showMsg('error', 'Pilih kelas tujuan');
        return;
    }
    
    if (srcSelected.length > 0) {
        moveMultipleStudents(srcSelected, destId);
    }
    
    if (destSelected.length > 0) {
        moveDestToDest(destSelected, destId);
    }
    
    clearSelection();
    updateDestSelectedUI();
}

function moveDestToDest(students, kelasBaru) {
    const tingkatKe = document.getElementById('tingkatKe').value;
    
    for (const s of students) {
        const formData = new FormData();
        formData.append('action', 'ajax_pindah');
        formData.append('siswa_id', s.id);
        formData.append('kelas_tujuan', kelasBaru);
        formData.append('tingkat_ke', tingkatKe);
        
        fetch(window.location.href, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const el = document.querySelector(`.dest-student[data-siswa-id="${s.id}"]`);
                if (el) {
                    const oldDest = el.closest('.dest-class');
                    el.remove();
                    if (oldDest) {
                        updateDestCount(oldDest);
                        const emptyText = oldDest.querySelector('.empty-zone-text');
                        if (emptyText && oldDest.querySelectorAll('.dest-student').length === 0) {
                            emptyText.style.display = '';
                        }
                    }
                    
                    const newDestStudents = document.querySelector(`.dest-students[data-kelas-id="${kelasBaru}"]`);
                    if (newDestStudents) {
                        const newSpan = document.createElement('span');
                        newSpan.className = 'dest-student';
                        newSpan.draggable = true;
                        newSpan.dataset.siswaId = s.id;
                        newSpan.dataset.kelasId = kelasBaru;
                        newSpan.dataset.kelasAsal = el.dataset.kelasAsal || s.kelasAsal || '';
                        newSpan.dataset.nama = s.nama;
                        newSpan.ondragstart = function(ev) { destDragStart(ev); };
                        newSpan.onclick = function(ev) { toggleSelectDest(ev, this); };
                        newSpan.innerHTML = `<i class="fas fa-user text-[10px]"></i> ${s.nama} <span class="remove-student" onclick="event.stopPropagation();undoMoveStudent(this)" title="Kembalikan">&times;</span>`;
                        newDestStudents.appendChild(newSpan);
                        
                        const newDest = newDestStudents.closest('.dest-class');
                        updateDestCount(newDest);
                        const emptyText = newDest.querySelector('.empty-zone-text');
                        if (emptyText) emptyText.style.display = 'none';
                    }
                }
            }
        });
    }
    
    showMsg('success', `${students.length} siswa dipindahkan antar kelas tujuan`);
}

// ─── Click-based: click destination = move all selected ──
function handleDestClick(e) {
    if (e.target.closest('.remove-student')) return;
    if (e.target.closest('.dest-student')) return;
    
    const selected = getSelectedStudents();
    if (selected.length === 0) return;
    
    const dest = e.target.closest('.dest-class');
    if (!dest) return;
    
    moveMultipleStudents(selected, dest.dataset.classId);
    clearSelection();
}

// ─── Undo: klik × ───────────────────────────────
function undoMoveStudent(btn) {
    const span = btn.closest('.dest-student');
    if (!span || !confirm('Kembalikan ke kelas asal?')) return;
    
    const siswaId = span.dataset.siswaId;
    const kelasAsal = span.dataset.kelasAsal;
    const kelasTujuan = span.dataset.kelasId;
    
    if (!kelasAsal) { location.reload(); return; }
    
    const tingkatKe = document.getElementById('tingkatKe').value;
    const tingkatAsal = parseInt(tingkatKe) - 1;
    
    const formData = new FormData();
    formData.append('action', 'ajax_pindah');
    formData.append('siswa_id', siswaId);
    formData.append('kelas_tujuan', kelasAsal);
    formData.append('tingkat_ke', tingkatAsal);
    
    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            span.remove();
            const destClass = document.querySelector(`.dest-class[data-class-id="${kelasTujuan}"]`);
            if (destClass) {
                updateDestCount(destClass);
                const emptyText = destClass.querySelector('.empty-zone-text');
                if (emptyText && destClass.querySelectorAll('.dest-student').length === 0) {
                    emptyText.style.display = '';
                }
            }
            showMsg('success', 'Dikembalikan');
            setTimeout(() => location.reload(), 800);
        } else {
            showMsg('error', 'Gagal');
            location.reload();
        }
    })
    .catch(() => location.reload());
}

// ─── UI Helpers ──────────────────────────────────
function updateSourceCount(container) {
    const count = container.querySelectorAll('.student-card').length;
    const badge = container.closest('.source-class').querySelector('.count-badge');
    if (badge) badge.textContent = count;
}

function updateDestCount(destClass) {
    const count = destClass.querySelectorAll('.dest-student').length;
    const badge = destClass.querySelector('.dest-class-count');
    if (badge) {
        badge.querySelector('.count-num').textContent = count;
        badge.className = `dest-class-count ${count > 0 ? 'filled' : 'empty'}`;
    }
    // Show/hide "Pilih Semua" checkbox
    const selectAllLabel = destClass.querySelector('.select-all-dest-cb')?.closest('label');
    if (selectAllLabel) {
        selectAllLabel.style.display = count > 1 ? '' : 'none';
    }
}

function updateStats() {
    const totalCards = document.querySelectorAll('.student-card').length;
    const totalDest = document.querySelectorAll('.dest-student').length;
    document.getElementById('totalSumber').textContent = totalCards + totalDest;
    document.getElementById('totalDipindah').textContent = totalDest;
    document.getElementById('totalSisa').textContent = totalCards;
}

function showMsg(type, text) {
    const container = document.getElementById('msgContainer');
    const div = document.createElement('div');
    div.className = `alert-modern ${type === 'success' ? 'alert-success-modern' : 'alert-danger-modern'} mb-4 flex items-center gap-3`;
    div.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} text-lg"></i>
        <span class="flex-1">${text}</span>
        <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">&times;</button>
    `;
    container.appendChild(div);
    setTimeout(() => { if (div.parentElement) div.remove(); }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    updateStats();
});
</script>

<?php
$content = ob_get_clean();
require_once '../views/layout.php';

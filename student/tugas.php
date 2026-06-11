<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$pdo  = getDB();
$user = currentUser();
$uid  = $user['id'];

$action   = $_GET['action'] ?? 'list';
$editData = null;
$error = $success = '';

// Ambil daftar jadwal untuk dropdown
$jadwalOpts = $pdo->prepare("SELECT id, matkul FROM schedules WHERE user_id=? ORDER BY matkul");
$jadwalOpts->execute([$uid]);
$jadwalList = $jadwalOpts->fetchAll();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_tugas  = trim($_POST['nama_tugas']  ?? '');
    $deskripsi   = trim($_POST['deskripsi']   ?? '');
    $deadline    = trim($_POST['deadline']    ?? '');
    $prioritas   = trim($_POST['prioritas']   ?? 'medium');
    $schedule_id = (int)($_POST['schedule_id']?? 0) ?: null;
    $id          = (int)($_POST['id']         ?? 0);

    if (empty($nama_tugas)) {
        $error = 'Nama tugas wajib diisi.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE tasks SET nama_tugas=?,deskripsi=?,deadline=?,prioritas=?,schedule_id=? WHERE id=? AND user_id=?");
            $stmt->execute([$nama_tugas,$deskripsi,$deadline ?: null,$prioritas,$schedule_id,$id,$uid]);
            $success = 'Tugas berhasil diperbarui.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO tasks (user_id,schedule_id,nama_tugas,deskripsi,deadline,prioritas) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$uid,$schedule_id,$nama_tugas,$deskripsi,$deadline ?: null,$prioritas]);
            $success = 'Tugas berhasil ditambahkan.';
        }
        $action = 'list';
    }
}

// Hapus
if ($action === 'hapus' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
    $stmt->execute([(int)$_GET['id'], $uid]);
    $success = 'Tugas dihapus.';
    $action  = 'list';
}

// Edit
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id=? AND user_id=?");
    $stmt->execute([(int)$_GET['id'], $uid]);
    $editData = $stmt->fetch();
    if (!$editData) { $action = 'list'; }
}

// Daftar tugas
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT t.*, s.matkul FROM tasks t LEFT JOIN schedules s ON t.schedule_id=s.id WHERE t.user_id=?";
if ($filter === 'pending')  $sql .= " AND t.status='pending'";
if ($filter === 'selesai')  $sql .= " AND t.status='selesai'";
if ($filter === 'high')     $sql .= " AND t.prioritas='high'";
$sql .= " ORDER BY FIELD(t.status,'pending','selesai'), FIELD(t.prioritas,'high','medium','low'), t.deadline ASC";
$tugasStmt = $pdo->prepare($sql);
$tugasStmt->execute([$uid]);
$daftarTugas = $tugasStmt->fetchAll();

$now = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Si-PAKAR — To-Do Tugas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  :root { --primary:#1a4f8a; --accent:#f0a500; --sidebar:#0d2d52; }
  body { background:#f0f4f8; font-family:'Segoe UI',sans-serif; }
  .sidebar { width:240px; min-height:100vh; background:var(--sidebar); position:fixed; top:0;left:0; padding:1.5rem 0; z-index:100; }
  .sidebar .brand { color:#fff; font-size:1.4rem; font-weight:800; padding:0 1.5rem 1.5rem; border-bottom:1px solid rgba(255,255,255,.1); margin-bottom:1rem; }
  .sidebar .brand span { color:var(--accent); }
  .sidebar .nav-link { color:rgba(255,255,255,.7); padding:.65rem 1.5rem; font-size:.9rem; display:flex; align-items:center; gap:.75rem; transition:all .2s; }
  .sidebar .nav-link:hover,.sidebar .nav-link.active { color:#fff; background:rgba(255,255,255,.1); border-left:3px solid var(--accent); }
  .main-content { margin-left:240px; padding:1.5rem; }
  .card-section { background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 2px 10px rgba(0,0,0,.06); margin-bottom:1.5rem; }
  .badge-low    { background:#d1e7dd; color:#0f5132; }
  .badge-medium { background:#fff3cd; color:#664d03; }
  .badge-high   { background:#f8d7da; color:#842029; }
  .task-row { border:1px solid #e8ecf0; border-radius:10px; padding:.85rem 1rem; margin-bottom:.5rem; transition:all .2s; }
  .task-row.selesai .task-title { text-decoration:line-through; opacity:.6; }
  .task-row.overdue { border-left:3px solid #dc3545; }
  @media(max-width:768px){ .sidebar{width:60px} .sidebar .brand,.sidebar .nav-link span{display:none} .main-content{margin-left:60px} }
</style>
</head>
<body>
<nav class="sidebar">
  <div class="brand">Si-<span>PAKAR</span></div>
  <ul class="nav flex-column">
    <li><a href="dashboard.php" class="nav-link"><i class="fa fa-gauge-high fa-fw"></i><span>Dashboard</span></a></li>
    <li><a href="jadwal.php" class="nav-link"><i class="fa fa-calendar fa-fw"></i><span>Jadwal Kuliah</span></a></li>
    <li><a href="tugas.php" class="nav-link active"><i class="fa fa-list-check fa-fw"></i><span>To-Do Tugas</span></a></li>
    <li style="position:absolute;bottom:1rem;width:100%">
      <a href="/logout.php" class="nav-link text-danger"><i class="fa fa-right-from-bracket fa-fw"></i><span>Keluar</span></a>
    </li>
  </ul>
</nav>

<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fa fa-list-check me-2 text-primary"></i>To-Do Tugas</h4>
    <?php if ($action === 'list'): ?>
    <a href="tugas.php?action=tambah" class="btn btn-primary"><i class="fa fa-plus me-1"></i>Tambah Tugas</a>
    <?php else: ?>
    <a href="tugas.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i>Kembali</a>
    <?php endif; ?>
  </div>

  <?php if ($success): ?><div class="alert alert-success py-2 small"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-danger  py-2 small"><?= $error   ?></div><?php endif; ?>

  <?php if ($action === 'tambah' || $action === 'edit'): ?>
  <div class="card-section">
    <h5 class="fw-bold mb-3"><?= $action === 'edit' ? 'Edit Tugas' : 'Tambah Tugas Baru' ?></h5>
    <form method="POST" action="">
      <?php if ($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label small fw-semibold">Nama Tugas <span class="text-danger">*</span></label>
          <input type="text" name="nama_tugas" class="form-control" required
                 value="<?= htmlspecialchars($editData['nama_tugas'] ?? '') ?>"
                 placeholder="cth. Laporan Praktikum Modul 5">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Prioritas</label>
          <select name="prioritas" class="form-select">
            <?php foreach (['low'=>'🟢 Low','medium'=>'🟡 Medium','high'=>'🔴 High'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($editData['prioritas'] ?? 'medium') === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Mata Kuliah Terkait</label>
          <select name="schedule_id" class="form-select">
            <option value="">-- Tidak terkait --</option>
            <?php foreach ($jadwalList as $j): ?>
            <option value="<?= $j['id'] ?>" <?= ($editData['schedule_id'] ?? 0) == $j['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($j['matkul']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Deadline</label>
          <input type="datetime-local" name="deadline" class="form-control"
                 value="<?= $editData['deadline'] ? date('Y-m-d\TH:i', strtotime($editData['deadline'])) : '' ?>">
        </div>
        <div class="col-12">
          <label class="form-label small fw-semibold">Deskripsi</label>
          <textarea name="deskripsi" class="form-control" rows="3"
                    placeholder="Deskripsi tugas (opsional)"><?= htmlspecialchars($editData['deskripsi'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary me-2">
          <i class="fa fa-floppy-disk me-1"></i><?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Tugas' ?>
        </button>
        <a href="tugas.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- Filter -->
  <div class="card-section">
    <div class="d-flex flex-wrap gap-2 mb-3">
      <?php foreach (['all'=>'Semua','pending'=>'Belum Selesai','selesai'=>'Selesai','high'=>'Prioritas Tinggi'] as $f => $label): ?>
      <a href="tugas.php?filter=<?= $f ?>"
         class="btn btn-sm <?= $filter === $f ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <?= $label ?>
      </a>
      <?php endforeach; ?>
    </div>

    <p class="small text-muted mb-2"><?= count($daftarTugas) ?> tugas ditemukan</p>

    <?php if (empty($daftarTugas)): ?>
      <p class="text-muted small">Tidak ada tugas. Yay! 🎉</p>
    <?php endif; ?>

    <?php foreach ($daftarTugas as $t):
      $isOverdue = $t['status'] === 'pending' && !empty($t['deadline']) && $t['deadline'] < $now;
    ?>
    <div class="task-row <?= $t['status'] === 'selesai' ? 'selesai' : '' ?> <?= $isOverdue ? 'overdue' : '' ?>"
         id="task-<?= $t['id'] ?>">
      <div class="d-flex align-items-start gap-3">
        <input type="checkbox" class="form-check-input mt-1 task-check"
               data-id="<?= $t['id'] ?>"
               <?= $t['status'] === 'selesai' ? 'checked' : '' ?> style="cursor:pointer">
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <span class="task-title fw-semibold"><?= htmlspecialchars($t['nama_tugas']) ?></span>
              <?php if ($t['deskripsi']): ?>
              <p class="text-muted small mb-1 mt-1"><?= htmlspecialchars($t['deskripsi']) ?></p>
              <?php endif; ?>
            </div>
            <div class="d-flex gap-1 ms-2 flex-shrink-0">
              <span class="badge badge-<?= $t['prioritas'] ?>"><?= ucfirst($t['prioritas']) ?></span>
              <?php if ($isOverdue): ?><span class="badge bg-danger">Overdue</span><?php endif; ?>
            </div>
          </div>
          <div class="text-muted" style="font-size:.75rem">
            <?php if ($t['matkul']): ?>
              <i class="fa fa-book me-1"></i><?= htmlspecialchars($t['matkul']) ?>
            <?php endif; ?>
            <?php if ($t['deadline']): ?>
              &nbsp;·&nbsp;<i class="fa fa-clock me-1"></i>
              <span class="<?= $isOverdue ? 'text-danger fw-semibold' : '' ?>">
                <?= date('d M Y, H:i', strtotime($t['deadline'])) ?>
              </span>
            <?php endif; ?>
          </div>
        </div>
        <div class="d-flex gap-1 flex-shrink-0">
          <a href="tugas.php?action=edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-secondary px-2 py-0">
            <i class="fa fa-pen"></i>
          </a>
          <a href="tugas.php?action=hapus&id=<?= $t['id'] ?>"
             class="btn btn-sm btn-outline-danger px-2 py-0"
             onclick="return confirm('Hapus tugas ini?')">
            <i class="fa fa-trash"></i>
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.task-check').forEach(cb => {
  cb.addEventListener('change', async function () {
    const id     = this.dataset.id;
    const status = this.checked ? 'selesai' : 'pending';
    const item   = document.getElementById('task-' + id);

    try {
      const res  = await fetch('/api/update_task_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
      });
      const data = await res.json();

      if (data.success) {
        if (status === 'selesai') {
          item.classList.add('selesai');
        } else {
          item.classList.remove('selesai');
        }
      }
    } catch (e) {
      this.checked = !this.checked;
    }
  });
});
</script>
</body>
</html>

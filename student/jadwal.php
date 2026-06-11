<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$pdo  = getDB();
$user = currentUser();
$uid  = $user['id'];

$action = $_GET['action'] ?? 'list';
$editData = null;
$error = $success = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matkul     = trim($_POST['matkul']     ?? '');
    $hari       = trim($_POST['hari']       ?? '');
    $jam_mulai  = trim($_POST['jam_mulai']  ?? '');
    $jam_selesai= trim($_POST['jam_selesai']?? '');
    $ruangan    = trim($_POST['ruangan']    ?? '');
    $dosen      = trim($_POST['dosen']      ?? '');
    $id         = (int)($_POST['id']        ?? 0);

    if (empty($matkul) || empty($hari) || empty($jam_mulai) || empty($jam_selesai)) {
        $error = 'Lengkapi semua field yang wajib diisi.';
    } else {
        if ($id > 0) {
            // Update — pastikan milik user ini
            $stmt = $pdo->prepare("UPDATE schedules SET matkul=?,hari=?,jam_mulai=?,jam_selesai=?,ruangan=?,dosen=? WHERE id=? AND user_id=?");
            $stmt->execute([$matkul,$hari,$jam_mulai,$jam_selesai,$ruangan,$dosen,$id,$uid]);
            $success = 'Jadwal berhasil diperbarui.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO schedules (user_id,matkul,hari,jam_mulai,jam_selesai,ruangan,dosen) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$uid,$matkul,$hari,$jam_mulai,$jam_selesai,$ruangan,$dosen]);
            $success = 'Jadwal berhasil ditambahkan.';
        }
        $action = 'list';
    }
}

// Hapus
if ($action === 'hapus' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM schedules WHERE id=? AND user_id=?");
    $stmt->execute([$id, $uid]);
    $success = 'Jadwal dihapus.';
    $action  = 'list';
}

// Edit — ambil data
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE id=? AND user_id=?");
    $stmt->execute([$id, $uid]);
    $editData = $stmt->fetch();
    if (!$editData) { $action = 'list'; }
}

// Daftar jadwal
$jadwals = $pdo->prepare("SELECT * FROM schedules WHERE user_id=? ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jam_mulai");
$jadwals->execute([$uid]);
$daftarJadwal = $jadwals->fetchAll();

$hariList = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Si-PAKAR — Jadwal Kuliah</title>
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
  .card-section { background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 2px 10px rgba(0,0,0,.06); }
  .badge-day { background:var(--primary); color:#fff; border-radius:6px; padding:.2rem .6rem; font-size:.75rem; }
  @media(max-width:768px){ .sidebar{width:60px} .sidebar .brand,.sidebar .nav-link span{display:none} .main-content{margin-left:60px} }
</style>
</head>
<body>
<nav class="sidebar">
  <div class="brand">Si-<span>PAKAR</span></div>
  <ul class="nav flex-column">
    <li><a href="dashboard.php" class="nav-link"><i class="fa fa-gauge-high fa-fw"></i><span>Dashboard</span></a></li>
    <li><a href="jadwal.php" class="nav-link active"><i class="fa fa-calendar fa-fw"></i><span>Jadwal Kuliah</span></a></li>
    <li><a href="tugas.php" class="nav-link"><i class="fa fa-list-check fa-fw"></i><span>To-Do Tugas</span></a></li>
    <li style="position:absolute;bottom:1rem;width:100%">
      <a href="/logout.php" class="nav-link text-danger"><i class="fa fa-right-from-bracket fa-fw"></i><span>Keluar</span></a>
    </li>
  </ul>
</nav>

<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fa fa-calendar me-2 text-primary"></i>Jadwal Kuliah</h4>
    <?php if ($action === 'list'): ?>
    <a href="jadwal.php?action=tambah" class="btn btn-primary"><i class="fa fa-plus me-1"></i>Tambah Jadwal</a>
    <?php else: ?>
    <a href="jadwal.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i>Kembali</a>
    <?php endif; ?>
  </div>

  <?php if ($success): ?><div class="alert alert-success py-2 small"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-danger  py-2 small"><?= $error   ?></div><?php endif; ?>

  <?php if ($action === 'tambah' || $action === 'edit'): ?>
  <!-- Form Tambah/Edit -->
  <div class="card-section mb-4">
    <h5 class="fw-bold mb-3"><?= $action === 'edit' ? 'Edit Jadwal' : 'Tambah Jadwal Baru' ?></h5>
    <form method="POST" action="">
      <?php if ($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Nama Mata Kuliah <span class="text-danger">*</span></label>
          <input type="text" name="matkul" class="form-control" required
                 value="<?= htmlspecialchars($editData['matkul'] ?? '') ?>" placeholder="cth. Pemrograman Web">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Hari <span class="text-danger">*</span></label>
          <select name="hari" class="form-select" required>
            <option value="">-- Pilih Hari --</option>
            <?php foreach ($hariList as $h): ?>
            <option value="<?= $h ?>" <?= ($editData['hari'] ?? '') === $h ? 'selected' : '' ?>><?= $h ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Ruangan</label>
          <input type="text" name="ruangan" class="form-control"
                 value="<?= htmlspecialchars($editData['ruangan'] ?? '') ?>" placeholder="cth. Lab A1">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Jam Mulai <span class="text-danger">*</span></label>
          <input type="time" name="jam_mulai" class="form-control" required
                 value="<?= $editData['jam_mulai'] ?? '' ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Jam Selesai <span class="text-danger">*</span></label>
          <input type="time" name="jam_selesai" class="form-control" required
                 value="<?= $editData['jam_selesai'] ?? '' ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Nama Dosen</label>
          <input type="text" name="dosen" class="form-control"
                 value="<?= htmlspecialchars($editData['dosen'] ?? '') ?>" placeholder="cth. Dr. Budi Santoso">
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary me-2">
          <i class="fa fa-floppy-disk me-1"></i><?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Jadwal' ?>
        </button>
        <a href="jadwal.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- Tabel Jadwal -->
  <div class="card-section">
    <h5 class="fw-bold mb-3">Daftar Jadwal (<?= count($daftarJadwal) ?> mata kuliah)</h5>
    <?php if (empty($daftarJadwal)): ?>
      <p class="text-muted small">Belum ada jadwal. Tambahkan jadwal kuliah kamu!</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle small">
        <thead class="table-light">
          <tr>
            <th>Mata Kuliah</th>
            <th>Hari</th>
            <th>Waktu</th>
            <th>Ruangan</th>
            <th>Dosen</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($daftarJadwal as $j): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($j['matkul']) ?></td>
          <td><span class="badge-day"><?= $j['hari'] ?></span></td>
          <td><?= substr($j['jam_mulai'],0,5) ?> – <?= substr($j['jam_selesai'],0,5) ?></td>
          <td><?= htmlspecialchars($j['ruangan'] ?? '-') ?></td>
          <td><?= htmlspecialchars($j['dosen'] ?? '-') ?></td>
          <td>
            <a href="jadwal.php?action=edit&id=<?= $j['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
              <i class="fa fa-pen"></i>
            </a>
            <a href="jadwal.php?action=hapus&id=<?= $j['id'] ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Hapus jadwal <?= addslashes($j['matkul']) ?>?')">
              <i class="fa fa-trash"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo    = getDB();
$action = $_GET['action'] ?? 'list';
$editData = null;
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_prodi = trim($_POST['nama_prodi'] ?? '');
    $fakultas   = trim($_POST['fakultas']   ?? '');
    $id         = (int)($_POST['id'] ?? 0);

    if (empty($nama_prodi) || empty($fakultas)) {
        $error = 'Semua field wajib diisi.';
    } else {
        if ($id > 0) {
            $pdo->prepare("UPDATE prodi SET nama_prodi=?,fakultas=? WHERE id=?")->execute([$nama_prodi,$fakultas,$id]);
            $success = 'Prodi diperbarui.';
        } else {
            $pdo->prepare("INSERT INTO prodi (nama_prodi,fakultas) VALUES (?,?)")->execute([$nama_prodi,$fakultas]);
            $success = 'Prodi ditambahkan.';
        }
        $action = 'list';
    }
}

if ($action === 'hapus' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM prodi WHERE id=?")->execute([(int)$_GET['id']]);
    $success = 'Prodi dihapus.'; $action = 'list';
}

if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM prodi WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editData = $stmt->fetch();
    if (!$editData) $action = 'list';
}

$allProdi = $pdo->query("SELECT * FROM prodi ORDER BY fakultas, nama_prodi")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Si-PAKAR — Master Prodi</title>
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
  @media(max-width:768px){ .sidebar{width:60px} .sidebar .brand,.sidebar .nav-link span{display:none} .main-content{margin-left:60px} }
</style>
</head>
<body>
<nav class="sidebar">
  <div class="brand">Si-<span>PAKAR</span></div>
  <ul class="nav flex-column">
    <li><a href="dashboard.php" class="nav-link"><i class="fa fa-gauge-high fa-fw"></i><span>Dashboard</span></a></li>
    <li><a href="users.php" class="nav-link"><i class="fa fa-users fa-fw"></i><span>Manajemen User</span></a></li>
    <li><a href="prodi.php" class="nav-link active"><i class="fa fa-university fa-fw"></i><span>Master Prodi</span></a></li>
    <li style="position:absolute;bottom:1rem;width:100%">
      <a href="/logout.php" class="nav-link text-danger"><i class="fa fa-right-from-bracket fa-fw"></i><span>Keluar</span></a>
    </li>
  </ul>
</nav>

<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fa fa-university me-2 text-primary"></i>Master Program Studi</h4>
    <?php if ($action === 'list'): ?>
    <a href="prodi.php?action=tambah" class="btn btn-primary"><i class="fa fa-plus me-1"></i>Tambah Prodi</a>
    <?php else: ?>
    <a href="prodi.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i>Kembali</a>
    <?php endif; ?>
  </div>

  <?php if ($success): ?><div class="alert alert-success py-2 small"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-danger  py-2 small"><?= $error   ?></div><?php endif; ?>

  <?php if ($action === 'tambah' || $action === 'edit'): ?>
  <div class="card-section mb-4">
    <h5 class="fw-bold mb-3"><?= $action === 'edit' ? 'Edit Prodi' : 'Tambah Prodi Baru' ?></h5>
    <form method="POST" action="">
      <?php if ($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Nama Program Studi <span class="text-danger">*</span></label>
          <input type="text" name="nama_prodi" class="form-control" required value="<?= htmlspecialchars($editData['nama_prodi'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Fakultas <span class="text-danger">*</span></label>
          <input type="text" name="fakultas" class="form-control" required value="<?= htmlspecialchars($editData['fakultas'] ?? '') ?>">
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary me-2"><i class="fa fa-floppy-disk me-1"></i>Simpan</button>
        <a href="prodi.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="card-section">
    <table class="table table-hover small align-middle">
      <thead class="table-light">
        <tr><th>#</th><th>Program Studi</th><th>Fakultas</th><th>Aksi</th></tr>
      </thead>
      <tbody>
      <?php foreach ($allProdi as $i => $p): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td class="fw-semibold"><?= htmlspecialchars($p['nama_prodi']) ?></td>
        <td><?= htmlspecialchars($p['fakultas']) ?></td>
        <td>
          <a href="prodi.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="fa fa-pen"></i></a>
          <a href="prodi.php?action=hapus&id=<?= $p['id'] ?>"
             class="btn btn-sm btn-outline-danger"
             onclick="return confirm('Hapus prodi ini?')">
            <i class="fa fa-trash"></i>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo    = getDB();
$action = $_GET['action'] ?? 'list';
$editData = null;
$error = $success = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']     ?? '');
    $nim      = trim($_POST['nim']      ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $prodi    = trim($_POST['prodi']    ?? '');
    $fakultas = trim($_POST['fakultas'] ?? '');
    $id       = (int)($_POST['id']      ?? 0);

    if (empty($nama) || empty($email)) {
        $error = 'Nama dan email wajib diisi.';
    } else {
        if ($id > 0) {
            if (!empty($password)) {
                $stmt = $pdo->prepare("UPDATE users SET nama=?,nim=?,email=?,password=?,prodi=?,fakultas=? WHERE id=?");
                $stmt->execute([$nama,$nim,$email,password_hash($password,PASSWORD_BCRYPT),$prodi,$fakultas,$id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nama=?,nim=?,email=?,prodi=?,fakultas=? WHERE id=?");
                $stmt->execute([$nama,$nim,$email,$prodi,$fakultas,$id]);
            }
            $success = 'Akun berhasil diperbarui.';
        } else {
            if (empty($password)) { $error = 'Password wajib diisi untuk akun baru.'; }
            else {
                $stmt = $pdo->prepare("INSERT INTO users (nama,nim,email,password,role,prodi,fakultas) VALUES (?,?,?,?,'student',?,?)");
                $stmt->execute([$nama,$nim,$email,password_hash($password,PASSWORD_BCRYPT),$prodi,$fakultas]);
                $success = 'Akun mahasiswa berhasil ditambahkan.';
            }
        }
        $action = 'list';
    }
}

if ($action === 'hapus' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM users WHERE id=? AND role='student'")->execute([(int)$_GET['id']]);
    $success = 'Akun dihapus.'; $action = 'list';
}

if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editData = $stmt->fetch();
    if (!$editData) $action = 'list';
}

// Ambil semua prodi
$prodiList = $pdo->query("SELECT DISTINCT nama_prodi, fakultas FROM prodi ORDER BY nama_prodi")->fetchAll();

// Daftar user
$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role='student' AND (nama LIKE ? OR nim LIKE ? OR email LIKE ?) ORDER BY nama");
    $stmt->execute(["%$search%","%$search%","%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE role='student' ORDER BY nama");
}
$allUsers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Si-PAKAR — Manajemen User</title>
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
    <li><a href="users.php" class="nav-link active"><i class="fa fa-users fa-fw"></i><span>Manajemen User</span></a></li>
    <li><a href="prodi.php" class="nav-link"><i class="fa fa-university fa-fw"></i><span>Master Prodi</span></a></li>
    <li style="position:absolute;bottom:1rem;width:100%">
      <a href="/logout.php" class="nav-link text-danger"><i class="fa fa-right-from-bracket fa-fw"></i><span>Keluar</span></a>
    </li>
  </ul>
</nav>

<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fa fa-users me-2 text-primary"></i>Manajemen Mahasiswa</h4>
    <?php if ($action === 'list'): ?>
    <a href="users.php?action=tambah" class="btn btn-primary"><i class="fa fa-plus me-1"></i>Tambah Mahasiswa</a>
    <?php else: ?>
    <a href="users.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i>Kembali</a>
    <?php endif; ?>
  </div>

  <?php if ($success): ?><div class="alert alert-success py-2 small"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-danger  py-2 small"><?= $error   ?></div><?php endif; ?>

  <?php if ($action === 'tambah' || $action === 'edit'): ?>
  <div class="card-section mb-4">
    <h5 class="fw-bold mb-3"><?= $action === 'edit' ? 'Edit Akun Mahasiswa' : 'Tambah Akun Mahasiswa' ?></h5>
    <form method="POST" action="">
      <?php if ($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($editData['nama'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">NIM</label>
          <input type="text" name="nim" class="form-control" value="<?= htmlspecialchars($editData['nim'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($editData['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Program Studi</label>
          <select name="prodi" class="form-select">
            <option value="">-- Pilih Prodi --</option>
            <?php foreach ($prodiList as $p): ?>
            <option value="<?= $p['nama_prodi'] ?>" <?= ($editData['prodi'] ?? '') === $p['nama_prodi'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['nama_prodi']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Fakultas</label>
          <input type="text" name="fakultas" class="form-control" value="<?= htmlspecialchars($editData['fakultas'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">
            Password <?= $action === 'edit' ? '(kosongkan jika tidak diubah)' : '<span class="text-danger">*</span>' ?>
          </label>
          <input type="password" name="password" class="form-control"
                 <?= $action === 'tambah' ? 'required' : '' ?> placeholder="min. 8 karakter">
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary me-2">
          <i class="fa fa-floppy-disk me-1"></i><?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Akun' ?>
        </button>
        <a href="users.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="card-section">
    <!-- Search -->
    <form method="GET" class="mb-3 d-flex gap-2">
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama, NIM, email..." value="<?= htmlspecialchars($search) ?>">
      <button class="btn btn-sm btn-primary px-3"><i class="fa fa-search"></i></button>
      <?php if ($search): ?><a href="users.php" class="btn btn-sm btn-outline-secondary">Reset</a><?php endif; ?>
    </form>

    <p class="small text-muted"><?= count($allUsers) ?> mahasiswa terdaftar</p>

    <div class="table-responsive">
      <table class="table table-hover small align-middle">
        <thead class="table-light">
          <tr><th>#</th><th>Nama</th><th>NIM</th><th>Email</th><th>Prodi</th><th>Bergabung</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php foreach ($allUsers as $i => $u): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td class="fw-semibold"><?= htmlspecialchars($u['nama']) ?></td>
          <td><code><?= htmlspecialchars($u['nim'] ?? '-') ?></code></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['prodi'] ?? '-') ?></td>
          <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td>
            <a href="users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="fa fa-pen"></i></a>
            <a href="users.php?action=hapus&id=<?= $u['id'] ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Hapus akun <?= addslashes($u['nama']) ?>?')">
              <i class="fa fa-trash"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

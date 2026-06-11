<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo  = getDB();
$user = currentUser();

// Stats
$totalUsers  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalTugas  = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$totalJadwal = $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
$tugasSelesai= $pdo->query("SELECT COUNT(*) FROM tasks WHERE status='selesai'")->fetchColumn();

// Recent users
$users = $pdo->query("SELECT * FROM users WHERE role='student' ORDER BY created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Si-PAKAR — Admin Dashboard</title>
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
  .topbar { background:#fff; border-radius:12px; padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 10px rgba(0,0,0,.06); margin-bottom:1.5rem; }
  .stat-card { background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 2px 10px rgba(0,0,0,.06); border-left:4px solid var(--primary); }
  .stat-card.y { border-left-color:var(--accent); }
  .stat-card.g { border-left-color:#198754; }
  .stat-card.r { border-left-color:#dc3545; }
  .stat-card .num { font-size:2rem; font-weight:800; color:var(--primary); }
  .stat-card.y .num { color:var(--accent); }
  .stat-card.g .num { color:#198754; }
  .card-section { background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 2px 10px rgba(0,0,0,.06); }
  @media(max-width:768px){ .sidebar{width:60px} .sidebar .brand,.sidebar .nav-link span{display:none} .main-content{margin-left:60px} }
</style>
</head>
<body>
<nav class="sidebar">
  <div class="brand">Si-<span>PAKAR</span></div>
  <ul class="nav flex-column">
    <li><a href="dashboard.php" class="nav-link active"><i class="fa fa-gauge-high fa-fw"></i><span>Dashboard</span></a></li>
    <li><a href="users.php" class="nav-link"><i class="fa fa-users fa-fw"></i><span>Manajemen User</span></a></li>
    <li><a href="prodi.php" class="nav-link"><i class="fa fa-university fa-fw"></i><span>Master Prodi</span></a></li>
    <li style="position:absolute;bottom:1rem;width:100%">
      <a href="/logout.php" class="nav-link text-danger"><i class="fa fa-right-from-bracket fa-fw"></i><span>Keluar</span></a>
    </li>
  </ul>
</nav>

<div class="main-content">
  <div class="topbar">
    <div>
      <h5 class="mb-0 fw-bold">Admin Dashboard</h5>
      <small class="text-muted"><?= date('l, d F Y') ?></small>
    </div>
    <div class="d-flex align-items-center gap-2">
      <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
           style="width:36px;height:36px;background:var(--accent)">
        <?= strtoupper(substr($user['nama'],0,1)) ?>
      </div>
      <div>
        <div class="fw-semibold small"><?= htmlspecialchars($user['nama']) ?></div>
        <div class="text-muted" style="font-size:.72rem">Administrator</div>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="text-muted small mb-1">Total Mahasiswa</div>
        <div class="num"><?= $totalUsers ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card y">
        <div class="text-muted small mb-1">Total Jadwal</div>
        <div class="num"><?= $totalJadwal ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card g">
        <div class="text-muted small mb-1">Total Tugas</div>
        <div class="num"><?= $totalTugas ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card g">
        <div class="text-muted small mb-1">Tugas Selesai</div>
        <div class="num"><?= $tugasSelesai ?></div>
      </div>
    </div>
  </div>

  <!-- Tabel User -->
  <div class="card-section">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold mb-0"><i class="fa fa-users me-2"></i>Daftar Mahasiswa Terdaftar</h5>
      <a href="users.php?action=tambah" class="btn btn-sm btn-primary"><i class="fa fa-plus me-1"></i>Tambah</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover small align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Nama</th>
            <th>NIM</th>
            <th>Email</th>
            <th>Prodi</th>
            <th>Bergabung</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $i => $u): ?>
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
    <a href="users.php" class="btn btn-outline-primary btn-sm">Lihat semua mahasiswa →</a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

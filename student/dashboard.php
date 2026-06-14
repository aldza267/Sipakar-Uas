<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$pdo  = getDB();
$user = currentUser();
$uid  = $user['id'];

// Statistik
$totalTugas   = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ?");
$totalTugas->execute([$uid]);
$total = (int)$totalTugas->fetchColumn();

$selesaiTugas = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status='selesai'");
$selesaiTugas->execute([$uid]);
$selesai = (int)$selesaiTugas->fetchColumn();

$progress = $total > 0 ? round(($selesai / $total) * 100) : 0;

// Jadwal
$jadwalStmt = $pdo->prepare("SELECT * FROM schedules WHERE user_id = ? ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jam_mulai");
$jadwalStmt->execute([$uid]);
$jadwals = $jadwalStmt->fetchAll();

// Tugas pending (deadline terdekat)
$tugasStmt = $pdo->prepare("
    SELECT t.*, s.matkul FROM tasks t
    LEFT JOIN schedules s ON t.schedule_id = s.id
    WHERE t.user_id = ?
    ORDER BY FIELD(t.status,'pending','selesai'), t.prioritas DESC, t.deadline ASC
");
$tugasStmt->execute([$uid]);
$tugas = $tugasStmt->fetchAll();

// Tugas overdue
$now = date('Y-m-d H:i:s');

// Jadwal hari ini
$hariIni = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'][date('l')];
$jadwalHariIni = array_filter($jadwals, fn($j) => $j['hari'] === $hariIni);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Si-PAKAR — Dashboard Mahasiswa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  :root {
    --primary: #1a4f8a;
    --accent:  #f0a500;
    --danger:  #dc3545;
    --success: #198754;
    --sidebar: #0d2d52;
  }
  body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }

  /* Sidebar */
  .sidebar {
    width: 240px;
    min-height: 100vh;
    background: var(--sidebar);
    position: fixed;
    top: 0; left: 0;
    padding: 1.5rem 0;
    z-index: 100;
    transition: width 0.3s;
  }
  .sidebar .brand {
    color: #fff;
    font-size: 1.4rem;
    font-weight: 800;
    padding: 0 1.5rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 1rem;
  }
  .sidebar .brand span { color: var(--accent); }
  .sidebar .nav-link {
    color: rgba(255,255,255,0.7);
    padding: 0.65rem 1.5rem;
    border-radius: 0;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.2s;
  }
  .sidebar .nav-link:hover,
  .sidebar .nav-link.active {
    color: #fff;
    background: rgba(255,255,255,0.1);
    border-left: 3px solid var(--accent);
  }

  /* Main */
  .main-content {
    margin-left: 240px;
    padding: 1.5rem;
    min-height: 100vh;
  }
  .topbar {
    background: #fff;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    margin-bottom: 1.5rem;
  }
  .stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    border-left: 4px solid var(--primary);
  }
  .stat-card.accent { border-left-color: var(--accent); }
  .stat-card.success { border-left-color: var(--success); }
  .stat-card .num { font-size: 2rem; font-weight: 800; color: var(--primary); }
  .stat-card.accent .num { color: var(--accent); }
  .stat-card.success .num { color: var(--success); }

  .card-section {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    margin-bottom: 1.5rem;
  }
  .card-section h5 {
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 1rem;
  }

  /* Progress */
  .progress-ring-wrap { text-align: center; padding: 1rem; }
  .progress-bar-custom {
    height: 14px;
    border-radius: 99px;
    background: #e9ecef;
    overflow: hidden;
    margin-bottom: 0.5rem;
  }
  .progress-bar-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
    transition: width 1s ease;
  }

  /* Task badge priority */
  .badge-low    { background: #d1e7dd; color: #0f5132; }
  .badge-medium { background: #fff3cd; color: #664d03; }
  .badge-high   { background: #f8d7da; color: #842029; }

  /* Task item */
  .task-item {
    border: 1px solid #e8ecf0;
    border-radius: 10px;
    padding: 0.85rem 1rem;
    margin-bottom: 0.5rem;
    transition: all 0.2s;
    background: #fff;
  }
  .task-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
  .task-item.selesai { opacity: 0.55; }
  .task-item.selesai .task-title { text-decoration: line-through; }
  .task-item.overdue { border-left: 3px solid var(--danger); }

  /* Jadwal */
  .jadwal-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
  }
  .jadwal-time {
    background: var(--primary);
    color: #fff;
    border-radius: 8px;
    padding: 0.35rem 0.65rem;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
    min-width: 90px;
    text-align: center;
  }

  @media (max-width: 768px) {
    .sidebar { width: 60px; }
    .sidebar .brand, .sidebar .nav-link span { display: none; }
    .main-content { margin-left: 60px; }
  }
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
  <div class="brand">Si-<span>PAKAR</span></div>
  <ul class="nav flex-column">
    <li><a href="dashboard.php" class="nav-link active"><i class="fa fa-gauge-high fa-fw"></i><span>Dashboard</span></a></li>
    <li><a href="jadwal.php" class="nav-link"><i class="fa fa-calendar fa-fw"></i><span>Jadwal Kuliah</span></a></li>
    <li><a href="tugas.php" class="nav-link"><i class="fa fa-list-check fa-fw"></i><span>To-Do Tugas</span></a></li>
    <li class="mt-auto" style="position:absolute;bottom:1rem;width:100%">
      <a href="/sipakar/sipakar/logout.php" class="nav-link text-danger"><i class="fa fa-right-from-bracket fa-fw"></i><span>Keluar</span></a>
    </li>
  </ul>
</nav>

<!-- Main Content -->
<div class="main-content">

  <!-- Topbar -->
  <div class="topbar">
    <div>
      <h5 class="mb-0 fw-bold">Dashboard Mahasiswa</h5>
      <small class="text-muted"><?= date('l, d F Y') ?></small>
    </div>
    <div class="d-flex align-items-center gap-2">
      <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-weight:700">
        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
      </div>
      <div>
        <div class="fw-semibold small"><?= htmlspecialchars($user['nama']) ?></div>
        <div class="text-muted" style="font-size:0.72rem">Mahasiswa</div>
      </div>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="text-muted small mb-1">Total Tugas</div>
        <div class="num"><?= $total ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card success">
        <div class="text-muted small mb-1">Selesai</div>
        <div class="num"><?= $selesai ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card accent">
        <div class="text-muted small mb-1">Pending</div>
        <div class="num"><?= $total - $selesai ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="text-muted small mb-1">Mata Kuliah</div>
        <div class="num"><?= count($jadwals) ?></div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Progress & Jadwal Hari Ini -->
    <div class="col-md-4">
      <div class="card-section">
        <h5><i class="fa fa-chart-pie me-2"></i>Progres Tugas</h5>
        <div class="progress-bar-custom">
          <div class="progress-bar-fill" id="progressFill" style="width:<?= $progress ?>%"></div>
        </div>
        <div class="d-flex justify-content-between small text-muted">
          <span><?= $selesai ?> selesai</span>
          <span class="fw-bold text-primary"><?= $progress ?>%</span>
        </div>
      </div>

      <div class="card-section">
        <h5><i class="fa fa-sun me-2"></i>Jadwal Hari Ini <small class="text-muted fw-normal">(<?= $hariIni ?>)</small></h5>
        <?php if (empty($jadwalHariIni)): ?>
          <p class="text-muted small mb-0">Tidak ada jadwal hari ini. Santai dulu! 😄</p>
        <?php else: ?>
          <?php foreach ($jadwalHariIni as $j): ?>
          <div class="jadwal-item">
            <div class="jadwal-time"><?= substr($j['jam_mulai'],0,5) ?>–<?= substr($j['jam_selesai'],0,5) ?></div>
            <div>
              <div class="fw-semibold small"><?= htmlspecialchars($j['matkul']) ?></div>
              <div class="text-muted" style="font-size:0.75rem"><?= htmlspecialchars($j['ruangan'] ?? '-') ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Daftar Tugas -->
    <div class="col-md-8">
      <div class="card-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0"><i class="fa fa-list-check me-2"></i>Daftar Tugas</h5>
          <a href="tugas.php?action=tambah" class="btn btn-sm btn-primary px-3">
            <i class="fa fa-plus me-1"></i>Tambah
          </a>
        </div>

        <?php if (empty($tugas)): ?>
          <p class="text-muted small">Belum ada tugas. Tambah tugas dulu!</p>
        <?php endif; ?>

        <?php foreach ($tugas as $t):
          $isOverdue = $t['status'] === 'pending' && !empty($t['deadline']) && $t['deadline'] < $now;
        ?>
        <div class="task-item <?= $t['status'] === 'selesai' ? 'selesai' : '' ?> <?= $isOverdue ? 'overdue' : '' ?>"
             id="task-<?= $t['id'] ?>">
          <div class="d-flex align-items-start gap-2">
            <input type="checkbox"
                   class="form-check-input mt-1 task-check"
                   data-id="<?= $t['id'] ?>"
                   <?= $t['status'] === 'selesai' ? 'checked' : '' ?>
                   style="cursor:pointer">
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-center">
                <span class="task-title fw-semibold small"><?= htmlspecialchars($t['nama_tugas']) ?></span>
                <div class="d-flex gap-1">
                  <span class="badge badge-<?= $t['prioritas'] ?> small"><?= ucfirst($t['prioritas']) ?></span>
                  <?php if ($isOverdue): ?>
                  <span class="badge bg-danger small">Overdue</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="text-muted" style="font-size:0.75rem">
                <?php if ($t['matkul']): ?>
                  <i class="fa fa-book me-1"></i><?= htmlspecialchars($t['matkul']) ?>
                <?php endif; ?>
                <?php if ($t['deadline']): ?>
                  &nbsp;·&nbsp;<i class="fa fa-clock me-1"></i><?= date('d M Y H:i', strtotime($t['deadline'])) ?>
                <?php endif; ?>
              </div>
            </div>
            <div class="d-flex gap-1">
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
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// AJAX update status tugas (Fetch API)
document.querySelectorAll('.task-check').forEach(cb => {
  cb.addEventListener('change', async function () {
    const id     = this.dataset.id;
    const status = this.checked ? 'selesai' : 'pending';
    const item   = document.getElementById('task-' + id);

    try {
      const res  = await fetch('/sipakar/sipakar/api/update_task_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
      });
      const data = await res.json();

      if (data.success) {
        if (status === 'selesai') {
          item.classList.add('selesai');
          item.querySelector('.task-title').style.textDecoration = 'line-through';
        } else {
          item.classList.remove('selesai');
          item.querySelector('.task-title').style.textDecoration = '';
        }
        // Update progress bar
        updateProgress();
      }
    } catch (e) {
      alert('Gagal update status. Coba lagi.');
      this.checked = !this.checked;
    }
  });
});

async function updateProgress() {
  const res  = await fetch('/sipakar/sipakar/api/get_progress.php');
  const data = await res.json();
  if (data.success) {
    document.getElementById('progressFill').style.width = data.progress + '%';
  }
}
</script>
</body>
</html>

<?php
require_once __DIR__ . '/includes/auth.php';

startSession();
if (isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $result = login($email, $password);
        if ($result['success']) {
            header('Location: ' . BASE_PATH . '/index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Si-PAKAR — Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  :root {
    --primary: #1a4f8a;
    --accent:  #f0a500;
    --bg:      #f0f4f8;
  }
  body {
    background: linear-gradient(135deg, var(--primary) 0%, #0d2d52 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
  }
  .login-card {
    background: #fff;
    border-radius: 16px;
    padding: 2.5rem;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  }
  .logo-wrap {
    text-align: center;
    margin-bottom: 1.5rem;
  }
  .logo-wrap .brand {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primary);
    letter-spacing: -1px;
  }
  .logo-wrap .brand span { color: var(--accent); }
  .logo-wrap p {
    font-size: 0.8rem;
    color: #666;
    margin: 0;
  }
  .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(26,79,138,0.15);
  }
  .btn-login {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    border-radius: 8px;
    transition: background 0.2s;
  }
  .btn-login:hover { background: #0d2d52; color: #fff; }
  .input-group-text {
    background: var(--bg);
    border-right: none;
    color: var(--primary);
  }
  .input-group .form-control { border-left: none; }
  .demo-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.8rem;
    color: #555;
    margin-top: 1rem;
  }
  .demo-info code { color: var(--primary); }
</style>
</head>
<body>
<div class="login-card">
  <div class="logo-wrap">
    <div class="brand">Si-<span>PAKAR</span></div>
    <p>Sistem Pengelolaan Agenda & Aktivitas Perkuliahan</p>
    <p style="font-size:0.72rem;color:#aaa;">UIN Maulana Malik Ibrahim Malang</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger py-2 small"><i class="fa fa-circle-exclamation me-1"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="mb-3">
      <label class="form-label fw-semibold small">Email</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
        <input type="email" name="email" class="form-control"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="email@student.ac.id" required>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold small">Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fa fa-lock"></i></span>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
    </div>
    <button type="submit" class="btn btn-login w-100">
      <i class="fa fa-right-to-bracket me-2"></i>Masuk
    </button>
  </form>

  <div class="demo-info">
    <strong>Demo Login:</strong><br>
    Admin &nbsp;: <code>admin@sipakar.ac.id</code> / <code>admin123</code><br>
    Student: <code>aldza@student.sipakar.ac.id</code> / <code>mahasiswa123</code>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
